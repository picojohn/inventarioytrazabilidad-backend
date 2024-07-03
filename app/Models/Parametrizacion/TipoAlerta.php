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

class TipoAlerta extends Model
{
    protected $table = 'tipos_alertas';

    protected $fillable = [
        'nombre',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('tipos_alertas')
            ->select(
                'id',
                'nombre',
                'estado',
            );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('tipos_alertas')
            ->select(
                'tipos_alertas.id',
                'tipos_alertas.nombre',
                'tipos_alertas.estado',
                'tipos_alertas.usuario_creacion_id',
                'tipos_alertas.usuario_creacion_nombre',
                'tipos_alertas.usuario_modificacion_id',
                'tipos_alertas.usuario_modificacion_nombre',
                'tipos_alertas.created_at AS fecha_creacion',
                'tipos_alertas.updated_at AS fecha_modificacion'
            );

        if(isset($dto['nombre'])){
            $query->where('tipos_alertas.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('tipos_alertas.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('tipos_alertas.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('tipos_alertas.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('tipos_alertas.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('tipos_alertas.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('tipos_alertas.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("tipos_alertas.updated_at", "desc");
        }

        $tiposAlertas = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($tiposAlertas ?? [] as $tipoAlerta){
            array_push($data, $tipoAlerta);
        }

        $cantidadTipoAlerta = count($tiposAlertas);
        $to = isset($tiposAlertas) && $cantidadTipoAlerta > 0 ? $tiposAlertas->currentPage() * $tiposAlertas->perPage() : null;
        $to = isset($to) && isset($tiposAlertas) && $to > $tiposAlertas->total() && $cantidadTipoAlerta> 0 ? $tiposAlertas->total() : $to;
        $from = isset($to) && isset($tiposAlertas) && $cantidadTipoAlerta > 0 ?
            ( $tiposAlertas->perPage() > $to ? 1 : ($to - $cantidadTipoAlerta) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($tiposAlertas) && $cantidadTipoAlerta > 0 ? +$tiposAlertas->perPage() : 0,
            'pagina_actual' => isset($tiposAlertas) && $cantidadTipoAlerta > 0 ? $tiposAlertas->currentPage() : 1,
            'ultima_pagina' => isset($tiposAlertas) && $cantidadTipoAlerta > 0 ? $tiposAlertas->lastPage() : 0,
            'total' => isset($tiposAlertas) && $cantidadTipoAlerta > 0 ? $tiposAlertas->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $tipoAlerta = TipoAlerta::find($id);

        return [
            'id' => $tipoAlerta->id,
            'nombre' => $tipoAlerta->nombre,
            'estado' => $tipoAlerta->estado,
            'usuario_creacion_id' => $tipoAlerta->usuario_creacion_id,
            'usuario_creacion_nombre' => $tipoAlerta->usuario_creacion_nombre,
            'usuario_modificacion_id' => $tipoAlerta->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $tipoAlerta->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($tipoAlerta->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($tipoAlerta->updated_at))->format("Y-m-d H:i:s"),
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
        $tipoAlerta = isset($dto['id']) ? TipoAlerta::find($dto['id']) : new TipoAlerta();

        // Guardar objeto original para auditoria
        $tipoAlertasOriginal = $tipoAlerta->toJson();

        $tipoAlerta->fill($dto);
        $guardado = $tipoAlerta->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el tipo de alertas.", $tipoAlerta);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoAlerta->id,
            'nombre_recurso' => TipoAlerta::class,
            'descripcion_recurso' => $tipoAlerta->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $tipoAlertasOriginal : $tipoAlerta->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $tipoAlerta->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return TipoAlerta::cargar($tipoAlerta->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $tipoAlerta = TipoAlerta::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoAlerta->id,
            'nombre_recurso' => TipoAlerta::class,
            'descripcion_recurso' => $tipoAlerta->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $tipoAlerta->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $tipoAlerta->delete();
    }
   
    use HasFactory;
}
