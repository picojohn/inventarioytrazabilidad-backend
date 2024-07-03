<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\Contenedor;

class ContenedorController extends Controller
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
                $contenedores = Contenedor::obtenerColeccionLigera($datos);
            } else if($request->digito) {
                $contenedores = Contenedor::digitoVerificacion($datos['contenedor']);
            } else {
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $contenedores = Contenedor::obtenerColeccion($datos);
            }
            return response($contenedores, Response::HTTP_OK);
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
                'numero_contenedor' => [
                    'string',
                    'required',
                    'min:10',
                    'max:10',
                    'regex:/^[a-zA-Z]{3}[u|U|j|J|z|Z][0-9]{6}/',
                ],
                'digito_verificacion' => 'integer|required',
                'cliente_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    Rule::unique('contenedores')
                     ->where(fn ($query) => 
                        $query->where('cliente_id', $datos['cliente_id'])
                        ->where('numero_contenedor', $datos['numero_contenedor']) 
                    ),
                ],
                'tipo_contenedor_id' => [
                    'integer',
                    'required',
                    Rule::exists('tipos_contenedores','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'indicativo_contenedor_reparacion' => 'string|required|max:1',
                'estado' => 'boolean|required'
            ], $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'cliente_id.unique'=>'Este contenedor ya existe para este cliente',
                'tipo_contenedor_id.exists'=>'El tipo de contenedor seleccionado no existe o está en estado inactivo',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $contenedor = Contenedor::modificarOCrear($datos);
            
            if ($contenedor) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El contenedor ha sido creado.", 2], $contenedor),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el contenedor."]), Response::HTTP_CONFLICT);
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
                'id' => 'integer|required|exists:contenedores,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(Contenedor::cargar($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:contenedores,id',
                'numero_contenedor' => [
                    'string',
                    'required',
                    'min:10',
                    'max:10',
                    'regex:/^[a-zA-Z]{3}[u|U|j|J|z|Z][0-9]{6}/',
                ],
                'digito_verificacion' => 'integer|required',
                'cliente_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    Rule::unique('contenedores')
                     ->where(fn ($query) => 
                        $query->where('cliente_id', $datos['cliente_id'])
                        ->where('numero_contenedor', $datos['numero_contenedor']) 
                    )->ignore(Contenedor::find($id)),
                ],
                'tipo_contenedor_id' => [
                    'integer',
                    'required',
                    Rule::exists('tipos_contenedores','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'indicativo_contenedor_reparacion' => 'string|required|max:1',
                'estado' => 'boolean|required'
            ], $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'cliente_id.unique'=>'Este contenedor ya existe para este cliente',
                'tipo_contenedor_id.exists'=>'El tipo de contenedor seleccionado no existe o está en estado inactivo',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $contenedor = Contenedor::modificarOCrear($datos);
            if($contenedor){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El contenedor ha sido modificado.", 1], $contenedor),
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
                'id' => 'integer|required|exists:contenedores,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = Contenedor::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El contenedor ha sido elimado.", 3]),
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
}
