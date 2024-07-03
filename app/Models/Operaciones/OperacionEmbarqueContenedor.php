<?php

namespace App\Models\Operaciones;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\Contenedor;
use App\Imports\ContenedoresOperacionImport;
use App\Models\Operaciones\OperacionEmbarque;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OperacionEmbarqueContenedor extends Model
{
    use HasFactory;

    protected $table = 'operaciones_embarque_contenedores';

    protected $fillable = [
        'operacion_embarque_id',
        'contenedor_id',
        'estado_contenedor',
        'observaciones',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public function operacionEmbarque(){
        return $this->belongsTo(OperacionEmbarque::class, 'operacion_embarque_id');
    }

    public function contenedor(){
        return $this->belongsTo(Contenedor::class, 'contenedor_id');
    }

    public static function obtenerColeccionLigera($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $query = DB::table('operaciones_embarque_contenedores AS mt')
            ->join('contenedores AS t1', 't1.id', 'mt.contenedor_id')
            ->select(
                'mt.id',
                DB::raw("CONCAT(t1.numero_contenedor, '-', t1.digito_verificacion) AS nombre"),
                'mt.operacion_embarque_id',
                'mt.estado_contenedor',
            )
            ->where('mt.operacion_embarque_id', $dto['operacion_embarque_id']);
            
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $query = DB::table('operaciones_embarque_contenedores AS t1')
            ->join('operaciones_embarque AS t2', 't2.id', 't1.operacion_embarque_id')
            ->join('contenedores AS t3', 't3.id', 't1.contenedor_id')
            ->select(
                't1.id',
                't2.nombre AS operacion_embarque',
                't1.operacion_embarque_id',
                DB::raw(
                    "CONCAT(t3.numero_contenedor, '-', t3.digito_verificacion)
                    AS contenedor"
                ),
                't1.estado_contenedor',
                't1.observaciones',
                't1.usuario_creacion_id',
                't1.usuario_creacion_nombre',
                't1.usuario_modificacion_id',
                't1.usuario_modificacion_nombre',
                't1.created_at AS t1.fecha_creacion',
                't1.updated_at AS fecha_modificacion'
            )
            ->where('t1.operacion_embarque_id', $dto['operacion_embarque_id']);

        if(isset($dto['nombre'])){
            $query->where('t3.numero_contenedor', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'operacion_embarque'){
                    $query->orderBy('t2.nombre', $value);
                }
                if($attribute == 'contenedor'){
                    $query->orderBy('t3.numero_contenedor', $value);
                }
                if($attribute == 'operacion_embarque_id'){
                    $query->orderBy('t1.operacion_embarque_id', $value);
                }
                if($attribute == 'estado_contenedor'){
                    $query->orderBy('t1.estado_contenedor', $value);
                }
                if($attribute == 'observaciones'){
                    $query->orderBy('t1.observaciones', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('t1.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('t1.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('t1.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('t1.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("t1.updated_at", "desc");
        }

        $opEmContenedores = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($opEmContenedores ?? [] as $opEmContenedor){
            array_push($data, $opEmContenedor);
        }

        $cantidaOpEmContenedores = count($opEmContenedores);
        $to = isset($opEmContenedores) && $cantidaOpEmContenedores > 0 ? $opEmContenedores->currentPage() * $opEmContenedores->perPage() : null;
        $to = isset($to) && isset($opEmContenedores) && $to > $opEmContenedores->total() && $cantidaOpEmContenedores> 0 ? $opEmContenedores->total() : $to;
        $from = isset($to) && isset($opEmContenedores) && $cantidaOpEmContenedores > 0 ?
            ( $opEmContenedores->perPage() > $to ? 1 : ($to - $cantidaOpEmContenedores) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($opEmContenedores) && $cantidaOpEmContenedores > 0 ? +$opEmContenedores->perPage() : 0,
            'pagina_actual' => isset($opEmContenedores) && $cantidaOpEmContenedores > 0 ? $opEmContenedores->currentPage() : 1,
            'ultima_pagina' => isset($opEmContenedores) && $cantidaOpEmContenedores > 0 ? $opEmContenedores->lastPage() : 0,
            'total' => isset($opEmContenedores) && $cantidaOpEmContenedores > 0 ? $opEmContenedores->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $opEmContenedor = OperacionEmbarqueContenedor::find($id);
        $operacionEmbarque = $opEmContenedor->operacionEmbarque;
        $contenedor = $opEmContenedor->contenedor;
        
        return [
            'id' => $opEmContenedor->id,
            'nombre' => $opEmContenedor->nombre,
            'estado_contenedor' => $opEmContenedor->estado_contenedor,
            'observaciones' => $opEmContenedor->observaciones,
            'usuario_creacion_id' => $opEmContenedor->usuario_creacion_id,
            'usuario_creacion_nombre' => $opEmContenedor->usuario_creacion_nombre,
            'usuario_modificacion_id' => $opEmContenedor->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $opEmContenedor->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($opEmContenedor->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($opEmContenedor->updated_at))->format("Y-m-d H:i:s"),
            'operacionEmbarque' => isset($operacionEmbarque) ? [
                'id' => $operacionEmbarque->id,
                'nombre' => $operacionEmbarque->nombre
            ] : null,
            'contenedor' => isset($contenedor) ? [
                'id' => $contenedor->id,
                'numero_contenedor' => $contenedor->numero_contenedor,
                'digito_verificacion' => $contenedor->digito_verificacion,
            ] : null,
        ];
    }

    public static function modificarOCrear($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();
        if (!isset($dto['id'])) {
            $dto['usuario_creacion_id'] = $usuario->id ?? ($dto['usuario_creacion_id'] ?? null);
            $dto['usuario_creacion_nombre'] = $usuario->nombre ?? ($dto['usuario_creacion_nombre'] ?? null);
        }
        if (isset($usuario) || isset($dto['usuario_modificacion_id'])) {
            $dto['usuario_modificacion_id'] = $usuario->id ?? ($dto['usuario_modificacion_id'] ?? null);
            $dto['usuario_modificacion_nombre'] = $usuario->nombre ?? ($dto['usuario_modificacion_nombre'] ?? null);
        }
        $operacionEmbarque = OperacionEmbarque::find($dto['operacion_embarque_id']);
        $contenedor = Contenedor::where('cliente_id', $operacionEmbarque->cliente_id)
            ->where('numero_contenedor', $dto['numero_contenedor'])
            ->first();
        if(!$contenedor){
            $contenedor = new Contenedor();
            $data = [
                'numero_contenedor' => $dto['numero_contenedor'],
                'digito_verificacion' => $dto['digito_verificacion'],
                'cliente_id' => $operacionEmbarque->cliente_id,
                'usuario_creacion_id' => $usuario->id,
                'usuario_creacion_nombre' => $usuario->nombre,
                'usuario_modificacion_id' => $usuario->id,
                'usuario_modificacion_nombre' => $usuario->nombre,
            ];
            $contenedor->fill($data);
            $contenedor->save();
        }
        $dto['contenedor_id'] = $contenedor->id;
        $unique = OperacionEmbarqueContenedor::where('contenedor_id', $contenedor->id)
            ->where('operacion_embarque_id', $dto['operacion_embarque_id']);
        if(isset($dto['id'])){
            $unique->where('id', '<>', $dto['id']);
        }
        if($unique->count()>0){
            return false;
        }
        // Consultar el servicio
        $opEmContenedor = isset($dto['id']) ? OperacionEmbarqueContenedor::find($dto['id']) : new OperacionEmbarqueContenedor();

        // Guardar objeto original para auditoria
        $opEmContenedorOriginal = $opEmContenedor->toJson();

        $opEmContenedor->fill($dto);
        $guardado = $opEmContenedor->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el contenedor.", $opEmContenedor);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $opEmContenedor->id,
            'nombre_recurso' => OperacionEmbarqueContenedor::class,
            'descripcion_recurso' => $opEmContenedor->contenedor->numero_contenedor,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $opEmContenedorOriginal : $opEmContenedor->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $opEmContenedor->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return OperacionEmbarqueContenedor::cargar($opEmContenedor->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $opEmContenedor = OperacionEmbarqueContenedor::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $opEmContenedor->id,
            'nombre_recurso' => OperacionEmbarqueContenedor::class,
            'descripcion_recurso' => $opEmContenedor->contenedor->numero_contenedor,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $opEmContenedor->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $opEmContenedor->delete();
    }

    public static function importar($archivo, $operacionEmbarqueId){

        $user = Auth::user();
        $usuario = $user->usuario();
        $errores = [];
        $import = new ContenedoresOperacionImport($operacionEmbarqueId, $usuario->id, $usuario->nombre);
        Excel::import($import, $archivo);

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
        $erroresReporte=[];
        if(count($erroresPersonalizados) > 0){
            foreach ($erroresPersonalizados ?? [] as $registro){
                array_push($erroresReporte, [
                    'numero_contenedor' => $registro['datosFila'][0],
                    'observaciones_contenedor' => $registro['datosFila'][1],
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
}
