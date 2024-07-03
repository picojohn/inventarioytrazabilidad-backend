<?php

namespace App\Http\Controllers\Parametrizacion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Parametrizacion\ZonaContenedor;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
    
class ZonaContenedorController extends Controller
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
                    $zonaContenedor = ZonaContenedor::obtenerColeccionLigera($datos);
                }else{
                    if(isset($datos['ordenar_por'])){
                        $datos['ordenar_por'] = format_order_by_attributes($datos);
                    }
                    $zonaContenedor = ZonaContenedor::obtenerColeccion($datos);
                }
                return response($zonaContenedor, Response::HTTP_OK);
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
                    'nombre' => 'string|required|min:1|max:128',
                    'numero_sellos_zona' => 'integer|nullable',
                    'estado' => 'boolean|required'
                ]);
    
                if ($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
    
                $zonaContenedor = ZonaContenedor::modificarOCrear($datos);
                
                if ($zonaContenedor) {
                    DB::commit(); // Se cierra la transacción correctamente
                    return response(
                        get_response_body(["La zona de Contenedor ha sido creado.", 2], $zonaContenedor),
                        Response::HTTP_CREATED
                    );
                } else {
                    DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                    return response(get_response_body(["Ocurrió un error al intentar crear la zona de Contenedor."]), Response::HTTP_CONFLICT);
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
                    'id' => 'integer|required|exists:zonas_contenedores,id'
                ]);
    
                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
    
                return response(ZonaContenedor::cargar($id), Response::HTTP_OK);
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
                    'id' => 'integer|required|exists:zonas_contenedores,id',
                    'nombre' => 'string|required|min:1|max:128',
                    'numero_sellos_zona' => 'integer|nullable',
                    'estado' => 'boolean|required'
                ]);
    
                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
    
                $zonaContenedor = ZonaContenedor::modificarOCrear($datos);
                if($zonaContenedor){
                    DB::commit(); // Se cierra la transacción correctamente
                    return response(
                        get_response_body(["La zona de Contenedor ha sido modificado.", 1], $zonaContenedor),
                        Response::HTTP_OK
                    );
                } else {
                    DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                    return response(get_response_body(["Ocurrió un error al intentar modificar la zona de Contenedor."]), Response::HTTP_CONFLICT);;
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
                    'id' => 'integer|required|exists:zonas_contenedores,id'
                ]);
    
                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
    
                $eliminado = ZonaContenedor::eliminar($id);
                if($eliminado){
                    DB::commit(); // Se cierra la transacción correctamente
                    return response(
                        get_response_body(["La zona de Contenedor ha sido elimado.", 3]),
                        Response::HTTP_OK
                    );
                }else{
                    DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                    return response(get_response_body(["Ocurrió un error al intentar eliminar la zona de Contenedor."]), Response::HTTP_CONFLICT);
                }
            }catch (Exception $e){
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
    }
