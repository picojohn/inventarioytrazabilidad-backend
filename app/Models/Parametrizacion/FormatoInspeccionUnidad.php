<?php

namespace App\Models\Parametrizacion;

use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\TipoListaChequeo;
use App\Models\Parametrizacion\FormatoInspeccion;
use App\Models\Parametrizacion\FormatoInsUndLista;
use App\Models\Parametrizacion\UnidadCargaTransporte;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormatoInspeccionUnidad extends Model
{
    use HasFactory;

    protected $table = 'formatos_inspeccion_unidades';

    protected $fillable = [
        'formato_id',
        'unidad_id',
    ];

    public function formatoInspeccion(){
        return $this->belongsTo(FormatoInspeccion::class, 'formato_id');
    }

    public function unidad(){
        return $this->belongsTo(UnidadCargaTransporte::class, 'unidad_id');
    }

    public function listas(){
        return $this->hasMany(FormatoInsUndLista::class,'formato_unidad_id');
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('formatos_inspeccion_unidades AS mt')
            ->join('unidades_carga_transporte AS t1', 't1.id', 'mt.unidad_id')
            ->select(
                'mt.id',
                't1.nombre AS unidad',
                't1.indicativo_tipo_unidad',
                'mt.created_at AS fecha_creacion',
                'mt.updated_at AS fecha_modificacion'
            )
            ->where('mt.formato_id', $dto['formato_id']);

        if(isset($dto['nombre'])){
            $query->where('t1.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('t1.nombre', $value);
                }
                if($attribute == 'indicativo_tipo_unidad'){
                    $query->orderBy('t1.indicativo_tipo_unidad', $value);
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
        foreach ($formatosInspeccion ?? [] as $formatoInspeccionUnidad){
            $formatoInsUnd = FormatoInspeccionUnidad::find($formatoInspeccionUnidad->id);
            foreach($formatoInsUnd->listas as $lista){
                $list = TipoListaChequeo::find($lista->tipo_lista_id);
                $lista->nombre = $list->nombre;
            }
            $formatoInspeccionUnidad->listas = $formatoInsUnd->listas;
            array_push($data, $formatoInspeccionUnidad);
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
    public static function cargar($id){
        $formatoInspeccionUnidad = FormatoInspeccionUnidad::find($id);
        $formatoInspeccion = $formatoInspeccionUnidad->formatoInspeccion;
        $unidad = $formatoInspeccionUnidad->unidad;
        $listas = $formatoInspeccionUnidad->listas;
        return [
            'id' => $formatoInspeccionUnidad->id,
            'nombre' => $formatoInspeccionUnidad->nombre,
            'estado' => $formatoInspeccionUnidad->estado,
            'usuario_creacion_id' => $formatoInspeccionUnidad->usuario_creacion_id,
            'usuario_creacion_nombre' => $formatoInspeccionUnidad->usuario_creacion_nombre,
            'usuario_modificacion_id' => $formatoInspeccionUnidad->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $formatoInspeccionUnidad->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($formatoInspeccionUnidad->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($formatoInspeccionUnidad->updated_at))->format("Y-m-d H:i:s"),
            'formatoInspeccion' => isset($formatoInspeccion) ? [
                'id' => $formatoInspeccion->id,
                'nombre' => $formatoInspeccion->nombre
            ] : null,
            'unidad' => isset($unidad) ? [
                'id' => $unidad->id,
                'tipo_unidad' => $unidad->indicativo_tipo_unidad,
                'nombre' => $unidad->nombre
            ] : null,
            'listas' => $listas??null
        ];
    }

    public static function modificarOCrear($dto){
        // Consultar el servicio
        $formatoInspeccionUnidad = isset($dto['id']) ? FormatoInspeccionUnidad::find($dto['id']) : new FormatoInspeccionUnidad();

        // Guardar objeto original para auditoria
        $formatoInspeccionUnidadOriginal = $formatoInspeccionUnidad->toJson();

        $formatoInspeccionUnidad->fill($dto);
        $formatoInspeccionUnidad->save();

        $existentes = [];
        foreach($formatoInspeccionUnidad->listas as $ls){
            $existentes[] = $ls->tipo_lista_id;
        }
        foreach($dto['listas'] as $lista){
            if(array_search($lista, $existentes) === false){
                FormatoInsUndLista::create([
                    'formato_unidad_id' => $formatoInspeccionUnidad->id,
                    'tipo_lista_id' => $lista
                ]);
            }
        }
        foreach($existentes as $existente){
            if(array_search($existente, $dto['listas']) === false){
                FormatoInsUndLista::where('formato_unidad_id', $formatoInspeccionUnidad->id)
                    ->where('tipo_lista_id', $existente)
                    ->delete();
            }
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $formatoInspeccionUnidad->id,
            'nombre_recurso' => FormatoInspeccionUnidad::class,
            'descripcion_recurso' => $formatoInspeccionUnidad->formatoInspeccion->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $formatoInspeccionUnidadOriginal : $formatoInspeccionUnidad->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $formatoInspeccionUnidad->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return true;
    }

    public static function eliminar($id){
        // Connsultar el objeto
        $formatoInspeccionUnidad = FormatoInspeccionUnidad::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $formatoInspeccionUnidad->id,
            'nombre_recurso' => FormatoInspeccionUnidad::class,
            'descripcion_recurso' => $formatoInspeccionUnidad->formatoInspeccion->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $formatoInspeccionUnidad->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $formatoInspeccionUnidad->delete();
    }
}
