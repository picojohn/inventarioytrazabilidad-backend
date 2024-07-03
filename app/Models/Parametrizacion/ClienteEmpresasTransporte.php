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

class ClienteEmpresasTransporte extends Model
{
	use HasFactory;

	protected $table = 'clientes_empresas_transporte'; // nombre de la tabla en la base de datos

	protected $fillable = [
		'cliente_id', 
		'tipo_documento_id', 
		'numero_documento', 
		'nombre_empresa_transporte', 
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
		$query = DB::table('clientes_empresas_transporte')
			->select(
				'id',
				'numero_documento',
				'nombre_empresa_transporte AS nombre',
				'cliente_id',
				'estado',
			);
		$query->orderBy('nombre', 'asc');
		return $query->get();
	}

	public static function obtenerColeccion($dto){
		$query = DB::table('clientes_empresas_transporte')
			->select(
				'id',
				'numero_documento', 
				'nombre_empresa_transporte As nombre', 
				'tipo_documento_id', 
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
					$query->orderBy('nombre_empresa_transporte', $value);
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

		$empresasTransporte = $query->paginate($dto['limite'] ?? 100);
		$datos = [];

		foreach ($empresasTransporte ?? [] as $empresaTransporte) {
			array_push($datos, $empresaTransporte);
		}

		$cantidadEmpresasTransporte = count($empresasTransporte);
		$to = isset($empresasTransporte) && $cantidadEmpresasTransporte > 0 ? $empresasTransporte->currentPage() * $empresasTransporte->perPage() : null;
		$to = isset($to) && isset($empresasTransporte) && $to > $empresasTransporte->total() && $cantidadEmpresasTransporte > 0 ? $empresasTransporte->total() : $to;
		$from = isset($to) && isset($empresasTransporte) && $cantidadEmpresasTransporte > 0 ? 
			( $empresasTransporte->perPage() > $to ? 1 : ($to - $cantidadEmpresasTransporte) + 1 ) 
			: null;

		return [
			'datos' => $datos,
			'desde' => $from,
			'hasta' => $to,
			'por_pagina' => isset($empresasTransporte) && $cantidadEmpresasTransporte > 0 ? + $empresasTransporte->perPage() : 0,
			'pagina_actual' => isset($empresasTransporte) && $cantidadEmpresasTransporte > 0 ? $empresasTransporte->currentPage() : 1,
			'ultima_pagina' => isset($empresasTransporte) && $cantidadEmpresasTransporte > 0 ? $empresasTransporte->lastPage() : 0,
			'total' => isset($empresasTransporte) && $cantidadEmpresasTransporte > 0 ? $empresasTransporte->total() : 0
		];
	}

	public static function cargar($cliente_id, $id){
		$empresaTransporte = ClienteEmpresasTransporte::find($id);
		$cliente = $empresaTransporte->cliente;
		return [
			'id' => $empresaTransporte->id,
			'cliente_id' => $empresaTransporte->cliente_id, 
			'numero_documento' => $empresaTransporte->numero_documento, 
			'nombre_empresa_transporte' => $empresaTransporte->nombre_empresa_transporte, 
			'tipo_documento_id' => $empresaTransporte->tipo_documento_id, 
			'estado' => $empresaTransporte->estado,
			'usuario_creacion_id' => $empresaTransporte->usuario_creacion_id,
			'usuario_creacion_nombre' => $empresaTransporte->usuario_creacion_nombre,
			'usuario_modificacion_id' => $empresaTransporte->usuario_modificacion_id,
			'usuario_modificacion_nombre' => $empresaTransporte->usuario_modificacion_nombre,
			'fecha_creacion' => (new Carbon($empresaTransporte->created_at))->format("Y-m-d H:i:s"),
			'fecha_modificacion' => (new Carbon($empresaTransporte->updated_at))->format("Y-m-d H:i:s"),
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
		$empresaTransporte = isset($dto['id']) ? ClienteEmpresasTransporte::find($dto['id']) : new ClienteEmpresasTransporte();

		// Guardar objeto original para auditoria
		$empTransporteOriginal = $empresaTransporte->toJson();
		$empresaTransporte->fill($dto);
		$guardado = $empresaTransporte->save();

		if (!$guardado) throw new Exception("Ocurrió un error al intentar guardar el Vehículo.", $empresaTransporte);

		// Guardar auditoria
		$auditoriaDto = [
			'id_recurso' => $empresaTransporte->id,
			'nombre_recurso' => ClienteEmpresasTransporte::class,
			'descripcion_recurso' => $empresaTransporte->cliente_id,
			'accion' => isset($dto['id']) ? AccionAuditoriaEnum::MODIFICAR : AccionAuditoriaEnum::CREAR,
			'recurso_original' => isset($dto['id']) ? $empTransporteOriginal : $empresaTransporte->toJson(),
			'recurso_resultante' => isset($dto['id']) ? $empresaTransporte->toJson() : null
		];

		AuditoriaTabla::crear($auditoriaDto);

		return ClienteEmpresasTransporte::cargar($cliente_id, $empresaTransporte->id);
	}

	public static function eliminar($id){
		$empresaTransporte = ClienteEmpresasTransporte::find($id);

		// Guardar auditoria
		$auditoriaDto = [
			'id_recurso' => $empresaTransporte->id,
			'nombre_recurso' => ClienteEmpresasTransporte::class,
			'descripcion_recurso' => $empresaTransporte->cliente_id,
			'accion' => AccionAuditoriaEnum::ELIMINAR,
			'recurso_original' => $empresaTransporte->toJson()
		];

		AuditoriaTabla::crear($auditoriaDto);
		return $empresaTransporte->delete();
	}
}
