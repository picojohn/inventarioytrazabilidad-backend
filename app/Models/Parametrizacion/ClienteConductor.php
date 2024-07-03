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

class ClienteConductor extends Model
{
   protected $table = 'conductores'; // nombre de la tabla en la base de datos

   protected $fillable = 
   [
      'cliente_id', 
      'tipo_documento_id', 
      'numero_documento', 
      'nombre_conductor', 
      'indicativo_conductor_empresa', 
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
      $query = DB::table('conductores')
         ->select(
            'conductores.id',
            'conductores.tipo_documento_id', 
            'conductores.numero_documento',
            'conductores.nombre_conductor',
            'conductores.estado',
         )->where('conductores.cliente_id', $dto['cliente_id']);
      $query->orderBy('conductores.nombre_conductor', 'asc');
      return $query->get();
   }

   public static function obtenerColeccion($dto) 
   {
      $query = DB::table('conductores')
         ->select(
            'conductores.id',
            'conductores.tipo_documento_id', 
            'conductores.numero_documento', 
            'conductores.nombre_conductor', 
            'conductores.indicativo_conductor_empresa',
            'conductores.estado',
            'conductores.usuario_creacion_id',
            'conductores.usuario_creacion_nombre',
            'conductores.usuario_modificacion_id',
            'conductores.usuario_modificacion_nombre',
            'conductores.created_at AS fecha_creacion',
            'conductores.updated_at AS fecha_modificacion',
         )
         ->where('conductores.cliente_id', $dto['cliente_id']);

      if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0)
         foreach ($dto['ordenar_por'] as $attribute => $value) {

            if ($attribute == 'tipo_documento_id')
               $query->orderBy('conductores.tipo_documento_id', $value);

            if ($attribute == 'numero_documento') 
               $query->orderBy('conductores.numero_documento', $value);

            if ($attribute == 'nombre_conductor') 
               $query->orderBy('conductores.nombre_conductor', $value);

            if ($attribute == 'indicativo_conductor_empresa') 
               $query->orderBy('conductores.indicativo_conductor_empresa', $value);

            if ($attribute == 'estado')
               $query->orderBy('conductores.estado', $value);

            if ($attribute == 'usuario_creacion_nombre')
               $query->orderBy('conductores.usuario_creacion_nombre', $value);

            if ($attribute == 'usuario_modificacion_nombre')
               $query->orderBy('conductores.usuario_modificacion_nombre', $value);

            if ($attribute == 'fecha_creacion')
               $query->orderBy('conductores.created_at', $value);

            if ($attribute == 'fecha_modificacion')
               $query->orderBy('conductores.updated_at', $value);
         }
      else 
         $query->orderBy("conductores.updated_at", "desc");

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
      $regCargar = ClienteConductor::find($id);
      $addCargar = $regCargar->cliente;

      return [
         'id' => $regCargar->id,
         'cliente_id' => $regCargar->cliente_id, 
         'tipo_documento_id' => $regCargar->tipo_documento_id, 
         'numero_documento' => $regCargar->numero_documento, 
         'nombre_conductor' => $regCargar->nombre_conductor, 
         'indicativo_conductor_empresa' => $regCargar->indicativo_conductor_empresa, 
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
         ? ClienteConductor::find($dto['id']) 
         : new ClienteConductor();
 
      // Guardar objeto original para auditoria
      $regOri = $reg->toJson();
 
      $reg->fill($dto);
      $guardado = $reg->save();
      if (!$guardado) 
         throw new Exception("Ocurrió un error al intentar guardar el Conductor.", $reg);
 
      // Guardar auditoria
      $auditoriaDto = [
         'id_recurso' => $reg->id,
         'nombre_recurso' => ClienteConductor::class,
         'descripcion_recurso' => $reg->nombre_conductor,
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
 
      return ClienteConductor::cargar($cliente_id, $reg->id);
   }

   public static function eliminar($id)
   {
      $regEli = ClienteConductor::find($id);

      // Guardar auditoria
      $auditoriaDto = [
         'id_recurso' => $regEli->id,
         'nombre_recurso' => ClienteConductor::class,
         'descripcion_recurso' => $regEli->nombre_conductor,
         'accion' => AccionAuditoriaEnum::ELIMINAR,
         'recurso_original' => $regEli->toJson()
      ];
      AuditoriaTabla::crear($auditoriaDto);

      return $regEli->delete();
   }
 
   use HasFactory;
}