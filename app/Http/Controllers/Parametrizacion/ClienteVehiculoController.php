<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\ClienteVehiculo;

class ClienteVehiculoController extends Controller
{
   /**
    * Presenta un listado con la información de la funcionalidad.
    * @param Request $request
    * @return Response
    */
   public function index($cliente_id, Request $request)
   {
      try {
         $datos = $request->all();
         $datos['cliente_id'] = $cliente_id;
         
         // valida entrada de parametros a la funcion
         if (!$request->ligera) {
            $retVal = Validator::make(
               $datos, 
               [  
                  'limite' => 'integer|between:1,500',
                  'cliente_id' => 'integer|exists:clientes,id|required' 
               ]
            );
   
            if ($retVal -> fails())
               return response(
                  get_response_body(format_messages_validator($retVal)), 
                  Response::HTTP_BAD_REQUEST
               );
         }
      
         // captura lista de registros de repositorio <vehiculos>
         if ($request->ligera)
            $retLista = ClienteVehiculo::obtenerColeccionLigera($datos);
         else {
            if (isset($datos['ordenar_por']))
               $datos['ordenar_por'] = format_order_by_attributes($datos);
   
            if ($request->headerInfo)
               $retLista = ClienteVehiculo::getHeaders($cliente_id);
            else 
               $retLista = ClienteVehiculo::obtenerColeccion($datos);        
         }
      
         return response($retLista, Response::HTTP_OK);
      }
      catch(Exception $e) {
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
   public function store($cliente_id, Request $request)
   {
      DB::beginTransaction(); // Se abre la transaccion
      try {
         $datos = $request->all();
         $datos['cliente_id'] = $cliente_id;
   
         // realiza validaciones generales de datos para el repositorio <vehiculos>
         $retVal = Validator::make($datos, 
            [  
               'cliente_id' => 'integer|exists:clientes,id|required',
               'placa_vehiculo' => [
                  'string',
                  'max:128',
                  'required',
                  Rule::unique('vehiculos')
                     ->where(fn ($query) => 
                        $query->where('cliente_id', $datos['cliente_id'])
                        ->where('placa_vehiculo', $datos['placa_vehiculo']) 
                     )
               ],   
               'marca_vehiculo' => 'string|max:128|required', 
               'modelo_vehiculo' => 'string|max:128|nullable', 
               'indicativo_vehiculo_propio' => 'string|max:1|required', 
               'observaciones' => 'string|max:128|nullable',
               'estado' => 'boolean|required'
            ], $messages = [
               'placa_vehiculo.unique'=>'Ya existe un vehículo con esta placa para este cliente',
            ]);
   
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // inserta registro en repositorio <vehiculos>
         $regCre = ClienteVehiculo::modificarOCrear($cliente_id, $datos);
         if ($regCre) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Vehículo ha sido creado.", 2], $regCre), 
               Response::HTTP_CREATED
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al crear el Vehículo."]), 
               Response::HTTP_CONFLICT
            );
         }
      }
      catch (Exception $e)
      {
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
   public function show($cliente_id, $id)
   {
      try {
         $datos['id'] = $id;
         $datos['cliente_id'] = $cliente_id;
   
         // verifica la existencia del id de registro en el repositorio <vehiculos>
         $retVal = Validator::make(
            $datos, 
            [  
               'id' => 'integer|exists:vehiculos,id|required',
            ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // captura y retorna el detalle de registro del repositorio <vehiculos>
         return response(
            ClienteVehiculo::cargar($cliente_id, $id), 
            Response::HTTP_OK
         );
      }
      catch (Exception $e) {
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
   public function update($cliente_id, Request $request, $id)
   {
      DB::beginTransaction(); // Se abre la transaccion
      try {
         $datos = $request->all();
         $datos['id'] = $id;
   
         // verifica la existencia del id de registro y realiza validaciones a los campos para actualizar el repositorio <vehiculos>
         $retVal = Validator::make($datos, 
            [  
               'id' => 'integer|exists:vehiculos,id|required',   
               'cliente_id' => 'integer|exists:clientes,id|required',
               'placa_vehiculo' => [
                  'string',
                  'max:128',
                  'required',
                  Rule::unique('vehiculos')
                     ->where(fn ($query) => 
                        $query->where('cliente_id', $datos['cliente_id'])
                        ->where('placa_vehiculo', $datos['placa_vehiculo']) 
                     )->ignore(ClienteVehiculo::find($id))
               ],
               'marca_vehiculo' => 'string|max:128|required', 
               'modelo_vehiculo' => 'string|max:128|nullable', 
               'indicativo_vehiculo_propio' => 'string|max:1|required', 
               'observaciones' => 'string|max:128|nullable',
               'estado' => 'boolean|required'
            ], $messages = [
               'placa_vehiculo.unique'=>'Ya existe un vehículo con esta placa para este cliente',
            ]);
   
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // actualiza/modifica registro en repositorio <vehiculos>
         $regMod = ClienteVehiculo::modificarOCrear($cliente_id, $datos);
         if ($regMod) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Vehículo, ha sido modificado.", 1], $regMod), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al modificar el Vehículo."]), 
               Response::HTTP_CONFLICT
            );
         }
      }
      catch (Exception $e) {
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
   public function destroy($id)
   {
      DB::beginTransaction(); // Se abre la transaccion
      try {
         $datos['id'] = $id;
   
         // verifica la existencia del id de registro en el repositorio <vehiculos>
         $retVal = Validator::make(
            $datos, 
            [  'id' => 'integer|exists:vehiculos,id|required'  ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // elimina registro en repositorio <vehiculos>
         $regEli = ClienteVehiculo::eliminar($id);
         if ($regEli) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Vehículo ha sido eliminado.", 3]), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al eliminar el Vehículo."]), 
               Response::HTTP_CONFLICT
            );
         }
      }
      catch (Exception $e) {
         DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
         return response(
            null, 
            Response::HTTP_INTERNAL_SERVER_ERROR
         );
      }
   }
}