<?php

namespace App\Models\Parametrizacion;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use App\Models\Parametrizacion\Kit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\ProductoCliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KitProducto extends Model
{
    protected $table = 'kits_productos'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'kit_id',
        'producto_id',
        'cantidad',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function kit(){
        return $this->belongsTo(Kit::class, 'kit_id');
    }

    public function producto(){
        return $this->belongsTo(ProductoCliente::class, 'producto_id');
    }

    public static function obtenerColeccionLigera($dto){
        $query = KitProducto::select(
                'id',
                'kit_id',
                'producto_id',
                'cantidad',
                'estado',
            )->where('kit_id', $dto['kit_id']);
        $query->orderBy('producto_id', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('kits_productos')
            ->join('kits', 'kits.id', 'kits_productos.kit_id')
            ->join('productos_clientes', 'productos_clientes.id', 'kits_productos.producto_id')
            ->select(
                'kits_productos.id',
                'kits.nombre AS kit',
                'productos_clientes.producto_s3_id',
                'productos_clientes.id AS producto_id',
                'productos_clientes.nombre_producto_cliente AS producto',
                'kits_productos.cantidad',
                'kits_productos.estado',
                'kits_productos.usuario_creacion_id',
                'kits_productos.usuario_creacion_nombre',
                'kits_productos.usuario_modificacion_id',
                'kits_productos.usuario_modificacion_nombre',
                'kits_productos.created_at AS fecha_creacion',
                'kits_productos.updated_at AS fecha_modificacion',
            )->where('kits.id', $dto['kit_id']);

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'cantidad'){
                    $query->orderBy('kits_productos.cantidad', $value);
                }
                if($attribute == 'kit'){
                    $query->orderBy('kits.nombre', $value);
                }
                if($attribute == 'producto'){
                    $query->orderBy('productos_clientes.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('kits_productos.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('kits_productos.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('kits_productos.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('kits_productos.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('kits_productos.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("kits_productos.updated_at", "desc");
        }

        $productosKit = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($productosKit ?? [] as $productoKit){
            array_push($datos, $productoKit);
        }

        $cantidadProductosKit = count($productosKit);
        $to = isset($productosKit) && $cantidadProductosKit > 0 ? $productosKit->currentPage() * $productosKit->perPage() : null;
        $to = isset($to) && isset($productosKit) && $to > $productosKit->total() && $cantidadProductosKit > 0 ? $productosKit->total() : $to;
        $from = isset($to) && isset($productosKit) && $cantidadProductosKit > 0 ?
            ( $productosKit->perPage() > $to ? 1 : ($to - $cantidadProductosKit) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($productosKit) && $cantidadProductosKit > 0 ? +$productosKit->perPage() : 0,
            'pagina_actual' => isset($productosKit) && $cantidadProductosKit > 0 ? $productosKit->currentPage() : 1,
            'ultima_pagina' => isset($productosKit) && $cantidadProductosKit > 0 ? $productosKit->lastPage() : 0,
            'total' => isset($productosKit) && $cantidadProductosKit > 0 ? $productosKit->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $productoKit = KitProducto::find($id);
        $kit = $productoKit->kit;
        $producto = $productoKit->producto;
        return [
            'id' => $productoKit->id,
            'kit_id' => $productoKit->kit_id,
            'producto_id' => $productoKit->producto_id,
            'cantidad' => $productoKit->cantidad,
            'estado' => $productoKit->estado,
            'usuario_creacion_id' => $productoKit->usuario_creacion_id,
            'usuario_creacion_nombre' => $productoKit->usuario_creacion_nombre,
            'usuario_modificacion_id' => $productoKit->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $productoKit->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($productoKit->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($productoKit->updated_at))->format("Y-m-d H:i:s"),
            'kit' => isset($kit) ? [
                'id' => $kit->id,
                'nombre' => $kit->nombre
            ] : null,
            'producto' => isset($producto) ? [
                'id' => $producto->id,
                'nombre' => $producto->nombre_producto_cliente
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
        $productoKit = isset($dto['id']) ? KitProducto::find($dto['id']) : new KitProducto();

        // Guardar objeto original para auditoria
        $productoKitOriginal = $productoKit->toJson();

        $productoKit->fill($dto);
        $guardado = $productoKit->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el producto del kit.", $productoKit);
        }
        
        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $productoKit->id,
            'nombre_recurso' => KitProducto::class,
            'descripcion_recurso' => $productoKit->producto->nombre_producto_cliente,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $productoKitOriginal : $productoKit->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $productoKit->toJson() : null
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return KitProducto::cargar($productoKit->id);
    }

    public static function eliminar($id)
    {
        $productoKit = KitProducto::find($id);
        
        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $productoKit->id,
            'nombre_recurso' => KitProducto::class,
            'descripcion_recurso' => $productoKit->producto->nombre_producto_cliente,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $productoKit->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return $productoKit->delete();
    }

    public static function getEmpaque($id){
        $productos = ProductoCliente::select('id')->where('indicativo_producto_empaque', 'S')->where('estado', 1);
        $empaqueDelKit = KitProducto::where('kit_id', $id)->where('estado', 1)->whereIn('producto_id', $productos)->first();
        return $empaqueDelKit->producto_id;
    }

    use HasFactory;
}
