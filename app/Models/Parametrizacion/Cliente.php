<?php

namespace App\Models\Parametrizacion;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'indicativo_lectura_sellos_externos',
        'indicativo_instalacion_contenedor',
        'indicativo_contenedor_exclusivo',
        'indicativo_operaciones_embarque',
        'indicativo_instalacion_automatica',
        'indicativo_registro_lugar_instalacion',
        'indicativo_registro_zona_instalacion',
        'indicativo_asignacion_serial_automatica',
        'indicativo_documento_referencia',
        'asignacion_sellos_lectura',
        'asociado_id',
        'dias_vigencia_operacion_embarque',
        'observaciones',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public static function obtenerColeccionLigera($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();

        $query = DB::table('clientes')
            ->select(
                'id',
                'nombre',
                'indicativo_lectura_sellos_externos',
                'indicativo_instalacion_contenedor',
                'indicativo_contenedor_exclusivo',
                'indicativo_operaciones_embarque',
                'indicativo_instalacion_automatica',
                'indicativo_registro_lugar_instalacion',
                'indicativo_registro_zona_instalacion',
                'indicativo_asignacion_serial_automatica',
                'indicativo_documento_referencia',
                'asignacion_sellos_lectura',
                'dias_vigencia_operacion_embarque',
                'estado',
                'asociado_id',
            );
        if($rol->type !== 'IN'){
            $query->where('id', $usuario->asociado_id);
        }
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();

        $query = Cliente::select(
            'clientes.id',
            'clientes.nombre',
            'clientes.indicativo_lectura_sellos_externos',
            'clientes.dias_vigencia_operacion_embarque',
            'clientes.indicativo_instalacion_contenedor',
            'clientes.indicativo_contenedor_exclusivo',
            'clientes.indicativo_operaciones_embarque',
            'clientes.indicativo_instalacion_automatica',
            'clientes.indicativo_registro_lugar_instalacion',
            'clientes.indicativo_registro_zona_instalacion',
            'clientes.indicativo_asignacion_serial_automatica',
            'clientes.indicativo_documento_referencia',
            'clientes.asignacion_sellos_lectura',
            'clientes.asociado_id',
            'clientes.observaciones',
            'clientes.estado',
            'clientes.usuario_creacion_id',
            'clientes.usuario_creacion_nombre',
            'clientes.usuario_modificacion_id',
            'clientes.usuario_modificacion_nombre',
            'clientes.created_at AS fecha_creacion',
            'clientes.updated_at AS fecha_modificacion'
        );

        if($rol->type !== 'IN'){
            $query->where('id', $usuario->asociado_id);
        }

        if(isset($dto['nombre'])){
            $query->where('clientes.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('clientes.nombre', $value);
                }
                if($attribute == 'indicativo_lectura_sellos_externos'){
                    $query->orderBy('clientes.indicativo_lectura_sellos_externos', $value);
                }
                if($attribute == 'indicativo_instalacion_contenedor'){
                    $query->orderBy('clientes.indicativo_instalacion_contenedor', $value);
                }
                if($attribute == 'indicativo_contenedor_exclusivo'){
                    $query->orderBy('clientes.indicativo_contenedor_exclusivo', $value);
                }
                if($attribute == 'indicativo_operaciones_embarque'){
                    $query->orderBy('clientes.indicativo_operaciones_embarque', $value);
                }
                if($attribute == 'indicativo_instalacion_automatica'){
                    $query->orderBy('clientes.indicativo_instalacion_automatica', $value);
                }
                if($attribute == 'indicativo_registro_lugar_instalacion'){
                    $query->orderBy('clientes.indicativo_registro_lugar_instalacion', $value);
                }
                if($attribute == 'indicativo_registro_zona_instalacion'){
                    $query->orderBy('clientes.indicativo_registro_zona_instalacion', $value);
                }
                if($attribute == 'dias_vigencia_operacion_embarque'){
                    $query->orderBy('clientes.dias_vigencia_operacion_embarque', $value);
                }
                if($attribute == 'indicativo_asignacion_serial_automatica'){
                    $query->orderBy('clientes.indicativo_asignacion_serial_automatica', $value);
                }
                if($attribute == 'indicativo_documento_referencia'){
                    $query->orderBy('clientes.indicativo_documento_referencia', $value);
                }
                if($attribute == 'asignacion_sellos_lectura'){
                    $query->orderBy('clientes.asignacion_sellos_lectura', $value);
                }
                if($attribute == 'asociado_id'){
                    $query->orderBy('clientes.asociado_id', $value);
                }
                if($attribute == 'observaciones'){
                    $query->orderBy('clientes.observaciones', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('clientes.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('clientes.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('clientes.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('clientes.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('clientes.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("clientes.updated_at", "desc");
        }

        $clientes = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($clientes ?? [] as $cliente){
            array_push($data, $cliente);
        }

        $cantidadClientes = count($clientes);
        $to = isset($clientes) && $cantidadClientes > 0 ? $clientes->currentPage() * $clientes->perPage() : null;
        $to = isset($to) && isset($clientes) && $to > $clientes->total() && $cantidadClientes> 0 ? $clientes->total() : $to;
        $from = isset($to) && isset($clientes) && $cantidadClientes > 0 ?
            ( $clientes->perPage() > $to ? 1 : ($to - $cantidadClientes) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($clientes) && $cantidadClientes > 0 ? +$clientes->perPage() : 0,
            'pagina_actual' => isset($clientes) && $cantidadClientes > 0 ? $clientes->currentPage() : 1,
            'ultima_pagina' => isset($clientes) && $cantidadClientes > 0 ? $clientes->lastPage() : 0,
            'total' => isset($clientes) && $cantidadClientes > 0 ? $clientes->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $cliente = Cliente::find($id);

        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'indicativo_lectura_sellos_externos' => $cliente->indicativo_lectura_sellos_externos,
            'indicativo_instalacion_contenedor' => $cliente->indicativo_instalacion_contenedor,
            'dias_vigencia_operacion_embarque' => $cliente->dias_vigencia_operacion_embarque,
            'indicativo_contenedor_exclusivo' => $cliente->indicativo_contenedor_exclusivo,
            'indicativo_operaciones_embarque' => $cliente->indicativo_operaciones_embarque,
            'indicativo_instalacion_automatica' => $cliente->indicativo_instalacion_automatica,
            'indicativo_registro_lugar_instalacion' => $cliente->indicativo_registro_lugar_instalacion,
            'indicativo_registro_zona_instalacion' => $cliente->indicativo_registro_zona_instalacion,
            'indicativo_asignacion_serial_automatica' => $cliente->indicativo_asignacion_serial_automatica,
            'indicativo_documento_referencia' => $cliente->indicativo_documento_referencia,
            'asignacion_sellos_lectura' => $cliente->asignacion_sellos_lectura,
            'observaciones' => $cliente->observaciones,
            'asociado_id' => $cliente->asociado_id,
            'estado' => $cliente->estado,
            'usuario_creacion_id' => $cliente->usuario_creacion_id,
            'usuario_creacion_nombre' => $cliente->usuario_creacion_nombre,
            'usuario_modificacion_id' => $cliente->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $cliente->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($cliente->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($cliente->updated_at))->format("Y-m-d H:i:s")
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
        $cliente = isset($dto['id']) ? Cliente::find($dto['id']) : new Cliente();

        // Guardar objeto original para auditoria
        $clienteOriginal = $cliente->toJson();

        $cliente->fill($dto);
        $guardado = $cliente->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el cliente.", $cliente);
        }

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $cliente->id,
            'nombre_recurso' => Cliente::class,
            'descripcion_recurso' => $cliente->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $clienteOriginal : $cliente->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $cliente->toJson() : null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return Cliente::cargar($cliente->id);
    }

    public static function eliminar($id)
    {
        // Connsultar el objeto
        $cliente = Cliente::find($id);

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $cliente->id,
            'nombre_recurso' => Cliente::class,
            'descripcion_recurso' => $cliente->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $cliente->toJson()
        );
        AuditoriaTabla::crear($auditoriaDto);

        return $cliente->delete();
    }
}
