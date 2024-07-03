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
use App\Models\Parametrizacion\ClaseInspeccion;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClaseInspeccionListaFotos extends Model
{
  protected $table = 'clases_inspeccion_lista_fotos'; // nombre de la tabla en la base de datos

  protected $fillable =
    [
      'cliente_id',
      'clase_inspeccion_id',
      'nombre',
      'numero_orden',
      'estado',
      'usuario_creacion_id',
      'usuario_creacion_nombre',
      'usuario_modificacion_id',
      'usuario_modificacion_nombre',
    ];

  public function cliente()
  {
    return $this->belongsTo(Cliente::class, 'cliente_id');
  }

  public function claseInspeccion()
  {
    return $this->belongsTo(ClaseInspeccion::class, 'clase_inspeccion_id');
  }

  public static function getHeaders($id)
  {
    $claseInspeccion = ClaseInspeccion::find($id);
    $cliente = $claseInspeccion->cliente;
    return [
      "cliente" => $cliente,
      "clase_inspeccion" => $claseInspeccion
    ];
  }

  public static function obtenerColeccionLigera($dto)
  {
    $query = DB::table('clases_inspeccion_lista_fotos')
      ->select(
        'clases_inspeccion_lista_fotos.id',
        'clases_inspeccion_lista_fotos.cliente_id',
        'clases_inspeccion_lista_fotos.nombre',
        'clases_inspeccion_lista_fotos.numero_orden',
        'clases_inspeccion_lista_fotos.estado',
      )->where('clases_inspeccion_lista_fotos.clase_inspeccion_id', $dto['clase_inspeccion_id']);
    $query->orderBy('clases_inspeccion_lista_fotos.nombre', 'asc');
    return $query->get();
  }

  public static function obtenerColeccion($dto)
  {
    $query = DB::table('clases_inspeccion_lista_fotos')
      ->join('clientes', 'clientes.id', 'clases_inspeccion_lista_fotos.cliente_id')
      ->join('clases_inspeccion', 'clases_inspeccion.id', 'clases_inspeccion_lista_fotos.clase_inspeccion_id')
      ->select(
        'clases_inspeccion_lista_fotos.id',
        'clases_inspeccion_lista_fotos.cliente_id',
        'clases_inspeccion_lista_fotos.clase_inspeccion_id',
        'clientes.nombre as cliente',
        'clases_inspeccion.nombre as clase_inspeccion',
        'clases_inspeccion_lista_fotos.nombre',
        'clases_inspeccion_lista_fotos.numero_orden',
        'clases_inspeccion_lista_fotos.estado',
        'clases_inspeccion_lista_fotos.usuario_creacion_id',
        'clases_inspeccion_lista_fotos.usuario_creacion_nombre',
        'clases_inspeccion_lista_fotos.usuario_modificacion_id',
        'clases_inspeccion_lista_fotos.usuario_modificacion_nombre',
        'clases_inspeccion_lista_fotos.created_at AS fecha_creacion',
        'clases_inspeccion_lista_fotos.updated_at AS fecha_modificacion',
      )->where('clases_inspeccion_lista_fotos.clase_inspeccion_id', $dto['clase_inspeccion_id']);

    if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0)
      foreach ($dto['ordenar_por'] as $attribute => $value) {

        if ($attribute == 'nombre')
          $query->orderBy('clases_inspeccion_lista_fotos.nombre', $value);

        if ($attribute == 'numero_orden')
          $query->orderBy('clases_inspeccion_lista_fotos.numero_orden', $value);

        if ($attribute == 'estado')
          $query->orderBy('clases_inspeccion_lista_fotos.estado', $value);

        if ($attribute == 'usuario_creacion_nombre')
          $query->orderBy('clases_inspeccion_lista_fotos.usuario_creacion_nombre', $value);

        if ($attribute == 'usuario_modificacion_nombre')
          $query->orderBy('clases_inspeccion_lista_fotos.usuario_modificacion_nombre', $value);

        if ($attribute == 'fecha_creacion')
          $query->orderBy('clases_inspeccion_lista_fotos.created_at', $value);

        if ($attribute == 'fecha_modificacion')
          $query->orderBy('clases_inspeccion_lista_fotos.updated_at', $value);
      } else
      $query->orderBy("clases_inspeccion_lista_fotos.updated_at", "desc");

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
      ? ($pag->perPage() > $to
        ? 1
        : ($to - $totReg) + 1)
      : null;

    return [
      'datos' => $datos,
      'desde' => $from,
      'hasta' => $to,
      'por_pagina' => isset($pag) && $totReg > 0
        ? +$pag->perPage()
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

  public static function cargar($clase_inspeccion_id, $id)
  {
    $claseInspeccionListaFotos = ClaseInspeccionListaFotos::find($id);
    $cliente = $claseInspeccionListaFotos->cliente;
    $claseInspeccion = $claseInspeccionListaFotos->claseInspeccion;

    return [
      'id' => $claseInspeccionListaFotos->id,
      'nombre' => $claseInspeccionListaFotos->nombre,
      'numero_orden' => $claseInspeccionListaFotos->numero_orden,
      'estado' => $claseInspeccionListaFotos->estado,
      'usuario_creacion_id' => $claseInspeccionListaFotos->usuario_creacion_id,
      'usuario_creacion_nombre' => $claseInspeccionListaFotos->usuario_creacion_nombre,
      'usuario_modificacion_id' => $claseInspeccionListaFotos->usuario_modificacion_id,
      'usuario_modificacion_nombre' => $claseInspeccionListaFotos->usuario_modificacion_nombre,
      'fecha_creacion' => (new Carbon($claseInspeccionListaFotos->created_at))->format("Y-m-d H:i:s"),
      'fecha_modificacion' => (new Carbon($claseInspeccionListaFotos->updated_at))->format("Y-m-d H:i:s"),
      'cliente' => isset($cliente)
        ? [
          'id' => $cliente->id,
          'nombre' => $cliente->nombre,
        ]
        : null,
      'clase_inspeccion' => isset($claseInspeccion)
        ? [
          'id' => $claseInspeccion->id,
          'nombre' => $claseInspeccion->nombre,
        ]
        : null,
    ];
  }

  public static function modificarOCrear($clase_inspeccion_id, $dto)
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
      ? ClaseInspeccionListaFotos::find($dto['id'])
      : new ClaseInspeccionListaFotos();

    // Guardar objeto original para auditoria
    $regOri = $reg->toJson();

    $reg->fill($dto);
    $guardado = $reg->save();
    if (!$guardado)
      throw new Exception("Ocurrió un error al intentar guardar el Conductor.", $reg);

    // Guardar auditoria
    $auditoriaDto = [
      'id_recurso' => $reg->id,
      'nombre_recurso' => ClaseInspeccionListaFotos::class,
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

    return ClaseInspeccionListaFotos::cargar($clase_inspeccion_id, $reg->id);
  }

  public static function eliminar($id)
  {
    $regEli = ClaseInspeccionListaFotos::find($id);

    // Guardar auditoria
    $auditoriaDto = [
      'id_recurso' => $regEli->id,
      'nombre_recurso' => ClaseInspeccionListaFotos::class,
      'descripcion_recurso' => $regEli->nombre,
      'accion' => AccionAuditoriaEnum::ELIMINAR,
      'recurso_original' => $regEli->toJson()
    ];
    AuditoriaTabla::crear($auditoriaDto);

    return $regEli->delete();
  }

  use HasFactory;
}