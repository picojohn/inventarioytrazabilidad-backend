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
use App\Models\Parametrizacion\TipoContenedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contenedor extends Model
{
    protected $table = 'contenedores';

    protected $fillable = [
        'numero_contenedor',
        'digito_verificacion',
        'cliente_id',
        'tipo_contenedor_id',
        'indicativo_contenedor_reparacion',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoContenedor(){
        return $this->belongsTo(TipoContenedor::class, 'tipo_contenedor_id');
    }

    public static function obtenerColeccionLigera($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('contenedores')
            ->select(
                'id',
                DB::raw("CONCAT(numero_contenedor, '-', digito_verificacion) AS nombre"),
                'numero_contenedor',
                'digito_verificacion',
                'estado',
            );
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('cliente_id', $dto['cliente']);
        }
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('contenedores')
            ->leftJoin('clientes', 'clientes.id', 'contenedores.cliente_id')
            ->leftJoin('tipos_contenedores', 'tipos_contenedores.id', 'contenedores.tipo_contenedor_id')
            ->select(
                'contenedores.id',
                DB::raw("
                    CONCAT(
                        CONCAT(contenedores.numero_contenedor, '-'), 
                        contenedores.digito_verificacion
                    ) AS numero_contenedor"
                ),
                'clientes.nombre AS cliente',
                'tipos_contenedores.nombre AS tipo_contenedor',
                'contenedores.indicativo_contenedor_reparacion',
                'contenedores.estado',
                'contenedores.usuario_creacion_id',
                'contenedores.usuario_creacion_nombre',
                'contenedores.usuario_modificacion_id',
                'contenedores.usuario_modificacion_nombre',
                'contenedores.created_at AS fecha_creacion',
                'contenedores.updated_at AS fecha_modificacion'
            );
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('contenedores.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['cliente'])){
            $query->where('contenedores.cliente_id', $dto['cliente']);
        }
        if(isset($dto['nombre'])){
            $query->where('contenedores.numero_contenedor', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'numero_contenedor'){
                    $query->orderBy('contenedores.numero_contenedor', $value);
                }
                if($attribute == 'indicativo_contenedor_reparacion'){
                    $query->orderBy('contenedores.indicativo_contenedor_reparacion', $value);
                }
                if($attribute == 'cliente'){
                    $query->orderBy('clientes.nombre', $value);
                }
                if($attribute == 'tipo_contenedor'){
                    $query->orderBy('tipos_contenedores.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('contenedores.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('contenedores.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('contenedores.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('contenedores.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('contenedores.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("contenedores.updated_at", "desc");
        }

        $contenedores = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($contenedores ?? [] as $contenedor){
            array_push($data, $contenedor);
        }

        $cantidadContenedores = count($contenedores);
        $to = isset($contenedores) && $cantidadContenedores > 0 ? $contenedores->currentPage() * $contenedores->perPage() : null;
        $to = isset($to) && isset($contenedores) && $to > $contenedores->total() && $cantidadContenedores> 0 ? $contenedores->total() : $to;
        $from = isset($to) && isset($contenedores) && $cantidadContenedores > 0 ?
            ( $contenedores->perPage() > $to ? 1 : ($to - $cantidadContenedores) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($contenedores) && $cantidadContenedores > 0 ? +$contenedores->perPage() : 0,
            'pagina_actual' => isset($contenedores) && $cantidadContenedores > 0 ? $contenedores->currentPage() : 1,
            'ultima_pagina' => isset($contenedores) && $cantidadContenedores > 0 ? $contenedores->lastPage() : 0,
            'total' => isset($contenedores) && $cantidadContenedores > 0 ? $contenedores->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $contenedor = Contenedor::find($id);
        $cliente = $contenedor->cliente;
        $tipoContenedor = $contenedor->tipoContenedor;

        return [
            'id' => $contenedor->id,
            'numero_contenedor' => $contenedor->numero_contenedor,
            'digito_verificacion' => $contenedor->digito_verificacion,
            'indicativo_contenedor_reparacion' => $contenedor->indicativo_contenedor_reparacion,
            'estado' => $contenedor->estado,
            'usuario_creacion_id' => $contenedor->usuario_creacion_id,
            'usuario_creacion_nombre' => $contenedor->usuario_creacion_nombre,
            'usuario_modificacion_id' => $contenedor->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $contenedor->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($contenedor->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($contenedor->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
            'tipo_contenedor' => isset($tipoContenedor) ? [
                'id' => $tipoContenedor->id,
                'nombre' => $tipoContenedor->nombre
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
        $contenedor = isset($dto['id']) ? Contenedor::find($dto['id']) : new Contenedor();

        // Guardar objeto original para auditoria
        $tipoAlertasOriginal = $contenedor->toJson();

        $contenedor->fill($dto);
        $guardado = $contenedor->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el contenedor.", $contenedor);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $contenedor->id,
            'nombre_recurso' => Contenedor::class,
            'descripcion_recurso' => $contenedor->numero_contenedor,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $tipoAlertasOriginal : $contenedor->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $contenedor->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return Contenedor::cargar($contenedor->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $contenedor = Contenedor::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $contenedor->id,
            'nombre_recurso' => Contenedor::class,
            'descripcion_recurso' => $contenedor->numero_contenedor,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $contenedor->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $contenedor->delete();
    }

    public static function digitoVerificacion($identificacion){
        $wordsValue = [
            'A' => 10,
            'B' => 12,
            'C' => 13,
            'D' => 14,
            'E' => 15,
            'F' => 16,
            'G' => 17,
            'H' => 18,
            'I' => 19,
            'J' => 20,
            'K' => 21,
            'L' => 23,
            'M' => 24,
            'N' => 25,
            'O' => 26,
            'P' => 27,
            'Q' => 28,
            'R' => 29,
            'S' => 30,
            'T' => 31,
            'U' => 32,
            'V' => 34,
            'W' => 35,
            'X' => 36,
            'Y' => 27,
            'Z' => 38,
        ];
        $accumulator = 0;

        for($i = 0; $i < strlen($identificacion); $i++){
            if($i <= 3){
                $accumulator += $wordsValue[$identificacion[$i]]*2**$i;
            } else {
                $accumulator += $identificacion[$i]*2**$i;
            }
        }

        $extra = floor($accumulator/11)*11;
        $diferencia = $accumulator-$extra;
        $digito = $diferencia == 10 ? 0 :  $diferencia;
        return $digito;
    }

    use HasFactory;
}
