<?php

namespace App\Models\Parametrizacion;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoContenedor extends Model
{
    protected $table = 'tipos_contenedores';

    protected $fillable = [
        'nombre',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('tipos_contenedores')
            ->select(
                'id',
                'nombre',
                'estado',
            );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('tipos_contenedores')
            ->select(
                'tipos_contenedores.id',
                'tipos_contenedores.nombre',
                'tipos_contenedores.estado',
                'tipos_contenedores.usuario_creacion_id',
                'tipos_contenedores.usuario_creacion_nombre',
                'tipos_contenedores.usuario_modificacion_id',
                'tipos_contenedores.usuario_modificacion_nombre',
                'tipos_contenedores.created_at AS fecha_creacion',
                'tipos_contenedores.updated_at AS fecha_modificacion'
            );

        if(isset($dto['nombre'])){
            $query->where('tipos_contenedores.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('tipos_contenedores.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('tipos_contenedores.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('tipos_contenedores.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('tipos_contenedores.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('tipos_contenedores.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('tipos_contenedores.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("tipos_contenedores.updated_at", "desc");
        }

        $tiposContenedores = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($tiposContenedores ?? [] as $tipoContenedor){
            array_push($data, $tipoContenedor);
        }

        $cantidadTiposContenedor = count($tiposContenedores);
        $to = isset($tiposContenedores) && $cantidadTiposContenedor > 0 ? $tiposContenedores->currentPage() * $tiposContenedores->perPage() : null;
        $to = isset($to) && isset($tiposContenedores) && $to > $tiposContenedores->total() && $cantidadTiposContenedor> 0 ? $tiposContenedores->total() : $to;
        $from = isset($to) && isset($tiposContenedores) && $cantidadTiposContenedor > 0 ?
            ( $tiposContenedores->perPage() > $to ? 1 : ($to - $cantidadTiposContenedor) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($tiposContenedores) && $cantidadTiposContenedor > 0 ? +$tiposContenedores->perPage() : 0,
            'pagina_actual' => isset($tiposContenedores) && $cantidadTiposContenedor > 0 ? $tiposContenedores->currentPage() : 1,
            'ultima_pagina' => isset($tiposContenedores) && $cantidadTiposContenedor > 0 ? $tiposContenedores->lastPage() : 0,
            'total' => isset($tiposContenedores) && $cantidadTiposContenedor > 0 ? $tiposContenedores->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $tipoContenedor = TipoContenedor::find($id);

        return [
            'id' => $tipoContenedor->id,
            'nombre' => $tipoContenedor->nombre,
            'estado' => $tipoContenedor->estado,
            'usuario_creacion_id' => $tipoContenedor->usuario_creacion_id,
            'usuario_creacion_nombre' => $tipoContenedor->usuario_creacion_nombre,
            'usuario_modificacion_id' => $tipoContenedor->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $tipoContenedor->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($tipoContenedor->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($tipoContenedor->updated_at))->format("Y-m-d H:i:s"),
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
        $tipoContenedor = isset($dto['id']) ? TipoContenedor::find($dto['id']) : new TipoContenedor();

        // Guardar objeto original para auditoria
        $tipoAlertasOriginal = $tipoContenedor->toJson();

        $tipoContenedor->fill($dto);
        $guardado = $tipoContenedor->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el tipo de contenedor.", $tipoContenedor);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoContenedor->id,
            'nombre_recurso' => TipoContenedor::class,
            'descripcion_recurso' => $tipoContenedor->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $tipoAlertasOriginal : $tipoContenedor->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $tipoContenedor->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return TipoContenedor::cargar($tipoContenedor->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $tipoContenedor = TipoContenedor::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoContenedor->id,
            'nombre_recurso' => TipoContenedor::class,
            'descripcion_recurso' => $tipoContenedor->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $tipoContenedor->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $tipoContenedor->delete();
    }

    use HasFactory;
}
