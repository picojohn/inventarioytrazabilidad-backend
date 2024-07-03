<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\TipoListaChequeo;

class TipoListaChequeoController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, $unidad_carga_id)
    {
        try{
            $datos = $request->all();
            $datos['unidad_carga_id'] = $unidad_carga_id;
            if(!$request->ligera){
                $validator = Validator::make($datos, [
                    'limite' => 'integer|between:1,500',
                    'unidad_carga_id' => 'integer|required|exists:unidades_carga_transporte,id',
                ]);

                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }

            if($request->ligera){
                $listasChequeo = TipoListaChequeo::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $listasChequeo = TipoListaChequeo::obtenerColeccion($datos);
            }
            return response($listasChequeo, Response::HTTP_OK);
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
    public function store(Request $request, $unidad_carga_id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $datos['unidad_carga_id'] = $unidad_carga_id;
            $validator = Validator::make($datos, [
                'unidad_carga_id' => 'integer|required|exists:unidades_carga_transporte,id',
                'clase_inspeccion_id' => 'integer|required|exists:clases_inspeccion,id',
                'nombre' => 'string|required|min:1|max:128',
                'estado' => 'boolean|required'
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $listaChequeo = TipoListaChequeo::modificarOCrear($datos);
            
            if ($listaChequeo) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La lista de chequeo ha sido creada.", 2], $listaChequeo),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear la lista de chequeo."]), Response::HTTP_CONFLICT);
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
    public function show($unidad_carga_id, $id)
    {
        try{
            $datos['unidad_carga_id'] = $unidad_carga_id;
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'unidad_carga_id' => 'integer|required|exists:unidades_carga_transporte,id',
                'id' => 'integer|required|exists:tipos_listas_chequeo,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(TipoListaChequeo::cargar($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:tipos_listas_chequeo,id',
                'unidad_carga_id' => 'integer|required|exists:unidades_carga_transporte,id',
                'clase_inspeccion_id' => 'integer|required|exists:clases_inspeccion,id',
                'nombre' => 'string|required|min:1|max:128',
                'estado' => 'boolean|required'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $listaChequeo = TipoListaChequeo::modificarOCrear($datos);
            if($listaChequeo){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La lista de chequeo ha sido modificada.", 1], $listaChequeo),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar la lista de chequeo."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:tipos_listas_chequeo,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = TipoListaChequeo::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La lista de chequeo ha sido eliminada.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar la lista de chequeo."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
