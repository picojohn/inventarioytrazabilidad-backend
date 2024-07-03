<?php

namespace App\Models\Operaciones;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OperacionEmbarque extends Model
{
    use HasFactory;

    protected $table = 'operaciones_embarque';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'indicativo_requiere_instalacion_previaje',
        'cliente_id',
        'observaciones',
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
        $query = DB::table('operaciones_embarque')
            ->select(
                'id',
                'nombre',
                'fecha_inicio',
                'fecha_fin',
                'indicativo_requiere_instalacion_previaje',
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
        $query = DB::table('operaciones_embarque')
            ->select(
                'id',
                'nombre',
                'fecha_inicio',
                'fecha_fin',
                'indicativo_requiere_instalacion_previaje',
                'observaciones',
                'cliente_id',
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
        if(isset($dto['cliente'])){
            $query->where('cliente_id', $dto['cliente']);
        }
        if(isset($dto['nombre'])){
            $query->where('nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        if(isset($dto['estado'])){
            $query->where('estado', $dto['estado']);
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('nombre', $value);
                }
                if($attribute == 'observaciones'){
                    $query->orderBy('observaciones', $value);
                }
                if($attribute == 'fecha_inicio'){
                    $query->orderBy('fecha_inicio', $value);
                }
                if($attribute == 'fecha_fin'){
                    $query->orderBy('fecha_fin', $value);
                }
                if($attribute == 'indicativo_requiere_instalacion_previaje'){
                    $query->orderBy('indicativo_requiere_instalacion_previaje', $value);
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

        $operacionesEmbarque = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($operacionesEmbarque ?? [] as $operacionEmbarque){
            array_push($data, $operacionEmbarque);
        }

        $cantidadOperaciones = count($operacionesEmbarque);
        $to = isset($operacionesEmbarque) && $cantidadOperaciones > 0 ? $operacionesEmbarque->currentPage() * $operacionesEmbarque->perPage() : null;
        $to = isset($to) && isset($operacionesEmbarque) && $to > $operacionesEmbarque->total() && $cantidadOperaciones> 0 ? $operacionesEmbarque->total() : $to;
        $from = isset($to) && isset($operacionesEmbarque) && $cantidadOperaciones > 0 ?
            ( $operacionesEmbarque->perPage() > $to ? 1 : ($to - $cantidadOperaciones) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($operacionesEmbarque) && $cantidadOperaciones > 0 ? +$operacionesEmbarque->perPage() : 0,
            'pagina_actual' => isset($operacionesEmbarque) && $cantidadOperaciones > 0 ? $operacionesEmbarque->currentPage() : 1,
            'ultima_pagina' => isset($operacionesEmbarque) && $cantidadOperaciones > 0 ? $operacionesEmbarque->lastPage() : 0,
            'total' => isset($operacionesEmbarque) && $cantidadOperaciones > 0 ? $operacionesEmbarque->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $operacionEmbarque = OperacionEmbarque::find($id);
        $cliente = $operacionEmbarque->cliente;
        return [
            'id' => $operacionEmbarque->id,
            'nombre' => $operacionEmbarque->nombre,
            'fecha_inicio' => $operacionEmbarque->fecha_inicio,
            'fecha_fin' => $operacionEmbarque->fecha_fin,
            'indicativo_requiere_instalacion_previaje' => $operacionEmbarque->indicativo_requiere_instalacion_previaje,
            'observaciones' => $operacionEmbarque->observaciones,
            'estado' => $operacionEmbarque->estado,
            'usuario_creacion_id' => $operacionEmbarque->usuario_creacion_id,
            'usuario_creacion_nombre' => $operacionEmbarque->usuario_creacion_nombre,
            'usuario_modificacion_id' => $operacionEmbarque->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $operacionEmbarque->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($operacionEmbarque->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($operacionEmbarque->updated_at))->format("Y-m-d H:i:s"),
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
        $operacionEmbarque = isset($dto['id']) ? OperacionEmbarque::find($dto['id']) : new OperacionEmbarque();

        // Guardar objeto original para auditoria
        $operacionEmbarquerOriginal = $operacionEmbarque->toJson();

        $operacionEmbarque->fill($dto);
        $guardado = $operacionEmbarque->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la operacion de embarque.", $operacionEmbarque);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $operacionEmbarque->id,
            'nombre_recurso' => OperacionEmbarque::class,
            'descripcion_recurso' => $operacionEmbarque->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $operacionEmbarquerOriginal : $operacionEmbarque->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $operacionEmbarque->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return OperacionEmbarque::cargar($operacionEmbarque->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $operacionEmbarque = OperacionEmbarque::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $operacionEmbarque->id,
            'nombre_recurso' => OperacionEmbarque::class,
            'descripcion_recurso' => $operacionEmbarque->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $operacionEmbarque->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $operacionEmbarque->delete();
    }

    public static function vencidas(){
        $operaciones = DB::table('operaciones_embarque AS mt')
            ->join('clientes AS t1', 't1.id', 'mt.cliente_id')
            ->select(
                'mt.id',
                'mt.nombre'
            )
            ->where('mt.estado', 'VIG')
            ->where('t1.dias_vigencia_operacion_embarque', '>', 0)
            ->whereRaw("DATEDIFF(SYSDATE(), mt.fecha_fin) > t1.dias_vigencia_operacion_embarque");
        return $operaciones->get();
    }
}
