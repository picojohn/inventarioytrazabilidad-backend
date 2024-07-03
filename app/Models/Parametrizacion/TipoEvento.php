<?php

namespace App\Models\Parametrizacion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Seguridad\AuditoriaTabla;

class TipoEvento extends Model
{
    protected $table = 'tipos_eventos';

    protected $fillable = [
        'nombre',
        'estado_sello',
        'indicativo_evento_manual',
        'indicativo_clase_evento',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('tipos_eventos')
            ->select(
                'id',
                'nombre',
                'estado_sello',
                'indicativo_evento_manual',
                'indicativo_clase_evento',
                'estado',
            );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('tipos_eventos')
            ->select(
                'tipos_eventos.id',
                'tipos_eventos.nombre',
                'tipos_eventos.estado_sello',
                'tipos_eventos.indicativo_evento_manual',
                'tipos_eventos.indicativo_clase_evento',
                'tipos_eventos.estado',
                'tipos_eventos.usuario_creacion_id',
                'tipos_eventos.usuario_creacion_nombre',
                'tipos_eventos.usuario_modificacion_id',
                'tipos_eventos.usuario_modificacion_nombre',
                'tipos_eventos.created_at AS fecha_creacion',
                'tipos_eventos.updated_at AS fecha_modificacion'
            );

        if(isset($dto['nombre'])){
            $query->where('tipos_eventos.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('tipos_eventos.nombre', $value);
                }
                if($attribute == 'estado_sello'){
                    $query->orderBy('tipos_eventos.estado_sello', $value);
                }
                if($attribute == 'indicativo_evento_manual'){
                    $query->orderBy('tipos_eventos.indicativo_evento_manual', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('tipos_eventos.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('tipos_eventos.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('tipos_eventos.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('tipos_eventos.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('tipos_eventos.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("tipos_eventos.updated_at", "desc");
        }

        $tiposEventos = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($tiposEventos ?? [] as $tipoEvento){
            array_push($data, $tipoEvento);
        }

        $cantidadTipoEvento = count($tiposEventos);
        $to = isset($tiposEventos) && $cantidadTipoEvento > 0 ? $tiposEventos->currentPage() * $tiposEventos->perPage() : null;
        $to = isset($to) && isset($tiposEventos) && $to > $tiposEventos->total() && $cantidadTipoEvento> 0 ? $tiposEventos->total() : $to;
        $from = isset($to) && isset($tiposEventos) && $cantidadTipoEvento > 0 ?
            ( $tiposEventos->perPage() > $to ? 1 : ($to - $cantidadTipoEvento) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($tiposEventos) && $cantidadTipoEvento > 0 ? +$tiposEventos->perPage() : 0,
            'pagina_actual' => isset($tiposEventos) && $cantidadTipoEvento > 0 ? $tiposEventos->currentPage() : 1,
            'ultima_pagina' => isset($tiposEventos) && $cantidadTipoEvento > 0 ? $tiposEventos->lastPage() : 0,
            'total' => isset($tiposEventos) && $cantidadTipoEvento > 0 ? $tiposEventos->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $tipoEvento = TipoEvento::find($id);

        return [
            'id' => $tipoEvento->id,
            'nombre' => $tipoEvento->nombre,
            'estado_sello' => $tipoEvento->estado_sello,
            'indicativo_evento_manual' => $tipoEvento->indicativo_evento_manual,
            'indicativo_clase_evento' => $tipoEvento->indicativo_clase_evento,
            'estado' => $tipoEvento->estado,
            'usuario_creacion_id' => $tipoEvento->usuario_creacion_id,
            'usuario_creacion_nombre' => $tipoEvento->usuario_creacion_nombre,
            'usuario_modificacion_id' => $tipoEvento->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $tipoEvento->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($tipoEvento->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($tipoEvento->updated_at))->format("Y-m-d H:i:s"),
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
        $tipoEvento = isset($dto['id']) ? TipoEvento::find($dto['id']) : new TipoEvento();

        // Guardar objeto original para auditoria
        $tipoEventoOriginal = $tipoEvento->toJson();

        $tipoEvento->fill($dto);
        $guardado = $tipoEvento->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el tipo de evento.", $tipoEvento);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoEvento->id,
            'nombre_recurso' => TipoEvento::class,
            'descripcion_recurso' => $tipoEvento->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $tipoEventoOriginal : $tipoEvento->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $tipoEvento->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return TipoEvento::cargar($tipoEvento->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $tipoEvento = TipoEvento::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoEvento->id,
            'nombre_recurso' => TipoEvento::class,
            'descripcion_recurso' => $tipoEvento->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $tipoEvento->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $tipoEvento->delete();
    }
    use HasFactory;
}
