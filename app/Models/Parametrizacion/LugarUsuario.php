<?php

namespace App\Models\Parametrizacion;

use Exception;
use Carbon\Carbon;
use App\Enum\AccionAuditoriaEnum;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Parametrizacion\Lugar;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LugarUsuario extends Model
{
    protected $table = 'lugares_usuarios'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'cliente_id',
        'lugar_id',
        'usuario_id',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function lugar(){
        return $this->belongsTo(Lugar::class, 'lugar_id');
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public static function obtenerColeccionLigera($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();

        $query = DB::table('lugares_usuarios')
            ->leftJoin('clientes', 'clientes.id', 'lugares_usuarios.cliente_id')
            ->leftJoin('usuarios', 'usuarios.id', 'lugares_usuarios.usuario_id')
            ->leftJoin('lugares', 'lugares.id', 'lugares_usuarios.lugar_id')
            ->select(
                'lugares_usuarios.id',
                'usuarios.nombre as nombre',
                'usuarios.id as usuario_id',
                'usuarios.estado as usuario_estado',
                'lugares.nombre as lugar',
                'lugares.estado as lugar_estado',
                'lugares_usuarios.estado',
                'lugares.id AS lugar_id',
                'clientes.nombre AS cliente',
                'clientes.id AS cliente_id',
            );

            if($rol->type !== 'IN'){
                $query->where('clientes.id','=',$usuario->asociado_id);
            }

        $query->orderBy('usuarios.nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();

        $query = DB::table('lugares_usuarios')
            ->join('clientes', 'clientes.id', 'lugares_usuarios.cliente_id')
            ->join('lugares', 'lugares.id', 'lugares_usuarios.lugar_id')
            ->join('usuarios', 'usuarios.id', 'lugares_usuarios.usuario_id')
            ->select(
                'lugares_usuarios.id',
                'clientes.nombre AS cliente',
                'lugares.nombre AS lugar',
                'usuarios.nombre AS usuario',
                'lugares_usuarios.estado',
                'lugares_usuarios.usuario_creacion_id',
                'lugares_usuarios.usuario_creacion_nombre',
                'lugares_usuarios.usuario_modificacion_id',
                'lugares_usuarios.usuario_modificacion_nombre',
                'lugares_usuarios.created_at AS fecha_creacion',
                'lugares_usuarios.updated_at AS fecha_modificacion',
            );

        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('lugares_usuarios.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['nombre'])){
            $query->where('usuarios.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        if(isset($dto['lugar'])){
            $query->where('lugares.nombre', 'like', '%' . $dto['lugar'] . '%');
        }
        if(isset($dto['cliente'])){
            $query->where('lugares_usuarios.cliente_id', $dto['cliente']);
        }

        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'cliente'){
                    $query->orderBy('clientes.nombre', $value);
                }
                if($attribute == 'lugar'){
                    $query->orderBy('lugares.nombre', $value);
                }
                if($attribute == 'usuario'){
                    $query->orderBy('usuarios.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('lugares_usuarios.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('lugares_usuarios.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('lugares_usuarios.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('lugares_usuarios.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('lugares_usuarios.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("lugares_usuarios.updated_at", "desc");
        }

        $lugaresUsuarios = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($lugaresUsuarios ?? [] as $lugarUsuario){
            array_push($datos, $lugarUsuario);
        }

        $cantidadLugaresUsuarios = count($lugaresUsuarios);
        $to = isset($lugaresUsuarios) && $cantidadLugaresUsuarios > 0 ? $lugaresUsuarios->currentPage() * $lugaresUsuarios->perPage() : null;
        $to = isset($to) && isset($lugaresUsuarios) && $to > $lugaresUsuarios->total() && $cantidadLugaresUsuarios > 0 ? $lugaresUsuarios->total() : $to;
        $from = isset($to) && isset($lugaresUsuarios) && $cantidadLugaresUsuarios > 0 ?
            ( $lugaresUsuarios->perPage() > $to ? 1 : ($to - $cantidadLugaresUsuarios) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($lugaresUsuarios) && $cantidadLugaresUsuarios > 0 ? +$lugaresUsuarios->perPage() : 0,
            'pagina_actual' => isset($lugaresUsuarios) && $cantidadLugaresUsuarios > 0 ? $lugaresUsuarios->currentPage() : 1,
            'ultima_pagina' => isset($lugaresUsuarios) && $cantidadLugaresUsuarios > 0 ? $lugaresUsuarios->lastPage() : 0,
            'total' => isset($lugaresUsuarios) && $cantidadLugaresUsuarios > 0 ? $lugaresUsuarios->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $lugarUsuario = LugarUsuario::find($id);
        $cliente = $lugarUsuario->cliente;
        $lugar = $lugarUsuario->lugar;
        $usuario = $lugarUsuario->usuario;

        return [
            'id' => $lugarUsuario->id,
            'estado' => $lugarUsuario->estado,
            'usuario_creacion_id' => $lugarUsuario->usuario_creacion_id,
            'usuario_creacion_nombre' => $lugarUsuario->usuario_creacion_nombre,
            'usuario_modificacion_id' => $lugarUsuario->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $lugarUsuario->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($lugarUsuario->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($lugarUsuario->updated_at))->format("Y-m-d H:i:s"),
            'cliente' => isset($cliente) ? [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre
            ] : null,
            'lugar' => isset($lugar) ? [
                'id' => $lugar->id,
                'nombre' => $lugar->nombre
            ] : null,
            'usuario' => isset($usuario) ? [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre
            ] : null,
        ];
    }

    public static function modificarOCrear($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();

        if(!isset($dto['id'])){
            $dto['usuario_creacion_id'] = $usuario->id ?? ($dto['usuario_creacion_id'] ?? null);
            $dto['usuario_creacion_nombre'] = $usuario->nombre ?? ($dto['usuario_creacion_nombre'] ?? null);
        }
        if(isset($usuario) || isset($dto['usuario_modificacion_id'])){
            $dto['usuario_modificacion_id'] = $usuario->id ?? ($dto['usuario_modificacion_id'] ?? null);
            $dto['usuario_modificacion_nombre'] = $usuario->nombre ?? ($dto['usuario_modificacion_nombre'] ?? null);
        }

        // Consultar aplicación
        $lugarUsuario = isset($dto['id']) ? LugarUsuario::find($dto['id']) : new LugarUsuario();

        // Guardar objeto original para auditoria
        $lugarUsuarioOriginal = $lugarUsuario->toJson();

        $lugarUsuario->fill($dto);
        $guardado = $lugarUsuario->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar el lugar de usuario.", $lugarUsuario);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $lugarUsuario->id,
            'nombre_recurso' => LugarUsuario::class,
            'descripcion_recurso' => $lugarUsuario->lugar->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $lugarUsuarioOriginal : $lugarUsuario->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $lugarUsuario->toJson() : null
        ];

        AuditoriaTabla::crear($auditoriaDto);

        return LugarUsuario::cargar($lugarUsuario->id);
    }

    public static function eliminar($id)
    {
        $lugarUsuario = LugarUsuario::find($id);

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $lugarUsuario->id,
            'nombre_recurso' => LugarUsuario::class,
            'descripcion_recurso' => $lugarUsuario->lugar->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $lugarUsuario->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return $lugarUsuario->delete();
    }

    use HasFactory;
}
