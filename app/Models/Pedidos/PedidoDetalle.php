<?php

namespace App\Models\Pedidos;

use Exception;
use Carbon\Carbon;
use App\Models\Pedidos\Pedido;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use App\Models\Parametrizacion\Kit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\KitProducto;
use App\Models\Parametrizacion\ProductoCliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoDetalle extends Model
{
    protected $table = 'pedidos_detalle'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'pedido_id',
        'consecutivo_detalle',
        'producto_id',
        'kit_id',
        'cantidad',
        'color_id',
        'prefijo',
        'posfijo',
        'longitud_serial',
        'consecutivo_serie_inicial',
        'serie_inicial_articulo',
        'serie_final_articulo',
        'longitud_sello',
        'diametro',
        'observaciones',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function pedido(){
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function producto(){
        return $this->belongsTo(ProductoCliente::class, 'producto_id');
    }

    public function kit(){
        return $this->belongsTo(Kit::class, 'kit_id');
    }

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('pedidos_detalle')
            ->join('pedidos', 'pedidos.id', 'pedidos_detalle.pedido_id')
            ->select(
                'pedidos_detalle.id',
                'pedidos_detalle.consecutivo_detalle',
                'pedidos_detalle.estado',
                'pedidos.id AS pedido_id',
                'pedidos.numero_pedido',
            );
        $query->orderBy('pedidos.numero_pedido', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        // $db2 = getenv('DB_DATABASE_2');
        $query = DB::table('pedidos_detalle')
            ->join('pedidos', 'pedidos.id', 'pedidos_detalle.pedido_id')
            ->leftJoin('productos_clientes', 'productos_clientes.id', 'pedidos_detalle.producto_id')
            ->leftJoin('kits', 'kits.id', 'pedidos_detalle.kit_id')
            ->select(
                'pedidos_detalle.id',
                'pedidos_detalle.consecutivo_detalle',
                'pedidos_detalle.cantidad',
                'pedidos_detalle.color_id',
                'pedidos_detalle.prefijo',
                'pedidos_detalle.posfijo',
                'pedidos_detalle.longitud_serial',
                'pedidos_detalle.consecutivo_serie_inicial',
                'pedidos_detalle.serie_inicial_articulo',
                'pedidos_detalle.serie_final_articulo',
                'pedidos_detalle.longitud_sello',
                'pedidos_detalle.diametro',
                'pedidos_detalle.observaciones',
                'pedidos_detalle.estado',
                'pedidos_detalle.usuario_creacion_id',
                'pedidos_detalle.usuario_creacion_nombre',
                'pedidos_detalle.usuario_modificacion_id',
                'pedidos_detalle.usuario_modificacion_nombre',
                'pedidos_detalle.created_at AS fecha_creacion',
                'pedidos_detalle.updated_at AS fecha_modificacion',
                'pedidos.numero_pedido',
                'productos_clientes.nombre_producto_cliente AS producto',
                'productos_clientes.producto_s3_id',
                'kits.nombre AS kit',
                'kits.id AS kit_id',
            )
            ->where('pedidos.id', $dto['pedido_id'])
            ->where(function($query1) {
                $query1->whereNull('pedidos_detalle.producto_id')
                    ->orWhereNull('pedidos_detalle.kit_id');
            });

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'consecutivo_detalle'){
                    $query->orderBy('pedidos_detalle.consecutivo_detalle', $value);
                }
                if($attribute == 'cantidad'){
                    $query->orderBy('pedidos_detalle.cantidad', $value);
                }
                if($attribute == 'color_id'){
                    $query->orderBy('pedidos_detalle.color_id', $value);
                }
                if($attribute == 'prefijo'){
                    $query->orderBy('pedidos_detalle.prefijo', $value);
                }
                if($attribute == 'posfijo'){
                    $query->orderBy('pedidos_detalle.posfijo', $value);
                }
                if($attribute == 'longitud_serial'){
                    $query->orderBy('pedidos_detalle.longitud_serial', $value);
                }
                if($attribute == 'consecutivo_serie_inicial'){
                    $query->orderBy('pedidos_detalle.consecutivo_serie_inicial', $value);
                }
                if($attribute == 'serie_inicial_articulo'){
                    $query->orderBy('pedidos_detalle.serie_inicial_articulo', $value);
                }
                if($attribute == 'serie_final_articulo'){
                    $query->orderBy('pedidos_detalle.serie_final_articulo', $value);
                }
                if($attribute == 'longitud_sello'){
                    $query->orderBy('pedidos_detalle.longitud_sello', $value);
                }
                if($attribute == 'diametro'){
                    $query->orderBy('pedidos_detalle.diametro', $value);
                }
                if($attribute == 'observaciones'){
                    $query->orderBy('pedidos_detalle.observaciones', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('pedidos_detalle.estado', $value);
                }
                if($attribute == 'numero_pedido'){
                    $query->orderBy('pedidos.numero_pedido', $value);
                }
                if($attribute == 'producto'){
                    $query->orderBy('productos_clientes.nombre_producto_cliente', $value);
                }
                if($attribute == 'kit'){
                    $query->orderBy('kits.nombre', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('pedidos_detalle.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('pedidos_detalle.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('pedidos_detalle.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('pedidos_detalle.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("pedidos_detalle.updated_at", "desc");
        }

        $pedidos_detalle = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($pedidos_detalle ?? [] as $pedidoDetalle){
            array_push($datos, $pedidoDetalle);
        }

        $cantidadPedidosDetalle = count($pedidos_detalle);
        $to = isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->currentPage() * $pedidos_detalle->perPage() : null;
        $to = isset($to) && isset($pedidos_detalle) && $to > $pedidos_detalle->total() && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->total() : $to;
        $from = isset($to) && isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ?
            ( $pedidos_detalle->perPage() > $to ? 1 : ($to - $cantidadPedidosDetalle) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? +$pedidos_detalle->perPage() : 0,
            'pagina_actual' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->currentPage() : 1,
            'ultima_pagina' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->lastPage() : 0,
            'total' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->total() : 0
        ];
    }

    public static function obtenerColeccionParaLectura($dto){
        // $db2 = getenv('DB_DATABASE_2');
        $pedido = Pedido::find($dto['pedido_id']);
        $numeroPedido = $pedido->numero_pedido;
        $query = DB::table('pedidos_detalle AS t1')
            ->join('pedidos AS t2', 't2.id', 't1.pedido_id')
            ->leftJoin('productos_clientes AS t3', 't3.id', 't1.producto_id')
            ->leftJoin('kits AS t4', 't4.id', 't1.kit_id')
            ->select(
                't1.id',
                't1.consecutivo_detalle',
                't1.cantidad',
                't1.color_id',
                't1.prefijo',
                't1.posfijo',
                't1.longitud_serial',
                't1.consecutivo_serie_inicial',
                't1.serie_inicial_articulo',
                't1.serie_final_articulo',
                't1.longitud_sello',
                't1.diametro',
                't1.observaciones',
                't1.estado',
                't1.usuario_creacion_id',
                't1.usuario_creacion_nombre',
                't1.usuario_modificacion_id',
                't1.usuario_modificacion_nombre',
                't1.created_at AS fecha_creacion',
                't1.updated_at AS fecha_modificacion',
                't2.numero_pedido',
                't3.nombre_producto_cliente AS producto',
                't3.producto_s3_id',
                't4.nombre AS kit',
                't4.id AS kit_id',
            )
            ->where('t2.id', $dto['pedido_id'])
            ->where(function($query1) {
                $query1->whereNull('t1.producto_id')
                    ->orWhereNull('t1.kit_id');
            })
            ->whereRaw("CASE WHEN t1.producto_id IS NOT NULL 
                    THEN
                (
                    SELECT COUNT(1)
                    FROM sellos t5
                    WHERE t5.numero_pedido = ?
                    AND t5.serial BETWEEN t1.serie_inicial_articulo AND t1.serie_final_articulo
                    AND t5.estado_sello IN ('GEN', 'DEV')
                ) 
                    ELSE
                (
                    SELECT COUNT(1)
                    FROM sellos t6
                    WHERE t6.numero_pedido = ?
                    AND t6.kit_id = t1.kit_id
                    AND t6.estado_sello IN ('GEN', 'DEV')
                    AND t6.serial BETWEEN 
                    (
                        SELECT t7.serie_inicial_articulo
                        FROM pedidos_detalle t7
                        WHERE t7.pedido_id = ?
                        AND t7.kit_id = t1.kit_id
                        AND t7.producto_id = 
                        (
                            SELECT t9.id
                            FROM kits_productos t8
                            JOIN productos_clientes t9
                                ON t8.producto_id = t9.id
                            WHERE t9.indicativo_producto_empaque = 'S'
                            AND t8.kit_id = t1.kit_id
                        )
                    ) AND
                    (
                        SELECT t10.serie_final_articulo
                        FROM pedidos_detalle t10
                        WHERE t10.pedido_id = ?
                        AND t10.kit_id = t1.kit_id
                        AND t10.producto_id = 
                        (
                            SELECT t12.id
                            FROM kits_productos t11
                            JOIN productos_clientes t12
                                ON t11.producto_id = t12.id
                            WHERE t12.indicativo_producto_empaque = 'S'
                            AND t11.kit_id = t1.kit_id
                        )
                    )
                ) END > 0
            ", [
                $numeroPedido, 
                $numeroPedido, 
                $dto['pedido_id'], 
                $dto['pedido_id']
            ]);

        // if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
        //     foreach ($dto['ordenar_por'] as $attribute => $value){
        //         if($attribute == 'consecutivo_detalle'){
        //             $query->orderBy('t1.consecutivo_detalle', $value);
        //         }
        //         if($attribute == 'cantidad'){
        //             $query->orderBy('t1.cantidad', $value);
        //         }
        //         if($attribute == 'color_id'){
        //             $query->orderBy('t1.color_id', $value);
        //         }
        //         if($attribute == 'prefijo'){
        //             $query->orderBy('t1.prefijo', $value);
        //         }
        //         if($attribute == 'posfijo'){
        //             $query->orderBy('t1.posfijo', $value);
        //         }
        //         if($attribute == 'longitud_serial'){
        //             $query->orderBy('t1.longitud_serial', $value);
        //         }
        //         if($attribute == 'consecutivo_serie_inicial'){
        //             $query->orderBy('t1.consecutivo_serie_inicial', $value);
        //         }
        //         if($attribute == 'serie_inicial_articulo'){
        //             $query->orderBy('t1.serie_inicial_articulo', $value);
        //         }
        //         if($attribute == 'serie_final_articulo'){
        //             $query->orderBy('t1.serie_final_articulo', $value);
        //         }
        //         if($attribute == 'longitud_sello'){
        //             $query->orderBy('t1.longitud_sello', $value);
        //         }
        //         if($attribute == 'diametro'){
        //             $query->orderBy('t1.diametro', $value);
        //         }
        //         if($attribute == 'observaciones'){
        //             $query->orderBy('t1.observaciones', $value);
        //         }
        //         if($attribute == 'estado'){
        //             $query->orderBy('t1.estado', $value);
        //         }
        //         if($attribute == 'numero_pedido'){
        //             $query->orderBy('t2.numero_pedido', $value);
        //         }
        //         if($attribute == 'producto'){
        //             $query->orderBy('t3.nombre_producto_cliente', $value);
        //         }
        //         if($attribute == 'kit'){
        //             $query->orderBy('t4.nombre', $value);
        //         }
        //         if($attribute == 'usuario_creacion_nombre'){
        //             $query->orderBy('t1.usuario_creacion_nombre', $value);
        //         }
        //         if($attribute == 'usuario_modificacion_nombre'){
        //             $query->orderBy('t1.usuario_modificacion_nombre', $value);
        //         }
        //         if($attribute == 'fecha_creacion'){
        //             $query->orderBy('t1.created_at', $value);
        //         }
        //         if($attribute == 'fecha_modificacion'){
        //             $query->orderBy('t1.updated_at', $value);
        //         }
        //     }
        // }else{
        //     $query->orderBy("t1.updated_at", "desc");
        // }

        $pedidos_detalle = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($pedidos_detalle ?? [] as $pedidoDetalle){
            array_push($datos, $pedidoDetalle);
        }

        $cantidadPedidosDetalle = count($pedidos_detalle);
        $to = isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->currentPage() * $pedidos_detalle->perPage() : null;
        $to = isset($to) && isset($pedidos_detalle) && $to > $pedidos_detalle->total() && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->total() : $to;
        $from = isset($to) && isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ?
            ( $pedidos_detalle->perPage() > $to ? 1 : ($to - $cantidadPedidosDetalle) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? +$pedidos_detalle->perPage() : 0,
            'pagina_actual' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->currentPage() : 1,
            'ultima_pagina' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->lastPage() : 0,
            'total' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->total() : 0
        ];
    }

    public static function obtenerColeccionKit($dto){
        $query = DB::table('pedidos_detalle')
            ->join('pedidos', 'pedidos.id', 'pedidos_detalle.pedido_id')
            ->leftJoin('productos_clientes', 'productos_clientes.id', 'pedidos_detalle.producto_id')
            ->leftJoin('kits', 'kits.id', 'pedidos_detalle.kit_id')
            ->select(
                'pedidos_detalle.id',
                'pedidos_detalle.consecutivo_detalle',
                'pedidos_detalle.cantidad',
                'pedidos_detalle.color_id',
                'pedidos_detalle.prefijo',
                'pedidos_detalle.posfijo',
                'pedidos_detalle.longitud_serial',
                'pedidos_detalle.consecutivo_serie_inicial',
                'pedidos_detalle.serie_inicial_articulo',
                'pedidos_detalle.serie_final_articulo',
                'pedidos_detalle.longitud_sello',
                'pedidos_detalle.diametro',
                'pedidos_detalle.observaciones',
                'pedidos_detalle.estado',
                'pedidos_detalle.usuario_creacion_id',
                'pedidos_detalle.usuario_creacion_nombre',
                'pedidos_detalle.usuario_modificacion_id',
                'pedidos_detalle.usuario_modificacion_nombre',
                'pedidos_detalle.created_at AS fecha_creacion',
                'pedidos_detalle.updated_at AS fecha_modificacion',
                'pedidos.numero_pedido',
                'productos_clientes.nombre_producto_cliente AS producto',
                'productos_clientes.producto_s3_id',
                'kits.nombre AS kit',
            )
            ->where('pedidos.id', $dto['pedido_id'])
            ->where('pedidos_detalle.kit_id', $dto['kit_id'])
            ->whereNotNull('pedidos_detalle.producto_id');

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'consecutivo_detalle'){
                    $query->orderBy('pedidos_detalle.consecutivo_detalle', $value);
                }
                if($attribute == 'cantidad'){
                    $query->orderBy('pedidos_detalle.cantidad', $value);
                }
                if($attribute == 'color_id'){
                    $query->orderBy('pedidos_detalle.color_id', $value);
                }
                if($attribute == 'prefijo'){
                    $query->orderBy('pedidos_detalle.prefijo', $value);
                }
                if($attribute == 'posfijo'){
                    $query->orderBy('pedidos_detalle.posfijo', $value);
                }
                if($attribute == 'longitud_serial'){
                    $query->orderBy('pedidos_detalle.longitud_serial', $value);
                }
                if($attribute == 'consecutivo_serie_inicial'){
                    $query->orderBy('pedidos_detalle.consecutivo_serie_inicial', $value);
                }
                if($attribute == 'serie_inicial_articulo'){
                    $query->orderBy('pedidos_detalle.serie_inicial_articulo', $value);
                }
                if($attribute == 'serie_final_articulo'){
                    $query->orderBy('pedidos_detalle.serie_final_articulo', $value);
                }
                if($attribute == 'longitud_sello'){
                    $query->orderBy('pedidos_detalle.longitud_sello', $value);
                }
                if($attribute == 'diametro'){
                    $query->orderBy('pedidos_detalle.diametro', $value);
                }
                if($attribute == 'observaciones'){
                    $query->orderBy('pedidos_detalle.observaciones', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('pedidos_detalle.estado', $value);
                }
                if($attribute == 'numero_pedido'){
                    $query->orderBy('pedidos.numero_pedido', $value);
                }
                if($attribute == 'producto'){
                    $query->orderBy('productos_clientes.nombre_producto_cliente', $value);
                }
                if($attribute == 'kit'){
                    $query->orderBy('kits.nombre', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('pedidos_detalle.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('pedidos_detalle.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('pedidos_detalle.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('pedidos_detalle.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("pedidos_detalle.updated_at", "desc");
        }

        $pedidos_detalle = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($pedidos_detalle ?? [] as $pedidoDetalle){
            array_push($datos, $pedidoDetalle);
        }

        $cantidadPedidosDetalle = count($pedidos_detalle);
        $to = isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->currentPage() * $pedidos_detalle->perPage() : null;
        $to = isset($to) && isset($pedidos_detalle) && $to > $pedidos_detalle->total() && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->total() : $to;
        $from = isset($to) && isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ?
            ( $pedidos_detalle->perPage() > $to ? 1 : ($to - $cantidadPedidosDetalle) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? +$pedidos_detalle->perPage() : 0,
            'pagina_actual' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->currentPage() : 1,
            'ultima_pagina' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->lastPage() : 0,
            'total' => isset($pedidos_detalle) && $cantidadPedidosDetalle > 0 ? $pedidos_detalle->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $pedidoDetalle = PedidoDetalle::find($id);
        $pedido = $pedidoDetalle->pedido;
        $producto = $pedidoDetalle->producto;
        $kit = $pedidoDetalle->kit;
        return [
            'id' => $pedidoDetalle->id,
            'consecutivo_detalle' => $pedidoDetalle->consecutivo_detalle,
            'cantidad' => $pedidoDetalle->cantidad,
            'color_id' => $pedidoDetalle->color_id,
            'prefijo' => $pedidoDetalle->prefijo,
            'posfijo' => $pedidoDetalle->posfijo,
            'longitud_serial' => $pedidoDetalle->longitud_serial,
            'consecutivo_serie_inicial' => $pedidoDetalle->consecutivo_serie_inicial,
            'serie_inicial_articulo' => $pedidoDetalle->serie_inicial_articulo,
            'serie_final_articulo' => $pedidoDetalle->serie_final_articulo,
            'longitud_sello' => $pedidoDetalle->longitud_sello,
            'diametro' => $pedidoDetalle->diametro,
            'observaciones' => $pedidoDetalle->observaciones,
            'estado' => $pedidoDetalle->estado,
            'usuario_creacion_id' => $pedidoDetalle->usuario_creacion_id,
            'usuario_creacion_nombre' => $pedidoDetalle->usuario_creacion_nombre,
            'usuario_modificacion_id' => $pedidoDetalle->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $pedidoDetalle->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($pedidoDetalle->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($pedidoDetalle->updated_at))->format("Y-m-d H:i:s"),
            'pedido' => isset($pedido) ? [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido
            ] : null,
            'producto' => isset($producto) ? [
                'id' => $producto->id,
                'nombre' => $producto->nombre_producto_cliente,
                'producto_s3_id' => $producto->producto_s3_id,
            ] : null,
            'kit' => isset($kit) ? [
                'id' => $kit->id,
                'nombre' => $kit->nombre
            ] : null,
        ];
    }

    public static function modificarProducto($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();
        if(isset($usuario)){
            $dto['usuario_modificacion_id'] = $usuario->id;
            $dto['usuario_modificacion_nombre'] = $usuario->nombre;
        }

        // Consultar aplicación
        $pedidoDetalle = PedidoDetalle::find($dto['id']);

        // Guardar objeto original para auditoria
        $pedidoDetalleOriginal = $pedidoDetalle->toJson();

        $pedidoDetalle->fill($dto);
        $guardado = $pedidoDetalle->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el pedido.", $pedidoDetalle);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $pedidoDetalle->id,
            'nombre_recurso' => PedidoDetalle::class,
            'descripcion_recurso' => $pedidoDetalle->pedido->numero_pedido,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $pedidoDetalleOriginal,
            'recurso_resultante' => $pedidoDetalle->toJson()
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return PedidoDetalle::cargar($pedidoDetalle->id);
    }

    public static function modificarKit($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();
        $dto['usuario_modificacion_id'] = $usuario->id;
        $dto['usuario_modificacion_nombre'] = $usuario->nombre;

        // Consultar aplicación
        $pedidoDetalle = PedidoDetalle::find($dto['id']);
        
        // Guardar objeto original para auditoria
        $pedidoDetalleOriginal = $pedidoDetalle->toJson();

        if($pedidoDetalle->kit_id != $dto['kit_id']){
            // // cambio de kit
            // eliminar productos asociados al kit
            $sellosKitEnPedido = PedidoDetalle::where('pedido_id', $dto['pedido_id'])
                ->where('kit_id', $pedidoDetalle->kit_id)
                ->whereNotNull('producto_id')
                ->get();
            foreach($sellosKitEnPedido as $sello){
                $selloPedido = PedidoDetalle::find($sello->id);
                $selloPedido->delete();
            }
            // cambio informacion de kit
            $pedidoDetalle->fill($dto);
            $guardado = $pedidoDetalle->save();
            if(!$guardado){
                throw new Exception("Ocurrió un error al intentar guardar el pedido.", $pedidoDetalle);
            }
            // creo registros de productos para el kit
            $dto['usuario_creacion_id'] = $usuario->id ?? null;
            $dto['usuario_creacion_nombre'] = $usuario->nombre ?? null;
            $sellosKit = KitProducto::where('kit_id', $dto['kit_id'])->where('estado', 1)->get();
            foreach($sellosKit as $sello){
                $productoDetalle = new PedidoDetalle();
                $newData = $dto;
                $newData['observaciones'] = null;
                $newData['producto_id'] = $sello->producto_id;
                $newData = PedidoDetalle::obtenerInformacionUltimoSello($newData, $newData['cantidad']*$sello->cantidad);
                $productoDetalle->fill($newData);
                $guardado = $productoDetalle->save();
                if(!$guardado){
                    throw new Exception("Ocurrió un error al intentar guardar el detalle pedido.", $productoDetalle);
                }
            }
        } else if ($pedidoDetalle->cantidad != $dto['cantidad']){
            // cambio de cantidad
            $pedidoDetalle->fill($dto);
            $guardado = $pedidoDetalle->save();
            if(!$guardado){
                throw new Exception("Ocurrió un error al intentar guardar el pedido.", $pedidoDetalle);
            }
            $sellosKitEnPedido = PedidoDetalle::where('pedido_id', $dto['pedido_id'])
                ->where('kit_id', $dto['kit_id'])
                ->whereNotNull('producto_id')
                ->get();
            foreach($sellosKitEnPedido as $sello){
                $selloKit = KitProducto::where('kit_id', $dto['kit_id'])
                    ->where('estado', 1)
                    ->where('producto_id', $sello->producto_id)
                    ->first();
                if(!$selloKit){
                    continue;
                }
                $selloPedido = PedidoDetalle::find($sello->id);
                $selloPedido->cantidad = $selloKit->cantidad*intval($dto['cantidad']);
                $final = $selloPedido->consecutivo_serie_inicial+$selloPedido->cantidad-1;
                $nuevaLongitud = strlen(strval($final));
                $longitud = max($nuevaLongitud, $selloPedido->longitud_serial);
                if($nuevaLongitud != $selloPedido->longitud_serial){
                    $inicialMod = str_pad($selloPedido->consecutivo_serie_inicial, $longitud, '0', STR_PAD_LEFT);
                    $selloPedido->serie_inicial_articulo = $selloPedido->prefijo.$inicialMod.$selloPedido->posfijo;
                    $selloPedido->longitud_serial = $longitud;
                }
                $finalMod = str_pad($final, $longitud, '0', STR_PAD_LEFT);
                $selloPedido->serie_final_articulo = $selloPedido->prefijo.$finalMod.$selloPedido->posfijo;
                $guardado = $selloPedido->save();
                if(!$guardado){
                    throw new Exception("Ocurrió un error al intentar guardar el pedido.", $selloPedido);
                }
            }
        } else {
            // cambio en observaciones
            $pedidoDetalle->fill($dto);
            $guardado = $pedidoDetalle->save();
            if(!$guardado){
                throw new Exception("Ocurrió un error al intentar guardar el pedido.", $pedidoDetalle);
            }
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $pedidoDetalle->id,
            'nombre_recurso' => PedidoDetalle::class,
            'descripcion_recurso' => $pedidoDetalle->pedido->numero_pedido,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $pedidoDetalleOriginal,
            'recurso_resultante' => $pedidoDetalle->toJson()
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return PedidoDetalle::cargar($pedidoDetalle->id);
    }

    public static function crear($data){
        $user = Auth::user();
        $usuario = $user->usuario();

        $data['usuario_creacion_id'] = $usuario->id ?? null;
        $data['usuario_creacion_nombre'] = $usuario->nombre ?? null;
        $data['usuario_modificacion_id'] = $usuario->id ?? null;
        $data['usuario_modificacion_nombre'] = $usuario->nombre ?? null;

        $pedidoDetalle = new PedidoDetalle();
        $newData = [];
        if($data['tipo'] === 'P'){
            $newData = PedidoDetalle::obtenerInformacionUltimoSello($data, $data['cantidad']);
            $pedidoDetalle->fill($newData);
            $guardado = $pedidoDetalle->save();
            if(!$guardado){
                throw new Exception("Ocurrió un error al intentar guardar el detalle pedido.", $pedidoDetalle);
            }
        } else {
            $consecutivo = PedidoDetalle::where('pedido_id', $data['pedido_id'])->max('consecutivo_detalle')??0;
            $data['consecutivo_detalle'] = $consecutivo+1;
            $pedidoDetalle->fill($data);
            $guardado = $pedidoDetalle->save();
            if(!$guardado){
                throw new Exception("Ocurrió un error al intentar guardar el detalle pedido.", $pedidoDetalle);
            }
            $sellosKit = KitProducto::where('kit_id', $data['kit_id'])->where('estado', 1)->get();
            foreach($sellosKit as $sello){
                $productoDetalle = new PedidoDetalle();
                $newData = $data;
                $newData['producto_id'] = $sello->producto_id;
                $newData = PedidoDetalle::obtenerInformacionUltimoSello($newData, $newData['cantidad']*$sello->cantidad);
                $productoDetalle->fill($newData);
                $guardado = $productoDetalle->save();
                if(!$guardado){
                    throw new Exception("Ocurrió un error al intentar guardar el detalle pedido.", $productoDetalle);
                }
            }
        }
        $auditoriaDto = [
            'id_recurso' => $pedidoDetalle->id,
            'nombre_recurso' => PedidoDetalle::class,
            'descripcion_recurso' => $pedidoDetalle->pedido->numero_pedido,
            'accion' => AccionAuditoriaEnum::CREAR,
            'recurso_original' => $pedidoDetalle->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        $pedido = Pedido::find($data['pedido_id']);
        if($pedido->estado_pedido !== 'REG'){
            $pedido->estado_pedido = 'REG';
            $pedido->save();
        }

        return PedidoDetalle::cargar($pedidoDetalle->id);
    }

    public static function obtenerInformacionUltimoSello($data, $cantidad){
        $newData = $data;
        $longitud = 0;
        $prefijo = null;
        $posfijo = null;
        $consecutivo_inicial = 1;
        $consecutivo_inicial_mod = '';
        $consecutivo_final = 0;
        $consecutivo_final_mod = '';
        $serie_inicial = '';
        $serie_final = '';
        $color_id = null;
        $longitud_sello = null;
        $diametro = null;

        $sello = DB::table('pedidos_detalle')
            ->join('pedidos', 'pedidos.id', 'pedidos_detalle.pedido_id')
            ->select(
                'pedidos_detalle.*'
            )
            ->where('pedidos.cliente_id', $newData['cliente_id'])
            ->where('pedidos_detalle.producto_id', $newData['producto_id'])
            ->where('pedidos_detalle.estado', 1)
            ->whereRaw('pedidos_detalle.consecutivo_serie_inicial = 
                (SELECT MAX(pedidos_detalle.consecutivo_serie_inicial) 
                FROM pedidos_detalle 
                JOIN pedidos
                    ON pedidos.id = pedidos_detalle.pedido_id
                WHERE pedidos.cliente_id = ?
                AND pedidos_detalle.estado = 1
                AND pedidos_detalle.producto_id = ?)',
                [$newData['cliente_id'], 
                $newData['producto_id']])
            ->first();

        if(isset($sello)){
            $prefijo = $sello->prefijo;
            $posfijo = $sello->posfijo;
            $consecutivo_inicial = ($sello->consecutivo_serie_inicial??1)+$sello->cantidad;
            $consecutivo_final = $consecutivo_inicial+intval($cantidad)-1;
            $longitud = max($sello->longitud_serial,strlen(strval($consecutivo_final)));
            $consecutivo_inicial_mod = str_pad($consecutivo_inicial, $longitud, '0', STR_PAD_LEFT);
            $consecutivo_final_mod = str_pad($consecutivo_final, $longitud, '0', STR_PAD_LEFT);
            $color_id =  $sello->color_id;
            $serie_inicial = $prefijo.$consecutivo_inicial_mod.$posfijo;
            $serie_final = $prefijo.$consecutivo_final_mod.$posfijo;
            $longitud_sello = $sello->longitud_sello;
            $diametro = $sello->diametro;
        } else {
            $consecutivo_inicial = ProductoCliente::maximoValorARestar($newData['producto_id']);
            $serie_final = $consecutivo_inicial+intval($cantidad)-1;
            $longitud = strlen(strval($serie_final));
            $serie_inicial = str_pad($consecutivo_inicial, $longitud, '0', STR_PAD_LEFT);
        }

        $consecutivo = PedidoDetalle::where('pedido_id', $newData['pedido_id'])->max('consecutivo_detalle')??0;

        $newData['color_id'] = $color_id;
        $newData['prefijo'] = $prefijo;
        $newData['posfijo'] = $posfijo;
        $newData['longitud_serial'] = $longitud;
        $newData['consecutivo_serie_inicial'] = $consecutivo_inicial;
        $newData['serie_inicial_articulo'] = $serie_inicial;
        $newData['serie_final_articulo'] = $serie_final;
        $newData['longitud_sello'] = $longitud_sello;
        $newData['diametro'] = $diametro;
        $newData['consecutivo_detalle'] = $consecutivo+1;
        $newData['cantidad'] = $cantidad;

        return $newData;
    }

    public static function eliminar($datos)
    {
        $pedidoDetalle = PedidoDetalle::find($datos['id']);

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $pedidoDetalle->id,
            'nombre_recurso' => PedidoDetalle::class,
            'descripcion_recurso' => $pedidoDetalle->pedido->numero_pedido,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $pedidoDetalle->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        if($datos['kit']){
            $sellosKitEnPedido = PedidoDetalle::where('pedido_id', $pedidoDetalle->pedido_id)
                ->where('kit_id', $pedidoDetalle->kit_id)
                ->whereNotNull('producto_id')
                ->get();
            foreach($sellosKitEnPedido as $sello){
                $selloPedido = PedidoDetalle::find($sello->id);
                $selloPedido->delete();
            }
        }

        return $pedidoDetalle->delete();
    }

    use HasFactory;
}
