<?php

namespace App\Models\Pedidos;

use Exception;
use Carbon\Carbon;
use App\Models\SelloBitacora;
use App\Models\Pedidos\Pedido;
use App\Enum\AccionAuditoriaEnum;
use App\Imports\InventarioImport;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\DB;
use App\Models\Parametrizacion\Kit;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Parametrizacion\LugarUsuario;
use App\Models\Parametrizacion\ProductoCliente;
use App\Models\Contenedores\InstalacionEvidencia;
use App\Models\Parametrizacion\ParametroConstante;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sello extends Model
{
    protected $table = 'sellos';

    protected $fillable = [ // nombres de los campos
        'serial',
        'serial_interno',
        'serial_qr',
        'serial_datamatrix',
        'serial_pdf',
        'serial_rfid',
        'serial_empacado',
        'producto_id',
        'producto_s3_id',
        'color_id',
        'cliente_id',
        'producto_empaque_id',
        'kit_id',
        'tipo_empaque_despacho',
        'estado_sello',
        'numero_pedido',
        'numero_ultima_remision',
        'fecha_ultima_remision',
        'hora_estimada_despacho',
        'user_id',
        'lugar_id',
        'fecha_ultima_recepcion',
        'contenedor_id',
        'documento_referencia',
        'lugar_instalacion_id',
        'zona_instalacion_id',
        'fecha_instalacion',
        'operacion_embarque_id',
        'numero_instalacion_evidencia',
        'indicativo_previaje',
        'fecha_instalacion_previaje',
        'fecha_ultima_verificacion_previaje',
        'ultimo_tipo_evento_id',
        'fecha_ultimo_evento',
        'tipo_inventario',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function producto(){
        return $this->belongsTo(ProductoCliente::class, 'producto_id');
    }

    public function kit(){
        return $this->belongsTo(Kit::class, 'kit_id');
    }

    public static function obtenerColeccionLigera($dto){
        $query = Sello::select(
                'id',
                'serial',
                'serial_interno',
                'serial_qr',
                'serial_datamatrix',
                'serial_pdf',
                'estado',
            )->orderBy('serial', 'asc');
        return $query->get();
    }

    public static function obtenerColeccionLeidos($dto){
        $query1 = DB::table('sellos AS mt')
            ->join('productos_clientes AS st2', 'st2.id', 'mt.producto_id')
            ->select(
                'mt.id',
                'mt.serial',
                'st2.producto_s3_id',
                DB::raw("(
                    SELECT GROUP_CONCAT(st.serial SEPARATOR ', ')
                    FROM sellos st
                    WHERE st.producto_empaque_id = mt.id
                    GROUP BY st.producto_empaque_id)
                    AS detalle"
                ),
                'mt.updated_at'
            )
            ->where('mt.numero_pedido', $dto['numero_pedido'])
            ->where('mt.estado_sello', 'LEC')
            ->whereNotNull('mt.kit_id')
            ->groupBy('mt.serial')
            ->groupBy('st2.producto_s3_id')
            ->groupBy('mt.id')
            ->groupBy('mt.updated_at');

        $query2 = DB::table('sellos AS mt2')
            ->join('productos_clientes AS p', 'p.id', 'mt2.producto_id')
            ->select(
                'mt2.id',
                'mt2.serial',
                'p.producto_s3_id',
                DB::raw("'' AS detalle"),
                'mt2.updated_at'
            )
            ->where('mt2.numero_pedido', $dto['numero_pedido'])
            ->where('mt2.estado_sello', 'LEC')
            ->where('mt2.tipo_empaque_despacho', 'I')
            ->union($query1);

        $mainQuery = DB::table($query2, 'sub')
            ->select(
                'sub.id',
                'sub.serial',
                'sub.producto_s3_id',
                'sub.detalle',
                'sub.updated_at',
            );

        if(isset($dto['serial'])){
            $mainQuery->where(function ($filtro) use($dto){
                $filtro->orWhere('sub.serial', 'like','%'.$dto['serial'].'%')
                    ->orWhere('sub.detalle', 'like','%'.$dto['serial'].'%');
            });
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'serial'){
                    $mainQuery->orderBy('sub.serial', $value);
                }
                if($attribute == 'producto_s3_id'){
                    $mainQuery->orderBy('sub.producto_s3_id', $value);
                }
                if($attribute == 'detalle'){
                    $mainQuery->orderBy('sub.detalle', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $mainQuery->orderBy('sub.updated_at', $value);
                }
            }
        }else{
            $mainQuery->orderBy("sub.updated_at", "desc");
        }

        $pedidos = $mainQuery->paginate($dto['limite'] ?? 100);
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

    public static function ordenLecturaKit($data){
        $query = DB::table('kits_productos AS t1')
            ->join('productos_clientes AS t2', 't2.id', 't1.producto_id')
            ->select(
                't1.kit_id',
                't1.producto_id',
                't2.producto_s3_id',
                't1.cantidad',
                DB::raw('0 AS leidos')
            )
            ->where('t1.kit_id', $data['kit_id'])
            ->orderBy('t2.indicativo_producto_empaque', 'desc');
        return $query->get();
    }

    public static function leerSello($data){
        $pedido = Pedido::where("numero_pedido", $data['numero_pedido'])->first();
        $asignacionLectura = "N";
        if($pedido){
            $cliente = Cliente::find($pedido->cliente_id);
            if($cliente->asignacion_sellos_lectura == "S"){
                $asignacionLectura = "S";
            }
        }
        if($asignacionLectura != "S"){
            $consulta = Sello::where('numero_pedido', $data['numero_pedido'])
            ->where('producto_s3_id', $data['producto_s3_id'])
            ->where('tipo_empaque_despacho', 'I')
            ->whereIn('estado_sello', ['GEN', 'DEV'])
            ->where(function($query) use($data){
                $query->where('serial', $data['serie'])
                    ->orWhere('serial_interno', $data['serie'])
                    ->orWhere('serial_qr', $data['serie'])
                    ->orWhere('serial_datamatrix', $data['serie'])
                    ->orWhere('serial_pdf', $data['serie']);
            })->first();
        } else {
            $consulta = Sello::where('numero_pedido', $data['numero_pedido'])
            ->where('producto_s3_id', $data['producto_s3_id'])
            ->where('tipo_empaque_despacho', 'I')
            ->whereIn('estado_sello', ['GEN', 'DEV'])
            ->first();
            
        }
        $sello = Sello::find($consulta->id);
        $selloOriginal = $sello->toJson();

        $sello->estado_sello = 'LEC';
        $sello->serial_empacado = $data['serie'];
        if($asignacionLectura == "S"){
            $sello->serial = $data['serie'];
            $sello->serial_interno = $data['serie'];
            $sello->serial_qr = $data['serie'];
            $sello->serial_datamatrix = $data['serie'];
            $sello->serial_pdf = $data['serie'];
        }
        $pedido = Pedido::where('numero_pedido', $data['numero_pedido'])->first();
        if($pedido->estado_pedido === 'CON'){
            $pedido->estado_pedido = 'EJE';
            $pedido->save();
        }
        $guardado = $sello->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar leer el sello.", $sello);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $sello->id,
            'nombre_recurso' => Sello::class,
            'descripcion_recurso' => $sello->numero_pedido,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $selloOriginal,
            'recurso_resultante' => $sello->toJson()
        ];

        AuditoriaTabla::crear($auditoriaDto);

        return $sello;
    }

    public static function importar($archivo,$usuario){

        $usuario = Usuario::find($usuario);
        // // Borrar datos historicos
        // // TODO validar si se borra para todas las empresas
        // OrdenServicioCargaError::query()->delete();

        $errores = [];

        $import = new InventarioImport($usuario->id, $usuario->nombre,$usuario->asociado_id );

        Excel::import($import, $archivo);

        // if($import->getWithErrors()){
        //     throw new Exception("Revisar archivo de carga. Estructura de información no corresponde.");
        // }

        foreach ($import->failures() as $failure) {
            array_push($errores, [
                "fila" => $failure->row(),
                "columna" => $failure->attribute(),
                "errores" => $failure->errors(),
                "datos" => $failure->values()
            ]);
        }

        // Procesar errores personalizados
        $erroresPersonalizados = $import->getCustomErrors();

        // return $erroresPersonalizados;
        if(count($erroresPersonalizados) > 0){
            $erroresReporte=[];
            foreach ($erroresPersonalizados ?? [] as $registro){
                array_push($erroresReporte, [
                    'producto_id' => $registro['datosFila'][0],
                    'serial' => $registro['datosFila'][1],
                    'nombre_producto' => $registro['datosFila'][2]??'',
                    'observaciones' => join("<br>", $registro['observaciones'])
                ]);
            }
        }

        // Cantidad de registros fallidos, OJO contabilizar antes de agregar los procesos cargados
        $registrosFallidos = count($erroresReporte ?? []);

        // Procesar registros importados
        $procesosImportados = $import->getImported();
        $registrosCargados = count($procesosImportados ?? []);

        return [
            "errores" => $erroresReporte,
            "registros_fallidos" => $registrosFallidos - $registrosCargados,
            "registros_cargados" => $registrosCargados,
            "registros_procesados" => $registrosFallidos
        ];
    }

    public static function consultar($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        // DB::statement('SET GLOBAL group_concat_max_len = 1000000');
        $query = DB::table('sellos')
            ->leftJoin('lugares', 'lugares.id', 'sellos.lugar_id')
            ->leftJoin('usuarios', 'usuarios.id', 'sellos.user_id')
            ->leftJoin('productos_clientes', 'productos_clientes.id', 'sellos.producto_id')
            ->leftJoin('kits', 'kits.id', 'sellos.kit_id')
            ->leftJoin('inventario_minimo AS im1', function ($join) {
                $join->on('im1.producto_cliente_id', '=', 'productos_clientes.id')
                    ->on('im1.lugar_id', '=', 'lugares.id');
            })
            ->leftJoin('inventario_minimo AS im2', function ($join) {
                $join->on('im2.kit_id', '=', 'kits.id')
                    ->on('im2.lugar_id', '=', 'lugares.id');
            })
            ->select(
                'lugares.id as lugar_id',
                'lugares.nombre as lugar',
                'usuarios.id as usuario_id',
                'usuarios.nombre as usuario',
                'productos_clientes.nombre_producto_cliente as producto_id',
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        productos_clientes.nombre_producto_cliente
                        ,kits.nombre
                    ) AS nombre"
                ),
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        GROUP_CONCAT(DISTINCT im1.cantidad_inventario_minimo SEPARATOR ', '),
                        GROUP_CONCAT(DISTINCT im2.cantidad_inventario_minimo SEPARATOR ', ')
                    ) AS stock_minimo"
                ),
                DB::Raw(
                    "count(*) AS cantidad"
                ),
                DB::Raw("GROUP_CONCAT(DISTINCT sellos.serial ORDER BY sellos.serial ASC SEPARATOR ',')
                    AS seriales"
                ),
            )
            ->whereIn('sellos.estado_sello', ['STO','TTO', 'DEV'])
            ->where(function($query1)  {
                $query1->where('sellos.tipo_empaque_despacho', '=', 'I')
                    ->orWhere(function($query2)  {
                        $query2->where('sellos.tipo_empaque_despacho', '=', 'K')
                            ->whereNotNull('sellos.kit_id');
                        });
                })
            ->groupBy('lugares.id')
            ->groupBy('usuarios.id')
            ->groupBy('productos_clientes.id')
            ->groupBy('kits.id');
        
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('lugares.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('lugares.cliente_id', $dto['cliente']);
        }
        if(isset($dto['lugar'])){
            $query->where('lugares.id', '=',$dto['lugar'] );
        }
        if(isset($dto['usuario'])){
            $query->where('usuarios.id', '=',$dto['usuario'] );
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'lugar'){
                    $query->orderBy('lugar', $value);
                }
                if($attribute == 'usuario'){
                    $query->orderBy('usuario', $value);
                }
                if($attribute == 'nombre'){
                    $query->orderBy('nombre', $value);
                }
                if($attribute == 'stock_minimo'){
                    $query->orderBy('stock_minimo', $value);
                }
                if($attribute == 'cantidad'){
                    $query->orderBy('cantidad', $value);
                }
            }
        }else{
            $query->orderBy("lugares.id", "desc");
        }

        $sellos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($sellos ?? [] as $pedido){
            array_push($datos, $pedido);
        }

        $cantidadSellos = count($sellos);
        $to = isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() * $sellos->perPage() : null;
        $to = isset($to) && isset($sellos) && $to > $sellos->total() && $cantidadSellos > 0 ? $sellos->total() : $to;
        $from = isset($to) && isset($sellos) && $cantidadSellos > 0 ?
            ( $sellos->perPage() > $to ? 1 : ($to - $cantidadSellos) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($sellos) && $cantidadSellos > 0 ? +$sellos->perPage() : 0,
            'pagina_actual' => isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() : 1,
            'ultima_pagina' => isset($sellos) && $cantidadSellos > 0 ? $sellos->lastPage() : 0,
            'total' => isset($sellos) && $cantidadSellos > 0 ? $sellos->total() : 0
        ];
    }

    public static function leerSellodeKit($data){
        $pedido = Pedido::where("numero_pedido", $data['numero_pedido'])->first();
        $asignacionLectura = "N";
        if($pedido){
            $cliente = Cliente::find($pedido->cliente_id);
            if($cliente->asignacion_sellos_lectura == "S"){
                $asignacionLectura = "S";
            }
        }
        if($asignacionLectura == "S"){
            return [
                'id' => $data['serie'],
                'producto_s3_id' => $data['producto_s3_id'],
                'numero_pedido' => $data['numero_pedido'],
                'serial_empacado' => $data['serie']
            ];
        }
        $sello = Sello::where('numero_pedido', $data['numero_pedido'])
        ->where('producto_s3_id', $data['producto_s3_id'])
        ->where('tipo_empaque_despacho', 'K')
        ->whereIn('estado_sello', ['GEN', 'DEV'])
        ->where(function($query) use($data){
            $query->where('serial', $data['serie'])
                ->orWhere('serial_interno', $data['serie'])
                ->orWhere('serial_qr', $data['serie'])
                ->orWhere('serial_datamatrix', $data['serie'])
                ->orWhere('serial_pdf', $data['serie']);
        })
        ->first();

        return [
            'id' => $sello->id,
            'producto_s3_id' => $sello->producto_s3_id,
            'numero_pedido' => $data['numero_pedido'],
            'serial_empacado' => $data['serie']
        ];
    }

    public static function guardarLecturaKit($data){
        $datos = $data['data'];
        $pedido = Pedido::where("numero_pedido", $datos[0]['numero_pedido'])->first();
        $asignacionLectura = "N";
        if($pedido){
            $cliente = Cliente::find($pedido->cliente_id);
            if($cliente->asignacion_sellos_lectura == "S"){
                $asignacionLectura = "S";
            }
        }
        if($asignacionLectura == "N"){
            $selloBolsaId = $datos[0]['id'];
            $selloBolsa = Sello::find($selloBolsaId);
            $selloOriginal = $selloBolsa->toJson();
            foreach($datos as $row){
                $sello = Sello::find($row['id']);
                $sello->estado_sello = 'LEC';
                $sello->serial_empacado = $row['serial_empacado'];
                if($selloBolsaId != $row['id']){
                    $sello->producto_empaque_id = $selloBolsaId;
                }
                $guardado = $sello->save();
                if(!$guardado){
                    throw new Exception("Ocurrió un error al intentar leer el sello.", $sello);
                }
            }
        } else {
            $selloBolsa = Sello::where('numero_pedido', $datos[0]['numero_pedido'])
                ->where('producto_s3_id', $datos[0]['producto_s3_id'])
                ->where('tipo_empaque_despacho', 'K')
                ->whereIn('estado_sello', ['GEN', 'DEV'])
                ->whereNotNull("kit_id")
                ->first();
            if(!$selloBolsa){
                return false;
            }
            $selloOriginal = $selloBolsa->toJson();
            $selloBolsa->estado_sello = "LEC";
            $selloBolsa->serial_empacado = $datos[0]["serial_empacado"];
            $selloBolsa->serial = $datos[0]["serial_empacado"];
            $selloBolsa->serial_interno = $datos[0]["serial_empacado"];
            $selloBolsa->serial_qr = $datos[0]["serial_empacado"];
            $selloBolsa->serial_datamatrix = $datos[0]["serial_empacado"];
            $selloBolsa->serial_pdf = $datos[0]["serial_empacado"];
            $guardado = $selloBolsa->save();
            if(!$guardado){
                throw new Exception("Ocurrió un error al intentar leer el sello.", $selloBolsa);
            }
            foreach($datos as $row){
                if($row["id"] == $selloBolsa->serial) continue;
                $sello = Sello::where('numero_pedido', $row['numero_pedido'])
                    ->where('producto_s3_id', $row['producto_s3_id'])
                    ->where('tipo_empaque_despacho', 'K')
                    ->whereIn('estado_sello', ['GEN', 'DEV'])
                    ->whereNull("kit_id")
                    ->first();
                if(!$sello){
                    return false;
                }
                $sello->estado_sello = 'LEC';
                $sello->serial_empacado = $row['serial_empacado'];
                $sello->serial = $row['serial_empacado'];
                $sello->serial_interno = $row['serial_empacado'];
                $sello->serial_qr = $row['serial_empacado'];
                $sello->serial_datamatrix = $row['serial_empacado'];
                $sello->serial_pdf = $row['serial_empacado'];
                $sello->producto_empaque_id = $selloBolsa->id;
                $guardado = $sello->save();
                if(!$guardado){
                    throw new Exception("Ocurrió un error al intentar leer el sello.", $sello);
                }
            }
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $selloBolsa->id,
            'nombre_recurso' => Sello::class,
            'descripcion_recurso' => $selloBolsa->numero_pedido,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $selloOriginal,
            'recurso_resultante' => $selloBolsa->toJson()
        ];
        
        AuditoriaTabla::crear($auditoriaDto);

        return $selloBolsa;
    }

    public static function eliminar($id){
        $sello = Sello::find($id);
        $selloOriginal = $sello->toJson();
        $unRead = Sello::where(function($query) use($id){
                $query->where('id', $id)
                    ->orWhere('producto_empaque_id', $id);
            })
            ->update([
                'serial_empacado' => null,
                'producto_empaque_id' => null,
                'estado_sello' => 'GEN'
            ]);
        if(!$unRead){
            throw new Exception("Ocurrió un error al intentar desleer el sello/kit.", $sello);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $sello->id,
            'nombre_recurso' => Sello::class,
            'descripcion_recurso' => $sello->numero_pedido,
            'accion' => AccionAuditoriaEnum::MODIFICAR,
            'recurso_original' => $selloOriginal,
            'recurso_resultante' => $sello->toJson()
        ];

        AuditoriaTabla::crear($auditoriaDto);

        return $sello;
    }

    public static function consultarStockMinimo($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        // DB::statement('SET GLOBAL group_concat_max_len = 1000000');
        $query = DB::table('sellos')
            ->leftJoin('lugares', 'lugares.id', 'sellos.lugar_id')
            ->leftJoin('usuarios', 'usuarios.id', 'sellos.user_id')
            ->leftJoin('productos_clientes', 'productos_clientes.id', 'sellos.producto_id')
            ->leftJoin('kits', 'kits.id', 'sellos.kit_id')
            ->leftJoin('inventario_minimo AS im1', function ($join) {
                $join->on('im1.producto_cliente_id', '=', 'productos_clientes.id')
                    ->on('im1.lugar_id', '=', 'lugares.id');
            })
            ->leftJoin('inventario_minimo AS im2', function ($join) {
                $join->on('im2.kit_id', '=', 'kits.id')
                    ->on('im2.lugar_id', '=', 'lugares.id');
            })
            ->whereIn('sellos.estado_sello', ['STO','TTO', 'DEV'])
            ->where(function($query1)  {
                $query1->where('sellos.tipo_empaque_despacho', '=', 'I')
                    ->orWhere(function($query2)  {
                        $query2->where('sellos.tipo_empaque_despacho', '=', 'K')
                            ->whereNotNull('sellos.kit_id');
                        });
            })
            ->select(
                'lugares.id as lugar_id',
                'lugares.nombre as lugar',
                'kits.id as kit_id',
                'productos_clientes.id as producto_id',
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        productos_clientes.nombre_producto_cliente
                        ,kits.nombre
                    ) AS nombre"
                ),
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        GROUP_CONCAT(DISTINCT im1.cantidad_inventario_minimo SEPARATOR ', '),
                        GROUP_CONCAT(DISTINCT im2.cantidad_inventario_minimo SEPARATOR ', ')
                    ) AS stock_minimo"
                ),
                DB::Raw(
                    "count(*) AS cantidad"
                ),
                DB::Raw("GROUP_CONCAT(DISTINCT sellos.serial ORDER BY sellos.serial ASC SEPARATOR ',')
                    AS seriales"
                ),
            )
            ->groupBy('lugares.id', 'productos_clientes.id', 'kits.id');

        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('lugares.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('lugares.cliente_id', $dto['cliente']);
        }
        if(isset($dto['lugar'])){
            $query->where('lugares.id', '=',$dto['lugar'] );
        }
    
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'lugar'){
                    $query->orderBy('lugar', $value);
                }
                if($attribute == 'nombre'){
                    $query->orderBy('nombre', $value);
                }
                if($attribute == 'stock_minimo'){
                    $query->orderBy('stock_minimo', $value);
                }
                if($attribute == 'cantidad'){
                    $query->orderBy('cantidad', $value);
                }
            }
        }else{
            $query->orderBy("lugares.id", "desc");
        }

        $sellos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($sellos ?? [] as $pedido){
            array_push($datos, $pedido);
        }

        $cantidadSellos = count($sellos);
        $to = isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() * $sellos->perPage() : null;
        $to = isset($to) && isset($sellos) && $to > $sellos->total() && $cantidadSellos > 0 ? $sellos->total() : $to;
        $from = isset($to) && isset($sellos) && $cantidadSellos > 0 ?
            ( $sellos->perPage() > $to ? 1 : ($to - $cantidadSellos) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($sellos) && $cantidadSellos > 0 ? +$sellos->perPage() : 0,
            'pagina_actual' => isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() : 1,
            'ultima_pagina' => isset($sellos) && $cantidadSellos > 0 ? $sellos->lastPage() : 0,
            'total' => isset($sellos) && $cantidadSellos > 0 ? $sellos->total() : 0
        ];
    }

    public static function consultarStockMinimoPorLugar($dto){
        // DB::statement('SET GLOBAL group_concat_max_len = 1000000');
        $query = DB::table('sellos')
            ->leftJoin('lugares', 'lugares.id', 'sellos.lugar_id')
            ->leftJoin('usuarios', 'usuarios.id', 'sellos.user_id')
            ->leftJoin('productos_clientes', 'productos_clientes.id', 'sellos.producto_id')
            ->leftJoin('kits', 'kits.id', 'sellos.kit_id')
            ->leftJoin('inventario_minimo AS im1', function ($join) {
                $join->on('im1.producto_cliente_id', '=', 'productos_clientes.id')
                    ->on('im1.lugar_id', '=', 'lugares.id');
            })
            ->leftJoin('inventario_minimo AS im2', function ($join) {
                $join->on('im2.kit_id', '=', 'kits.id')
                    ->on('im2.lugar_id', '=', 'lugares.id');
            })
            ->whereIn('sellos.estado_sello', ['STO','TTO', 'DEV'])
            ->where(function($query1)  {
                $query1->where('sellos.tipo_empaque_despacho', '=', 'I')
                    ->orWhere(function($query2)  {
                        $query2->where('sellos.tipo_empaque_despacho', '=', 'K')
                            ->whereNotNull('sellos.kit_id');
                        });
            })
            ->select(
                'lugares.id as lugar_id',
                'lugares.nombre as lugar',
                'usuarios.id as usuario_id',
                'usuarios.nombre as usuario',
                'productos_clientes.nombre_producto_cliente as producto_id',
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        productos_clientes.nombre_producto_cliente
                        ,kits.nombre
                    ) AS nombre"
                ),
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        GROUP_CONCAT(DISTINCT im1.cantidad_inventario_minimo SEPARATOR ', '),
                        GROUP_CONCAT(DISTINCT im2.cantidad_inventario_minimo SEPARATOR ', ')
                    ) AS stock_minimo"
                ),
                DB::Raw(
                    "count(*) AS cantidad"
                ),
                DB::Raw("GROUP_CONCAT(DISTINCT sellos.serial ORDER BY sellos.serial ASC SEPARATOR ',')
                    AS seriales"
                ),
            )
            ->groupBy('lugares.id', 'usuarios.id', 'productos_clientes.id','kits.id');

        if(isset($dto['lugar'])){
            $query->where('lugares.id', '=',$dto['lugar'] );
        }
        if(isset($dto['producto_kit']) && isset($dto['tipo'])){
            $query->whereRaw("
                CASE WHEN 'P' = ?
                THEN productos_clientes.id = ?
                ELSE kits.id = ?
                END
            ",
            [
                $dto['tipo'],
                $dto['producto_kit'],
                $dto['producto_kit'],
            ] 
            );
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'lugar'){
                    $query->orderBy('lugar', $value);
                }
                if($attribute == 'usuario'){
                    $query->orderBy('usuario', $value);
                }
                if($attribute == 'nombre'){
                    $query->orderBy('nombre', $value);
                }
                if($attribute == 'stock_minimo'){
                    $query->orderBy('stock_minimo', $value);
                }
                if($attribute == 'cantidad'){
                    $query->orderBy('cantidad', $value);
                }
            }
        }else{
            $query->orderBy("lugares.id", "desc");
        }

        $sellos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($sellos ?? [] as $pedido){
            array_push($datos, $pedido);
        }

        $cantidadSellos = count($sellos);
        $to = isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() * $sellos->perPage() : null;
        $to = isset($to) && isset($sellos) && $to > $sellos->total() && $cantidadSellos > 0 ? $sellos->total() : $to;
        $from = isset($to) && isset($sellos) && $cantidadSellos > 0 ?
            ( $sellos->perPage() > $to ? 1 : ($to - $cantidadSellos) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($sellos) && $cantidadSellos > 0 ? +$sellos->perPage() : 0,
            'pagina_actual' => isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() : 1,
            'ultima_pagina' => isset($sellos) && $cantidadSellos > 0 ? $sellos->lastPage() : 0,
            'total' => isset($sellos) && $cantidadSellos > 0 ? $sellos->total() : 0
        ];
    }

    public static function leerSelloParaInstalar($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $sello = Sello::where('cliente_id', $dto['cliente_id'])
            ->where('producto_id', $dto['producto_id'])
            ->where('user_id', $usuario->id)
            ->where(function($filter) use($dto){
                $filter->where('serial', $dto['serial'])
                    ->orWhere('serial_interno', $dto['serial'])
                    ->orWhere('serial_qr', $dto['serial'])
                    ->orWhere('serial_datamatrix', $dto['serial'])
                    ->orWhere('serial_pdf', $dto['serial']);
            })
            ->where('estado_sello', 'STO')
            ->first();

        return [
            'id' => $sello->id,
            'serial' => $dto['serial'],
        ];
    }

    public static function instalar($dto, $req){
        $user = Auth::user();
        $usuario = $user->usuario();
        $fecha = Carbon::now();
        $eventoInstalacion = TipoEvento::find(
            ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_INSTALACION')->first()->valor_parametro
        );
        $cliente = Cliente::find($dto['cliente_id']);
        $tieneEvidencias = isset($dto['evidencias']);
        if($tieneEvidencias){
            $param = ParametroConstante::where('codigo_parametro', 'CONSECUTIVO_EVENTO_INSTALACION_EVIDENCIAS')->first();
            if(!$param){
                ParametroConstante::create([
                    'codigo_parametro' => 'CONSECUTIVO_EVENTO_INSTALACION_EVIDENCIAS',
                    'descripcion_parametro' => 'Consecutivo instalacion para evidencias',
                    'valor_parametro' => '1',
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                    'usuario_modificacion_id' => $usuario->id,
                    'usuario_modificacion_nombre' => $usuario->nombre,
                ]);
            }
            $consecutivo = ParametroConstante::where('codigo_parametro', 'CONSECUTIVO_EVENTO_INSTALACION_EVIDENCIAS')->first();
        }
        foreach(json_decode($dto['rows']) as $row){
            $sello = Sello::find($row->id);
            if($sello->estado_sello === $eventoInstalacion->estado_sello) continue;
            $sello->estado_sello = $eventoInstalacion->estado_sello;
            // $sello->user_id = $usuario->id;
            // $sello->lugar_id = $dto['lugar_instalacion_id'];
            $sello->contenedor_id = $dto['contenedor_id']??null;
            $sello->documento_referencia = $dto['documento_referencia']??null;
            $sello->lugar_instalacion_id = $dto['lugar_instalacion_id']??null;
            $sello->zona_instalacion_id = $dto['zona_instalacion_id']??null;
            $sello->fecha_instalacion = $fecha;
            $sello->numero_instalacion_evidencia = $tieneEvidencias?$consecutivo->valor_parametro:null;
            $sello->ultimo_tipo_evento_id = $eventoInstalacion->id;
            $sello->fecha_ultimo_evento = $fecha;
            $sello->usuario_modificacion_id = $usuario->id;
            $sello->usuario_modificacion_nombre = $usuario->nombre;
            $sello->operacion_embarque_id = $dto['operacion_embarque_id']??null;
            $sello->save();
            $bitacoraDto = [
                'sello_id' => $sello->id,
                'producto_id' => $sello->producto_id,
                'cliente_id' => $sello->cliente_id,
                'producto_empaque_id' => $sello->producto_empaque_id,
                'kit_id' => $sello->kit_id,
                'tipo_empaque_despacho' => $sello->tipo_empaque_despacho,
                'tipo_evento_id' => $eventoInstalacion->id,
                'fecha_evento' => $sello->fecha_instalacion,
                'estado_sello' => $eventoInstalacion->estado_sello,
                'clase_evento' => $eventoInstalacion->indicativo_clase_evento,
                'numero_pedido' => $sello->numero_pedido,
                'numero_remision' => $sello->numero_ultima_remision,
                'lugar_origen_id' => $sello->lugar_id,
                'lugar_destino_id' => null,
                'usuario_destino_id' => null,
                'contenedor_id' => $sello->contenedor_id,
                'documento_referencia' => $sello->documento_referencia,
                'lugar_instalacion_id' => $sello->lugar_instalacion_id,
                'zona_instalacion_id' => $sello->zona_instalacion_id,
                'operacion_embarque_id' => $sello->operacion_embarque_id,
                'longitud' => $row->longitude,
                'latitud' => $row->latitude,
                'numero_instalacion_evidencia' => $tieneEvidencias?$consecutivo->valor_parametro:null,
                'usuario_creacion_id' => $usuario->id,
                'usuario_creacion_nombre' => $usuario->nombre,
            ];
            SelloBitacora::create($bitacoraDto);
            if($cliente->indicativo_instalacion_automatica === 'S' && $sello->kit_id !== null){
                $sellosDelKit = Sello::where('producto_empaque_id', $sello->id)
                    ->whereNull('kit_id')
                    ->get();
                foreach($sellosDelKit as $selloDelKit){
                    if($selloDelKit->estado_sello === $eventoInstalacion->estado_sello) continue;
                    $selloDelKit->estado_sello = $eventoInstalacion->estado_sello;
                    // $selloDelKit->user_id = $usuario->id;
                    // $selloDelKit->lugar_id = $dto['lugar_instalacion_id'];
                    $selloDelKit->contenedor_id = $dto['contenedor_id']??null;
                    $selloDelKit->documento_referencia = $dto['documento_referencia']??null;
                    $selloDelKit->lugar_instalacion_id = $dto['lugar_instalacion_id']??null;
                    $selloDelKit->zona_instalacion_id = $dto['zona_instalacion_id']??null;
                    $selloDelKit->fecha_instalacion = $fecha;
                    $selloDelKit->numero_instalacion_evidencia = $tieneEvidencias?$consecutivo->valor_parametro:null;
                    $selloDelKit->ultimo_tipo_evento_id = $eventoInstalacion->id;
                    $selloDelKit->fecha_ultimo_evento = $fecha;
                    $selloDelKit->usuario_modificacion_id = $usuario->id;
                    $selloDelKit->usuario_modificacion_nombre = $usuario->nombre;
                    $selloDelKit->operacion_embarque_id = $dto['operacion_embarque_id']??null;
                    $selloDelKit->save(); 
                    $bitacoraDto = [
                        'sello_id' => $selloDelKit->id,
                        'producto_id' => $selloDelKit->producto_id,
                        'cliente_id' => $selloDelKit->cliente_id,
                        'producto_empaque_id' => $selloDelKit->producto_empaque_id,
                        'kit_id' => $selloDelKit->kit_id,
                        'tipo_empaque_despacho' => $selloDelKit->tipo_empaque_despacho,
                        'tipo_evento_id' => $eventoInstalacion->id,
                        'fecha_evento' => $selloDelKit->fecha_instalacion,
                        'estado_sello' => $eventoInstalacion->estado_sello,
                        'clase_evento' => $eventoInstalacion->indicativo_clase_evento,
                        'numero_pedido' => $selloDelKit->numero_pedido,
                        'numero_remision' => $selloDelKit->numero_ultima_remision,
                        'lugar_origen_id' => $selloDelKit->lugar_id,
                        'lugar_destino_id' => null,
                        'usuario_destino_id' => null,
                        'contenedor_id' => $selloDelKit->contenedor_id,
                        'documento_referencia' => $selloDelKit->documento_referencia,
                        'lugar_instalacion_id' => $selloDelKit->lugar_instalacion_id,
                        'zona_instalacion_id' => $selloDelKit->zona_instalacion_id,
                        'operacion_embarque_id' => $selloDelKit->operacion_embarque_id,
                        'longitud' => $row->longitude,
                        'latitud' => $row->latitude,
                        'numero_instalacion_evidencia' => $tieneEvidencias?$consecutivo->valor_parametro:null,
                        'usuario_creacion_id' => $usuario->id,
                        'usuario_creacion_nombre' => $usuario->nombre,
                    ];
                    SelloBitacora::create($bitacoraDto);
                }
            }
        }
        if($tieneEvidencias){
            $files = $req->file('evidencias');
            $rutaEvidencias = ParametroConstante::where('codigo_parametro', 'RUTA_EVIDENCIAS_INSTALACION')->first();
            foreach($files as $file){
                $fileExtension = $file->extension();
                $fileName = $file->hashName(); 
                $file->storeAs($rutaEvidencias->valor_parametro.$consecutivo->valor_parametro, $fileName, 's3');
                InstalacionEvidencia::create([
                    'numero_instalacion_evidencia' => $consecutivo->valor_parametro,
                    'evidencia' => $fileName,
                    'usuario_creacion_id' => $usuario->id,
                    'usuario_creacion_nombre' => $usuario->nombre,
                ]);
            }
            $consecutivo->valor_parametro = intval($consecutivo->valor_parametro)+1;
            $consecutivo->save();
        }
        return true;
    }

    public static function listaSellosParaInstalar($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $sellos = DB::table('sellos')
            ->select(
                'id',
                'serial',   
                'producto_id',   
            )
            ->where('cliente_id', $usuario->asociado_id)
            ->where('user_id', $usuario->id)
            ->where('estado_sello', 'STO');
        if(isset($dto['producto_id'])){
            $sellos->where('producto_id', $dto['producto_id']);
        }
        return $sellos->get();
    }

    public static function getRandomSeal($dto){
        $buscarEntre = ParametroConstante::where('codigo_parametro', 'LIMITE_BUSQUEDA_SELLOS')->first()->valor_parametro??0;
        $user = Auth::user();
        $usuario = $user->usuario();
        $sellos = DB::table('sellos')
            ->select(
                'id',
                'serial',   
                'producto_id',   
            )
            ->where('cliente_id', $usuario->asociado_id)
            ->where('producto_id', $dto['producto_id'])
            ->where('user_id', $usuario->id)
            ->where('estado_sello', 'STO')
            ->orderBy('serial');
        $copy = clone $sellos;
        $cantidad = $copy->count();
        if($cantidad === 0) {
            return false;
        } 
        $random = random_int(1, min(intval($buscarEntre), $cantidad));
        $randomSeal = $sellos->get()[$random-1];

        return [
            'id' => $randomSeal->id,
            'serial' => $randomSeal->serial,
        ];
    }

    public static function indexActualizarEstado($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('sellos AS mt')
            ->join('productos_clientes AS t1', 't1.id', 'mt.producto_id')
            ->join('clientes AS t2', 't2.id', 'mt.cliente_id')
            ->leftJoin('lugares AS t3', 't3.id', 'mt.lugar_id')
            ->leftJoin('contenedores AS t4', 't4.id', 'mt.contenedor_id')
            ->leftJoin('lugares AS t5', 't5.id', 'mt.lugar_instalacion_id')
            ->leftJoin('zonas_contenedores AS t6', 't6.id', 'mt.zona_instalacion_id')
            ->leftJoin('tipos_eventos AS t7', 't7.id', 'mt.ultimo_tipo_evento_id')
            ->select(
                'mt.id',
                't2.nombre AS cliente',
                't1.nombre_producto_cliente AS producto',
                'mt.serial',
                'mt.estado_sello',
                'mt.tipo_empaque_despacho',
                'mt.numero_pedido',
                'mt.numero_ultima_remision',
                't3.nombre AS lugar_origen',
                'mt.usuario_creacion_nombre AS usuario_origen',
                'mt.documento_referencia',
                't5.nombre AS lugar_instalacion',
                't6.nombre AS zona_instalacion',
                't4.numero_contenedor',
                't7.nombre AS ultimo_evento',
                'mt.fecha_ultimo_evento',
                'mt.operacion_embarque_id',
                'mt.created_at AS fecha_creacion',
            )
            ->whereIn('mt.estado_sello', ['STO', 'DEV', 'INS']);

        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where(function($filter) use($usuario){
                $filter->where('t3.cliente_id', $usuario->asociado_id);
            });
        }
        if(isset($dto['cliente'])){
            $query->where(function($filter) use($dto){
                $filter->where('t3.cliente_id', $dto['cliente'])
                ->orWhere('t5.cliente_id', $dto['cliente']);
            });
        }
        if(isset($dto['documentoRef'])){
            $query->where('mt.documento_referencia', 'like', '%'.$dto['documentoRef'].'%');
        }
        if(isset($dto['serial'])){
            $query->where('mt.serial', 'like', '%'.$dto['serial'].'%');
        }
        if(isset($dto['contenedor'])){
            $query->where('t4.numero_contenedor', 'like', '%'.$dto['contenedor'].'%');
        }
        if(isset($dto['lugar'])){
            $query->where(function($filter) use($dto){
                $filter->where('mt.lugar_id', $dto['lugar'])
                ->orWhere('mt.lugar_instalacion_id', $dto['lugar']);
            });
        }
        if(isset($dto['usuario'])){
            $query->where(function($filter) use($dto){
                $filter->where('mt.usuario_modificacion_id', $dto['usuario']);
            });
        }
        if(isset($dto['operacionEmbarque'])){
            $query->where('mt.operacion_embarque_id', $dto['operacionEmbarque']);
        }

        if (isset($dto['ordenar_por'])){
            $attribute = explode(':',$dto['ordenar_por'])[0];  
            $value = explode(':',$dto['ordenar_por'])[1];  
            if($attribute == 'cliente'){
                $query->orderBy('t2.nombre', $value);
            }
            if($attribute == 'producto'){
                $query->orderBy('t1.nombre_producto_cliente', $value);
            }
            if($attribute == 'serial'){
                $query->orderBy('mt.serial', $value);
            }
            if($attribute == 'estado_sello'){
                $query->orderBy('mt.estado_sello', $value);
            }
            if($attribute == 'numero_pedido'){
                $query->orderBy('mt.numero_pedido', $value);
            }
            if($attribute == 'numero_ultima_remision'){
                $query->orderBy('mt.numero_ultima_remision', $value);
            }
            if($attribute == 'lugar_origen'){
                $query->orderBy('t3.nombre', $value);
            }
            if($attribute == 'usuario_origen'){
                $query->orderBy('mt.usuario_creacion_nombre', $value);
            }
            if($attribute == 'documento_referencia'){
                $query->orderBy('mt.documento_referencia', $value);
            }
            if($attribute == 'lugar_instalacion'){
                $query->orderBy('t5.nombre', $value);
            }
            if($attribute == 'zona_instalacion'){
                $query->orderBy('t6.nombre', $value);
            }
            if($attribute == 'numero_contenedor'){
                $query->orderBy('t4.numero_contenedor', $value);
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
        }else{
            $query->orderBy("mt.serial", "asc");
        }

        $sellos = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($sellos ?? [] as $sello){
            array_push($datos, $sello);
        }

        $cantidadSellos = count($sellos);
        $to = isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() * $sellos->perPage() : null;
        $to = isset($to) && isset($sellos) && $to > $sellos->total() && $cantidadSellos > 0 ? $sellos->total() : $to;
        $from = isset($to) && isset($sellos) && $cantidadSellos > 0 ?
            ( $sellos->perPage() > $to ? 1 : ($to - $cantidadSellos) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($sellos) && $cantidadSellos > 0 ? +$sellos->perPage() : 0,
            'pagina_actual' => isset($sellos) && $cantidadSellos > 0 ? $sellos->currentPage() : 1,
            'ultima_pagina' => isset($sellos) && $cantidadSellos > 0 ? $sellos->lastPage() : 0,
            'total' => isset($sellos) && $cantidadSellos > 0 ? $sellos->total() : 0
        ];
    }

    public static function actualizarEstado($dto){
        $sello = Sello::find($dto['id']);
        $user = Auth::user();
        $fecha = Carbon::now();
        $usuario = $user->usuario();
        $evento = TipoEvento::find($dto['evento_id']);
        $sello->user_id = $usuario->id;
        $sello->estado_sello = $evento->estado_sello;
        $sello->lugar_id = LugarUsuario::where('usuario_id', $usuario->id)->first()->lugar_id;
        $sello->ultimo_tipo_evento_id = $evento->id;
        $sello->fecha_ultimo_evento = $fecha;
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
            'tipo_evento_id' => $evento->id,
            'fecha_evento' => $fecha,
            'estado_sello' => $evento->estado_sello,
            'clase_evento' => $evento->indicativo_clase_evento,
            'numero_pedido' => $sello->numero_pedido,
            'numero_remision' => $sello->numero_ultima_remision,
            'lugar_origen_id' => $sello->lugar_id,
            'lugar_destino_id' => null,
            'usuario_destino_id' => null,
            'contenedor_id' => $sello->contenedor_id,
            'documento_referencia' => $sello->documento_referencia,
            'lugar_instalacion_id' => $sello->lugar_instalacion_id,
            'zona_instalacion_id' => $sello->zona_instalacion_id,
            'operacion_embarque_id' => $sello->operacion_embarque_id,
            'latitud' => $dto['latitude'],
            'longitud' => $dto['longitude'],
            'observaciones_evento' => $dto['observaciones_evento'],
            'usuario_creacion_id' => $usuario->id,
            'usuario_creacion_nombre' => $usuario->nombre,
        ];
        SelloBitacora::create($bitacoraDto);
        return true;
    }

    use HasFactory;
}
