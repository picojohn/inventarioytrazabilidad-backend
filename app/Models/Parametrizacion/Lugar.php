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

class Lugar extends Model
{
   protected $table = 'lugares'; // nombre de la tabla en la base de datos

   protected $fillable =
   [
      'nombre',
      'direccion',
      'telefono',
      'cliente_id',
      'tipo_lugar',
      'indicativo_lugar_remision',
      'indicativo_lugar_instalacion',
      'indicativo_lugar_inspeccion',
      'codigo_externo_lugar',
      'geocerca_id',
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
      $query = DB::table('lugares')
        ->leftJoin('clientes','clientes.id','=','lugares.cliente_id')
        ->select(
        'lugares.id',
        'lugares.nombre',
        'lugares.indicativo_lugar_remision',
        'lugares.indicativo_lugar_instalacion',
        'lugares.indicativo_lugar_inspeccion',
        'lugares.cliente_id',
        'lugares.estado',
        'clientes.nombre AS cliente',
        );

      if(isset($dto['cliente'])){
         $query->where('lugares.cliente_id', $dto['cliente']);
      }

      $query->orderBy('lugares.nombre', 'asc');
      return $query->get();
   }

   public static function obtenerColeccion($dto)
   {
      $query = DB::table('lugares')
         ->select(
            'lugares.id',
            'lugares.nombre',
            'lugares.direccion',
            'lugares.telefono',
            DB::Raw("CASE lugares.tipo_lugar
                     WHEN 'SD' THEN 'Sede/ oficina'
                     WHEN 'PA' THEN 'Patio'
                     WHEN 'FI' THEN 'Finca'
                     WHEN 'PU' THEN 'Puerto'
                     ELSE '' END AS tipo_lugar"),
            'lugares.indicativo_lugar_remision',
            'lugares.indicativo_lugar_instalacion',
            'lugares.indicativo_lugar_inspeccion',
            'lugares.codigo_externo_lugar',
            'lugares.geocerca_id',
            'lugares.observaciones',
            'lugares.estado',
            'lugares.usuario_creacion_id',
            'lugares.usuario_creacion_nombre',
            'lugares.usuario_modificacion_id',
            'lugares.usuario_modificacion_nombre',
            'lugares.created_at AS fecha_creacion',
            'lugares.updated_at AS fecha_modificacion',
         )
         ->where('lugares.cliente_id', $dto['cliente_id']);

      if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0)
         foreach ($dto['ordenar_por'] as $attribute => $value) {

            if ($attribute == 'nombre_lugar')
               $query->orderBy('lugares.nombre', $value);

            if ($attribute == 'direccion')
               $query->orderBy('lugares.direccion', $value);

            if ($attribute == 'telefono')
               $query->orderBy('lugares.telefono', $value);

            if ($attribute == 'tipo_lugar')
               $query->orderBy('lugares.tipo_lugar', $value);

            if ($attribute == 'indicativo_lugar_remision')
               $query->orderBy('lugares.indicativo_lugar_remision', $value);

            if ($attribute == 'indicativo_lugar_instalacion')
               $query->orderBy('lugares.indicativo_lugar_instalacion', $value);

            if ($attribute == 'indicativo_lugar_inspeccion')
               $query->orderBy('lugares.indicativo_lugar_inspeccion', $value);

            if ($attribute == 'codigo_externo_lugar')
               $query->orderBy('lugares.codigo_externo_lugar', $value);

            if ($attribute == 'geocerca_id')
               $query->orderBy('lugares.geocerca_id', $value);

            if ($attribute == 'observaciones')
               $query->orderBy('lugares.observaciones', $value);

            if ($attribute == 'estado')
               $query->orderBy('lugares.estado', $value);

            if ($attribute == 'usuario_creacion_nombre')
               $query->orderBy('lugares.usuario_creacion_nombre', $value);

            if ($attribute == 'usuario_modificacion_nombre')
               $query->orderBy('lugares.usuario_modificacion_nombre', $value);

            if ($attribute == 'fecha_creacion')
               $query->orderBy('lugares.created_at', $value);

            if ($attribute == 'fecha_modificacion')
               $query->orderBy('lugares.updated_at', $value);
         }
      else
         $query->orderBy("lugares.updated_at", "desc");

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
      $regCargar = Lugar::find($id);
      $addCargar = $regCargar->cliente;

      return [
         'id' => $regCargar->id,
         'nombre' => $regCargar->nombre,
         'direccion' => $regCargar->direccion,
         'telefono' => $regCargar->telefono,
         'cliente_id' => $regCargar->cliente_id,
         'tipo_lugar' => $regCargar->tipo_lugar,
         'indicativo_lugar_remision' => $regCargar->indicativo_lugar_remision,
         'indicativo_lugar_instalacion' => $regCargar->indicativo_lugar_instalacion,
         'indicativo_lugar_inspeccion' => $regCargar->indicativo_lugar_inspeccion,
         'codigo_externo_lugar' => $regCargar->codigo_externo_lugar,
         'geocerca_id' => $regCargar->geocerca_id,
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
         ? Lugar::find($dto['id'])
         : new Lugar();

      // Guardar objeto original para auditoria
      $regOri = $reg->toJson();

      $reg->fill($dto);
      $guardado = $reg->save();
      if (!$guardado)
         throw new Exception("Ocurrió un error al intentar guardar el Lugar.", $reg);

      // Guardar auditoria
      $auditoriaDto = [
         'id_recurso' => $reg->id,
         'nombre_recurso' => Lugar::class,
         'descripcion_recurso' => $reg->nombre,
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

      return Lugar::cargar($cliente_id, $reg->id);
   }

   public static function eliminar($id)
   {
      $regEli = Lugar::find($id);

      // Guardar auditoria
      $auditoriaDto = [
         'id_recurso' => $regEli->id,
         'nombre_recurso' => Lugar::class,
         'descripcion_recurso' => $regEli->nombre,
         'accion' => AccionAuditoriaEnum::ELIMINAR,
         'recurso_original' => $regEli->toJson()
      ];
      AuditoriaTabla::crear($auditoriaDto);

      return $regEli->delete();
   }

   use HasFactory;
}
