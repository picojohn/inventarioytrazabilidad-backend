<?php

namespace App\Models\Remisiones;

use Exception;
use Carbon\Carbon;
use App\Models\Pedidos\Sello;
use App\Models\SelloBitacora;
use App\Enum\AccionAuditoriaEnum;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Parametrizacion\Lugar;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\TipoAlerta;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Remisiones\RemisionDetalle;
use App\Models\Parametrizacion\ParametroConstante;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remision extends Model
{
    protected $table = 'remisiones'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'numero_remision',
        'cliente_envio_id',
        'cliente_destino_id',
        'fecha_remision',
        'lugar_envio_id',
        'user_envio_id',
        'lugar_destino_id',
        'user_destino_id',
        'hora_estimada_envio',
        'transportador',
        'guia_transporte',
        'indicativo_confirmacion_recepcion',
        'estado_remision',
        'fecha_aceptacion',
        'fecha_rechazo',
        'fecha_anulacion',
        'observaciones_remision',
        'observaciones_rechazo',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function clienteEnvio(){
        return $this->belongsTo(Cliente::class, 'cliente_envio_id');
    }

    public function clienteDestino(){
        return $this->belongsTo(Cliente::class, 'cliente_destino_id');
    }

    public function lugarEnvio(){
        return $this->belongsTo(Lugar::class, 'lugar_envio_id');
    }

    public function lugarDestino(){
        return $this->belongsTo(Lugar::class, 'lugar_destino_id');
    }

    public function usuarioEnvio(){
        return $this->belongsTo(Usuario::class, 'user_envio_id');
    }

    public function usuarioDestino(){
        return $this->belongsTo(Usuario::class, 'user_destino_id');
    }

    public function detalles(){
        return $this->hasMany(RemisionDetalle::class,'numero_remision', 'numero_remision');
    }

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('remisiones')
            ->join('clientes As t1', 't1.id', 'remisiones.cliente_envio_id')
            ->join('clientes As t2', 't2.id', 'remisiones.cliente_destino_id')
            ->select(
                'remisiones.id',
                'remisiones.numero_remision',
                DB::raw('CONCAT(remisiones.numero_remision, " - ", remisiones.fecha_remision) AS nombre'),
                'remisiones.estado_remision',
                'remisiones.fecha_remision',
                't1.nombre AS cliente_envio',
                't1.id AS cliente_envio_id',
                't2.nombre AS cliente_destino',
                't2.id AS cliente_destino_id',
            );
        if(isset($dto['usuario'])){
            $query->where('remisiones.user_destino_id', $dto['usuario'])
            ->where('remisiones.estado_remision', 'GEN');
        }
        $query->orderBy('remisiones.numero_remision', 'ASC');
        $rows = $query->get();
        foreach($rows as $row){
            $q2 = DB::table('remisiones_detalles AS mt')
                ->join('productos_clientes AS t1', 't1.id', 'mt.producto_id')
                ->leftJoin('kits AS t2', 't2.id', 'mt.kit_id')
                ->select(
                    DB::raw('CASE WHEN mt.kit_id IS NULL THEN
                        t1.id ELSE
                        t2.id END AS id'
                    ),
                    DB::raw('CASE WHEN mt.kit_id IS NULL THEN
                        t1.nombre_producto_cliente ELSE
                        t2.nombre END AS nombre'
                    ),
                    DB::raw('CASE WHEN mt.kit_id IS NULL THEN
                        "P" ELSE
                        "K" END AS tipo'
                    ),
                    DB::raw("CONCAT(MIN(mt.serial), '-', MAX(mt.serial)) AS rango"),
                )
                ->where('mt.numero_remision', $row->numero_remision)
                ->groupBy(
                    DB::raw('CASE WHEN mt.kit_id IS NULL THEN
                        t1.id ELSE
                        t2.id END'
                    ),
                    DB::raw('CASE WHEN mt.kit_id IS NULL THEN
                        t1.nombre_producto_cliente ELSE
                        t2.nombre END'
                    ),
                    DB::raw('CASE WHEN mt.kit_id IS NULL THEN
                        "P" ELSE
                        "K" END'
                    ),
                )
                ->get();
            $row->productos = $q2;
            $rem = Remision::find($row->id);
            $row->cuenta = count($rem->detalles);
        } 
        return $rows;
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();

        $query = DB::table('remisiones')
            ->join('clientes AS t1', 't1.id', 'remisiones.cliente_envio_id')
            ->join('clientes AS t2', 't2.id', 'remisiones.cliente_destino_id')
            ->join('usuarios AS u_origen', 'u_origen.id', 'remisiones.user_envio_id')
            ->join('usuarios AS u_destino', 'u_destino.id', 'remisiones.user_destino_id')
            ->join('lugares AS l_origen', 'l_origen.id', 'remisiones.lugar_envio_id')
            ->join('lugares AS l_destino', 'l_destino.id', 'remisiones.lugar_destino_id')
            ->select(
                'remisiones.id',
                'remisiones.numero_remision',
                'remisiones.fecha_remision',
                'remisiones.hora_estimada_envio',
                'remisiones.transportador',
                'remisiones.guia_transporte',
                'remisiones.indicativo_confirmacion_recepcion',
                'remisiones.estado_remision',
                'remisiones.fecha_aceptacion',
                'remisiones.fecha_rechazo',
                'remisiones.fecha_anulacion',
                'remisiones.observaciones_remision',
                'remisiones.observaciones_rechazo',
                'remisiones.user_envio_id',
                'remisiones.user_destino_id',
                'remisiones.lugar_envio_id',
                'remisiones.lugar_destino_id',
                'remisiones.usuario_creacion_id',
                'remisiones.usuario_creacion_nombre',
                'remisiones.usuario_modificacion_id',
                'remisiones.usuario_modificacion_nombre',
                'remisiones.created_at AS fecha_creacion',
                'remisiones.updated_at AS fecha_modificacion',
                'u_origen.nombre AS usuario_envio',
                'l_origen.nombre AS lugar_envio',
                'u_destino.nombre AS usuario_destino',
                'l_destino.nombre AS lugar_destino',
                't1.nombre AS cliente_envio',
                't2.nombre AS cliente_destino',
            );

        if($rol->type == 'AC' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where(function($query1) use($usuario) {
                $query1->where('t1.id', $usuario->asociado_id)
                    ->orWhere('t2.id', $usuario->asociado_id);
            });
        } else if($rol->type != 'AC' && $rol->type != 'IN'){
            $query->where(function($query1) use($usuario){
                $query1->where('u_origen.id', $usuario->id)
                    ->orWhere('u_destino.id', $usuario->id);
            });
        }

        if(isset($dto['numero'])){
            $query->where('remisiones.numero_remision', $dto['numero']);
        }

        if(isset($dto['fecha'])){
            $query->where('remisiones.fecha_remision', '=' ,$dto['fecha']);
        }

        if(isset($dto['guia'])){
            $query->where('remisiones.guia_transporte',  'like', '%'.$dto['guia'].'%');
        }

        if(isset($dto['cliente'])){
            $query->where(function($query1) use($dto) {
                $query1->where('t1.id', $dto['cliente'])
                    ->orWhere('t2.id', $dto['cliente']);
            });
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'numero_remision'){
                    $query->orderBy('remisiones.numero_remision', $value);
                }
                if($attribute == 'fecha_remision'){
                    $query->orderBy('remisiones.fecha_remision', $value);
                }
                if($attribute == 'hora_estimada_envio'){
                    $query->orderBy('remisiones.hora_estimada_envio', $value);
                }
                if($attribute == 'transportador'){
                    $query->orderBy('remisiones.transportador', $value);
                }
                if($attribute == 'guia_transporte'){
                    $query->orderBy('remisiones.guia_transporte', $value);
                }
                if($attribute == 'indicativo_confirmacion_recepcion'){
                    $query->orderBy('remisiones.indicativo_confirmacion_recepcion', $value);
                }
                if($attribute == 'estado_remision'){
                    $query->orderBy('remisiones.estado_remision', $value);
                }
                if($attribute == 'fecha_aceptacion'){
                    $query->orderBy('remisiones.fecha_aceptacion', $value);
                }
                if($attribute == 'fecha_rechazo'){
                    $query->orderBy('remisiones.fecha_rechazo', $value);
                }
                if($attribute == 'fecha_anulacion'){
                    $query->orderBy('remisiones.fecha_anulacion', $value);
                }
                if($attribute == 'observaciones_remision'){
                    $query->orderBy('remisiones.observaciones_remision', $value);
                }
                if($attribute == 'observaciones_rechazo'){
                    $query->orderBy('remisiones.observaciones_rechazo', $value);
                }
                if($attribute == 'cliente_envio'){
                    $query->orderBy('t1.nombre', $value);
                }
                if($attribute == 'cliente_destino'){
                    $query->orderBy('t2.nombre', $value);
                }
                if($attribute == 'usuario_envio'){
                    $query->orderBy('u_origen.nombre', $value);
                }
                if($attribute == 'lugar_envio'){
                    $query->orderBy('l_origen.nombre', $value);
                }
                if($attribute == 'usuario_destino'){
                    $query->orderBy('u_destino.nombre', $value);
                }
                if($attribute == 'lugar_destino'){
                    $query->orderBy('l_destino.nombre', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('remisiones.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('remisiones.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('remisiones.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('remisiones.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("remisiones.updated_at", "desc");
        }

        $remisiones = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($remisiones ?? [] as $remision){
            array_push($datos, $remision);
        }

        $cantidadRemisiones = count($remisiones);
        $to = isset($remisiones) && $cantidadRemisiones > 0 ? $remisiones->currentPage() * $remisiones->perPage() : null;
        $to = isset($to) && isset($remisiones) && $to > $remisiones->total() && $cantidadRemisiones > 0 ? $remisiones->total() : $to;
        $from = isset($to) && isset($remisiones) && $cantidadRemisiones > 0 ?
            ( $remisiones->perPage() > $to ? 1 : ($to - $cantidadRemisiones) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($remisiones) && $cantidadRemisiones > 0 ? +$remisiones->perPage() : 0,
            'pagina_actual' => isset($remisiones) && $cantidadRemisiones > 0 ? $remisiones->currentPage() : 1,
            'ultima_pagina' => isset($remisiones) && $cantidadRemisiones > 0 ? $remisiones->lastPage() : 0,
            'total' => isset($remisiones) && $cantidadRemisiones > 0 ? $remisiones->total() : 0
        ];
    }

    public static function obtenerColeccionSellos($dto){
        $query = DB::table('sellos')
            ->join('productos_clientes', 'productos_clientes.id', 'sellos.producto_id')
            ->leftJoin('kits', 'kits.id', 'sellos.kit_id')
            ->select(
                'sellos.id',
                'sellos.serial',
                DB::raw("
                    CASE WHEN sellos.tipo_empaque_despacho = 'I'
                    THEN productos_clientes.nombre_producto_cliente
                    ELSE kits.nombre
                    END AS producto_kit
                "),
                DB::raw("
                    CASE WHEN sellos.estado_sello = 'TTO'
                    THEN 1 
                    ELSE 0 
                    END AS checked
                "),
                'sellos.estado_sello',
                'sellos.producto_id',
                'sellos.kit_id',
                'sellos.numero_ultima_remision',
            )
            ->where('sellos.user_id', $dto['user_envio_id'])
            ->where('sellos.lugar_id', $dto['lugar_envio_id'])
            ->where(function($query1) use($dto){
                $query1->whereIn('sellos.estado_sello', ['LEC', 'STO'])
                    ->orWhere(function($query2) use($dto){
                        $query2->where('sellos.estado_sello', 'TTO')
                            ->where('sellos.numero_ultima_remision', $dto['numero_remision']);
                    });
            })
            ->whereRaw("
                1 = CASE WHEN sellos.tipo_empaque_despacho = 'K' 
                THEN IF(sellos.kit_id IS NULL, 0, 1)
                ELSE 1 
                END
            ")
            ->where('productos_clientes.cliente_id', $dto['cliente_destino_id']);
        $query3 = clone $query;
        $seleccionados = $query3->where('sellos.estado_sello', 'TTO')->count();
        
        if(isset($dto['serial_inicial'])){
            $query->where('sellos.serial', '>=', $dto['serial_inicial']);
        }
        
        if(isset($dto['serial_final'])){
            $query->where('sellos.serial', '<=' ,$dto['serial_final']);
        }

        if(isset($dto['numero_pedido'])){
            $query->where('sellos.numero_pedido', $dto['numero_pedido']);
        }

        if(isset($dto['soloSeleccionados']) && boolval($dto['soloSeleccionados']) === true){
            $query->where('sellos.estado_sello', 'TTO');
        }

        $query4 = clone $query;
        $seleccionadosSobreFiltro = $query4->where('sellos.estado_sello', 'TTO')->count();

        if (isset($dto['ordenar_por']) && is_countable($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'serial'){
                    $query->orderBy('sellos.serial', $value);
                }
                if($attribute == 'producto_kit'){
                    $query->orderBy(DB::raw("
                        CASE WHEN sellos.tipo_empaque_despacho = 'I'
                        THEN productos_clientes.nombre_producto_cliente
                        ELSE kits.nombre
                        END
                    "), $value);
                }
                if($attribute == 'estado_sello'){
                    $query->orderBy('sellos.estado_sello', $value);
                }
                if($attribute == 'numero_ultima_remision'){
                    $query->orderBy('sellos.numero_ultima_remision', $value);
                }
            }
        }else{
            $query->orderBy(
                DB::raw("
                    CASE WHEN sellos.tipo_empaque_despacho = 'I'
                    THEN productos_clientes.nombre_producto_cliente
                    ELSE kits.nombre
                    END
                "), "asc")
                ->orderBy('sellos.serial', 'asc');
        }

        $sellos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($sellos ?? [] as $remision){
            array_push($datos, $remision);
        }

        $cantidadRemisiones = count($sellos);
        $to = isset($sellos) && $cantidadRemisiones > 0 ? $sellos->currentPage() * $sellos->perPage() : null;
        $to = isset($to) && isset($sellos) && $to > $sellos->total() && $cantidadRemisiones > 0 ? $sellos->total() : $to;
        $from = isset($to) && isset($sellos) && $cantidadRemisiones > 0 ?
            ( $sellos->perPage() > $to ? 1 : ($to - $cantidadRemisiones) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($sellos) && $cantidadRemisiones > 0 ? +$sellos->perPage() : 0,
            'pagina_actual' => isset($sellos) && $cantidadRemisiones > 0 ? $sellos->currentPage() : 1,
            'ultima_pagina' => isset($sellos) && $cantidadRemisiones > 0 ? $sellos->lastPage() : 0,
            'total' => isset($sellos) && $cantidadRemisiones > 0 ? $sellos->total() : 0,
            'seleccionados' => $seleccionados,
            'seleccionadosSobreFiltro' => $seleccionadosSobreFiltro,
        ];
    }

    public static function obtenerColeccionSellosRemisionados($dto){
        $eventoRemision = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_REMISION')->first()->valor_parametro??0
        );
        $query = DB::table('sellos_bitacora AS mt')
            ->join('productos_clientes AS t1', 't1.id', 'mt.producto_id')
            ->leftJoin('kits AS t2', 't2.id', 'mt.kit_id')
            ->join('sellos AS t3', 't3.id', 'mt.sello_id')
            ->select(
                't3.id',
                't3.serial',
                DB::raw("
                    CASE WHEN mt.tipo_empaque_despacho = 'I'
                    THEN t1.nombre_producto_cliente
                    ELSE t2.nombre
                    END AS producto_kit
                "),
                DB::raw("1 AS checked"),
                'mt.estado_sello',
                'mt.producto_id',
                'mt.kit_id',
                'mt.numero_remision',
            )
            ->where('mt.numero_remision', $dto['numero_remision'])
            ->where('mt.tipo_evento_id', $eventoRemision->id)
            ->where(function($filter){
                $filter->where('mt.tipo_empaque_despacho', 'I')
                    ->orWhere(function($filter2){
                        $filter2->where('mt.tipo_empaque_despacho', 'K')
                            ->whereNotNull('mt.kit_id');
                    });
            })
            ->where('t1.cliente_id', $dto['cliente_destino_id']);

        $query3 = clone $query;
        $seleccionados = $query3->count();
        
        if(isset($dto['serial_inicial'])){
            $query->where('t3.serial', '>=', $dto['serial_inicial']);
        }
        
        if(isset($dto['serial_final'])){
            $query->where('t3.serial', '<=' ,$dto['serial_final']);
        }

        if(isset($dto['numero_pedido'])){
            $query->where('mt.numero_pedido', $dto['numero_pedido']);
        }

        if(isset($dto['soloSeleccionados']) && boolval($dto['soloSeleccionados']) === true){
            $query->where('mt.estado_sello', 'TTO');
        }

        $query4 = clone $query;
        $seleccionadosSobreFiltro = $query4->count();

        if (isset($dto['ordenar_por']) && is_countable($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'serial'){
                    $query->orderBy('t3.serial', $value);
                }
                if($attribute == 'producto_kit'){
                    $query->orderBy(DB::raw("
                        CASE WHEN mt.tipo_empaque_despacho = 'I'
                        THEN t1.nombre_producto_cliente
                        ELSE t2.nombre
                        END
                    "), $value);
                }
                if($attribute == 'estado_sello'){
                    $query->orderBy('mt.estado_sello', $value);
                }
                if($attribute == 'numero_remision'){
                    $query->orderBy('mt.numero_remision', $value);
                }
            }
        }else{
            $query->orderBy(
                DB::raw("
                    CASE WHEN mt.tipo_empaque_despacho = 'I'
                    THEN t1.nombre_producto_cliente
                    ELSE t2.nombre
                    END
                "), "asc")
                ->orderBy('t3.serial', 'asc');
        }

        $sellos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($sellos ?? [] as $remision){
            array_push($datos, $remision);
        }

        $cantidadSellosRemisionados = count($sellos);
        $to = isset($sellos) && $cantidadSellosRemisionados > 0 ? $sellos->currentPage() * $sellos->perPage() : null;
        $to = isset($to) && isset($sellos) && $to > $sellos->total() && $cantidadSellosRemisionados > 0 ? $sellos->total() : $to;
        $from = isset($to) && isset($sellos) && $cantidadSellosRemisionados > 0 ?
            ( $sellos->perPage() > $to ? 1 : ($to - $cantidadSellosRemisionados) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($sellos) && $cantidadSellosRemisionados > 0 ? +$sellos->perPage() : 0,
            'pagina_actual' => isset($sellos) && $cantidadSellosRemisionados > 0 ? $sellos->currentPage() : 1,
            'ultima_pagina' => isset($sellos) && $cantidadSellosRemisionados > 0 ? $sellos->lastPage() : 0,
            'total' => isset($sellos) && $cantidadSellosRemisionados > 0 ? $sellos->total() : 0,
            'seleccionados' => $seleccionados,
            'seleccionadosSobreFiltro' => $seleccionadosSobreFiltro,
        ];
    }

    public static function cargar($id)
    {
        $remision = Remision::find($id);
        $clienteEnvio = $remision->clienteEnvio;
        $clienteDestino = $remision->clienteDestino;
        $lugarEnvio = $remision->lugarEnvio;
        $lugarDestino = $remision->lugarDestino;
        $usuarioEnvio = $remision->usuarioEnvio;
        $usuarioDestino = $remision->usuarioDestino;

        return [
            'id' => $remision->id,
            'numero_remision' => $remision->numero_remision,
            'fecha_remision' => $remision->fecha_remision,
            'hora_estimada_envio' => $remision->hora_estimada_envio,
            'transportador' => $remision->transportador,
            'guia_transporte' => $remision->guia_transporte,
            'indicativo_confirmacion_recepcion' => $remision->indicativo_confirmacion_recepcion,
            'estado_remision' => $remision->estado_remision,
            'fecha_aceptacion' => $remision->fecha_aceptacion,
            'fecha_rechazo' => $remision->fecha_rechazo,
            'fecha_anulacion' => $remision->fecha_anulacion,
            'observaciones_remision' => $remision->observaciones_remision,
            'observaciones_rechazo' => $remision->observaciones_rechazo,
            'usuario_creacion_id' => $remision->usuario_creacion_id,
            'usuario_creacion_nombre' => $remision->usuario_creacion_nombre,
            'usuario_modificacion_id' => $remision->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $remision->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($remision->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($remision->updated_at))->format("Y-m-d H:i:s"),
            'clienteEnvio' => isset($clienteEnvio) ? [
                'id' => $clienteEnvio->id,
                'nombre' => $clienteEnvio->nombre
            ] : null,
            'clienteDestino' => isset($clienteDestino) ? [
                'id' => $clienteDestino->id,
                'nombre' => $clienteDestino->nombre
            ] : null,
            'lugarEnvio' => isset($lugarEnvio) ? [
                'id' => $lugarEnvio->id,
                'nombre' => $lugarEnvio->nombre
            ] : null,
            'lugarDestino' => isset($lugarDestino) ? [
                'id' => $lugarDestino->id,
                'nombre' => $lugarDestino->nombre
            ] : null,
            'usuarioEnvio' => isset($usuarioEnvio) ? [
                'id' => $usuarioEnvio->id,
                'nombre' => $usuarioEnvio->nombre
            ] : null,
            'usuarioDestino' => isset($usuarioDestino) ? [
                'id' => $usuarioDestino->id,
                'nombre' => $usuarioDestino->nombre
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
        $remision = isset($dto['id']) ? Remision::find($dto['id']) : new Remision();

        // Guardar objeto original para auditoria
        $pedidoOriginal = $remision->toJson();
        
        $numeroRemision = ParametroConstante::find(
            ParametroConstante::where('CODIGO_PARAMETRO', 'CONSECUTIVO_REMISION')->first()->id
        );

        if(!isset($dto['id'])){
            $dto['numero_remision'] = $numeroRemision->valor_parametro;
            $numeroRemision->valor_parametro = strval(intval($numeroRemision->valor_parametro)+1);
            $numeroRemision->save();
        }
        if($dto['indicativo_confirmacion_recepcion'] === 'N'){
            $dto['estado_remision'] = 'ACP';
            $dto['fecha_aceptacion'] = Carbon::now();
        }

        $remision->fill($dto);
        $guardado = $remision->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la remisión.", $remision);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $remision->id,
            'nombre_recurso' => 'REMIS-'.$remision->numero_remision,
            'descripcion_recurso' => $remision->numero_remision,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $pedidoOriginal : $remision->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $remision->toJson() : null
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return Remision::cargar($remision->id);
    }

    public static function eliminar($id, $datos)
    {
        $user = Auth::user();
        $usuario = $user->usuario();
        $remision = Remision::find($id);
        $remisionOriginal = $remision->toJson();
        $remision->estado_remision = 'ANU';
        $remision->fecha_anulacion = Carbon::now();
        $remision->usuario_modificacion_id = $usuario->id;
        $remision->usuario_modificacion_nombre = $usuario->nombre;
        $guardar = $remision->save();

        if(!$guardar){
            throw new Exception("Ocurrió un error al intentar anular la remisión.", $remision);
        }

        $remisionesDetalle = RemisionDetalle::where('numero_remision', $remision->numero_remision)
            ->update([
                'estado' => 0,
                'usuario_modificacion_id' => $usuario->id,
                'usuario_modificacion_nombre' => $usuario->nombre,
            ]);

        $sellos = Sello::where('numero_ultima_remision', $remision->numero_remision)
            ->whereRaw("
                1 = CASE WHEN tipo_empaque_despacho = 'K'
                THEN IF(kit_id IS NULL, 0, 1)
                ELSE 1 END
            ")
            ->get();
            
        $eventoAnularRemision = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_ANULAR_REMISION')->first()->valor_parametro??0
        );

        foreach($sellos as $sello){
            $selloOriginal = $sello->toJson();
            $numeroUltimaRemision = null;
            $fechaUltimaRemision = null;
            $remisionDetalleAnterior = RemisionDetalle::where('sello_id', $sello->id)
                ->where('estado', 1)
                ->whereRaw("
                    numero_remision = (
                        SELECT MAX(t1.numero_remision)
                        FROM remisiones_detalles t1
                        WHERE t1.sello_id = ?
                    )
                ", [$sello->id])
                ->first();
            if($remisionDetalleAnterior){
                $remisionAnterior = Remision::where('numero_remision', $remisionDetalleAnterior->numero_remision)->first();
                $numeroUltimaRemision = $remisionAnterior->numero_remision;
                $fechaUltimaRemision = $remisionAnterior->fecha_remision;
            }
            if($sello->kit_id){
                $sellosDeKit = Sello::where('producto_empaque_id', $sello->id)->get();
                foreach($sellosDeKit as $selloDeKit){
                    $selloDeKitOriginal = $selloDeKit->toJson();
                    $selloDeKit->estado_sello = $eventoAnularRemision->estado_sello;
                    $selloDeKit->numero_ultima_remision = $numeroUltimaRemision;
                    $selloDeKit->ultimo_tipo_evento_id = $eventoAnularRemision->id;
                    $selloDeKit->fecha_ultimo_evento = $remision->fecha_anulacion;
                    $selloDeKit->fecha_ultima_remision = $fechaUltimaRemision;
                    $selloDeKit->usuario_modificacion_id = $usuario->id;
                    $selloDeKit->usuario_modificacion_nombre = $usuario->nombre;
                    $selloDeKit->save();

                    $bitacoraDto = [
                        'sello_id' => $selloDeKit->id,
                        'producto_id' => $selloDeKit->producto_id,
                        'cliente_id' => $selloDeKit->cliente_id,
                        'producto_empaque_id' => $selloDeKit->producto_empaque_id,
                        'kit_id' => $selloDeKit->kit_id,
                        'tipo_empaque_despacho' => $selloDeKit->tipo_empaque_despacho,
                        'tipo_evento_id' => $eventoAnularRemision->id,
                        'fecha_evento' => $remision->fecha_anulacion,
                        'estado_sello' => $eventoAnularRemision->estado_sello,
                        'clase_evento' => $eventoAnularRemision->indicativo_clase_evento,
                        'numero_pedido' => $selloDeKit->numero_pedido,
                        'numero_remision' => $remision->numero_remision,
                        'lugar_origen_id' => $remision->lugar_envio_id,
                        'lugar_destino_id' => $remision->lugar_destino_id,
                        'usuario_destino_id' => $remision->user_destino_id,
                        'contenedor_id' => $selloDeKit->contenedor_id,
                        'documento_referencia' => $selloDeKit->documento_referencia,
                        'lugar_instalacion_id' => $selloDeKit->lugar_instalacion_id,
                        'zona_instalacion_id' => $selloDeKit->zona_instalacion_id,
                        'operacion_embarque_id' => $selloDeKit->operacion_embarque_id,
                        'longitud' => $datos['longitude'],
                        'latitud' => $datos['latitude'],
                        'usuario_creacion_id' => $usuario->id,
                        'usuario_creacion_nombre' => $usuario->nombre,
                    ];
                    SelloBitacora::create($bitacoraDto);
                }
            }
            $sello->estado_sello = $eventoAnularRemision->estado_sello;
            $sello->numero_ultima_remision = $numeroUltimaRemision;
            $sello->fecha_ultima_remision = $fechaUltimaRemision;
            $sello->ultimo_tipo_evento_id = $eventoAnularRemision->id;
            $sello->fecha_ultimo_evento = $remision->fecha_anulacion;
            $sello->usuario_modificacion_id = $usuario->id;
            $sello->usuario_modificacion_nombre = $usuario->nombre;
            $sello->save();

            $bitacoraDto = [
                'sello_id' => $sello->id,
                'producto_id' => $sello->producto_id,
                'cliente_id' => $sello->cliente_id,
                'producto_empaque_id' => $sello->producto_empaque_id,
                'kit_id' => $sello->kit_id,
                'tipo_empaque_despacho' => $sello->tipo_empaque_despacho,
                'tipo_evento_id' => $eventoAnularRemision->id,
                'fecha_evento' => $remision->fecha_anulacion,
                'estado_sello' => $eventoAnularRemision->estado_sello,
                'clase_evento' => $eventoAnularRemision->indicativo_clase_evento,
                'numero_pedido' => $sello->numero_pedido,
                'numero_remision' => $remision->numero_remision,
                'lugar_origen_id' => $remision->lugar_envio_id,
                'lugar_destino_id' => $remision->lugar_destino_id,
                'usuario_destino_id' => $remision->user_destino_id,
                'contenedor_id' => $sello->contenedor_id,
                'documento_referencia' => $sello->documento_referencia,
                'lugar_instalacion_id' => $sello->lugar_instalacion_id,
                'zona_instalacion_id' => $sello->zona_instalacion_id,
                'operacion_embarque_id' => $sello->operacion_embarque_id,
                'longitud' => $datos['longitude'],
                'latitud' => $datos['latitude'],
                'usuario_creacion_id' => $usuario->id,
                'usuario_creacion_nombre' => $usuario->nombre,
            ];
            SelloBitacora::create($bitacoraDto);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $remision->id,
            'nombre_recurso' => 'REMIS-'.$remision->numero_remision,
            'descripcion_recurso' => $remision->numero_remision,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $remisionOriginal,
            'recurso_resultante' => $remision->toJson() 
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return true;
    }

    public static function confirmarORechazar($datos){
        $user = Auth::user();
        $usuario = $user->usuario();
        $remision = Remision::find($datos['id']);
        $remisionOriginal = $remision->toJson();
        $eventoRechazarRemisionS = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECHAZAR_REMISION_SERIAL_SI')->first()->valor_parametro??0
        );
        $eventoRechazarRemisionN = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECHAZAR_REMISION_SERIAL_NO')->first()->valor_parametro??0
        );
        $eventoRecepcionRemision = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECIBIR_REMISION')->first()->valor_parametro??0
        );
        if($datos['action'] == 'Reject'){
            $remision->estado_remision = 'RCH';
            $remision->fecha_rechazo = Carbon::now();
            $remision->observaciones_rechazo = $datos['observaciones_rechazo'];
        } else {
            $remision->estado_remision = 'ACP';
            $remision->fecha_aceptacion = Carbon::now();
        }
        $remision->usuario_modificacion_id = $usuario->id;
        $remision->usuario_modificacion_nombre = $usuario->nombre;
        $guardar = $remision->save();
        if(!$guardar){
            throw new Exception("Ocurrió un error al intentar modificar la remisión.", $remision);
        }
        $sellos = Sello::where('numero_ultima_remision', $remision->numero_remision)->get();
        foreach($sellos as $sello){
            $selloOriginal = $sello->toJson();
            $sello->estado_sello = $eventoRechazarRemisionS->estado_sello;
            if(isset($datos['indicativo_series'])){
                if($datos['indicativo_series'] == 'S'){
                $sello->estado_sello = $eventoRechazarRemisionS->estado_sello;
                $sello->ultimo_tipo_evento_id = $eventoRechazarRemisionS->id;
                } else if($datos['indicativo_series'] == 'N'){
                $sello->estado_sello = $eventoRechazarRemisionN->estado_sello;
                $sello->ultimo_tipo_evento_id = $eventoRechazarRemisionN->id;
                }
            }
            if($datos['action'] == 'Confirm'){
                $sello->estado_sello = $eventoRecepcionRemision->estado_sello;
                $sello->ultimo_tipo_evento_id = $eventoRecepcionRemision->id;
                $sello->lugar_id = $remision->lugar_destino_id;
                $sello->user_id = $remision->user_destino_id;
                $sello->fecha_ultima_recepcion = $remision->fecha_aceptacion;
            }
            $sello->fecha_ultimo_evento = Carbon::now();
            $sello->usuario_modificacion_id = $usuario->id;
            $sello->usuario_modificacion_nombre = $usuario->nombre;
            $sello->save();

            if($datos['action'] == 'Confirm'){
                $bitacoraDto = [
                    'sello_id' => $sello->id,
                    'producto_id' => $sello->producto_id,
                    'cliente_id' => $sello->cliente_id,
                    'producto_empaque_id' => $sello->producto_empaque_id,
                    'kit_id' => $sello->kit_id,
                    'tipo_empaque_despacho' => $sello->tipo_empaque_despacho,
                    'tipo_evento_id' => $eventoRecepcionRemision->id,
                    'fecha_evento' => $remision->fecha_remision,
                    'estado_sello' => $eventoRecepcionRemision->estado_sello,
                    'clase_evento' => $eventoRecepcionRemision->indicativo_clase_evento,
                    'numero_pedido' => $sello->numero_pedido,
                    'numero_remision' => $remision->numero_remision,
                    'lugar_origen_id' => $remision->lugar_destino_id,
                    'lugar_destino_id' => null,
                    'usuario_destino_id' => null,
                    'contenedor_id' => $sello->contenedor_id,
                    'documento_referencia' => $sello->documento_referencia,
                    'lugar_instalacion_id' => $sello->lugar_instalacion_id,
                    'zona_instalacion_id' => $sello->zona_instalacion_id,
                    'operacion_embarque_id' => $sello->operacion_embarque_id,
                    'longitud' => $datos['longitude'],
                    'latitud' => $datos['latitude'],
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                ];
                SelloBitacora::create($bitacoraDto);
            } else {
                $bitacoraDto = [
                    'sello_id' => $sello->id,
                    'producto_id' => $sello->producto_id,
                    'cliente_id' => $sello->cliente_id,
                    'producto_empaque_id' => $sello->producto_empaque_id,
                    'kit_id' => $sello->kit_id,
                    'tipo_empaque_despacho' => $sello->tipo_empaque_despacho,
                    'tipo_evento_id' => isset($datos['indicativo_series']) && $datos['indicativo_series'] == 'N' 
                        ? $eventoRechazarRemisionN->id
                        : $eventoRechazarRemisionS->id,
                    'fecha_evento' => $remision->fecha_remision,
                    'estado_sello' => $sello->estado_sello,
                    'clase_evento' => isset($datos['indicativo_series']) && $datos['indicativo_series'] == 'N' 
                        ? $eventoRechazarRemisionN->indicativo_clase_evento
                        : $eventoRechazarRemisionS->indicativo_clase_evento,
                    'numero_pedido' => $sello->numero_pedido,
                    'numero_remision' => $remision->numero_remision,
                    'lugar_origen_id' => $remision->lugar_destino_id,
                    'lugar_destino_id' => $remision->lugar_envio_id,
                    'usuario_destino_id' => $remision->user_envio_id,
                    'contenedor_id' => $sello->contenedor_id,
                    'documento_referencia' => $sello->documento_referencia,
                    'lugar_instalacion_id' => $sello->lugar_instalacion_id,
                    'zona_instalacion_id' => $sello->zona_instalacion_id,
                    'operacion_embarque_id' => $sello->operacion_embarque_id,
                    'longitud' => $datos['longitude'],
                    'latitud' => $datos['latitude'],
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                ];
                SelloBitacora::create($bitacoraDto);
            }
        }
        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $remision->id,
            'nombre_recurso' => 'REMIS-'.$remision->numero_remision,
            'descripcion_recurso' => $remision->numero_remision,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $remisionOriginal,
            'recurso_resultante' => $remision->toJson() 
        ];
        AuditoriaTabla::crear($auditoriaDto);
        
        return Remision::cargar($datos['id']);
    }

    public static function obtenerRemisionesAtrasadas(){
        $now = Carbon::now();
        $idAlertaRecepcionRemision = (TipoAlerta::find(
            (ParametroConstante::where('codigo_parametro', 'ID_ALERTA_ACEPTACION_REMISION')->first()->valor_parametro)??0
        )->id)??0;
        $query = DB::table('remisiones AS t1')
            ->join ('clientes AS t2', 't2.id', 't1.cliente_destino_id')
            ->join('usuarios AS t3', 't3.id', 't1.user_destino_id')
            ->join('clientes_alertas AS t4', 't4.cliente_id', 't2.id')
            ->join('tipos_alertas AS t5', 't5.id', 't4.alerta_id')
            ->join('usuarios AS t6', 't6.id', 't1.user_envio_id')
            ->join('lugares AS t7', 't7.id', 't1.lugar_envio_id')
            ->select(
                DB::raw("
                    GROUP_CONCAT(
                        CONCAT(t1.numero_remision, '&', t1.fecha_remision, '&', t6.nombre, '&', t7.nombre) 
                        ORDER BY t1.numero_remision ASC SEPARATOR ', '
                    ) AS remisiones
                "),
                't3.nombre AS usuario_destino',
                't3.correo_electronico',
                DB::raw("(
                    SELECT s1.correo_electronico
                    FROM usuarios s1
                    JOIN users s2
                        ON s2.id = s1.user_id
                    JOIN model_has_roles s3
                        ON s3.model_id = s2.id
                    JOIN roles s4
                        ON s4.id = s3.role_id
                    JOIN clientes s5
                        ON s5.id = s1.asociado_id
                    WHERE s4.`type` = 'AC'
                    AND s5.id = t2.id
                    LIMIT 1) AS correo_admin
                ")
            )
            ->where('t1.estado_remision', 'GEN')
            ->whereRaw("
                CASE WHEN TIMEDIFF(?, CONCAT(t1.fecha_remision, ' ', t1.hora_estimada_envio)) < 0 
                THEN -HOUR(TIMEDIFF(?, CONCAT(t1.fecha_remision, ' ', t1.hora_estimada_envio)))
                ELSE HOUR(TIMEDIFF(?, CONCAT(t1.fecha_remision, ' ', t1.hora_estimada_envio)))
                END > t4.numero_horas", [
                    $now, 
                    $now, 
                    $now
                ]
            )
            ->where('t5.id', $idAlertaRecepcionRemision)
            ->where('t4.estado', 1)
            ->groupBy('t1.user_destino_id', 't2.id');
        
        return $query->get();
    }

    use HasFactory;
}
