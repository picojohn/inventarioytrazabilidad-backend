<?php

namespace App\Models\Pedidos;

use Exception;
use Carbon\Carbon;
use App\Models\Pedidos\Sello;
use App\Models\SelloBitacora;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Pedidos\PedidoDetalle;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Parametrizacion\KitProducto;
use App\Models\Parametrizacion\LugarUsuario;
use App\Models\Parametrizacion\ParametroConstante;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends Model
{
    protected $table = 'pedidos'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'numero_pedido',
        'cliente_id',
        'numero_pedido_s3',
        'fecha_pedido',
        'fecha_entrega_pedido',
        'orden_compra_cliente',
        'numero_lote',
        'estado_pedido',
        'fecha_confirmacion',
        'fecha_ejecucion',
        'fecha_despacho',
        'fecha_anulacion',
        'observaciones',
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
        $query = DB::table('pedidos')
            ->join('clientes', 'clientes.id', 'pedidos.cliente_id')
            ->select(
                'pedidos.id',
                'pedidos.numero_pedido',
                'pedidos.estado',
                'pedidos.fecha_pedido',
                'clientes.nombre AS cliente',
                'clientes.id AS cliente_id',
            );
        $query->orderBy('pedidos.numero_pedido', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();

        $query = DB::table('pedidos')
            ->join('clientes', 'clientes.id', 'pedidos.cliente_id')
            ->select(
                'pedidos.id',
                'pedidos.numero_pedido',
                'pedidos.numero_pedido_s3',
                'pedidos.fecha_pedido',
                'pedidos.fecha_entrega_pedido',
                'pedidos.orden_compra_cliente',
                'pedidos.numero_lote',
                'pedidos.estado_pedido',
                'pedidos.fecha_confirmacion',
                'pedidos.fecha_ejecucion',
                'pedidos.fecha_despacho',
                'pedidos.fecha_anulacion',
                'pedidos.observaciones',
                'pedidos.estado',
                'clientes.nombre AS cliente',
                'clientes.id AS cliente_id',
                'pedidos.usuario_creacion_id',
                'pedidos.usuario_creacion_nombre',
                'pedidos.usuario_modificacion_id',
                'pedidos.usuario_modificacion_nombre',
                'pedidos.created_at AS fecha_creacion',
                'pedidos.updated_at AS fecha_modificacion',
            );

        if($rol->type !== 'IN'){
            $query->where('clientes.id', $usuario->asociado_id);
        }

        if(isset($dto['lectura'])){
            $query->whereIn('pedidos.estado_pedido', ['CON', 'EJE']);
        }

        if(isset($dto['numero_pedido_lectura'])){
            $query->where('pedidos.numero_pedido', '>=' ,$dto['numero_pedido_lectura']);
        }

        if(isset($dto['numero_pedido'])){
            $query->where('pedidos.numero_pedido', $dto['numero_pedido']);
        }

        if(isset($dto['cliente'])){
            $query->where('clientes.nombre', 'like', '%'.$dto['cliente'].'%');
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'numero_pedido'){
                    $query->orderBy('pedidos.numero_pedido', $value);
                }
                if($attribute == 'numero_pedido_s3'){
                    $query->orderBy('pedidos.numero_pedido_s3', $value);
                }
                if($attribute == 'fecha_pedido'){
                    $query->orderBy('pedidos.fecha_pedido', $value);
                }
                if($attribute == 'fecha_entrega_pedido'){
                    $query->orderBy('pedidos.fecha_entrega_pedido', $value);
                }
                if($attribute == 'orden_compra_cliente'){
                    $query->orderBy('pedidos.orden_compra_cliente', $value);
                }
                if($attribute == 'numero_lote'){
                    $query->orderBy('pedidos.numero_lote', $value);
                }
                if($attribute == 'estado_pedido'){
                    $query->orderBy('pedidos.estado_pedido', $value);
                }
                if($attribute == 'fecha_confirmacion'){
                    $query->orderBy('pedidos.fecha_confirmacion', $value);
                }
                if($attribute == 'fecha_ejecucion'){
                    $query->orderBy('pedidos.fecha_ejecucion', $value);
                }
                if($attribute == 'fecha_despacho'){
                    $query->orderBy('pedidos.fecha_despacho', $value);
                }
                if($attribute == 'fecha_anulacion'){
                    $query->orderBy('pedidos.fecha_anulacion', $value);
                }
                if($attribute == 'observaciones'){
                    $query->orderBy('pedidos.observaciones', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('pedidos.estado', $value);
                }
                if($attribute == 'cliente'){
                    $query->orderBy('clientes.nombre', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('pedidos.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('pedidos.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('pedidos.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('pedidos.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("pedidos.updated_at", "desc");
        }

        $pedidos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($pedidos ?? [] as $pedido){
            array_push($datos, $pedido);
        }

        $cantidadPedidos = count($pedidos);
        $to = isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->currentPage() * $pedidos->perPage() : null;
        $to = isset($to) && isset($pedidos) && $to > $pedidos->total() && $cantidadPedidos > 0 ? $pedidos->total() : $to;
        $from = isset($to) && isset($pedidos) && $cantidadPedidos > 0 ?
            ( $pedidos->perPage() > $to ? 1 : ($to - $cantidadPedidos) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($pedidos) && $cantidadPedidos > 0 ? +$pedidos->perPage() : 0,
            'pagina_actual' => isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->currentPage() : 1,
            'ultima_pagina' => isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->lastPage() : 0,
            'total' => isset($pedidos) && $cantidadPedidos > 0 ? $pedidos->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $pedido = Pedido::find($id);
        $cliente = $pedido->cliente;
        return [
            'id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'numero_pedido_s3' => $pedido->numero_pedido_s3,
            'fecha_pedido' => $pedido->fecha_pedido,
            'fecha_entrega_pedido' => $pedido->fecha_entrega_pedido,
            'orden_compra_cliente' => $pedido->orden_compra_cliente,
            'numero_lote' => $pedido->numero_lote,
            'estado_pedido' => $pedido->estado_pedido,
            'fecha_confirmacion' => $pedido->fecha_confirmacion,
            'fecha_ejecucion' => $pedido->fecha_ejecucion,
            'fecha_despacho' => $pedido->fecha_despacho,
            'fecha_anulacion' => $pedido->fecha_anulacion,
            'observaciones' => $pedido->observaciones,
            'estado' => $pedido->estado,
            'usuario_creacion_id' => $pedido->usuario_creacion_id,
            'usuario_creacion_nombre' => $pedido->usuario_creacion_nombre,
            'usuario_modificacion_id' => $pedido->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $pedido->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($pedido->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($pedido->updated_at))->format("Y-m-d H:i:s"),
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
        $pedido = isset($dto['id']) ? Pedido::find($dto['id']) : new Pedido();

        // Guardar objeto original para auditoria
        $pedidoOriginal = $pedido->toJson();
        
        $parametros = ParametroConstante::cargarParametros();
        $numeroPedido = ParametroConstante::find(
            ParametroConstante::where('CODIGO_PARAMETRO', 'CONSECUTIVO_PEDIDO')->first()->id
        );
        $numeroLote = ParametroConstante::find(
            ParametroConstante::where('CODIGO_PARAMETRO', 'CONSECUTIVO_LOTE')->first()->id
        );

        if(!isset($dto['id'])){
            $dto['numero_pedido'] = $numeroPedido->valor_parametro;
            $dto['numero_lote'] = $numeroLote->valor_parametro;
            $numeroPedido->valor_parametro = strval(intval($numeroPedido->valor_parametro)+1);
            $numeroLote->valor_parametro = strval(intval($numeroLote->valor_parametro)+1);
            $numeroPedido->save();
            $numeroLote->save();
        }

        $pedido->fill($dto);
        $guardado = $pedido->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el pedido.", $pedido);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $pedido->id,
            'nombre_recurso' => Pedido::class,
            'descripcion_recurso' => $pedido->numero_pedido,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $pedidoOriginal : $pedido->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $pedido->toJson() : null
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return Pedido::cargar($pedido->id);
    }

    public static function eliminar($id)
    {
        $pedido = Pedido::find($id);
        if($pedido->estado_pedido === 'CON'){
            $deleted = Sello::where('numero_pedido', $pedido->numero_pedido)->delete();
            if(!$deleted){
                throw new Exception("Ocurrió un error al intentar anular el pedido.", $pedido);
            }
        }
        $pedidoDetalle = PedidoDetalle::where('pedido_id', $pedido->id)
            ->update(['estado' => 0]);
        $pedido->estado_pedido = 'ANU';
        $pedido->fecha_anulacion = Carbon::now();
        $guardar = $pedido->save();

        if(!$guardar){
            throw new Exception("Ocurrió un error al intentar anular el pedido.", $pedido);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $pedido->id,
            'nombre_recurso' => Pedido::class,
            'descripcion_recurso' => $pedido->numero_pedido,
            'accion' => 'Anulado',
            'recurso_original' => $pedido->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return true;
    }

    public static function confirmar($id, $datos){
        $user = Auth::user();
        $usuario = $user->usuario();
        $lugarUsuario = LugarUsuario::where('usuario_id', $usuario->id)->first();

        $pedido = Pedido::find($id);
        $pedidoOriginal = $pedido->toJson();
        $pedido->fecha_confirmacion = Carbon::now();
        $pedido->estado_pedido = 'CON';
        $guardado = $pedido->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar confirmar el pedido.", $pedido);
        }
        $eventoConfirmarPedido = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_CONFIRMAR_PEDIDO')->first()->valor_parametro??0
        );
        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $pedido->id,
            'nombre_recurso' => Pedido::class,
            'descripcion_recurso' => $pedido->numero_pedido,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $pedidoOriginal,
            'recurso_resultante' => $pedido->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        $pedidoDetalle = PedidoDetalle::where('pedido_id', $id)->whereNotNull('producto_id')->get();
        $data = [
            'user_id' => $usuario->id,
            'lugar_id' => $lugarUsuario->lugar_id,
            'usuario_creacion_id' => $usuario->id,
            'usuario_creacion_nombre' => $usuario->nombre,
            'usuario_modificacion_id' => $usuario->id,
            'usuario_modificacion_nombre' => $usuario->nombre,
        ];
        foreach($pedidoDetalle as $sello){
            $cantidad = $sello->cantidad;
            $consecutivo_inicial = $sello->consecutivo_serie_inicial;
            $longitud = $sello->longitud_serial;
            $prefijo = $sello->prefijo;
            $posfijo = $sello->posfijo;
            $producto_empaque_id = null;
            $sInterno = $sello->producto->valor_serial_interno;
            $oInterno = $sello->producto->operador_serial_interno;
            $sQR = $sello->producto->valor_serial_qr;
            $oQR = $sello->producto->operador_serial_qr;
            $sDtmx = $sello->producto->valor_serial_datamatrix;
            $oDtmx = $sello->producto->operador_serial_datamatrix;
            $sPDF = $sello->producto->valor_serial_pdf;
            $oPDF = $sello->producto->operador_serial_pdf;
            if($sello->kit_id){
                $producto_empaque_id = KitProducto::getEmpaque($sello->kit_id);
            }
            $data['tipo_inventario'] = $sello->producto->indicativo_producto_externo === 'N'?'I':'E';
            $data['producto_id'] = $sello->producto->id;
            $data['producto_s3_id'] = $sello->producto->producto_s3_id;
            $data['color_id'] = $sello->color_id;
            $data['cliente_id'] = $sello->pedido->cliente->id;
            $data['kit_id'] = $producto_empaque_id==$sello->producto->id?$sello->kit_id:null;
            $data['tipo_empaque_despacho'] = $sello->kit_id?'K':'I';
            $data['numero_pedido'] = $sello->pedido->numero_pedido;
            $data['ultimo_tipo_evento_id'] = $eventoConfirmarPedido->id;
            $data['fecha_ultimo_evento'] = $pedido->fecha_confirmacion;
            for($i = $consecutivo_inicial; $i < ($consecutivo_inicial+$cantidad); $i++){
                $numero_interno = $i;
                switch($oInterno){
                    case '+':
                        $numero_interno = intval($i+$sInterno);
                        break;
                    case '-':
                        $numero_interno = intval($i-$sInterno);
                        break;
                    case '*':
                        $numero_interno = intval($i*$sInterno);
                        break;
                    default:
                        break;
                }
                $numero_qr = $i;
                switch($oQR){
                    case '+':
                        $numero_qr = intval($i+$sQR);
                        break;
                    case '-':
                        $numero_qr = intval($i-$sQR);
                        break;
                    case '*':
                        $numero_qr = intval($i*$sQR);
                        break;
                    default:
                        break;
                }
                $numero_dtmx = $i;
                switch($oDtmx){
                    case '+':
                        $numero_dtmx = intval($i+$sDtmx);
                        break;
                    case '-':
                        $numero_dtmx = intval($i-$sDtmx);
                        break;
                    case '*':
                        $numero_dtmx = intval($i*$sDtmx);
                        break;
                    default:
                        break;
                }
                $numero_pdf = $i;
                switch($oPDF){
                    case '+':
                        $numero_pdf = intval($i+$sPDF);
                        break;
                    case '-':
                        $numero_pdf = intval($i-$sPDF);
                        break;
                    case '*':
                        $numero_pdf = intval($i*$sPDF);
                        break;
                    default:
                        break;
                }
                $numero_interno = str_pad($numero_interno, $longitud, '0', STR_PAD_LEFT);
                $numero_qr = str_pad($numero_qr, $longitud, '0', STR_PAD_LEFT);
                $numero_dtmx = str_pad($numero_dtmx, $longitud, '0', STR_PAD_LEFT);
                $numero_pdf = str_pad($numero_pdf, $longitud, '0', STR_PAD_LEFT);
                $numero_serial = str_pad($i, $longitud, '0', STR_PAD_LEFT);
                $data['serial'] = $prefijo.$numero_serial.$posfijo;
                $data['serial_interno'] = $prefijo.$numero_interno.$posfijo;
                $data['serial_qr'] = $prefijo.$numero_qr.$posfijo;
                $data['serial_datamatrix'] = $prefijo.$numero_dtmx.$posfijo;
                $data['serial_pdf'] = $prefijo.$numero_pdf.$posfijo;
                $newSello = new Sello();
                $newSello->fill($data);
                $guardado = $newSello->save();
                $bitacoraDto = [
                    'sello_id' => $newSello->id,
                    'producto_id' => $newSello->producto_id,
                    'cliente_id' => $newSello->cliente_id,
                    'producto_empaque_id' => $newSello->producto_empaque_id,
                    'kit_id' => $newSello->kit_id,
                    'tipo_empaque_despacho' => $newSello->tipo_empaque_despacho,
                    'tipo_evento_id' => $eventoConfirmarPedido->id,
                    'fecha_evento' => $pedido->fecha_confirmacion,
                    'estado_sello' => $eventoConfirmarPedido->estado_sello,
                    'clase_evento' => $eventoConfirmarPedido->indicativo_clase_evento,
                    'numero_pedido' => $pedido->numero_pedido,
                    'numero_remision' => null,
                    'lugar_origen_id' => null,
                    'lugar_destino_id' => null,
                    'usuario_destino_id' => null,
                    'contenedor_id' => $newSello->contenedor_id,
                    'documento_referencia' => $newSello->documento_referencia,
                    'lugar_instalacion_id' => $newSello->lugar_instalacion_id,
                    'zona_instalacion_id' => $newSello->zona_instalacion_id,
                    'operacion_embarque_id' => $newSello->operacion_embarque_id,
                    'longitud' => $datos['longitude'],
                    'latitud' => $datos['latitude'],
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                ];
                SelloBitacora::create($bitacoraDto);
                if(!$guardado){
                    throw new Exception("Ocurrió un error al intentar confirmar el pedido.", $newSello);
                }
            }
        }
        return true;
    }

    use HasFactory;
}
