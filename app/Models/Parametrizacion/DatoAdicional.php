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

class DatoAdicional extends Model
{
    use HasFactory;

    protected $table = 'datos_adicionales';

    protected $fillable = [
        'nombre',
        'numero_consecutivo',
        'nivel',
        'tipo_dato',
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
        $query = DB::table('datos_adicionales')
            ->select(
                'id',
                'nombre',
                'nivel',
                'tipo_dato',
                'cliente_id',
                'estado',
            )
            ->where('cliente_id', $usuario->asociado_id);

        $query->orderBy('numero_consecutivo', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('datos_adicionales')
            ->select(
                'id',
                'nombre',
                'numero_consecutivo',
                'nivel',
                'tipo_dato',
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
                if($attribute == 'numero_consecutivo'){
                    $query->orderBy('numero_consecutivo', $value);
                }
                if($attribute == 'nivel'){
                    $query->orderBy('nivel', $value);
                }
                if($attribute == 'tipo_dato'){
                    $query->orderBy('tipo_dato', $value);
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

        $datosAdicionales = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($datosAdicionales ?? [] as $datoAdicional){
            array_push($data, $datoAdicional);
        }

        $cantidadDatosAdicionales = count($datosAdicionales);
        $to = isset($datosAdicionales) && $cantidadDatosAdicionales > 0 ? $datosAdicionales->currentPage() * $datosAdicionales->perPage() : null;
        $to = isset($to) && isset($datosAdicionales) && $to > $datosAdicionales->total() && $cantidadDatosAdicionales> 0 ? $datosAdicionales->total() : $to;
        $from = isset($to) && isset($datosAdicionales) && $cantidadDatosAdicionales > 0 ?
            ( $datosAdicionales->perPage() > $to ? 1 : ($to - $cantidadDatosAdicionales) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($datosAdicionales) && $cantidadDatosAdicionales > 0 ? +$datosAdicionales->perPage() : 0,
            'pagina_actual' => isset($datosAdicionales) && $cantidadDatosAdicionales > 0 ? $datosAdicionales->currentPage() : 1,
            'ultima_pagina' => isset($datosAdicionales) && $cantidadDatosAdicionales > 0 ? $datosAdicionales->lastPage() : 0,
            'total' => isset($datosAdicionales) && $cantidadDatosAdicionales > 0 ? $datosAdicionales->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $datoAdicional = DatoAdicional::find($id);
        $cliente = $datoAdicional->cliente;
        return [
            'id' => $datoAdicional->id,
            'nombre' => $datoAdicional->nombre,
            'numero_consecutivo' => $datoAdicional->numero_consecutivo,
            'nivel' => $datoAdicional->nivel,
            'tipo_dato' => $datoAdicional->tipo_dato,
            'estado' => $datoAdicional->estado,
            'usuario_creacion_id' => $datoAdicional->usuario_creacion_id,
            'usuario_creacion_nombre' => $datoAdicional->usuario_creacion_nombre,
            'usuario_modificacion_id' => $datoAdicional->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $datoAdicional->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($datoAdicional->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($datoAdicional->updated_at))->format("Y-m-d H:i:s"),
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
        $datoAdicional = isset($dto['id']) ? DatoAdicional::find($dto['id']) : new DatoAdicional();

        // Guardar objeto original para auditoria
        $datoAdicionalOriginal = $datoAdicional->toJson();

        $datoAdicional->fill($dto);
        $guardado = $datoAdicional->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el dato adicional.", $datoAdicional);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $datoAdicional->id,
            'nombre_recurso' => DatoAdicional::class,
            'descripcion_recurso' => $datoAdicional->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $datoAdicionalOriginal : $datoAdicional->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $datoAdicional->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return DatoAdicional::cargar($datoAdicional->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $datoAdicional = DatoAdicional::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $datoAdicional->id,
            'nombre_recurso' => DatoAdicional::class,
            'descripcion_recurso' => $datoAdicional->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $datoAdicional->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $datoAdicional->delete();
    }
}
