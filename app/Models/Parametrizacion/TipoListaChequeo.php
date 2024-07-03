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
use App\Models\Parametrizacion\UnidadCargaTransporte;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoListaChequeo extends Model
{
    use HasFactory;

    protected $table = 'tipos_listas_chequeo';

    protected $fillable = [
        'nombre',
        'unidad_carga_id',
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

    public function unidadCarga(){
        return $this->belongsTo(UnidadCargaTransporte::class, 'unidad_carga_id');
    }

    public function claseInspeccion(){
        return $this->belongsTo(ClaseInspeccion::class, 'clase_inspeccion_id');
    }

    public static function obtenerColeccionLigera($dto){
        // $user = Auth::user();
        // $usuario = $user->usuario();
        $query = DB::table('tipos_listas_chequeo')
            ->select(
                'id',
                'nombre',
                'unidad_carga_id',
                'clase_inspeccion_id',
                'cliente_id',
                'estado',
            );
        if(isset($dto['unidad_carga_id'])){
            $query->where('unidad_carga_id', $dto['unidad_carga_id']);
        }
            
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $query = DB::table('tipos_listas_chequeo AS t1')
            ->join('unidades_carga_transporte AS t2', 't2.id', 't1.unidad_carga_id')
            ->leftJoin('clases_inspeccion AS t3', 't3.id', 't1.clase_inspeccion_id')
            ->select(
                't1.id',
                't1.nombre',
                't2.nombre AS unidad',
                't1.unidad_carga_id',
                't3.nombre AS clase_inspeccion',
                't1.estado',
                't1.usuario_creacion_id',
                't1.usuario_creacion_nombre',
                't1.usuario_modificacion_id',
                't1.usuario_modificacion_nombre',
                't1.created_at AS t1.fecha_creacion',
                't1.updated_at AS fecha_modificacion'
            )
            ->where('t1.unidad_carga_id', $dto['unidad_carga_id']);

        if(isset($dto['nombre'])){
            $query->where('t1.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('t1.nombre', $value);
                }
                if($attribute == 'unidad'){
                    $query->orderBy('t2.nombre', $value);
                }
                if($attribute == 'clase_inspeccion'){
                    $query->orderBy('t3.nombre', $value);
                }
                if($attribute == 'unidad_carga_id'){
                    $query->orderBy('t1.unidad_carga_id', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('t1.estado', $value);
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

        $listasChequeo = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($listasChequeo ?? [] as $listaChequeo){
            array_push($data, $listaChequeo);
        }

        $cantidaListasChequeo = count($listasChequeo);
        $to = isset($listasChequeo) && $cantidaListasChequeo > 0 ? $listasChequeo->currentPage() * $listasChequeo->perPage() : null;
        $to = isset($to) && isset($listasChequeo) && $to > $listasChequeo->total() && $cantidaListasChequeo> 0 ? $listasChequeo->total() : $to;
        $from = isset($to) && isset($listasChequeo) && $cantidaListasChequeo > 0 ?
            ( $listasChequeo->perPage() > $to ? 1 : ($to - $cantidaListasChequeo) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($listasChequeo) && $cantidaListasChequeo > 0 ? +$listasChequeo->perPage() : 0,
            'pagina_actual' => isset($listasChequeo) && $cantidaListasChequeo > 0 ? $listasChequeo->currentPage() : 1,
            'ultima_pagina' => isset($listasChequeo) && $cantidaListasChequeo > 0 ? $listasChequeo->lastPage() : 0,
            'total' => isset($listasChequeo) && $cantidaListasChequeo > 0 ? $listasChequeo->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $listaChequeo = TipoListaChequeo::find($id);
        $cliente = $listaChequeo->cliente;
        $unidadCarga = $listaChequeo->unidadCarga;
        $claseInspeccion = $listaChequeo->claseInspeccion;
        return [
            'id' => $listaChequeo->id,
            'nombre' => $listaChequeo->nombre,
            'estado' => $listaChequeo->estado,
            'usuario_creacion_id' => $listaChequeo->usuario_creacion_id,
            'usuario_creacion_nombre' => $listaChequeo->usuario_creacion_nombre,
            'usuario_modificacion_id' => $listaChequeo->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $listaChequeo->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($listaChequeo->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($listaChequeo->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
            'unidadCarga' => isset($unidadCarga) ? [
                'id' => $unidadCarga->id,
                'nombre' => $unidadCarga->nombre
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
        $unidadCarga = UnidadCargaTransporte::find($dto['unidad_carga_id']);
        $dto['cliente_id'] = $unidadCarga->cliente_id;

        // Consultar el servicio
        $listaChequeo = isset($dto['id']) ? TipoListaChequeo::find($dto['id']) : new TipoListaChequeo();

        // Guardar objeto original para auditoria
        $tipoChequeoOriginal = $listaChequeo->toJson();

        $listaChequeo->fill($dto);
        $guardado = $listaChequeo->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la unidad de carga.", $listaChequeo);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $listaChequeo->id,
            'nombre_recurso' => TipoListaChequeo::class,
            'descripcion_recurso' => $listaChequeo->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $tipoChequeoOriginal : $listaChequeo->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $listaChequeo->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return TipoListaChequeo::cargar($listaChequeo->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $listaChequeo = TipoListaChequeo::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $listaChequeo->id,
            'nombre_recurso' => TipoListaChequeo::class,
            'descripcion_recurso' => $listaChequeo->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $listaChequeo->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $listaChequeo->delete();
    }
}
