<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\ClienteConductor;
use App\Models\Parametrizacion\ClienteInspector;

class ClienteInspectorController extends Controller {
	/**
	* Presenta un listado con la información de la funcionalidad.
	* @param Request $request
	* @return Response
	*/
	public function index(Request $request, $cliente_id){
		try {
			$datos = $request->all();
			$datos['cliente_id'] = $cliente_id;
			// valida entrada de parametros a la funcion
			if (!$request->ligera) {
				$validation = Validator::make($datos, [  
					'limite' => 'integer|between:1,500',
					'cliente_id' => 'integer|exists:clientes,id|required' 
				]);

				if ($validation->fails()) {
					return response(
						get_response_body(format_messages_validator($validation)), 
						Response::HTTP_BAD_REQUEST
					);
				}
			}
		
			// captura lista de registros de repositorio <clientes_alertas>
			if ($request->ligera) {
				$inspectores = ClienteInspector::obtenerColeccionLigera($datos);
			} else {
				if (isset($datos['ordenar_por'])) {
					$datos['ordenar_por'] = format_order_by_attributes($datos);
				}
				if ($request->headerInfo) {
					$inspectores = ClienteInspector::getHeaders($cliente_id);
				} else {
					$inspectores = ClienteInspector::obtenerColeccion($datos);        
				} 
			}
			return response($inspectores, Response::HTTP_OK);
		} catch(Exception $e) {
				return response(
					$e->getMessage(), 
					Response::HTTP_INTERNAL_SERVER_ERROR
				);
		}
	}
  
   /**
    * Almacena o crea un registro en el repositorio de la funcionalidad.
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
	public function store(Request $request, $cliente_id){
		DB::beginTransaction(); // Se abre la transaccion
		try {
			$datos = $request->all();
			$datos['cliente_id'] = $cliente_id;
	
			// realiza validaciones generales de datos para el repositorio <clientes_alertas>
			$validation = Validator::make($datos, [  
				'cliente_id' => 'integer|exists:clientes,id|required',
				'tipo_documento_id' => [
						'integer',
						'required',
				],
				'numero_documento' => [
					'string',
					'max:128',
					'required',
					Rule::unique('clientes_inspectores')
						 ->where(fn ($query) => 
								$query->where('cliente_id', $datos['cliente_id'])
								->where('numero_documento', $datos['numero_documento']) 
						 )
			 ], 
				'nombre_inspector' => 'string|max:128|required',      
				'cargo' => 'string|max:128|nullable',      
				'celular_inspector' => 'string|max:10|nullable',      
				'correo_inspector' => 'string|max:128|nullable',      
				'indicativo_formado_inspeccion' => 'string|max:1|required',      
				'fecha_ultima_formacion' => 'date|nullable',      
				'estado' => 'boolean|required'
			], $messages = [
				'cliente_id.exists' => 'El cliente seleccionado no existe',
				'numero_documento.unique'=>'Ya existe un inspector con este número de documento para este cliente',
			]);

			if ($validation->fails()){
				return response(
					get_response_body(format_messages_validator($validation)), 
					Response::HTTP_BAD_REQUEST
				);
			}

			// inserta registro en repositorio <clientes_alertas>
			$inspector = ClienteInspector::modificarOCrear($cliente_id, $datos);
			if ($inspector) {
				DB::commit(); // Se cierra la transaccion correctamente
				return response(
					get_response_body(["El inspector ha sido creado.", 2], $inspector), 
					Response::HTTP_CREATED
				);
			} else {
				DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
				return response(
					get_response_body(["Error al crear el inspector."]), 
					Response::HTTP_CONFLICT
				);
			}
		} catch (Exception $e) {
				DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
				return response(
					null, 
					Response::HTTP_INTERNAL_SERVER_ERROR
				);
		}
	}
  
   /**
    * Presenta la información de un registro especifico de la funcionalidad.
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
	public function show($cliente_id, $id){
		try {
			$datos['id'] = $id;
			$datos['cliente_id'] = $cliente_id;

			// verifica la existencia del id de registro en el repositorio <clientes_alertas>
			$validation = Validator::make($datos, [  
					'id' => 'integer|exists:clientes_inspectores,id|required',
			]);
			if ($validation->fails()){
				return response(
					get_response_body(format_messages_validator($validation)), 
					Response::HTTP_BAD_REQUEST
				);
			}

			// captura y retorna el detalle de registro del repositorio <clientes_alertas>
			return response(ClienteInspector::cargar($cliente_id, $id), Response::HTTP_OK);
		} catch (Exception $e) {
				return response(
					null, 
					Response::HTTP_INTERNAL_SERVER_ERROR
				);
		}
	}
  
   /**
    * Presenta el formulario para actualizar el registro especifico de la funcionalidad.
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
	public function update(Request $request, $cliente_id, $id){
		DB::beginTransaction(); // Se abre la transaccion
		try {
			$datos = $request->all();
			$datos['id'] = $id;

			// verifica la existencia del id de registro y realiza validaciones a los campos para actualizar el repositorio <clientes_alertas>
			$validation = Validator::make($datos, [  
				'id' => 'integer|exists:clientes_inspectores,id|required',     
				'cliente_id' => 'integer|exists:clientes,id|required',
				'tipo_documento_id' => [
						'integer',
						'required',
				],
				'numero_documento' => [
					'string',
					'max:128',
					'required',
					Rule::unique('clientes_inspectores')
						 ->where(fn ($query) => 
								$query->where('cliente_id', $datos['cliente_id'])
								->where('numero_documento', $datos['numero_documento']) 
						 )
						 ->ignore(ClienteConductor::find($id))
			 ], 
				'nombre_inspector' => 'string|max:128|required',     
				'cargo' => 'string|max:128|nullable',       
				'celular_inspector' => 'string|max:10|nullable',      
				'correo_inspector' => 'string|max:128|nullable',      
				'indicativo_formado_inspeccion' => 'string|max:1|required',      
				'fecha_ultima_formacion' => 'date|nullable',      
				'estado' => 'boolean|required'
			], $messages = [
				'cliente_id.exists' => 'El cliente seleccionado no existe',
				'numero_documento.unique'=>'Ya existe un inspector con este número de documento para este cliente',
			]);

			if ($validation->fails()){
				return response(
					get_response_body(format_messages_validator($validation)), 
					Response::HTTP_BAD_REQUEST
				);
			}

			// actualiza/modifica registro en repositorio <clientes_alertas>
			$inspector = ClienteInspector::modificarOCrear($cliente_id, $datos);
			if ($inspector) {
				DB::commit(); // Se cierra la transaccion correctamente
				return response(
					get_response_body(["El inspector ha sido modificado.", 1], $inspector), 
					Response::HTTP_OK
				);
			} else {
				DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
				return response(
					get_response_body(["Error al modificar el inspector."]), 
					Response::HTTP_CONFLICT
				);
			}
		} catch (Exception $e) {
				DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
				return response(
					get_response_body([$e->getMessage()]), 
					Response::HTTP_INTERNAL_SERVER_ERROR
				);
		}
	}
  
   /**
    * Elimina un registro especifico de la funcionalidad.
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
	public function destroy($id){
		DB::beginTransaction(); // Se abre la transaccion
		try {
			$datos['id'] = $id;

			// verifica la existencia del id de registro en el repositorio <clientes_alertas>
			$validation = Validator::make($datos, [
				'id' => 'integer|exists:clientes_inspectores,id|required'
			]);
			if ($validation->fails()){
				return response(
					get_response_body(format_messages_validator($validation)), 
					Response::HTTP_BAD_REQUEST
				);
			}

			// elimina registro en repositorio <clientes_alertas>
			$inspector = ClienteInspector::eliminar($id);
			if ($inspector) {
				DB::commit(); // Se cierra la transaccion correctamente
				return response(
					get_response_body(["El inspector ha sido eliminado.", 3]), 
					Response::HTTP_OK
				);
			} else {
				DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
				return response(
					get_response_body(["Error al eliminar el inspector."]), 
					Response::HTTP_CONFLICT
				);
			}
		} catch (Exception $e) {
				DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
				return response(
					null, 
					Response::HTTP_INTERNAL_SERVER_ERROR
				);
		}
	}
}
