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

class Kit extends Model
{
    protected $table = 'kits'; // nombre de la tabla en la base de datos

    protected $fillable = [ // nombres de los campos
        'cliente_id',
        'nombre',
        'estado',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public static function obtenerColeccionLigera($dto){
        $query = DB::table('kits')
            ->join('clientes', 'clientes.id', 'kits.cliente_id')
            ->select(
                'kits.id',
                'kits.nombre',
                'kits.estado',
                'clientes.nombre AS cliente',
                'clientes.id AS cliente_id',
            );
        $query->orderBy('kits.nombre', 'asc');
        return $query->get();
    }

    public static function obtenerColeccion($dto){
        $user = Auth::user();
        $usuario = $user->usuario();
        $rol = $user->rol();
        $query = DB::table('kits')
            ->join('clientes', 'clientes.id', 'kits.cliente_id')
            ->select(
                'kits.id',
                'kits.nombre',
                'clientes.nombre AS cliente',
                'clientes.id AS cliente_id',
                'kits.estado',
                'kits.usuario_creacion_id',
                'kits.usuario_creacion_nombre',
                'kits.usuario_modificacion_id',
                'kits.usuario_modificacion_nombre',
                'kits.created_at AS fecha_creacion',
                'kits.updated_at AS fecha_modificacion',
            );
        if($rol->type !== 'IN' || ($rol->type === 'IN' && !isset($dto['cliente']))){
            $query->where('kits.cliente_id', $usuario->asociado_id);
        }
        if(isset($dto['nombre'])){
            $query->where('kits.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        if(isset($dto['cliente'])){
            $query->where('kits.cliente_id', $dto['cliente']);
        }
        if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
            foreach ($dto['ordenar_por'] as $attribute => $value){
                if($attribute == 'nombre'){
                    $query->orderBy('kits.nombre', $value);
                }
                if($attribute == 'cliente'){
                    $query->orderBy('clientes.nombre', $value);
                }
                if($attribute == 'estado'){
                    $query->orderBy('kits.estado', $value);
                }
                if($attribute == 'usuario_creacion_nombre'){
                    $query->orderBy('kits.usuario_creacion_nombre', $value);
                }
                if($attribute == 'usuario_modificacion_nombre'){
                    $query->orderBy('kits.usuario_modificacion_nombre', $value);
                }
                if($attribute == 'fecha_creacion'){
                    $query->orderBy('kits.created_at', $value);
                }
                if($attribute == 'fecha_modificacion'){
                    $query->orderBy('kits.updated_at', $value);
                }
            }
        }else{
            $query->orderBy("kits.updated_at", "desc");
        }

        $kits = $query->paginate($dto['limite'] ?? 100);
        $datos = [];

        foreach ($kits ?? [] as $kit){
            array_push($datos, $kit);
        }

        $cantidadKits = count($kits);
        $to = isset($kits) && $cantidadKits > 0 ? $kits->currentPage() * $kits->perPage() : null;
        $to = isset($to) && isset($kits) && $to > $kits->total() && $cantidadKits > 0 ? $kits->total() : $to;
        $from = isset($to) && isset($kits) && $cantidadKits > 0 ?
            ( $kits->perPage() > $to ? 1 : ($to - $cantidadKits) + 1 )
            : null;

        return [
            'datos' => $datos,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($kits) && $cantidadKits > 0 ? +$kits->perPage() : 0,
            'pagina_actual' => isset($kits) && $cantidadKits > 0 ? $kits->currentPage() : 1,
            'ultima_pagina' => isset($kits) && $cantidadKits > 0 ? $kits->lastPage() : 0,
            'total' => isset($kits) && $cantidadKits > 0 ? $kits->total() : 0
        ];
    }

    public static function cargar($id)
    {
        $kit = Kit::find($id);
        $cliente = $kit->cliente;
        return [
            'id' => $kit->id,
            'cliente_id' => $kit->cliente_id,
            'nombre' => $kit->nombre,
            'estado' => $kit->estado,
            'usuario_creacion_id' => $kit->usuario_creacion_id,
            'usuario_creacion_nombre' => $kit->usuario_creacion_nombre,
            'usuario_modificacion_id' => $kit->usuario_modificacion_id,
            'usuario_modificacion_nombre' => $kit->usuario_modificacion_nombre,
            'fecha_creacion' => (new Carbon($kit->created_at))->format("Y-m-d H:i:s"),
            'fecha_modificacion' => (new Carbon($kit->updated_at))->format("Y-m-d H:i:s"),
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

        if(!isset($dto['id'])){
            $dto['usuario_creacion_id'] = $usuario->id ?? ($dto['usuario_creacion_id'] ?? null);
            $dto['usuario_creacion_nombre'] = $usuario->nombre ?? ($dto['usuario_creacion_nombre'] ?? null);
        }
        if(isset($usuario) || isset($dto['usuario_modificacion_id'])){
            $dto['usuario_modificacion_id'] = $usuario->id ?? ($dto['usuario_modificacion_id'] ?? null);
            $dto['usuario_modificacion_nombre'] = $usuario->nombre ?? ($dto['usuario_modificacion_nombre'] ?? null);
        }

        // Consultar aplicación
        $kit = isset($dto['id']) ? Kit::find($dto['id']) : new Kit();

        // Guardar objeto original para auditoria
        $kitOriginal = $kit->toJson();

        $kit->fill($dto);
        $guardado = $kit->save();
        if(!$guardado){
            throw new Exception("Ocurrió un error al intentar guardar la aplicación.", $kit);
        }

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $kit->id,
            'nombre_recurso' => Kit::class,
            'descripcion_recurso' => $kit->nombre,
            'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
            'recurso_original' => isset($dto['id']) ? $kitOriginal : $kit->toJson(),
            'recurso_resultante' => isset($dto['id']) ? $kit->toJson() : null
        ];
        
        AuditoriaTabla::crear($auditoriaDto);
        
        return Kit::cargar($kit->id);
    }

    public static function eliminar($id)
    {
        $kit = Kit::find($id);

        // Guardar auditoria
        $auditoriaDto = [
            'id_recurso' => $kit->id,
            'nombre_recurso' => Kit::class,
            'descripcion_recurso' => $kit->nombre,
            'accion' => AccionAuditoriaEnum::ELIMINAR,
            'recurso_original' => $kit->toJson()
        ];
        AuditoriaTabla::crear($auditoriaDto);

        return $kit->delete();
    }

    use HasFactory;
}
