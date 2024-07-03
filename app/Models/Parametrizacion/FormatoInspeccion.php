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
use App\Models\Parametrizacion\ClaseInspeccion;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormatoInspeccion extends Model
{
    use HasFactory;

    protected $table = 'formatos_inspeccion';

    protected $fillable = [
        'nombre',
        'clase_inspeccion_id',
        'cliente_id',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function claseInspeccion(){
        return $this->belongsTo(ClaseInspeccion::class, 'clase_inspeccion_id');
    }

    public function formatoUnidades(){
        return $this->hasMany(FormatoInspeccionUnidad::class,'formato_id');
    }

    public static function obtenerColeccionLigera($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $query = DB::table('formatos_inspeccion')
            ->select(
                'id',
                'nombre',
                'cliente_id',
                'clase_inspeccion_id',
                'estado',
            )
            ->where('cliente_id', $usuario->asociado_id);
            
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('formatos_inspeccion AS mt')
            ->join('clases_inspeccion AS t1', 't1.id', 'mt.clase_inspeccion_id')
            ->select(
                'mt.id',
                'mt.nombre',
                't1.nombre AS clase_inspeccion',
                'mt.clase_inspeccion_id',
                'mt.cliente_id',
                'mt.estado',
                'mt.usuario_creacion_id',
                'mt.usuario_creacion_nombre',
                'mt.usuario_modificacion_id',
                'mt.usuario_modificacion_nombre',
                'mt.created_at AS fecha_creacion',
                'mt.updated_at AS fecha_modificacion'
            );

        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('mt.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['nombre'])){
            $query->where('mt.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        if(isset($dto['cliente'])){
            $query->where('mt.cliente_id', $dto['cliente']);
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('mt.nombre', $value);
                }
                if($attribute == 'clase_inspeccion'){
                    $query->orderBy('t1.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('mt.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('mt.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('mt.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('mt.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('mt.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("mt.updated_at", "desc");
        }

        $formatosInspeccion = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($formatosInspeccion ?? [] as $formatoInspeccion){
            array_push($data, $formatoInspeccion);
        }

        $cantidadFormatos = count($formatosInspeccion);
        $to = isset($formatosInspeccion) && $cantidadFormatos > 0 ? $formatosInspeccion->currentPage() * $formatosInspeccion->perPage() : null;
        $to = isset($to) && isset($formatosInspeccion) && $to > $formatosInspeccion->total() && $cantidadFormatos> 0 ? $formatosInspeccion->total() : $to;
        $from = isset($to) && isset($formatosInspeccion) && $cantidadFormatos > 0 ?
            ( $formatosInspeccion->perPage() > $to ? 1 : ($to - $cantidadFormatos) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($formatosInspeccion) && $cantidadFormatos > 0 ? +$formatosInspeccion->perPage() : 0,
            'pagina_actual' => isset($formatosInspeccion) && $cantidadFormatos > 0 ? $formatosInspeccion->currentPage() : 1,
            'ultima_pagina' => isset($formatosInspeccion) && $cantidadFormatos > 0 ? $formatosInspeccion->lastPage() : 0,
            'total' => isset($formatosInspeccion) && $cantidadFormatos > 0 ? $formatosInspeccion->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $formatoInspeccion = FormatoInspeccion::find($id);
        $cliente = $formatoInspeccion->cliente;
        $claseInspeccion = $formatoInspeccion->claseInspeccion;
        return [
            'id' => $formatoInspeccion->id,
            'nombre' => $formatoInspeccion->nombre,
            'estado' => $formatoInspeccion->estado,
            'usuario_creacion_id' => $formatoInspeccion->usuario_creacion_id,
            'usuario_creacion_nombre' => $formatoInspeccion->usuario_creacion_nombre,
            'usuario_modificacion_id' => $formatoInspeccion->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $formatoInspeccion->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($formatoInspeccion->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($formatoInspeccion->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
            'claseInspeccion' => isset($claseInspeccion) ? [
                'id' => $claseInspeccion->id,
                'nombre' => $claseInspeccion->nombre
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

        // Consultar el servicio
        $formatoInspeccion = isset($dto['id']) ? FormatoInspeccion::find($dto['id']) : new FormatoInspeccion();

        // Guardar objeto original para auditoria
        $formatoInspeccionOriginal = $formatoInspeccion->toJson();

        $formatoInspeccion->fill($dto);
        $guardado = $formatoInspeccion->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el formato.", $formatoInspeccion);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $formatoInspeccion->id,
            'nombre_recurso' => FormatoInspeccion::class,
            'descripcion_recurso' => $formatoInspeccion->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $formatoInspeccionOriginal : $formatoInspeccion->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $formatoInspeccion->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return FormatoInspeccion::cargar($formatoInspeccion->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $formatoInspeccion = FormatoInspeccion::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $formatoInspeccion->id,
            'nombre_recurso' => FormatoInspeccion::class,
            'descripcion_recurso' => $formatoInspeccion->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $formatoInspeccion->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $formatoInspeccion->delete();
    }
}
