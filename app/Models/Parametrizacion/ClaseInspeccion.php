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

class ClaseInspeccion extends Model
{
    use HasFactory;

    protected $table = 'clases_inspeccion';

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
        $query = DB::table('clases_inspeccion')
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
        $query = DB::table('clases_inspeccion')
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

        $clasesInspeccion = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($clasesInspeccion ?? [] as $claseInspeccion){
            array_push($data, $claseInspeccion);
        }

        $cantidadClasesInspeccion = count($clasesInspeccion);
        $to = isset($clasesInspeccion) && $cantidadClasesInspeccion > 0 ? $clasesInspeccion->currentPage() * $clasesInspeccion->perPage() : null;
        $to = isset($to) && isset($clasesInspeccion) && $to > $clasesInspeccion->total() && $cantidadClasesInspeccion> 0 ? $clasesInspeccion->total() : $to;
        $from = isset($to) && isset($clasesInspeccion) && $cantidadClasesInspeccion > 0 ?
            ( $clasesInspeccion->perPage() > $to ? 1 : ($to - $cantidadClasesInspeccion) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($clasesInspeccion) && $cantidadClasesInspeccion > 0 ? +$clasesInspeccion->perPage() : 0,
            'pagina_actual' => isset($clasesInspeccion) && $cantidadClasesInspeccion > 0 ? $clasesInspeccion->currentPage() : 1,
            'ultima_pagina' => isset($clasesInspeccion) && $cantidadClasesInspeccion > 0 ? $clasesInspeccion->lastPage() : 0,
            'total' => isset($clasesInspeccion) && $cantidadClasesInspeccion > 0 ? $clasesInspeccion->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $claseInspeccion = ClaseInspeccion::find($id);
        $cliente = $claseInspeccion->cliente;
        return [
            'id' => $claseInspeccion->id,
            'nombre' => $claseInspeccion->nombre,
            'estado' => $claseInspeccion->estado,
            'usuario_creacion_id' => $claseInspeccion->usuario_creacion_id,
            'usuario_creacion_nombre' => $claseInspeccion->usuario_creacion_nombre,
            'usuario_modificacion_id' => $claseInspeccion->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $claseInspeccion->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($claseInspeccion->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($claseInspeccion->updated_at))->format("Y-m-d H:i:s"),
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
        $claseInspeccion = isset($dto['id']) ? ClaseInspeccion::find($dto['id']) : new ClaseInspeccion();

        // Guardar objeto original para auditoria
        $claseInspeccionOriginal = $claseInspeccion->toJson();

        $claseInspeccion->fill($dto);
        $guardado = $claseInspeccion->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la clase de inspección.", $claseInspeccion);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $claseInspeccion->id,
            'nombre_recurso' => ClaseInspeccion::class,
            'descripcion_recurso' => $claseInspeccion->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $claseInspeccionOriginal : $claseInspeccion->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $claseInspeccion->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return ClaseInspeccion::cargar($claseInspeccion->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $claseInspeccion = ClaseInspeccion::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $claseInspeccion->id,
            'nombre_recurso' => ClaseInspeccion::class,
            'descripcion_recurso' => $claseInspeccion->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $claseInspeccion->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $claseInspeccion->delete();
    }
}
