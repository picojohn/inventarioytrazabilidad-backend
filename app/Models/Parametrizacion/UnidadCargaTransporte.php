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
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnidadCargaTransporte extends Model
{
    protected $table = 'unidades_carga_transporte';

    protected $fillable = [
        'nombre',
        'indicativo_tipo_unidad',
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

    public static function obtenerColeccionLigera($dto){
        // $user = Auth::user();
        // $usuario = $user->usuario();
        $query = DB::table('unidades_carga_transporte')
            ->select(
                'id',
                'nombre',
                'indicativo_tipo_unidad',
                'cliente_id',
                'estado',
            );            
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('unidades_carga_transporte')
            ->select(
                'id',
                'nombre',
                'indicativo_tipo_unidad',
                'estado',
                'usuario_creacion_id',
                'usuario_creacion_nombre',
                'usuario_modificacion_id',
                'usuario_modificacion_nombre',
                'created_at AS fecha_creacion',
                'updated_at AS fecha_modificacion'
            );

        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['nombre'])){
            $query->where('nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        if(isset($dto['cliente'])){
            $query->where('cliente_id', $dto['cliente']);
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('nombre', $value);
                }
                if($attribute == 'indicativo_tipo_unidad'){
                    $query->orderBy('indicativo_tipo_unidad', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('updated_at', $value);
                }
            }
        }else{
            $query->orderBy("updated_at", "desc");
        }

        $unidadesCargaTransporte = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($unidadesCargaTransporte ?? [] as $unidadCargaTransporte){
            array_push($data, $unidadCargaTransporte);
        }

        $cantidadUnidades = count($unidadesCargaTransporte);
        $to = isset($unidadesCargaTransporte) && $cantidadUnidades > 0 ? $unidadesCargaTransporte->currentPage() * $unidadesCargaTransporte->perPage() : null;
        $to = isset($to) && isset($unidadesCargaTransporte) && $to > $unidadesCargaTransporte->total() && $cantidadUnidades> 0 ? $unidadesCargaTransporte->total() : $to;
        $from = isset($to) && isset($unidadesCargaTransporte) && $cantidadUnidades > 0 ?
            ( $unidadesCargaTransporte->perPage() > $to ? 1 : ($to - $cantidadUnidades) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($unidadesCargaTransporte) && $cantidadUnidades > 0 ? +$unidadesCargaTransporte->perPage() : 0,
            'pagina_actual' => isset($unidadesCargaTransporte) && $cantidadUnidades > 0 ? $unidadesCargaTransporte->currentPage() : 1,
            'ultima_pagina' => isset($unidadesCargaTransporte) && $cantidadUnidades > 0 ? $unidadesCargaTransporte->lastPage() : 0,
            'total' => isset($unidadesCargaTransporte) && $cantidadUnidades > 0 ? $unidadesCargaTransporte->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $unidadCargaTransporte = UnidadCargaTransporte::find($id);
        $cliente = $unidadCargaTransporte->cliente;
        return [
            'id' => $unidadCargaTransporte->id,
            'nombre' => $unidadCargaTransporte->nombre,
            'indicativo_tipo_unidad' => $unidadCargaTransporte->indicativo_tipo_unidad,
            'estado' => $unidadCargaTransporte->estado,
            'usuario_creacion_id' => $unidadCargaTransporte->usuario_creacion_id,
            'usuario_creacion_nombre' => $unidadCargaTransporte->usuario_creacion_nombre,
            'usuario_modificacion_id' => $unidadCargaTransporte->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $unidadCargaTransporte->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($unidadCargaTransporte->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($unidadCargaTransporte->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
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
        $unidadCargaTransporte = isset($dto['id']) ? UnidadCargaTransporte::find($dto['id']) : new UnidadCargaTransporte();

        // Guardar objeto original para auditoria
        $tipoChequeoOriginal = $unidadCargaTransporte->toJson();

        $unidadCargaTransporte->fill($dto);
        $guardado = $unidadCargaTransporte->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la unidad de carga.", $unidadCargaTransporte);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $unidadCargaTransporte->id,
            'nombre_recurso' => UnidadCargaTransporte::class,
            'descripcion_recurso' => $unidadCargaTransporte->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $tipoChequeoOriginal : $unidadCargaTransporte->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $unidadCargaTransporte->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return UnidadCargaTransporte::cargar($unidadCargaTransporte->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $unidadCargaTransporte = UnidadCargaTransporte::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $unidadCargaTransporte->id,
            'nombre_recurso' => UnidadCargaTransporte::class,
            'descripcion_recurso' => $unidadCargaTransporte->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $unidadCargaTransporte->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $unidadCargaTransporte->delete();
    }

    use HasFactory;
}
