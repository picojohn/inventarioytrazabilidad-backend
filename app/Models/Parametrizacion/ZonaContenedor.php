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

class ZonaContenedor extends Model
{
    protected $table = 'zonas_contenedores';

    protected $fillable = [
        'nombre',
        'numero_sellos_zona',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('zonas_contenedores')
            ->select(
                'id',
                'nombre',
                'numero_sellos_zona',
                'estado',
            );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('zonas_contenedores')
            ->select(
                'zonas_contenedores.id',
                'zonas_contenedores.nombre',
                'zonas_contenedores.numero_sellos_zona',
                'zonas_contenedores.estado',
                'zonas_contenedores.usuario_creacion_id',
                'zonas_contenedores.usuario_creacion_nombre',
                'zonas_contenedores.usuario_modificacion_id',
                'zonas_contenedores.usuario_modificacion_nombre',
                'zonas_contenedores.created_at AS fecha_creacion',
                'zonas_contenedores.updated_at AS fecha_modificacion'
            );

        if(isset($dto['nombre'])){
            $query->where('zonas_contenedores.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('zonas_contenedores.nombre', $value);
                }
                if($attribute == 'numero_sellos_zona'){
                    $query->orderBy('zonas_contenedores.numero_sellos_zona', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('zonas_contenedores.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('zonas_contenedores.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('zonas_contenedores.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('zonas_contenedores.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('zonas_contenedores.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("zonas_contenedores.updated_at", "desc");
        }

        $zonasContenedores = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($zonasContenedores ?? [] as $zonaContenedor){
            array_push($data, $zonaContenedor);
        }

        $cantidadZonaContenedor = count($zonasContenedores);
        $to = isset($zonasContenedores) && $cantidadZonaContenedor > 0 ? $zonasContenedores->currentPage() * $zonasContenedores->perPage() : null;
        $to = isset($to) && isset($zonasContenedores) && $to > $zonasContenedores->total() && $cantidadZonaContenedor> 0 ? $zonasContenedores->total() : $to;
        $from = isset($to) && isset($zonasContenedores) && $cantidadZonaContenedor > 0 ?
            ( $zonasContenedores->perPage() > $to ? 1 : ($to - $cantidadZonaContenedor) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($zonasContenedores) && $cantidadZonaContenedor > 0 ? +$zonasContenedores->perPage() : 0,
            'pagina_actual' => isset($zonasContenedores) && $cantidadZonaContenedor > 0 ? $zonasContenedores->currentPage() : 1,
            'ultima_pagina' => isset($zonasContenedores) && $cantidadZonaContenedor > 0 ? $zonasContenedores->lastPage() : 0,
            'total' => isset($zonasContenedores) && $cantidadZonaContenedor > 0 ? $zonasContenedores->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $zonaContenedor = ZonaContenedor::find($id);

        return [
            'id' => $zonaContenedor->id,
            'nombre' => $zonaContenedor->nombre,
            'numero_sellos_zona' => $zonaContenedor->numero_sellos_zona,
            'estado' => $zonaContenedor->estado,
            'usuario_creacion_id' => $zonaContenedor->usuario_creacion_id,
            'usuario_creacion_nombre' => $zonaContenedor->usuario_creacion_nombre,
            'usuario_modificacion_id' => $zonaContenedor->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $zonaContenedor->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($zonaContenedor->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($zonaContenedor->updated_at))->format("Y-m-d H:i:s"),
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
        $zonaContenedor = isset($dto['id']) ? ZonaContenedor::find($dto['id']) : new ZonaContenedor();

        // Guardar objeto original para auditoria
        $zonaContenedorOriginal = $zonaContenedor->toJson();

        $zonaContenedor->fill($dto);
        $guardado = $zonaContenedor->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la zona de Contenedor.", $zonaContenedor);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $zonaContenedor->id,
            'nombre_recurso' => ZonaContenedor::class,
            'descripcion_recurso' => $zonaContenedor->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $zonaContenedorOriginal : $zonaContenedor->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $zonaContenedor->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return ZonaContenedor::cargar($zonaContenedor->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $zonaContenedor = ZonaContenedor::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $zonaContenedor->id,
            'nombre_recurso' => ZonaContenedor::class,
            'descripcion_recurso' => $zonaContenedor->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $zonaContenedor->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $zonaContenedor->delete();
    }
   use HasFactory;
}
