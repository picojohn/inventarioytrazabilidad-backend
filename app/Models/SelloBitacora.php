<?php

namespace App\Models;

use App\Models\Pedidos\Sello;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\DB;
use App\Models\Parametrizacion\Kit;
use Illuminate\Support\Facades\Auth;
use App\Models\Parametrizacion\Lugar;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Parametrizacion\Contenedor;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Parametrizacion\ZonaContenedor;
use App\Models\Parametrizacion\ProductoCliente;
use App\Models\Contenedores\InstalacionEvidencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelloBitacora extends Model
{
    protected $table = 'sellos_bitacora'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'sello_id',
        'producto_id',
        'cliente_id',
        'producto_empaque_id',
        'kit_id',
        'tipo_empaque_despacho',
        'tipo_evento_id',
        'fecha_evento',
        'estado_sello',
        'clase_evento',
        'numero_pedido',
        'numero_remision',
        'lugar_origen_id',
        'lugar_destino_id',
        'usuario_destino_id',
        'contenedor_id',
        'documento_referencia',
        'lugar_instalacion_id',
        'zona_instalacion_id',
        'operacion_embarque_id',
        'longitud',
        'latitud',
        'numero_instalacion_evidencia',
        'observaciones_evento',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
    ];

    public function sello(){
        return $this->belongsTo(Sello::class, 'sello_id');
    }

    public function producto(){
        return $this->belongsTo(ProductoCliente::class, 'producto_id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function productoEmpaque(){
        return $this->belongsTo(Sello::class, 'producto_empaque_id');
    }

    public function kit(){
        return $this->belongsTo(Kit::class, 'kit_id');
    }

    public function tipoEvento(){
        return $this->belongsTo(TipoEvento::class, 'tipo_evento_id');
    }

    public function lugarOrigen(){
        return $this->belongsTo(Lugar::class, 'lugar_origen_id');
    }

    public function lugarDestino(){
        return $this->belongsTo(Lugar::class, 'lugar_destino_id');
    }

    public function usuarioDestino(){
        return $this->belongsTo(Usuario::class, 'usuario_destino_id');
    }

    public function contenedor(){
        return $this->belongsTo(Contenedor::class, 'contenedor_id');
    }

    public function lugarInstalacion(){
        return $this->belongsTo(Lugar::class, 'lugar_instalacion_id');
    }

    public function zonaInstalacion(){
        return $this->belongsTo(ZonaContenedor::class, 'zona_instalacion_id');
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('sellos_bitacora AS mt')
            ->join('sellos AS t1', 't1.id', 'mt.sello_id')
            ->join('productos_clientes AS t2', 't2.id', 'mt.producto_id')
            ->join('clientes AS t3', 't3.id', 'mt.cliente_id')
            ->leftJoin('sellos AS t4', 't4.id', 'mt.producto_empaque_id')
            ->leftJoin('kits AS t5', 't5.id', 'mt.kit_id')
            ->join('tipos_eventos AS t6', 't6.id', 'mt.tipo_evento_id')
            ->leftJoin('lugares AS t7', 't7.id', 'mt.lugar_origen_id')
            ->leftJoin('lugares AS t8', 't8.id', 'mt.lugar_destino_id')
            ->leftJoin('usuarios AS t9', 't9.id', 'mt.usuario_destino_id')
            ->leftJoin('contenedores AS t10', 't10.id', 'mt.contenedor_id')
            ->leftJoin('lugares AS t11', 't11.id', 'mt.lugar_instalacion_id')
            ->leftJoin('zonas_contenedores AS t12', 't12.id', 'mt.zona_instalacion_id')
            ->select(
                'mt.id',
                't3.nombre AS cliente',
                't2.nombre_producto_cliente AS producto',
                't1.serial',
                't6.nombre AS evento',
                'mt.fecha_evento',
                't6.estado_sello',
                't1.tipo_empaque_despacho',
                't4.serial AS serial_empaque',
                'mt.numero_pedido',
                'mt.numero_remision',
                't7.nombre AS lugar_origen',
                'mt.usuario_creacion_nombre AS usuario_origen',
                't8.nombre AS lugar_destino',
                't9.nombre AS usuario_destino',
                'mt.documento_referencia',
                't11.nombre AS lugar_instalacion',
                't12.nombre AS zona_instalacion',
                't10.numero_contenedor',
                'mt.operacion_embarque_id',
                'mt.numero_instalacion_evidencia',
                'mt.observaciones_evento',
                'mt.created_at AS fecha_creacion',
                DB::raw("CONCAT('https://maps.google.com/?q=', mt.latitud, ',', mt.longitud) AS ubicacion"),
            );
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where(function($filter) use($usuario){
                $filter->where('t7.cliente_id', $usuario->asociado_id)
                    ->orWhere('t8.cliente_id', $usuario->asociado_id);
            });
        }
        if(isset($dto['cliente'])){
            $query->where(function($query1) use($dto) {
                $query1->where('t7.cliente_id', $dto['cliente'])
                    ->orWhere('t8.cliente_id', $dto['cliente']);
            });
        }
        if(isset($dto['fechaInicial'])){
            $query->where('mt.fecha_evento', '>=', $dto['fechaInicial']);
        }
        if(isset($dto['fechaFinal'])){
            $query->where('mt.fecha_evento', '<=', $dto['fechaFinal']);
        }
        if(isset($dto['evento'])){
            $query->where('mt.tipo_evento_id', $dto['evento']);
        }
        if(isset($dto['serial'])){
            $query->where('t1.serial', 'like', '%'.$dto['serial'].'%');
        }
        if(isset($dto['documentoRef'])){
            $query->where('mt.documento_referencia', 'like', '%'.$dto['documentoRef'].'%');
        }
        if(isset($dto['contenedor'])){
            $query->where('t10.numero_contenedor', 'like', '%'.$dto['contenedor'].'%');
        }
        if(isset($dto['lugar'])){
            $query->where(function($filter) use($dto){
                $filter->where('mt.lugar_origen_id', $dto['lugar'])
                    ->orWhere('mt.lugar_destino_id', $dto['lugar'])
                    ->orWhere('mt.lugar_instalacion_id', $dto['lugar']);
            });
        }
        if(isset($dto['usuario'])){
            $query->where(function($filter) use($dto){
                $filter->where('mt.usuario_creacion_id', $dto['usuario'])
                    ->orWhere('mt.usuario_destino_id', $dto['usuario']);
            });
        }
        if(isset($dto['operacionEmbarque'])){
            $query->where('mt.operacion_embarque_id', $dto['operacionEmbarque']);
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'cliente'){
                    $query->orderBy('t3.nombre', $value);
                }
                if($attribute == 'producto'){
                    $query->orderBy('t2.nombre_producto_cliente', $value);
                }
                if($attribute == 'serial'){
                    $query->orderBy('t1.serial', $value);
                }
                if($attribute == 'evento'){
                    $query->orderBy('t6.nombre', $value);
                }
                if($attribute == 'fecha_evento'){
                    $query->orderBy('mt.fecha_evento', $value);
                }
                if($attribute == 'estado_sello'){
                    $query->orderBy('t6.estado_sello', $value);
                }
                if($attribute == 'tipo_empaque_despacho'){
                    $query->orderBy('t1.tipo_empaque_despacho', $value);
                }
                if($attribute == 'serial_empaque'){
                    $query->orderBy('t4.serial', $value);
                }
                if($attribute == 'numero_pedido'){
                    $query->orderBy('mt.numero_pedido', $value);
                }
                if($attribute == 'numero_remision'){
                    $query->orderBy('mt.numero_remision', $value);
                }
                if($attribute == 'lugar_origen'){
                    $query->orderBy('t7.nombre', $value);
                }
                if($attribute == 'usuario_origen'){
                    $query->orderBy('mt.usuario_creacion_nombre', $value);
                }
                if($attribute == 'lugar_destino'){
                    $query->orderBy('t8.nombre', $value);
                }
                if($attribute == 'usuario_destino'){
                    $query->orderBy('t9.nombre', $value);
                }
                if($attribute == 'documento_referencia'){
                    $query->orderBy('mt.documento_referencia', $value);
                }
                if($attribute == 'lugar_instalacion'){
                    $query->orderBy('t11.nombre', $value);
                }
                if($attribute == 'zona_instalacion'){
                    $query->orderBy('t12.nombre', $value);
                }
                if($attribute == 'numero_contenedor'){
                    $query->orderBy('t10.numero_contenedor', $value);
                }
                if($attribute == 'operacion_embarque_id'){
                    $query->orderBy('mt.operacion_embarque_id', $value);
                }
                if($attribute == 'observaciones_evento'){
                    $query->orderBy('mt.observaciones_evento', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('mt.created_at', $value);
                }
            }
        }else{
            $query->orderBy("mt.fecha_evento", "desc");
        }

        $sellosBitacora = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($sellosBitacora ?? [] as $selloBitacora){
            array_push($datos, $selloBitacora);
        }

        $cantidadSellosBitacora = count($sellosBitacora);
        $to = isset($sellosBitacora) && $cantidadSellosBitacora > 0 ? $sellosBitacora->currentPage() * $sellosBitacora->perPage() : null;
        $to = isset($to) && isset($sellosBitacora) && $to > $sellosBitacora->total() && $cantidadSellosBitacora > 0 ? $sellosBitacora->total() : $to;
        $from = isset($to) && isset($sellosBitacora) && $cantidadSellosBitacora > 0 ?
            ( $sellosBitacora->perPage() > $to ? 1 : ($to - $cantidadSellosBitacora) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($sellosBitacora) && $cantidadSellosBitacora > 0 ? +$sellosBitacora->perPage() : 0,
            'pagina_actual' => isset($sellosBitacora) && $cantidadSellosBitacora > 0 ? $sellosBitacora->currentPage() : 1,
            'ultima_pagina' => isset($sellosBitacora) && $cantidadSellosBitacora > 0 ? $sellosBitacora->lastPage() : 0,
            'total' => isset($sellosBitacora) && $cantidadSellosBitacora > 0 ? $sellosBitacora->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $selloBitacora = SelloBitacora::find($id);
        $sello = $selloBitacora->sello;
        $producto = $selloBitacora->producto;
        $cliente = $selloBitacora->cliente;
        $productoEmpaque = $selloBitacora->productoEmpaque;
        $kit = $selloBitacora->kit;
        $tipoEvento = $selloBitacora->tipoEvento;
        $lugarOrigen = $selloBitacora->lugarOrigen;
        $lugarDestino = $selloBitacora->lugarDestino;
        $usuarioDestino = $selloBitacora->usuarioDestino;
        $contenedor = $selloBitacora->contenedor;
        $lugarInstalacion = $selloBitacora->lugarInstalacion;
        $zonaInstalacion = $selloBitacora->zonaInstalacion;

        return [
            'id' => $selloBitacora->id,
            'numero_pedido' => $selloBitacora->numero_pedido,
            'numero_remision' => $selloBitacora->numero_remision,
            'fecha_evento' => $selloBitacora->fecha_evento,
            'usuario_origen' => $selloBitacora->usuario_creacion_nombre,
            'documento_referencia' => $selloBitacora->documento_referencia,
            'operacion_embarque_id' => $selloBitacora->operacion_embarque_id,
            'numero_instalacion_evidencia' => $selloBitacora->numero_instalacion_evidencia,
            'observaciones_evento' => $selloBitacora->observaciones_evento,
            'created_at' => $selloBitacora->created_at,
            'ubicacion' => 'https://maps.google.com/?q='.$selloBitacora->latitud.','.$selloBitacora->longitud,
            'sello' => isset($sello) ? [
                'id' => $sello->id,
                'serial' => $sello->serial,
                'tipo_empaque' => $sello->tipo_empaque_despacho
            ] : null,
            'producto' => isset($producto) ? [
                'id' => $producto->id,
                'nombre' => $producto->nombre_producto_cliente
            ] : null,
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
            'productoEmpaque' => isset($productoEmpaque) ? [
                'id' => $productoEmpaque->id,
                'serial' => $productoEmpaque->serial
            ] : null,
            'kit' => isset($kit) ? [
                'id' => $kit->id,
                'nombre' => $kit->nombre
            ] : null,
            'tipoEvento' => isset($tipoEvento) ? [
                'id' => $tipoEvento->id,
                'nombre' => $tipoEvento->nombre,
                'estado' => $tipoEvento->estado_sello,
            ] : null,
            'lugarOrigen' => isset($lugarOrigen) ? [
                'id' => $lugarOrigen->id,
                'nombre' => $lugarOrigen->nombre
            ] : null,
            'lugarDestino' => isset($lugarDestino) ? [
                'id' => $lugarDestino->id,
                'nombre' => $lugarDestino->nombre
            ] : null,
            'usuarioDestino' => isset($usuarioDestino) ? [
                'id' => $usuarioDestino->id,
                'nombre' => $usuarioDestino->nombre
                ] : null,
            'contenedor' => isset($contenedor) ? [
                'id' => $contenedor->id,
                'numero_contenedor' => $contenedor->numero_contenedor
                ] : null,
            'lugarInstalacion' => isset($lugarInstalacion) ? [
                'id' => $lugarInstalacion->id,
                'nombre' => $lugarInstalacion->nombre
            ] : null,
            'zonaInstalacion' => isset($zonaInstalacion) ? [
                'id' => $zonaInstalacion->id,
                'nombre' => $zonaInstalacion->nombre
            ] : null,
            'evidencias' => isset($selloBitacora->numero_instalacion_evidencia) ? [
                'evidencias' => InstalacionEvidencia::evidencias($selloBitacora->numero_instalacion_evidencia) 
            ] : null,
        ];
    }

    public static function instalacionesPorProducto($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('sellos_bitacora AS t1')
            ->join('productos_clientes AS t2', 't2.id', 't1.producto_id')
            ->leftJoin('kits AS t3', 't3.id', 't1.kit_id')
            ->select(
                DB::raw("CASE WHEN t1.kit_id IS NULL 
                    THEN t2.nombre_producto_cliente
                    ELSE t3.nombre
                    END AS name
                "),
                DB::raw("count(1) AS value")
            )
            ->where('t1.estado_sello', 'INS')
            ->where(function($filter) {
                $filter->where('t1.tipo_empaque_despacho', 'I')
                    ->orWhere(function($filter2){
                        $filter2->where('t1.tipo_empaque_despacho', 'K')
                            ->whereNotNull('t1.kit_id');
                    });
            });
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('t1.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('t1.cliente_id', $dto['cliente']);
        }
        $query->whereBetween('t1.fecha_evento', [$dto['fechaInicial'], $dto['fechaFinal']])
            ->groupBy("name");

        return $query->get();
    }

    public static function instalacionesXLugarXProducto($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('sellos_bitacora AS t1')
            ->join('productos_clientes AS t2', 't2.id', 't1.producto_id')
            ->leftJoin('kits AS t3', 't3.id', 't1.kit_id')
            ->join('lugares AS t4', 't4.id', 't1.lugar_origen_id')
            ->where('t1.estado_sello', 'INS')
            ->where(function($filter) {
                $filter->where('t1.tipo_empaque_despacho', 'I')
                    ->orWhere(function($filter2){
                        $filter2->where('t1.tipo_empaque_despacho', 'K')
                            ->whereNotNull('t1.kit_id');
                    });
            })
            ->whereBetween('t1.fecha_evento', [$dto['fechaInicial'], $dto['fechaFinal']]);
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('t1.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('t1.cliente_id', $dto['cliente']);
        }
        $query_2 = clone $query;
    
        $result_2 = $query_2->select(
            DB::raw("CASE WHEN t1.kit_id IS NULL 
                THEN t2.nombre_producto_cliente
                ELSE t3.nombre
                END AS name
            "),
            DB::raw("count(1) AS value")
        )
        ->groupBy('name')
        ->get();

        $query->select(
            "t4.nombre AS lugar",
            DB::raw("CASE WHEN t1.kit_id IS NULL 
                THEN t2.nombre_producto_cliente
                ELSE t3.nombre
                END AS name
            "),
            DB::raw("count(1) AS value")
        )
        ->groupBy("lugar", "name");
        
        $result = DB::table($query, 'sub')
            ->select(
                'sub.lugar',
                DB::raw("GROUP_CONCAT(CONCAT(name,'~', value) separator '¡') AS data")
            )
            ->groupBy('sub.lugar')
            ->get();
        
        $rows = [];

        foreach($result as $i => $cities){
            $row=[];
            $row['name'] = $cities->lugar;
            $total=0;
            $keys=[];
            $values=[];
            $array = explode('¡', $cities->data);
            foreach($array as $array2){
                $num = explode('~', $array2);
                $keys[] = $num[0];
                $values[] = $num[1];
            }
            foreach($result_2 as $productos){
                if(in_array($productos->name, $keys)){
                    $index = array_search($productos->name, $keys);
                    $row[$keys[$index]] = intval($values[$index]);
                } else {
                    $row[$productos->name] = 0;
                }
            }
            foreach($row as $k => $v){
                if($k === 'name') continue;
                $total+=$v;
            }
            $row['Total'] = $total;
            $rows[] = $row;
        }

        return $rows;
    }

    public static function eventosPorLugar($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('sellos_bitacora AS t1')
            ->join('tipos_eventos AS t2', 't2.id', 't1.tipo_evento_id')
            ->join('lugares AS t3', 't3.id', 't1.lugar_origen_id')
            ->where(function($filter) {
                $filter->where('t1.tipo_empaque_despacho', 'I')
                    ->orWhere(function($filter2){
                        $filter2->where('t1.tipo_empaque_despacho', 'K')
                            ->whereNotNull('t1.kit_id');
                    });
            })
            ->where('t2.indicativo_clase_evento', 'C')
            ->whereBetween('t1.fecha_evento', [$dto['fechaInicial'], $dto['fechaFinal']]);
        
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('t1.cliente_id', $usuario->asociado_id)
                ->where('t3.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('t1.cliente_id', $dto['cliente'])
                ->where('t3.cliente_id', $dto['cliente']);
        }
        
        $query_2 = clone $query;
        $result_2 = $query_2->select(
            "t2.nombre AS name",
            DB::raw("count(1) AS value")
        )
        ->groupBy('name')
        ->get();

        $query->select(
            "t3.nombre AS lugar",
            "t2.nombre AS evento",
            DB::raw("count(1) AS value")
        )
        ->groupBy("lugar", "evento");
        
        $result = DB::table($query, 'sub')
            ->select(
                'sub.lugar',
                DB::raw("GROUP_CONCAT(CONCAT(evento,'~', value) separator '¡') AS data")
            )
            ->groupBy('sub.lugar')
            ->get();
        
        $rows = [];

        foreach($result as $i => $cities){
            $row=[];
            $row['name'] = $cities->lugar;
            $total=0;
            $keys=[];
            $values=[];
            $array = explode('¡', $cities->data);
            foreach($array as $array2){
                $num = explode('~', $array2);
                $keys[] = $num[0];
                $values[] = $num[1];
            }
            foreach($result_2 as $productos){
                if(in_array($productos->name, $keys)){
                    $index = array_search($productos->name, $keys);
                    $row[$keys[$index]] = intval($values[$index]);
                } else {
                    $row[$productos->name] = 0;
                }
            }
            foreach($row as $k => $v){
                if($k === 'name') continue;
                $total+=$v;
            }
            // $row['Total'] = $total;
            $rows[] = $row;
        }

        return $rows;
    }
    
    use HasFactory;
}
