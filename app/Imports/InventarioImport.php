<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use App\Models\Parametrizacion\ProductoCliente;
use App\Models\Pedidos\Sello;
use Illuminate\Validation\Rule;

// class OrdenServicioImport implements ToCollection, WithStartRow, SkipsOnFailure, SkipsOnError
class InventarioImport implements ToCollection, WithStartRow, SkipsOnFailure,SkipsOnError
{

    use Importable, SkipsFailures;
    private $errors = [];
    private $importedRows = 0;
    private $withErrors = false;
    private $imported = [];
    private $usuarioId;
    private $usuarioNombre;
    /**
    * @param Collection $collection
    */

    public function __construct($usuarioId, $usuarioNombre,$usuarioCliente){
        $this->usuarioId = $usuarioId;
        $this->usuarioNombre = $usuarioNombre;
        $this->usuarioCliente = $usuarioCliente;
    }

    public function collection(Collection $collection)
    {
        // Procesar datos
        $imported = [];
        foreach ($collection as $rowKey => $row) {
            $row = $row->toArray();
            if (($row[0]===''||$row[0]===null) && ($row[1]===''||$row[1]===null)){
                break;
            }

            $productoNoExiste = '2. Producto no registrado en el sistema.';

           // Mensajes de validaciones
            $mensajes = [
                '0.required' => '1. Identificación producto es un dato requerido.',
                '0.exists' =>  $productoNoExiste,
                '1.required' => ' 3. Serie es un dato requerido',
                '1.unique' => '4. Serie ya registrada para el producto.',
            ];

            //Validaciones
            $validator = Validator::make($row, [
                // '0' => 'required',
                // '1' => 'required',
                '0' => [
                    'required',
                    Rule::exists('productos_clientes','id')->where(function ($query) {
                        $query->where('cliente_id',$this->usuarioCliente);
                    }),
                ],
                '1' => [
                    'required',
                    Rule::unique('sellos','serial')->where(function ($query) use($row){
                        $query->where('producto_id', $row[0]);
                    }),
                ],
            ], $mensajes);



            if($validator->fails()){
                $temp_errores = [];
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $temp_errores[]=$error;
                    }
                }
                $exist = str_contains(join("<br>", $temp_errores), $productoNoExiste);
                if (!$exist){
                    $producto = ProductoCliente::where('id',$row[0])->first();
                    $row[2]= $producto->nombre_producto_cliente;
                }
                $this->errors[] = ['datosFila'=>$row,'key'=>$rowKey,'observaciones'=>$temp_errores];
                continue;
            }

            $idProducto = $row[0];
            $producto = ProductoCliente::where('id',$row[0])->first();
            $serial = $row[1];
            $lugar = DB::table('lugares_usuarios')
            ->select('lugar_id')
            ->where('usuario_id','=',$this->usuarioId)
            ->first();

            $sello = Sello::create([
                'serial' =>  $serial,
                'producto_id' => $idProducto,
                'producto_s3_id' => $producto->producto_s3_id,
                'cliente_id'=>$this->usuarioCliente,
                'tipo_empaque_despacho' => 'I',
                'estado_sello' => 'STO',
                'numero_pedido' => 0,
                'user_id'=>$this->usuarioId,
                'lugar_id'=>$lugar->lugar_id,
                'usuario_creacion_id' => $this->usuarioId,
                'usuario_creacion_nombre' => $this->usuarioNombre,
                'usuario_modificacion_id' => $this->usuarioId,
                'usuario_modificacion_nombre' => $this->usuarioNombre
            ]);
            if(isset($sello)){
                $sello->save();
                array_push($imported,$sello);
                $row[2]= $producto->nombre_producto_cliente;
                $this->errors[] = ['datosFila'=>$row,'key'=>$rowKey,'observaciones'=>['OK']];
                $this->importedRows++;
            }
        }
        $this->imported = $imported;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function customValidationAttributes()
    {
        return [
            '0' => 'ID PRODUCTO',
            '1' => 'SERIAL',
        ];
    }

    public function onError(\Throwable $e)
    {
        return $this->withErrors = true;
    }

    public function getImportedRows(): int
    {
        return $this->importedRows;
    }

    public function getCustomErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener las filas importadas
     * @return array
     */
    public function getImported(): array
    {
        return $this->imported;
    }

    public function getWithErrors(): bool
    {
        return $this->withErrors;
    }

}
