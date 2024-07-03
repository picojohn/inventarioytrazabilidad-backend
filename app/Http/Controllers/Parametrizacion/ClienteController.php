<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use App\Rules\ExistsS3;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        try{
            $datos = $request->all();
            if(!$request->ligera){
                $validator = Validator::make($datos, [
                    'limite' => 'integer|between:1,500'
                ]);

                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }

            if($request->ligera){
                $cliente = Cliente::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $cliente = Cliente::obtenerColeccion($datos);
            }
            return response($cliente, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $validator = Validator::make($datos, [
                'nombre' => 'string|required|max:128',
                'observaciones' => 'string|nullable|max:128',
                'indicativo_lectura_sellos_externos' => 'string|required|max:1',
                'indicativo_instalacion_contenedor' => 'string|required|max:1',
                'indicativo_contenedor_exclusivo' => 'string|required|max:1',
                'indicativo_operaciones_embarque' => 'string|required|max:1',
                'indicativo_instalacion_automatica' => 'string|required|max:1',
                'indicativo_registro_lugar_instalacion' => 'string|required|max:1',
                'indicativo_registro_zona_instalacion' => 'string|required|max:1',
                'indicativo_asignacion_serial_automatica' => 'string|required|max:1',
                'indicativo_documento_referencia' => 'string|required|max:1',
                'asignacion_sellos_lectura' => 'string|required|max:1',
                'asociado_id' => [
                    'integer',
                    'required',
                    new ExistsS3(
                        'asociados_negocios',
                        $datos['asociado_id']
                    ),
                ],
                'estado' => 'boolean|required',
                'dias_vigencia_operacion_embarque' => 'integer|nullable|min:0'
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $cliente = Cliente::modificarOCrear($datos);
            
            if ($cliente) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El cliente ha sido creado.", 2], $cliente),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el cliente."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:clientes,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(Cliente::cargar($id), Response::HTTP_OK);
        }catch (Exception $e){
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:clientes,id',
                'nombre' => 'string|required|max:128',
                'observaciones' => 'string|nullable|max:128',
                'indicativo_lectura_sellos_externos' => 'string|required|max:1',
                'indicativo_instalacion_contenedor' => 'string|required|max:1',
                'indicativo_contenedor_exclusivo' => 'string|required|max:1',
                'indicativo_operaciones_embarque' => 'string|required|max:1',
                'indicativo_instalacion_automatica' => 'string|required|max:1',
                'indicativo_registro_lugar_instalacion' => 'string|required|max:1',
                'indicativo_registro_zona_instalacion' => 'string|required|max:1',
                'indicativo_asignacion_serial_automatica' => 'string|required|max:1',
                'indicativo_documento_referencia' => 'string|required|max:1',
                'asignacion_sellos_lectura' => 'string|required|max:1',
                'asociado_id' => [
                    'integer',
                    'required',
                    new ExistsS3(
                        'asociados_negocios',
                        $datos['asociado_id']
                    ),
                ],
                'estado' => 'boolean|required',
                'dias_vigencia_operacion_embarque' => 'integer|nullable|min:0'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $cliente = Cliente::modificarOCrear($datos);
            if($cliente){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El cliente ha sido modificado.", 1], $cliente),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el cliente."]), Response::HTTP_CONFLICT);;
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(get_response_body([$e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:clientes,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = Cliente::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El cliente ha sido elimado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el cliente."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
