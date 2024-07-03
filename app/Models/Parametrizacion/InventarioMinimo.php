<?php

namespace App\Models\Parametrizacion;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\DB;
use App\Models\Parametrizacion\Kit;
use Illuminate\Support\Facades\Auth;
use App\Models\Parametrizacion\Lugar;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\ProductoCliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventarioMinimo extends Model
{
    protected $table = 'inventario_minimo'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'kit_id',
        'producto_cliente_id',
        'cliente_id',
        'lugar_id',
        'cantidad_inventario_minimo',
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
        return $this->belongsTo(ProductoCliente::class, 'producto_cliente_id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function lugar(){
        return $this->belongsTo(Lugar::class, 'lugar_id');
    }

    public static function obtenerColeccionLigera($dto){
        $query = InventarioMinimo::select(
                'id',
                'cantidad_inventario_minimo',
                'estado',
            );
        $query->orderBy('cantidad_inventario_minimo', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();

        $query = DB::table('inventario_minimo')
            ->leftJoin('kits', 'kits.id', 'inventario_minimo.kit_id')
            ->leftJoin('productos_clientes', 'productos_clientes.id', 'inventario_minimo.producto_cliente_id')
            ->join('clientes', 'clientes.id', 'inventario_minimo.cliente_id')
            ->join('lugares', 'lugares.id', 'inventario_minimo.lugar_id')
            ->select(
                'inventario_minimo.id',
                'clientes.nombre AS cliente',
                DB::raw("CASE WHEN inventario_minimo.kit_id IS NULL 
                        THEN 'Producto'
                        ELSE 'Kit'
                        END AS tipo"
                ),
                DB::raw("CASE WHEN inventario_minimo.kit_id IS NULL 
                        THEN productos_clientes.nombre_producto_cliente
                        ELSE kits.nombre
                        END AS producto"
                ),
                'lugares.nombre AS lugar',
                'inventario_minimo.cantidad_inventario_minimo',
                'inventario_minimo.estado',
                'inventario_minimo.usuario_creacion_id',
                'inventario_minimo.usuario_creacion_nombre',
                'inventario_minimo.usuario_modificacion_id',
                'inventario_minimo.usuario_modificacion_nombre',
                'inventario_minimo.created_at AS fecha_creacion',
                'inventario_minimo.updated_at AS fecha_modificacion',
            );

        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('inventario_minimo.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('inventario_minimo.cliente_id', $dto['cliente']);
        }

        if(isset($dto['nombre'])){
            $query->whereRaw("CASE WHEN inventario_minimo.kit_id IS NOT NULL
                THEN kits.nombre 
                ELSE productos_clientes.nombre_producto_cliente
                END LIKE ?", 
                ['%'.$dto['nombre'].'%']
            );
        }
        
        if(isset($dto['lugar'])){
            $query->where('lugares.id', $dto['lugar']);
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'cliente'){
                    $query->orderBy('clientes.nombre', $value);
                }
                if($attribute == 'lugar'){
                    $query->orderBy('lugares.nombre', $value);
                }
                if($attribute == 'cantidad_inventario_minimo'){
                    $query->orderBy('inventario_minimo.cantidad_inventario_minimo', $value);
                }
                if($attribute == 'tipo'){
                    $query->orderBy(DB::raw("
                        CASE WHEN inventario_minimo.kit_id IS NULL 
                        THEN 'Producto'
                        ELSE 'Kit'
                        END"), $value);
                }
                if($attribute == 'producto'){
                    $query->orderBy(DB::raw("
                        CASE WHEN inventario_minimo.kit_id IS NULL 
                        THEN productos_clientes.nombre_producto_cliente
                        ELSE kits.nombre
                        END"), $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('inventario_minimo.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('inventario_minimo.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('inventario_minimo.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('inventario_minimo.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('inventario_minimo.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("inventario_minimo.updated_at", "desc");
        }

        $inventariosMinimos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($inventariosMinimos ?? [] as $inventarioMinimo){
            array_push($datos, $inventarioMinimo);
        }

        $cantidadInventariosMinimos = count($inventariosMinimos);
        $to = isset($inventariosMinimos) && $cantidadInventariosMinimos > 0 ? $inventariosMinimos->currentPage() * $inventariosMinimos->perPage() : null;
        $to = isset($to) && isset($inventariosMinimos) && $to > $inventariosMinimos->total() && $cantidadInventariosMinimos > 0 ? $inventariosMinimos->total() : $to;
        $from = isset($to) && isset($inventariosMinimos) && $cantidadInventariosMinimos > 0 ?
            ( $inventariosMinimos->perPage() > $to ? 1 : ($to - $cantidadInventariosMinimos) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($inventariosMinimos) && $cantidadInventariosMinimos > 0 ? +$inventariosMinimos->perPage() : 0,
            'pagina_actual' => isset($inventariosMinimos) && $cantidadInventariosMinimos > 0 ? $inventariosMinimos->currentPage() : 1,
            'ultima_pagina' => isset($inventariosMinimos) && $cantidadInventariosMinimos > 0 ? $inventariosMinimos->lastPage() : 0,
            'total' => isset($inventariosMinimos) && $cantidadInventariosMinimos > 0 ? $inventariosMinimos->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $inventarioMinimo = InventarioMinimo::find($id);
        $cliente = $inventarioMinimo->cliente;
        $kit = $inventarioMinimo->kit;
        $producto = $inventarioMinimo->producto;
        $lugar = $inventarioMinimo->lugar;

        return [
            'id' => $inventarioMinimo->id,
            'cantidad_inventario_minimo' => $inventarioMinimo->cantidad_inventario_minimo,
            'estado' => $inventarioMinimo->estado,
            'usuario_creacion_id' => $inventarioMinimo->usuario_creacion_id,
            'usuario_creacion_nombre' => $inventarioMinimo->usuario_creacion_nombre,
            'usuario_modificacion_id' => $inventarioMinimo->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $inventarioMinimo->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($inventarioMinimo->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($inventarioMinimo->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
            'kit' => isset($kit) ? [
                'id' => $kit->id,
                'nombre' => $kit->nombre
            ] : null,
            'producto' => isset($producto) ? [
                'id' => $producto->id,
                'nombre' => $producto->nombre_producto_cliente
            ] : null,
            'lugar' => isset($lugar) ? [
                'id' => $lugar->id,
                'nombre' => $lugar->nombre
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
        $inventarioMinimo = isset($dto['id']) ? InventarioMinimo::find($dto['id']) : new InventarioMinimo();

        // Guardar objeto original para auditoria
        $inventarioMinimoOriginal = $inventarioMinimo->toJson();

        $inventarioMinimo->fill($dto);
        $guardado = $inventarioMinimo->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la aplicación.", $inventarioMinimo);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $inventarioMinimo->id,
            'nombre_recurso' => InventarioMinimo::class,
            'descripcion_recurso' => $inventarioMinimo->cantidad_inventario_minimo,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $inventarioMinimoOriginal : $inventarioMinimo->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $inventarioMinimo->toJson() : null
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return InventarioMinimo::cargar($inventarioMinimo->id);
    }

    public static function eliminar($id)
    {
        $inventarioMinimo = InventarioMinimo::find($id);

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $inventarioMinimo->id,
            'nombre_recurso' => InventarioMinimo::class,
            'descripcion_recurso' => $inventarioMinimo->cantidad_inventario_minimo,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $inventarioMinimo->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return $inventarioMinimo->delete();
    }

    public static function obtenerInventario(){
        $paramAlertaInvMin = ParametroConstante::where('codigo_parametro', 'ID_ALERTA_INVENTARIO_MINIMO')
            ->first();
        $idAlertaInvMin = $paramAlertaInvMin?$paramAlertaInvMin->valor_parametro:0;
        $clientes = DB::table('inventario_minimo AS t1')
            ->join('sellos AS t2', function($join){
                $join->on('t2.lugar_id', 't1.lugar_id')
                    ->whereRaw("
                        CASE WHEN t1.kit_id IS NULL 
                        THEN
                            t2.producto_id = t1.producto_cliente_id
                            AND t2.tipo_empaque_despacho = 'I'
                        ELSE
                            t2.kit_id = t1.kit_id
                            AND t2.tipo_empaque_despacho = 'K'
                        END
                    ");
            })
            ->join('clientes AS t3', 't3.id', 't1.cliente_id')
            ->join('lugares AS t4', 't4.id', 't1.lugar_id')
            ->leftJoin('productos_clientes AS t5', 't5.id', 't1.producto_cliente_id')
            ->leftJoin('kits AS t6', 't6.id', 't1.kit_id')
            ->join('clientes_alertas AS t7', 't7.cliente_id', 't3.id')
            ->whereIn('t2.estado_sello', ['STO','DEV','TTO'])
            ->where('t7.alerta_id', $idAlertaInvMin)
            ->where('t7.estado', 1)
            ->select(
                't1.cliente_id'
            )
            ->groupBy('t1.cliente_id')
            ->get();

        $informacion = [];

        foreach($clientes as $cliente){
            $data = DB::table('inventario_minimo AS t1')
            ->join('sellos AS t2', function($join){
                $join->on('t2.lugar_id', 't1.lugar_id')
                    ->whereRaw("
                        CASE WHEN t1.kit_id IS NULL 
                        THEN
                            t2.producto_id = t1.producto_cliente_id
                            AND t2.tipo_empaque_despacho = 'I'
                        ELSE
                            t2.kit_id = t1.kit_id
                            AND t2.tipo_empaque_despacho = 'K'
                        END
                    ");
            })
            ->join('clientes AS t3', 't3.id', 't1.cliente_id')
            ->join('lugares AS t4', 't4.id', 't1.lugar_id')
            ->leftJoin('productos_clientes AS t5', 't5.id', 't1.producto_cliente_id')
            ->leftJoin('kits AS t6', 't6.id', 't1.kit_id')
            ->whereIn('t2.estado_sello', ['STO','DEV','TTO'])
            ->where('t1.cliente_id', $cliente->cliente_id)
            ->select(
                't3.nombre AS cliente',
                't4.nombre AS lugar',
                DB::raw("CASE WHEN t1.kit_id IS NULL 
                    THEN t5.nombre_producto_cliente
                    ELSE t6.nombre 
                    END AS producto_kit"
                ),
                DB::raw("count(1) AS inventario"),
                't1.cantidad_inventario_minimo AS inventario_minimo'
            )->groupBy('t1.cliente_id', 't1.lugar_id', 't1.kit_id', 't1.producto_cliente_id', 't1.cantidad_inventario_minimo')
            ->orderBy('t1.lugar_id','asc');
            
            $filtereData = DB::table($data, 'sub')
                ->select(
                    'sub.cliente',
                    'sub.lugar',
                    'sub.producto_kit',
                    'sub.inventario',
                    'sub.inventario_minimo',
                )
                ->whereRaw('CAST(sub.inventario_minimo AS SIGNED) > CAST(sub.inventario AS SIGNED)')
                ->get();

            if(count($filtereData) == 0) continue;
            
            $email = Usuario::join('users', 'users.id', 'usuarios.user_id')
                ->join('model_has_roles', 'model_has_roles.model_id', 'users.id')
                ->join('roles', 'roles.id', 'model_has_roles.role_id')
                ->where('roles.type', 'AC')
                ->where('usuarios.asociado_id', $cliente->cliente_id)
                ->select('correo_electronico')
                ->first();
            
            $row = [
                'email' => $email->correo_electronico,
                'data' => $filtereData
            ];

            $informacion[] = $row;
        }
        return $informacion;
    }

    use HasFactory;
}
