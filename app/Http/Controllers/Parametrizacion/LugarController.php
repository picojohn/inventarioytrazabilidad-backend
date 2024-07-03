<?php

/**
 * Controlador para comunicación entre la vista y el modelo de la funcionalidad de Lugares.
 * @author  ASSIS S.A.S
 *          Jose Alejandro Gutierrez B
 * @version 14/06/2022/A
 */

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Parametrizacion\Lugar;

class LugarController extends Controller
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
   
         // captura lista de registros de repositorio <lugares>
         if ($request->ligera)
            $retLista = Lugar::obtenerColeccionLigera($datos);
         else {
            if (isset($datos['ordenar_por']))
               $datos['ordenar_por'] = format_order_by_attributes($datos);

            if ($request->headerInfo)
               $retLista = Lugar::getHeaders($cliente_id);
            else 
               $retLista = Lugar::obtenerColeccion($datos);          
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
 
         // realiza validaciones generales de datos para el repositorio <lugares>
         $retVal = Validator::make(
            $datos, 
            [  'nombre' => 'string|max:128|required',
               'direccion' => 'string|max:128|nullable', 
               'telefono' => 'string|max:128|nullable', 
               'cliente_id' => 'integer|exists:clientes,id|required',
               'tipo_lugar' => 'string|max:2|required',
               'indicativo_lugar_remision' => 'string|max:1|required', 
               'indicativo_lugar_instalacion' => 'string|max:1|required', 
               'indicativo_lugar_inspeccion' => 'string|max:1|required', 
               'codigo_externo_lugar' => 'string|max:128|nullable',
               'geocerca_id' => 'integer|nullable',
               'observaciones' => 'string|max:128|nullable',
               'estado' => 'boolean|required'
            ]
         );

         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
 
         // inserta registro en repositorio <lugares>
         $regCre = Lugar::modificarOCrear($cliente_id, $datos);
         if ($regCre) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Lugar ha sido creado.", 2], $regCre), 
               Response::HTTP_CREATED
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al crear el Lugar."]), 
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
 
         // verifica la existencia del id de registro en el repositorio <lugares>
         $retVal = Validator::make(
            $datos, 
            [  
               'id' => 'integer|exists:lugares,id|required',
            ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
 
         // captura y retorna el detalle de registro del repositorio <lugares>
         return response(
            Lugar::cargar($cliente_id, $id), 
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
 
         // verifica la existencia del id de registro y realiza validaciones a los campos para actualizar el repositorio <lugares>
         $retVal = Validator::make(
            $datos, 
            [  'id' => 'integer|exists:lugares,id|required',
               'nombre' => 'string|max:128|required',
               'direccion' => 'string|max:128|nullable', 
               'telefono' => 'string|max:128|nullable', 
               'cliente_id' => 'integer|exists:clientes,id|required',
               'tipo_lugar' => 'string|max:2|required',
               'indicativo_lugar_remision' => 'string|max:1|required',
               'indicativo_lugar_instalacion' => 'string|max:1|required', 
               'indicativo_lugar_inspeccion' => 'string|max:1|required', 
               'codigo_externo_lugar' => 'string|max:128|nullable',
               'geocerca_id' => 'integer|nullable',
               'observaciones' => 'string|max:128|nullable',
               'estado' => 'boolean|required'
            ]
         );

         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
 
         // actualiza/modifica registro en repositorio <lugares>
         $regMod = Lugar::modificarOCrear($cliente_id, $datos);
         if ($regMod) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Lugar, ha sido modificado.", 1], $regMod), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al modificar el Lugar."]), 
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
 
         // verifica la existencia del id de registro en el repositorio <lugares>
         $retVal = Validator::make(
            $datos, 
            [  'id' => 'integer|exists:lugares,id|required'  ]
         );
         if ($retVal -> fails())
            return response(
               get_response_body(format_messages_validator($retVal)), 
               Response::HTTP_BAD_REQUEST
            );
 
         // elimina registro en repositorio <lugares>
         $regEli = Lugar::eliminar($id);
         if ($regEli) {
            DB::commit(); // Se cierra la transaccion correctamente
            return response(
               get_response_body(["El Lugar ha sido eliminado.", 3]), 
               Response::HTTP_OK
            );
         }
         else {
            DB::rollback(); // Se devuelven los cambios, por que la transaccion falla
            return response(
               get_response_body(["Error al eliminar el Lugar."]), 
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