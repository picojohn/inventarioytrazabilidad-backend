<?php

namespace App\Models\Parametrizacion;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductoCliente extends Model
{
    protected $table = 'productos_clientes'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'producto_s3_id',
        'cliente_id',
        'nombre_producto_cliente',
        'codigo_externo_producto',
        'indicativo_producto_externo',
        'indicativo_producto_empaque',
        'valor_serial_interno',
        'operador_serial_interno',
        'valor_serial_qr',
        'operador_serial_qr',
        'valor_serial_datamatrix',
        'operador_serial_datamatrix',
        'valor_serial_pdf',
        'operador_serial_pdf',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public static function obtenerColeccionLigera($dto){
        $query = ProductoCliente::select(
                'id',
                'producto_s3_id',
                'cliente_id',
                'nombre_producto_cliente AS nombre',
                'codigo_externo_producto AS codigo_externo',
                'estado',
            );
        if(isset($dto['cliente'])){
            $query->where('cliente_id', $dto['cliente']);
        }
        $query->orderBy('nombre_producto_cliente', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('productos_clientes')
            ->join('clientes', 'clientes.id', 'productos_clientes.cliente_id')
            ->select(
                'productos_clientes.id',
                'productos_clientes.producto_s3_id',
                'clientes.nombre',
                'productos_clientes.nombre_producto_cliente',
                'productos_clientes.codigo_externo_producto',
                'productos_clientes.indicativo_producto_externo',
                'productos_clientes.indicativo_producto_empaque',
                'productos_clientes.valor_serial_interno',
                'productos_clientes.operador_serial_interno',
                'productos_clientes.valor_serial_qr',
                'productos_clientes.operador_serial_qr',
                'productos_clientes.valor_serial_datamatrix',
                'productos_clientes.operador_serial_datamatrix',
                'productos_clientes.valor_serial_pdf',
                'productos_clientes.operador_serial_pdf',
                'productos_clientes.estado',
                'productos_clientes.usuario_creacion_id',
                'productos_clientes.usuario_creacion_nombre',
                'productos_clientes.usuario_modificacion_id',
                'productos_clientes.usuario_modificacion_nombre',
                'productos_clientes.created_at AS fecha_creacion',
                'productos_clientes.updated_at AS fecha_modificacion',
            );

        if(isset($dto['nombre'])){
            $query->where('productos_clientes.nombre_producto_cliente', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if(isset($dto['cliente'])){
            $query->where('clientes.id', $dto['cliente']);
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre_producto_cliente'){
                    $query->orderBy('productos_clientes.nombre_producto_cliente', $value);
                }
                if($attribute == 'nombre_producto_cliente'){
                    $query->orderBy('productos_clientes.nombre_producto_cliente', $value);
                }
                if($attribute == 'codigo_externo_producto'){
                    $query->orderBy('productos_clientes.codigo_externo_producto', $value);
                }
                if($attribute == 'indicativo_producto_externo'){
                    $query->orderBy('productos_clientes.indicativo_producto_externo', $value);
                }
                if($attribute == 'indicativo_producto_empaque'){
                    $query->orderBy('productos_clientes.indicativo_producto_empaque', $value);
                }
                if($attribute == 'valor_serial_interno'){
                    $query->orderBy('productos_clientes.valor_serial_interno', $value);
                }
                if($attribute == 'operador_serial_interno'){
                    $query->orderBy('productos_clientes.operador_serial_interno', $value);
                }
                if($attribute == 'valor_serial_qr'){
                    $query->orderBy('productos_clientes.valor_serial_qr', $value);
                }
                if($attribute == 'operador_serial_qr'){
                    $query->orderBy('productos_clientes.operador_serial_qr', $value);
                }
                if($attribute == 'valor_serial_datamatrix'){
                    $query->orderBy('productos_clientes.valor_serial_datamatrix', $value);
                }
                if($attribute == 'operador_serial_datamatrix'){
                    $query->orderBy('productos_clientes.operador_serial_datamatrix', $value);
                }
                if($attribute == 'valor_serial_pdf'){
                    $query->orderBy('productos_clientes.valor_serial_pdf', $value);
                }
                if($attribute == 'operador_serial_pdf'){
                    $query->orderBy('productos_clientes.operador_serial_pdf', $value);
                }
                if($attribute == 'nombre'){
                    $query->orderBy('clientes.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('productos_clientes.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('productos_clientes.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('productos_clientes.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('productos_clientes.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('productos_clientes.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("productos_clientes.updated_at", "desc");
        }

        $productosCliente = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($productosCliente ?? [] as $productoCliente){
            array_push($datos, $productoCliente);
        }

        $cantidadProductosCliente = count($productosCliente);
        $to = isset($productosCliente) && $cantidadProductosCliente > 0 ? $productosCliente->currentPage() * $productosCliente->perPage() : null;
        $to = isset($to) && isset($productosCliente) && $to > $productosCliente->total() && $cantidadProductosCliente > 0 ? $productosCliente->total() : $to;
        $from = isset($to) && isset($productosCliente) && $cantidadProductosCliente > 0 ?
            ( $productosCliente->perPage() > $to ? 1 : ($to - $cantidadProductosCliente) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($productosCliente) && $cantidadProductosCliente > 0 ? +$productosCliente->perPage() : 0,
            'pagina_actual' => isset($productosCliente) && $cantidadProductosCliente > 0 ? $productosCliente->currentPage() : 1,
            'ultima_pagina' => isset($productosCliente) && $cantidadProductosCliente > 0 ? $productosCliente->lastPage() : 0,
            'total' => isset($productosCliente) && $cantidadProductosCliente > 0 ? $productosCliente->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $productoCliente = ProductoCliente::find($id);
        $cliente = $productoCliente->cliente;
        return [
            'id' => $productoCliente->id,
            'producto_s3_id' => $productoCliente->producto_s3_id,
            'cliente_id' => $productoCliente->cliente_id,
            'nombre_producto_cliente' => $productoCliente->nombre_producto_cliente,
            'codigo_externo_producto' => $productoCliente->codigo_externo_producto,
            'indicativo_producto_externo' => $productoCliente->indicativo_producto_externo,
            'indicativo_producto_empaque' => $productoCliente->indicativo_producto_empaque,
            'valor_serial_interno' => $productoCliente->valor_serial_interno,
            'operador_serial_interno' => $productoCliente->operador_serial_interno,
            'valor_serial_qr' => $productoCliente->valor_serial_qr,
            'operador_serial_qr' => $productoCliente->operador_serial_qr,
            'valor_serial_datamatrix' => $productoCliente->valor_serial_datamatrix,
            'operador_serial_datamatrix' => $productoCliente->operador_serial_datamatrix,
            'valor_serial_pdf' => $productoCliente->valor_serial_pdf,
            'operador_serial_pdf' => $productoCliente->operador_serial_pdf,
            'estado' => $productoCliente->estado,
            'usuario_creacion_id' => $productoCliente->usuario_creacion_id,
            'usuario_creacion_nombre' => $productoCliente->usuario_creacion_nombre,
            'usuario_modificacion_id' => $productoCliente->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $productoCliente->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($productoCliente->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($productoCliente->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
        ];
    }

    public static function modificarOCrear($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();

        if(!isset($dto['id'])){
            $dto['usuario_creacion_id'] = $usuario->id ?? ($dto['usuario_creacion_id'] ?? null);
            $dto['usuario_creacion_nombre'] = $usuario->nombre ?? ($dto['usuario_creacion_nombre'] ?? null);
        }
        if(isset($usuario) || isset($dto['usuario_modificacion_id'])){
            $dto['usuario_modificacion_id'] = $usuario->id ?? ($dto['usuario_modificacion_id'] ?? null);
            $dto['usuario_modificacion_nombre'] = $usuario->nombre ?? ($dto['usuario_modificacion_nombre'] ?? null);
        }

        // Consultar aplicación
        $productoCliente = isset($dto['id']) ? ProductoCliente::find($dto['id']) : new ProductoCliente();

        // Guardar objeto original para auditoria
        $productoClienteOriginal = $productoCliente->toJson();

        $productoCliente->fill($dto);
        $guardado = $productoCliente->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la aplicación.", $productoCliente);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $productoCliente->id,
            'nombre_recurso' => ProductoCliente::class,
            'descripcion_recurso' => $productoCliente->nombre_producto_cliente,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $productoClienteOriginal : $productoCliente->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $productoCliente->toJson() : null
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return ProductoCliente::cargar($productoCliente->id);
    }

    public static function eliminar($id)
    {
        $productoCliente = ProductoCliente::find($id);

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $productoCliente->id,
            'nombre_recurso' => ProductoCliente::class,
            'descripcion_recurso' => $productoCliente->nombre_producto_cliente,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $productoCliente->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return $productoCliente->delete();
    }

    public static function maximoValorARestar($id){
        $producto = ProductoCliente::find($id);
        $numbers = [1];
        if($producto->operador_serial_interno == '-'){
            $numbers[] = $producto->valor_serial_interno+1;
        }
        if($producto->operador_serial_qr == '-'){
            $numbers[] = $producto->valor_serial_qr+1;
        }
        if($producto->operador_serial_datamatrix == '-'){
            $numbers[] = $producto->valor_serial_datamatrix+1;
        }
        if($producto->operador_serial_pdf == '-'){
            $numbers[] = $producto->valor_serial_pdf+1;
        }
        return max($numbers);
    }

    use HasFactory;
}
