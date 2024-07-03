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

class ClienteInspector extends Model {

	use HasFactory;

	protected $table = 'clientes_inspectores'; // nombre de la tabla en la base de datos

	protected $fillable = [
		'cliente_id', 
		'tipo_documento_id', 
		'numero_documento', 
		'nombre_inspector', 
		'celular_inspector', 
		'correo_inspector', 
		'indicativo_formado_inspeccion',
		'fecha_ultima_formacion',
		'cargo',
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

	public static function obtenerColeccionLigera($dto){
		$query = DB::table('clientes_inspectores')
			->select(
				'id',
				'numero_documento',
				'nombre_inspector AS nombre',
				'estado',
			);
		$query->orderBy('nombre', 'asc');
		return $query->get();
	}

	public static function obtenerColeccion($dto){
		$query = DB::table('clientes_inspectores')
			->select(
				'id',
				'numero_documento', 
				'nombre_inspector As nombre', 
				'celular_inspector', 
				'correo_inspector', 
				'tipo_documento_id', 
				'indicativo_formado_inspeccion', 
				'fecha_ultima_formacion', 
				'cargo',
				'estado',
				'usuario_creacion_id',
				'usuario_creacion_nombre',
				'usuario_modificacion_id',
				'usuario_modificacion_nombre',
				'created_at AS fecha_creacion',
				'updated_at AS fecha_modificacion',
			)
			->where('cliente_id', $dto['cliente_id']);

		if (isset($dto['ordenar_por']) && count($dto['ordenar_por']) > 0){
			foreach ($dto['ordenar_por'] as $attribute => $value) {
				if ($attribute=='numero_documento') {
					$query->orderBy('numero_documento', $value);
				}
				if ($attribute=='nombre') {
					$query->orderBy('nombre_inspector', $value);
				}
				if ($attribute=='cargo') {
					$query->orderBy('cargo', $value);
				}
				if ($attribute=='celular_inspector') {
					$query->orderBy('celular_inspector', $value);
				}
				if ($attribute=='correo_inspector') {
					$query->orderBy('correo_inspector', $value);
				}
				if ($attribute=='indicativo_formado_inspeccion') {
					$query->orderBy('indicativo_formado_inspeccion', $value);
				}
				if ($attribute=='fecha_ultima_formacion') {
					$query->orderBy('fecha_ultima_formacion', $value);
				}
				if ($attribute=='estado') {
					$query->orderBy('estado', $value);
				}
				if ($attribute=='usuario_creacion_nombre') {
					$query->orderBy('usuario_creacion_nombre', $value);
				}
				if ($attribute=='usuario_modificacion_nombre') {
					$query->orderBy('usuario_modificacion_nombre', $value);
				}
				if ($attribute=='fecha_creacion') {
					$query->orderBy('created_at', $value);
				}
				if ($attribute=='fecha_modificacion') {
					$query->orderBy('updated_at', $value);
				}
			}
		} else {
			$query->orderBy("updated_at", "desc");
		} 

		$inspectores = $query->paginate($dto['limite'] ?? 100);
		$datos = [];

		foreach ($inspectores ?? [] as $inspector) {
			array_push($datos, $inspector);
		}

		$cantidadInspectores = count($inspectores);
		$to = isset($inspectores) && $cantidadInspectores > 0 ? $inspectores->currentPage() * $inspectores->perPage() : null;
		$to = isset($to) && isset($inspectores) && $to > $inspectores->total() && $cantidadInspectores > 0 ? $inspectores->total() : $to;
		$from = isset($to) && isset($inspectores) && $cantidadInspectores > 0 ? 
			( $inspectores->perPage() > $to ? 1 : ($to - $cantidadInspectores) + 1 ) 
			: null;

		return [
			'datos' => $datos,
			'desde' => $from,
			'hasta' => $to,
			'por_pagina' => isset($inspectores) && $cantidadInspectores > 0 ? + $inspectores->perPage() : 0,
			'pagina_actual' => isset($inspectores) && $cantidadInspectores > 0 ? $inspectores->currentPage() : 1,
			'ultima_pagina' => isset($inspectores) && $cantidadInspectores > 0 ? $inspectores->lastPage() : 0,
			'total' => isset($inspectores) && $cantidadInspectores > 0 ? $inspectores->total() : 0
		];
	}

	public static function cargar($cliente_id, $id){
		$inspector = ClienteInspector::find($id);
		$cliente = $inspector->cliente;
		return [
			'id' => $inspector->id,
			'cliente_id' => $inspector->cliente_id, 
			'numero_documento' => $inspector->numero_documento, 
			'nombre_inspector' => $inspector->nombre_inspector, 
			'celular_inspector' => $inspector->celular_inspector, 
			'correo_inspector' => $inspector->correo_inspector, 
			'tipo_documento_id' => $inspector->tipo_documento_id, 
			'indicativo_formado_inspeccion' => $inspector->indicativo_formado_inspeccion, 
			'fecha_ultima_formacion' => $inspector->fecha_ultima_formacion, 
			'cargo' => $inspector->cargo,
			'estado' => $inspector->estado,
			'usuario_creacion_id' => $inspector->usuario_creacion_id,
			'usuario_creacion_nombre' => $inspector->usuario_creacion_nombre,
			'usuario_modificacion_id' => $inspector->usuario_modificacion_id,
			'usuario_modificacion_nombre' => $inspector->usuario_modificacion_nombre,
			'fecha_creacion' => (new Carbon($inspector->created_at))->format("Y-m-d H:i:s"),
			'fecha_modificacion' => (new Carbon($inspector->updated_at))->format("Y-m-d H:i:s"),
			'cliente' => isset($cliente) ? [ 
					'id' => $cliente->id,
					'nombre' => $cliente->nombre,
			] : null,
		];
	}
 
	public static function modificarOCrear($cliente_id, $dto){
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
		$inspector = isset($dto['id']) ? ClienteInspector::find($dto['id']) : new ClienteInspector();

		// Guardar objeto original para auditoria
		$inspectorOriginal = $inspector->toJson();
		$inspector->fill($dto);
		$guardado = $inspector->save();

		if (!$guardado) throw new Exception("Ocurrió un error al intentar guardar el Vehículo.", $inspector);

		// Guardar auditoria
		$auditoriaDto = [
			'id_recurso' => $inspector->id,
			'nombre_recurso' => ClienteInspector::class,
			'descripcion_recurso' => $inspector->cliente_id,
			'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
			'recurso_original' => isset($dto['id']) ? $inspectorOriginal : $inspector->toJson(),
			'recurso_resultante' => isset($dto['id']) ? $inspector->toJson() : null
		];

		AuditoriaTabla::crear($auditoriaDto);

		return ClienteInspector::cargar($cliente_id, $inspector->id);
	}

	public static function eliminar($id){
		$inspector = ClienteInspector::find($id);

		// Guardar auditoria
		$auditoriaDto = [
			'id_recurso' => $inspector->id,
			'nombre_recurso' => ClienteInspector::class,
			'descripcion_recurso' => $inspector->cliente_id,
			'accion' => AccionAuditoriaEnum::ELIMINAR,
			'recurso_original' => $inspector->toJson()
		];

		AuditoriaTabla::crear($auditoriaDto);
		return $inspector->delete();
	}
}
