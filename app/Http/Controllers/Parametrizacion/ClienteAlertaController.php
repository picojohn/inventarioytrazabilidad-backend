<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\ClienteAlerta;

class ClienteAlertaController extends Controller
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
      
         // captura lista de registros de repositorio <clientes_alertas>
         if ($request->ligera)
            $retLista = ClienteAlerta::obtenerColeccionLigera($datos);
         else {
            if (isset($datos['ordenar_por']))
               $datos['ordenar_por'] = format_order_by_attributes($datos);
   
            if ($request->headerInfo)
               $retLista = ClienteAlerta::getHeaders($cliente_id);
            else 
               $retLista = ClienteAlerta::obtenerColeccion($datos);        
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
   
         // realiza validaciones generales de datos para el repositorio <clientes_alertas>
         $retVal = Validator::make($datos, [  
            'cliente_id' => 'integer|exists:clientes,id|required',
            'alerta_id' => [
               'integer',
               'exists:tipos_alertas,id',
               'required',
               Rule::unique('clientes_alertas')
                  ->where(fn ($query) => 
                     $query->where('cliente_id', $datos['cliente_id'])
                        ->where('alerta_id', $datos['alerta_id']) 
               )
            ],
            'numero_horas' => 'integer|required',
            'observaciones' => 'string|max:128|nullable',      
            'estado' => 'boolean|required'
         ], $messages = [
            'alerta_id.unique'=>'Ya se registró esta alerta para este cliente',
         ]);
   
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // inserta registro en repositorio <clientes_alertas>
         $regCre = ClienteAlerta::modificarOCrear($cliente_id, $datos);
         if ($regCre) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["La Alerta ha sido creada.", 2], $regCre), 
               Response::HTTP_CREATED
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al crear la Alerta."]), 
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
   
         // verifica la existencia del id de registro en el repositorio <clientes_alertas>
         $retVal = Validator::make(
            $datos, 
            [  
               'id' => 'integer|exists:clientes_alertas,id|required',
            ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // captura y retorna el detalle de registro del repositorio <clientes_alertas>
         return response(
            ClienteAlerta::cargar($cliente_id, $id), 
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
   
         // verifica la existencia del id de registro y realiza validaciones a los campos para actualizar el repositorio <clientes_alertas>
         $retVal = Validator::make($datos, [  
            'id' => 'integer|exists:clientes_alertas,id|required',     
            'cliente_id' => 'integer|exists:clientes,id|required',      
            'alerta_id' => [
               'integer',
               'exists:tipos_alertas,id',
               'required',
               Rule::unique('clientes_alertas')
                  ->where(fn ($query) => 
                     $query->where('cliente_id', $datos['cliente_id'])
                        ->where('alerta_id', $datos['alerta_id']) 
                  )->ignore(ClienteAlerta::find($id))
            ],
            'numero_horas' => 'integer|required',      
            'observaciones' => 'string|max:128|nullable',
            'estado' => 'boolean|required'
         ], $messages = [
            'alerta_id.unique'=>'Ya se registró esta alerta para este cliente',
         ]);
   
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // actualiza/modifica registro en repositorio <clientes_alertas>
         $regMod = ClienteAlerta::modificarOCrear($cliente_id, $datos);
         if ($regMod) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["La Alerta, ha sido modificada.", 1], $regMod), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al modificar la Alerta."]), 
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
   
         // verifica la existencia del id de registro en el repositorio <clientes_alertas>
         $retVal = Validator::make(
            $datos, 
            [  'id' => 'integer|exists:clientes_alertas,id|required'  ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
   
         // elimina registro en repositorio <clientes_alertas>
         $regEli = ClienteAlerta::eliminar($id);
         if ($regEli) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["La Alerta ha sido eliminada.", 3]), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al eliminar la Alerta."]), 
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