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

class TipoChequeo extends Model
{
    use HasFactory;

    protected $table = 'tipos_chequeos';

    protected $fillable = [
        'nombre',
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
        $user = Auth::user();
        $usuario = $user->usuario();
        $query = DB::table('tipos_chequeos')
            ->select(
                'id',
                'nombre',
                'cliente_id',
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
        $query = DB::table('tipos_chequeos')
            ->select(
                'id',
                'nombre',
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

        $tiposChequeos = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($tiposChequeos ?? [] as $tipoChequeo){
            array_push($data, $tipoChequeo);
        }

        $cantidadTiposChequeo = count($tiposChequeos);
        $to = isset($tiposChequeos) && $cantidadTiposChequeo > 0 ? $tiposChequeos->currentPage() * $tiposChequeos->perPage() : null;
        $to = isset($to) && isset($tiposChequeos) && $to > $tiposChequeos->total() && $cantidadTiposChequeo> 0 ? $tiposChequeos->total() : $to;
        $from = isset($to) && isset($tiposChequeos) && $cantidadTiposChequeo > 0 ?
            ( $tiposChequeos->perPage() > $to ? 1 : ($to - $cantidadTiposChequeo) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($tiposChequeos) && $cantidadTiposChequeo > 0 ? +$tiposChequeos->perPage() : 0,
            'pagina_actual' => isset($tiposChequeos) && $cantidadTiposChequeo > 0 ? $tiposChequeos->currentPage() : 1,
            'ultima_pagina' => isset($tiposChequeos) && $cantidadTiposChequeo > 0 ? $tiposChequeos->lastPage() : 0,
            'total' => isset($tiposChequeos) && $cantidadTiposChequeo > 0 ? $tiposChequeos->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $tipoChequeo = TipoChequeo::find($id);
        $cliente = $tipoChequeo->cliente;
        return [
            'id' => $tipoChequeo->id,
            'nombre' => $tipoChequeo->nombre,
            'estado' => $tipoChequeo->estado,
            'usuario_creacion_id' => $tipoChequeo->usuario_creacion_id,
            'usuario_creacion_nombre' => $tipoChequeo->usuario_creacion_nombre,
            'usuario_modificacion_id' => $tipoChequeo->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $tipoChequeo->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($tipoChequeo->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($tipoChequeo->updated_at))->format("Y-m-d H:i:s"),
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
        $tipoChequeo = isset($dto['id']) ? TipoChequeo::find($dto['id']) : new TipoChequeo();

        // Guardar objeto original para auditoria
        $tipoChequeoOriginal = $tipoChequeo->toJson();

        $tipoChequeo->fill($dto);
        $guardado = $tipoChequeo->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el tipo de chequeo.", $tipoChequeo);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoChequeo->id,
            'nombre_recurso' => TipoChequeo::class,
            'descripcion_recurso' => $tipoChequeo->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $tipoChequeoOriginal : $tipoChequeo->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $tipoChequeo->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return TipoChequeo::cargar($tipoChequeo->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $tipoChequeo = TipoChequeo::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoChequeo->id,
            'nombre_recurso' => TipoChequeo::class,
            'descripcion_recurso' => $tipoChequeo->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $tipoChequeo->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $tipoChequeo->delete();
    }
}
