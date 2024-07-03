<?php

namespace App\Http\Controllers\Operaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Operaciones\OperacionEmbarqueContenedor;

class OperacionEmbarqueContenedorController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, $operacion_embarque_id)
    {
        try{
            $datos = $request->all();
            $datos['operacion_embarque_id'] = $operacion_embarque_id;
            if(!$request->ligera){
                $validator = Validator::make($datos, [
                    'limite' => 'integer|between:1,500',
                    'operacion_embarque_id' => 'integer|required|exists:operaciones_embarque,id',
                ]);

                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }

            if($request->ligera){
                $opEmContenedores = OperacionEmbarqueContenedor::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $opEmContenedores = OperacionEmbarqueContenedor::obtenerColeccion($datos);
            }
            return response($opEmContenedores, Response::HTTP_OK);
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
    public function store(Request $request, $operacion_embarque_id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $datos['operacion_embarque_id'] = $operacion_embarque_id;
            $validator = Validator::make($datos, [
                'operacion_embarque_id' => 'integer|required|exists:operaciones_embarque,id',
                'numero_contenedor' => [
                    'string',
                    'required',
                    'min:10',
                    'max:10',
                    'regex:/^[a-zA-Z]{3}[u|U|j|J|z|Z][0-9]{6}/',
                ],
                'digito_verificacion' => 'integer|required',
                'estado_contenedor' => 'string|required|max:3|min:3',
                'observaciones' => 'string|nullable|max:128'
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $opEmContenedor = OperacionEmbarqueContenedor::modificarOCrear($datos);
            
            if ($opEmContenedor) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El contenedor ha sido añadido exitosamente.", 2], $opEmContenedor),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["El contenedor ya ha sido asociado a la Operación seleccionada"]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($operacion_embarque_id, $id)
    {
        try{
            $datos['operacion_embarque_id'] = $operacion_embarque_id;
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'operacion_embarque_id' => 'integer|required|exists:operaciones_embarque,id',
                'id' => 'integer|required|exists:operaciones_embarque_contenedores,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(OperacionEmbarqueContenedor::cargar($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:operaciones_embarque_contenedores,id',
                'operacion_embarque_id' => 'integer|required|exists:operaciones_embarque,id',
                'numero_contenedor' => [
                    'string',
                    'required',
                    'min:10',
                    'max:10',
                    'regex:/^[a-zA-Z]{3}[u|U|j|J|z|Z][0-9]{6}/',
                ],
                'digito_verificacion' => 'integer|required',
                'estado_contenedor' => 'string|required|max:3|min:3',
                'observaciones' => 'string|nullable|max:128'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $opEmContenedor = OperacionEmbarqueContenedor::modificarOCrear($datos);
            if($opEmContenedor){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El contenedor ha sido modificado exitosamente.", 1], $opEmContenedor),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el contenedor."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:operaciones_embarque_contenedores,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = OperacionEmbarqueContenedor::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El contenedor ha sido eliminado de la operación exitosamente.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el contenedor."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importar(Request $request){
        DB::beginTransaction(); // Se abre la transacción
        try{
            $archivo = $request->file('archivo');
            $opEmId = $request->all()['operacion_embarque_id'];
            $errores = OperacionEmbarqueContenedor::importar($archivo, $opEmId);
            DB::commit(); // Se cierra la transacción correctamente
            return response(
                get_response_body(["Los contenedores se han importado correctamente.", 2], $errores),
                Response::HTTP_CREATED
            );
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(get_response_body(["Revisar archivo de carga. Estructura de información no corresponde."],$e), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
