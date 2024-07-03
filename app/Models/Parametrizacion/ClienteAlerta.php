<?php

namespace App\Models\Parametrizacion;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enum\AccionAuditoriaEnum;
use App\Models\Seguridad\AuditoriaTabla;

class ClienteAlerta extends Model
{
   protected $table = 'clientes_alertas'; // nombre de la tabla en la base de datos

   protected $fillable = 
   [
      'cliente_id', 
      'alerta_id', 
      'numero_horas', 
      'observaciones', 
      'estado',
      'usuario_creacion_id',
      'usuario_creacion_nombre',
      'usuario_modificacion_id',
      'usuario_modificacion_nombre',
   ];
 
   public function cliente(){
      return $this->belongsTo(Cliente::class, 'cliente_id');
   }
 
   public static function getHeaders($id){
      $cliente = Cliente::find($id);
      return $cliente;
   }
 
   public static function obtenerColeccionLigera($dto) 
   {
      $query = DB::table('clientes_alertas')
         ->select(
            'clientes_alertas.id',
            'clientes_alertas.alerta_id',
            'clientes_alertas.estado',
         );
      $query->orderBy('clientes_alertas.alerta_id', 'asc');
      return $query->get();
   }
 
   public static function obtenerColeccion($dto) 
   {
      $query = DB::table('clientes_alertas')
         ->join('tipos_alertas', 'tipos_alertas.id', '=', 'clientes_alertas.alerta_id')
         ->select(
            'clientes_alertas.id',
            'tipos_alertas.nombre AS nombre_alerta', 
            'clientes_alertas.numero_horas',
            'clientes_alertas.observaciones', 
            'clientes_alertas.estado',
            'clientes_alertas.usuario_creacion_id',
            'clientes_alertas.usuario_creacion_nombre',
            'clientes_alertas.usuario_modificacion_id',
            'clientes_alertas.usuario_modificacion_nombre',
            'clientes_alertas.created_at AS fecha_creacion',
            'clientes_alertas.updated_at AS fecha_modificacion',
         )
         ->where('clientes_alertas.cliente_id', $dto['cliente_id']);
 
      if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0)
         foreach ($dto['ordenar_por'] as $attribute => $value) {
 
          if ($attribute == 'nombre_alerta') 
            $query->orderBy('tipos_alertas.nombre', $value);
 
          if ($attribute == 'numero_horas') 
            $query->orderBy('clientes_alertas.numero_horas', $value);
 
          if ($attribute == 'observaciones') 
            $query->orderBy('clientes_alertas.observaciones', $value);
 
          if ($attribute == 'estado')
            $query->orderBy('clientes_alertas.estado', $value);
 
          if ($attribute == 'usuario_creacion_nombre')
            $query->orderBy('clientes_alertas.usuario_creacion_nombre', $value);
 
          if ($attribute == 'usuario_modificacion_nombre')
            $query->orderBy('clientes_alertas.usuario_modificacion_nombre', $value);
 
          if ($attribute == 'fecha_creacion')
            $query->orderBy('clientes_alertas.created_at', $value);
 
          if ($attribute == 'fecha_modificacion')
            $query->orderBy('clientes_alertas.updated_at', $value);
        }
      else 
        $query->orderBy("clientes_alertas.updated_at", "desc");
 
      $pag = $query->paginate($dto['limite'] ?? 100);
      $datos = [];
 
      foreach ($pag ?? [] as $pagTmp)
        array_push($datos, $pagTmp);
 
      $totReg = count($pag);
      $to = isset($pag) && $totReg > 0 
        ? $pag->currentPage() * $pag->perPage() 
        : null;
      $to = isset($to) && isset($pag) && $to > $pag->total() && $totReg > 0 
        ? $pag->total() 
        : $to;
      $from = isset($to) && isset($pag) && $totReg > 0 
        ? ( $pag->perPage() > $to 
          ? 1 
          : ($to - $totReg) + 1 ) 
        : null;
 
      return [
        'datos' => $datos,
        'desde' => $from,
        'hasta' => $to,
        'por_pagina' => isset($pag) && $totReg > 0 
          ? + $pag->perPage() 
          : 0,
        'pagina_actual' => isset($pag) && $totReg > 0 
          ? $pag->currentPage() 
          : 1,
        'ultima_pagina' => isset($pag) && $totReg > 0 
          ? $pag->lastPage() 
          : 0,
        'total' => isset($pag) && $totReg > 0 
          ? $pag->total() 
          : 0
      ];
   }
 
   public static function cargar($cliente_id, $id)
   {
      $regCargar = ClienteAlerta::find($id);
      $addCargar = $regCargar->cliente;
 
      return [
         'id' => $regCargar->id,
         'cliente_id' => $regCargar->cliente_id, 
         'alerta_id' => $regCargar->alerta_id, 
         'numero_horas' => $regCargar->numero_horas, 
         'observaciones' => $regCargar->observaciones, 
         'estado' => $regCargar->estado,
         'usuario_creacion_id' => $regCargar->usuario_creacion_id,
         'usuario_creacion_nombre' => $regCargar->usuario_creacion_nombre,
         'usuario_modificacion_id' => $regCargar->usuario_modificacion_id,
         'usuario_modificacion_nombre' => $regCargar->usuario_modificacion_nombre,
         'fecha_creacion' => (new Carbon($regCargar->created_at))->format("Y-m-d H:i:s"),
         'fecha_modificacion' => (new Carbon($regCargar->updated_at))->format("Y-m-d H:i:s"),
         'cliente' => isset($addCargar) 
         ?  [ 
               'id' => $addCargar->id,
            ] 
         : null,
      ];
   }
  
   public static function modificarOCrear($cliente_id, $dto)
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
  
      // Consultar aplicación
      $reg = isset($dto['id']) 
         ? ClienteAlerta::find($dto['id']) 
         : new ClienteAlerta();
  
      // Guardar objeto original para auditoria
      $regOri = $reg->toJson();
  
      $reg->fill($dto);
      $guardado = $reg->save();
      if (!$guardado) 
         throw new Exception("Ocurrió un error al intentar guardar la Alerta.", $reg);
  
      // Guardar auditoria
      $auditoriaDto = [
         'id_recurso' => $reg->id,
         'nombre_recurso' => ClienteAlerta::class,
         'descripcion_recurso' => $reg->alerta_id,
         'accion' => isset($dto['id']) 
            ? AccionAuditoriaEnum::MODIFICAR 
            : AccionAuditoriaEnum::CREAR,
         'recurso_original' => isset($dto['id']) 
            ? $regOri 
            : $reg->toJson(),
         'recurso_resultante' => isset($dto['id']) 
            ? $reg->toJson() 
            : null
      ];
  
      AuditoriaTabla::crear($auditoriaDto);
  
      return ClienteAlerta::cargar($cliente_id, $reg->id);
   }
 
   public static function eliminar($id)
   {
      $regEli = ClienteAlerta::find($id);
 
      // Guardar auditoria
      $auditoriaDto = [
         'id_recurso' => $regEli->id,
         'nombre_recurso' => ClienteAlerta::class,
         'descripcion_recurso' => $regEli->alerta_id,
         'accion' => AccionAuditoriaEnum::ELIMINAR,
         'recurso_original' => $regEli->toJson()
      ];
      AuditoriaTabla::crear($auditoriaDto);
 
      return $regEli->delete();
   }
  
   use HasFactory;
 }