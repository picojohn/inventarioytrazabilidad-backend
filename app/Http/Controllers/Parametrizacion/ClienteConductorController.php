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

class ClienteConductorController extends Controller
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
      
         // captura lista de registros de repositorio <conductores>
         if ($request->ligera)
            $retLista = ClienteConductor::obtenerColeccionLigera($datos);
         else {
            if (isset($datos['ordenar_por']))
               $datos['ordenar_por'] = format_order_by_attributes($datos);
   
            if ($request->headerInfo)
               $retLista = ClienteConductor::getHeaders($cliente_id);
            else 
               $retLista = ClienteConductor::obtenerColeccion($datos);        
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
   
         // realiza validaciones generales de datos para el repositorio <conductores>
         $retVal = Validator::make($datos, 
            [  
               'cliente_id' => 'integer|exists:clientes,id|required',
               'tipo_documento_id' => 'integer|required', 
               'numero_documento' => [
                  'string',
                  'max:128',
                  'required',
                  Rule::unique('conductores')
                     ->where(fn ($query) => 
                        $query->where('cliente_id', $datos['cliente_id'])
                        ->where('numero_documento', $datos['numero_documento']) 
                     )
               ], 
               'nombre_conductor' => 'string|max:128|required', 
               'indicativo_conductor_empresa' => 'string|max:1|required', 
               'estado' => 'boolean|required'
            ], $messages = [
               'numero_documento.unique'=>'Ya existe un conductor con este número de documento para este cliente',
            ]);
   
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // inserta registro en repositorio <conductores>
         $regCre = ClienteConductor::modificarOCrear($cliente_id, $datos);
         if ($regCre) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Conductor ha sido creado.", 2], $regCre), 
               Response::HTTP_CREATED
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al crear el Conductor."]), 
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
   
         // verifica la existencia del id de registro en el repositorio <conductores>
         $retVal = Validator::make(
            $datos, 
            [  
               'id' => 'integer|exists:conductores,id|required',
            ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // captura y retorna el detalle de registro del repositorio <conductores>
         return response(
            ClienteConductor::cargar($cliente_id, $id), 
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
   
         // verifica la existencia del id de registro y realiza validaciones a los campos para actualizar el repositorio <conductores>
         $retVal = Validator::make($datos, [  
               'id' => 'integer|exists:conductores,id|required', 
               'cliente_id' => 'integer|exists:clientes,id|required',
               'tipo_documento_id' => 'integer|required',
               'numero_documento' => [
                  'string',
                  'max:128',
                  'required',
                  Rule::unique('conductores')
                     ->where(fn ($query) => 
                        $query->where('cliente_id', $datos['cliente_id'])
                        ->where('numero_documento', $datos['numero_documento']) 
                     )->ignore(ClienteConductor::find($id))
               ], 
               'nombre_conductor' => 'string|max:128|required', 
               'indicativo_conductor_empresa' => 'string|max:1|required', 
               'estado' => 'boolean|required'
            ], 
               $messages = [
               'numero_documento.unique'=>'Ya existe un conductor con este número de documento para este cliente',
            ]);
   
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // actualiza/modifica registro en repositorio <conductores>
         $regMod = ClienteConductor::modificarOCrear($cliente_id, $datos);
         if ($regMod) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Conductor, ha sido modificado.", 1], $regMod), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al modificar el Conductor."]), 
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
   
         // verifica la existencia del id de registro en el repositorio <conductores>
         $retVal = Validator::make(
            $datos, 
            [  'id' => 'integer|exists:conductores,id|required'  ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // elimina registro en repositorio <conductores>
         $regEli = ClienteConductor::eliminar($id);
         if ($regEli) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Conductor ha sido eliminado.", 3]), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al eliminar el Conductor."]), 
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