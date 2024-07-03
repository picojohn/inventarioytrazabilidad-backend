<?php

namespace App\Models\Remisiones;

use Exception;
use Carbon\Carbon;
use App\Models\Pedidos\Sello;
use App\Models\SelloBitacora;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use App\Models\Parametrizacion\Kit;
use App\Models\Remisiones\Remision;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Parametrizacion\ProductoCliente;
use App\Models\Parametrizacion\ParametroConstante;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemisionDetalle extends Model
{
    protected $table = 'remisiones_detalles'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'numero_remision',
        'consecutivo_detalle',
        'sello_id',
        'producto_id',
        'kit_id',
        'serial',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function remision(){
        return $this->belongsTo(Remision::class, 'numero_remision', 'numero_remision');
    }

    public function sello(){
        return $this->belongsTo(Sello::class, 'sello_id');
    }

    public function producto(){
        return $this->belongsTo(ProductoCliente::class, 'producto_id');
    }

    public function kit(){
        return $this->belongsTo(Kit::class, 'kit_id');
    }

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('remisiones')
            ->join('clientes', 'clientes.id', 'remisiones.cliente_id')
            ->select(
                'remisiones.id',
                'remisiones.numero_remision',
                'remisiones.estado',
                'remisiones.fecha_remision',
                'clientes.nombre AS cliente',
                'clientes.id AS cliente_id',
            );
        $query->orderBy('remisiones.numero_remision', 'desc');
        return $query->get();
    }

    public static function toogleRemisionar($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();
        $remision = Remision::where('numero_remision', $dto['numero_remision'])->first();
        $selloActual = Sello::find($dto['sello_id']);
        $sellos = Sello::where('id', $dto['sello_id'])
            ->orWhere('producto_empaque_id', $dto['sello_id'])
            ->get();
        $eventoRemision = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_REMISION')->first()->valor_parametro??0
        );
        $eventoRecepcionRemision = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECIBIR_REMISION')->first()->valor_parametro??0
        );
        if($dto['indicativo_confirmacion_recepcion'] === 'S'){
            if($selloActual->estado_sello === 'TTO'){
                $numeroUltimaRemision = null;
                $fechaUltimaRemision = null;
                $remisionesDetalle = RemisionDetalle::where('numero_remision', $dto['numero_remision'])
                    ->where('sello_id', $dto['sello_id'])
                    ->delete();
                $remisionDetalleAnterior = RemisionDetalle::where('sello_id', $dto['sello_id'])
                    ->where('estado', 1)
                    ->whereRaw("
                        numero_remision = (
                            SELECT MAX(t1.numero_remision)
                            FROM remisiones_detalles t1
                            WHERE t1.sello_id = ?
                        )
                    ", [$dto['sello_id']])
                    ->first();
                if($remisionDetalleAnterior){
                    $remisionAnterior = Remision::where('numero_remision', $remisionDetalleAnterior->numero_remision)->first();
                    $numeroUltimaRemision = $remisionAnterior->numero_remision;
                    $fechaUltimaRemision = $remisionAnterior->fecha_remision;
                }
                foreach($sellos as $sello){
                    $bitacora = SelloBitacora::where('sello_id', $sello->id)->orderBy('id', 'desc')->first();
                    if($bitacora){
                        $bitacora->delete();
                    }
                    $selloOriginal = $sello->toJson();
                    $sello->estado_sello = 'STO';
                    $sello->numero_ultima_remision = $numeroUltimaRemision;
                    $sello->fecha_ultima_remision = $fechaUltimaRemision;
                    $sello->usuario_modificacion_id = $usuario->id;
                    $sello->usuario_modificacion_nombre = $usuario->nombre;
                    $sello->save();

                    $auditoriaDto = [
                        'id_recurso' => $remision->id,
                        'nombre_recurso' => 'REMIS-'.$remision->numero_remision,
                        'descripcion_recurso' => $remision->numero_remision,
                        'accion' => AccionAuditoriaEnum::MODIFICAR,
                        'recurso_original' => $selloOriginal,
                        'recurso_resultante' => $sello->toJson() 
                    ];
                    AuditoriaTabla::crear($auditoriaDto);
                }
            } else {
                $consecutivo = RemisionDetalle::where('numero_remision', $dto['numero_remision'])
                    ->where('estado', 1)->max('consecutivo_detalle')??0;
                $consecutivo += 1;

                $remisionDetalle = RemisionDetalle::create([
                    'numero_remision' => $dto['numero_remision'],
                    'consecutivo_detalle' => $consecutivo,
                    'sello_id' => $dto['sello_id'],
                    'producto_id' => $dto['producto_id'],
                    'kit_id' => $dto['kit_id'] !== '' ? $dto['kit_id'] : null,
                    'serial' => $dto['serial'],
                    'estado' => 1,
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                    'usuario_modificacion_id' => $usuario->id,
                    'usuario_modificacion_nombre' => $usuario->nombre,
                ]);

                foreach($sellos as $sello){
                    $selloOriginal = $sello->toJson();
                    $sello->estado_sello = 'TTO';
                    $sello->numero_ultima_remision = $dto['numero_remision'];
                    $sello->fecha_ultima_remision = Carbon::now();
                    $sello->ultimo_tipo_evento_id = $eventoRemision->id;
                    $sello->fecha_ultimo_evento = Carbon::now();
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
                        'tipo_evento_id' => $eventoRemision->id,
                        'fecha_evento' => $remision->fecha_remision,
                        'estado_sello' => $eventoRemision->estado_sello,
                        'clase_evento' => $eventoRemision->indicativo_clase_evento,
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
                        'longitud' => $dto['longitude'],
                        'latitud' => $dto['latitude'],
                        'usuario_creacion_id' => $usuario->id,
                        'usuario_creacion_nombre' => $usuario->nombre,
                    ];
                    SelloBitacora::create($bitacoraDto);
                }
            }
        } else {
            $consecutivo = RemisionDetalle::where('numero_remision', $dto['numero_remision'])
                    ->where('estado', 1)->max('consecutivo_detalle')??0;
            $consecutivo += 1;
            $remisionDetalle = RemisionDetalle::create([
                'numero_remision' => $dto['numero_remision'],
                'consecutivo_detalle' => $consecutivo,
                'sello_id' => $dto['sello_id'],
                'producto_id' => $dto['producto_id'],
                'kit_id' => $dto['kit_id'] !== '' ? $dto['kit_id'] : null,
                'serial' => $dto['serial'],
                'estado' => 1,
                'usuario_creacion_id' => $usuario->id,
                'usuario_creacion_nombre' => $usuario->nombre,
                'usuario_modificacion_id' => $usuario->id,
                'usuario_modificacion_nombre' => $usuario->nombre,
            ]);

            foreach($sellos as $sello){
                $selloOriginal = $sello->toJson();
                $sello->estado_sello = $eventoRecepcionRemision->estado_sello;
                $sello->numero_ultima_remision = $dto['numero_remision'];
                $sello->fecha_ultima_remision = Carbon::now();
                $sello->lugar_id = $dto['lugar_destino_id'];
                $sello->user_id = $dto['user_destino_id'];
                $sello->fecha_ultima_recepcion = Carbon::now();
                $sello->ultimo_tipo_evento_id = $eventoRecepcionRemision->id;
                $sello->fecha_ultimo_evento = Carbon::now();
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
                    'tipo_evento_id' => $eventoRemision->id,
                    'fecha_evento' => $remision->fecha_remision,
                    'estado_sello' => $eventoRemision->estado_sello,
                    'clase_evento' => $eventoRemision->indicativo_clase_evento,
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
                    'longitud' => $dto['longitude'],
                    'latitud' => $dto['latitude'],
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                ];
                SelloBitacora::create($bitacoraDto);

                $bitacoraDto['tipo_evento_id'] = $eventoRecepcionRemision->id;
                $bitacoraDto['clase_evento'] = $eventoRecepcionRemision->indicativo_clase_evento;
                $bitacoraDto['fecha_evento'] = $remision->fecha_aceptacion;
                $bitacoraDto['estado_sello'] = $eventoRecepcionRemision->estado_sello;
                SelloBitacora::create($bitacoraDto);
            }
        }
        
        return true;
    }

    public static function toogleRemisionarTodos($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();
        $remision = Remision::where('numero_remision', $dto['numero_remision'])->first();
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
            ");
        
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
        $rows = $query->get();
        $eventoRemision = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_REMISION')->first()->valor_parametro??0
        );
        $eventoRecepcionRemision = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECIBIR_REMISION')->first()->valor_parametro??0
        );
        foreach($rows as $row){
            $sellos = Sello::where('id', $row->id)
                ->orWhere('producto_empaque_id', $row->id)
                ->get();
            if($dto['indicativo_confirmacion_recepcion'] === 'S'){
                if(boolval($dto['seleccionar']) === true){
                    $remisionesDetalle = RemisionDetalle::where('numero_remision', $dto['numero_remision'])
                        ->where('sello_id', $row->id)
                        ->delete();
                    $remisionDetalleAnterior = RemisionDetalle::where('sello_id', $row->id)
                        ->where('estado', 1)
                        ->whereRaw("
                            numero_remision = (
                                SELECT MAX(t1.numero_remision)
                                FROM remisiones_detalles t1
                                WHERE t1.sello_id = ?
                            )
                        ", [$row->id])
                        ->first();
                    if($remisionDetalleAnterior){
                        $remisionAnterior = Remision::where('numero_remision', $remisionDetalleAnterior->numero_remision)->first();
                        $numeroUltimaRemision = $remisionAnterior->numero_remision;
                        $fechaUltimaRemision = $remisionAnterior->fecha_remision;
                    }
                    foreach($sellos as $sello){
                        $selloOriginal = $sello->toJson();
                        if($sello->estado_sello == 'STO') continue;
                        $sello->estado_sello = 'STO';
                        $sello->numero_ultima_remision = $numeroUltimaRemision;
                        $sello->fecha_ultima_remision = $fechaUltimaRemision;
                        $sello->usuario_modificacion_id = $usuario->id;
                        $sello->usuario_modificacion_nombre = $usuario->nombre;
                        $sello->save();
    
                        $bitacora = SelloBitacora::where('sello_id', $sello->id)->orderBy('id', 'desc')->first();
                        if($bitacora){
                            $bitacora->delete();
                        }
                    }
                } else {
                    $consecutivo = RemisionDetalle::where('numero_remision', $dto['numero_remision'])
                        ->where('estado', 1)->max('consecutivo_detalle')??0;
                    $consecutivo += 1;

                    $cuenta = RemisionDetalle::where('sello_id', $row->id)
                        ->where('estado', 1)->count();

                    if( $cuenta<1 ){
                        $remisionDetalle = RemisionDetalle::create([
                            'numero_remision' => $dto['numero_remision'],
                            'consecutivo_detalle' => $consecutivo,
                            'sello_id' => $row->id,
                            'producto_id' => $row->producto_id,
                            'kit_id' => $row->kit_id !== '' ? $row->kit_id : null,
                            'serial' => $row->serial,
                            'estado' => 1,
                            'usuario_creacion_id' => $usuario->id,
                            'usuario_creacion_nombre' => $usuario->nombre,
                            'usuario_modificacion_id' => $usuario->id,
                            'usuario_modificacion_nombre' => $usuario->nombre,
                        ]);
                    }
    
                    foreach($sellos as $sello){
                        $selloOriginal = $sello->toJson();
                        if($sello->estado_sello == 'TTO') continue;
                        $sello->estado_sello = 'TTO';
                        $sello->numero_ultima_remision = $dto['numero_remision'];
                        $sello->fecha_ultima_remision = Carbon::now();
                        $sello->ultimo_tipo_evento_id = $eventoRemision->id;
                        $sello->fecha_ultimo_evento = Carbon::now();
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
                            'tipo_evento_id' => $eventoRemision->id,
                            'fecha_evento' => $remision->fecha_remision,
                            'estado_sello' => $eventoRemision->estado_sello,
                            'clase_evento' => $eventoRemision->indicativo_clase_evento,
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
                            'longitud' => $dto['longitude'],
                            'latitud' => $dto['latitude'],
                            'usuario_creacion_id' => $usuario->id,
                            'usuario_creacion_nombre' => $usuario->nombre,
                        ];
                        SelloBitacora::create($bitacoraDto);
                    }
                }
            } else {
                $consecutivo = RemisionDetalle::where('numero_remision', $dto['numero_remision'])
                        ->where('estado', 1)->max('consecutivo_detalle')??0;
                $consecutivo += 1;
                $remisionDetalle = RemisionDetalle::create([
                    'numero_remision' => $dto['numero_remision'],
                    'consecutivo_detalle' => $consecutivo,
                    'sello_id' => $row->id,
                    'producto_id' => $row->producto_id,
                    'kit_id' => $row->kit_id !== '' ? $row->kit_id : null,
                    'serial' => $row->serial,
                    'estado' => 1,
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                    'usuario_modificacion_id' => $usuario->id,
                    'usuario_modificacion_nombre' => $usuario->nombre,
                ]);
    
                foreach($sellos as $sello){
                    $selloOriginal = $sello->toJson();
                    $sello->estado_sello = 'STO';
                    $sello->numero_ultima_remision = $dto['numero_remision'];
                    $sello->fecha_ultima_remision = Carbon::now();
                    $sello->lugar_id = $dto['lugar_destino_id'];
                    $sello->user_id = $dto['user_destino_id'];
                    $sello->fecha_ultima_recepcion = Carbon::now();
                    $sello->ultimo_tipo_evento_id = $eventoRecepcionRemision->id;
                    $sello->fecha_ultimo_evento = Carbon::now();
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
                        'tipo_evento_id' => $eventoRemision->id,
                        'fecha_evento' => $remision->fecha_remision,
                        'estado_sello' => $eventoRemision->estado_sello,
                        'clase_evento' => $eventoRemision->indicativo_clase_evento,
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
                        'longitud' => $dto['longitude'],
                        'latitud' => $dto['latitude'],
                        'usuario_creacion_id' => $usuario->id,
                        'usuario_creacion_nombre' => $usuario->nombre,
                    ];
                    SelloBitacora::create($bitacoraDto);
    
                    $bitacoraDto['tipo_evento_id'] = $eventoRecepcionRemision->id;
                    $bitacoraDto['clase_evento'] = $eventoRecepcionRemision->indicativo_clase_evento;
                    $bitacoraDto['fecha_evento'] = $remision->fecha_aceptacion;
                    $bitacoraDto['estado_sello'] = $eventoRecepcionRemision->estado_sello;
                    SelloBitacora::create($bitacoraDto);
                }
            }
        }
        
        return true;
    }

    public static function eliminar($id)
    {
        $remision = RemisionDetalle::find($id);
        if($remision->estado_pedido === 'CON'){
            $deleted = Sello::where('numero_pedido', $remision->numero_pedido)->delete();
            if(!$deleted){
                throw new Exception("Ocurrió un error al intentar anular la remisión.", $remision);
            }
        }
        $remision->estado_pedido = 'ANU';
        $remision->fecha_anulacion = Carbon::now();
        $guardar = $remision->save();

        if(!$guardar){
            throw new Exception("Ocurrió un error al intentar anular la remisión.", $remision);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $remision->id,
            'nombre_recurso' => RemisionDetalle::class,
            'descripcion_recurso' => $remision->numero_remision,
            'accion' => 'Anulado',
            'recurso_original' => $remision->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return true;
    }

    public static function read($datos){
        $sellos = Sello::where('numero_ultima_remision', $datos['numero_remision'])
            ->where('estado_sello', 'TTO');
        if(isset($datos['serial_final'])){
            $sellos->where(function($filter) use($datos){
                $filter->where('serial', '>=', $datos['serial_inicial'])
                    ->orWhere('serial_interno', '>=', $datos['serial_inicial'])
                    ->orWhere('serial_qr', '>=', $datos['serial_inicial'])
                    ->orWhere('serial_datamatrix', '>=', $datos['serial_inicial'])
                    ->orWhere('serial_pdf', '>=', $datos['serial_inicial']);
            })->where(function($filter2) use($datos){
                $filter2->where('serial', '<=', $datos['serial_final'])
                    ->orWhere('serial_interno', '<=', $datos['serial_final'])
                    ->orWhere('serial_qr', '<=', $datos['serial_final'])
                    ->orWhere('serial_datamatrix', '<=', $datos['serial_final'])
                    ->orWhere('serial_pdf', '<=', $datos['serial_final']);
            });
        } else {
            $sellos->where(function($filter) use($datos){
                $filter->where('serial', $datos['serial_inicial'])
                    ->orWhere('serial_interno', $datos['serial_inicial'])
                    ->orWhere('serial_qr', $datos['serial_inicial'])
                    ->orWhere('serial_datamatrix', $datos['serial_inicial'])
                    ->orWhere('serial_pdf', $datos['serial_inicial']);
            });
        }
        if($datos['tipo'] === 'P'){
            $sellos->where('tipo_empaque_despacho', 'I')
                ->where('producto_id', $datos['producto_id']);
        } else {
            $sellos->whereNotNull('kit_id')
                ->where('kit_id', $datos['producto_id']);
        }
        $sellos = $sellos->get();
        foreach($sellos as $sello){
            if($datos['tipo'] === 'P'){
                $sello->detalle = $sello->producto;
                $sello->detalle->nombre = $sello->detalle->nombre_producto_cliente;
            } else {
                $sello->detalle = $sello->kit;
            }
        }
        return $sellos;
    }

    public static function readAll($datos){
        $sellos = Sello::where('numero_ultima_remision', $datos['numero_remision'])
            ->where('estado_sello', 'TTO');
        if($datos['tipo'] === 'P'){
            $sellos->where('tipo_empaque_despacho', 'I')
                ->where('producto_id', $datos['producto_id']);
        } else {
            $sellos->whereNotNull('kit_id')
                ->where('kit_id', $datos['producto_id']);
        }
        $sellos = $sellos->get();
        foreach($sellos as $sello){
            if($datos['tipo'] === 'P'){
                $sello->detalle = $sello->producto;
                $sello->detalle->nombre = $sello->detalle->nombre_producto_cliente;
            } else {
                $sello->detalle = $sello->kit;
            }
        }
        return $sellos;
    }

    use HasFactory;
}
