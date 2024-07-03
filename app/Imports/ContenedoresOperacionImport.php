<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Pedidos\Sello;
use App\Rules\ContainerFormat;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\Contenedor;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\Operaciones\OperacionEmbarque;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use App\Models\Parametrizacion\ProductoCliente;
use App\Models\Operaciones\OperacionEmbarqueContenedor;

// class OrdenServicioImport implements ToCollection, WithStartRow, SkipsOnFailure, SkipsOnError
class ContenedoresOperacionImport implements ToCollection, WithStartRow, SkipsOnFailure,SkipsOnError
{

    use Importable, SkipsFailures;
    private $errors = [];
    private $importedRows = 0;
    private $withErrors = false;
    private $imported = [];
    private $operacionEmbarqueId;
    private $usuarioId;
    private $usuarioNombre;
    /**
    * @param Collection $collection
    */

    public function __construct($operacionEmbarqueId, $usuarioId, $usuarioNombre){
        $this->usuarioId = $usuarioId;
        $this->operacionEmbarqueId = $operacionEmbarqueId;
        $this->usuarioNombre = $usuarioNombre;
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

            $row[0] = strtoupper(str_replace(['-', ' '], '', $row[0]));

           // Mensajes de validaciones
            $mensajes = [
                '0.required' => '1. Numero contenedor es un dato requerido.',
                '1.max' => ' 5. Debe tener máximo 128 caracteres',
            ];

            //Validaciones
            $validator = Validator::make($row, [
                '0' => [
                    'required',
                    new ContainerFormat($this->operacionEmbarqueId),
                ],
                '1' => [
                    'string',
                    'nullable',
                    'max:128'
                ],
            ], $mensajes);

            if($validator->fails()){
                $temp_errores = [];
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $temp_errores[]=$error;
                    }
                }
                $this->errors[] = ['datosFila'=>$row,'key'=>$rowKey,'observaciones'=>$temp_errores];
                continue;
            }

            $row[0] = substr($row[0], 0, 10);
            $operacionEmbarque = OperacionEmbarque::find($this->operacionEmbarqueId);
            $contenedor = Contenedor::where('numero_contenedor', $row[0])
                ->where('cliente_id', $operacionEmbarque->cliente_id)
                ->first();
            $digito = Contenedor::digitoVerificacion($row[0]);
            if(!$contenedor){
                $contenedor = new Contenedor();
                $data = [
                    'numero_contenedor' => $row[0],
                    'digito_verificacion' => $digito,
                    'cliente_id' => $operacionEmbarque->cliente_id,
                    'usuario_creacion_id' => $this->usuarioId,
                    'usuario_creacion_nombre' => $this->usuarioNombre,
                    'usuario_modificacion_id' => $this->usuarioId,
                    'usuario_modificacion_nombre' => $this->usuarioNombre,
                ];
                $contenedor->fill($data);
                $contenedor->save();
            }
            $create = OperacionEmbarqueContenedor::create([
                'operacion_embarque_id' => $operacionEmbarque->id,
                'contenedor_id' => $contenedor->id,
                'estado_contenedor' => 'ACT',
                'observaciones' => $row[1],
                'usuario_creacion_id' => $this->usuarioId,
                'usuario_creacion_nombre' => $this->usuarioNombre,
                'usuario_modificacion_id' => $this->usuarioId,
                'usuario_modificacion_nombre' => $this->usuarioNombre,
            ]);
            $row[0] = $row[0].'-'.$digito;
            if(isset($create)){
                array_push($imported,$create);
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
            '0' => 'NUMERO CONTENDOR',
            '1' => 'OBSERVACIONES',
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
