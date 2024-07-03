<?php

namespace App\Http\Controllers\Parametrizacion;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Rules\UniqueUserInPlace;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\LugarUsuario;

class LugarUsuarioController extends Controller
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
                $lugaresUsuarios = LugarUsuario::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $lugaresUsuarios = LugarUsuario::obtenerColeccion($datos);
            }
            return response($lugaresUsuarios, Response::HTTP_OK);
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
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'lugar_id' => [
                    'integer',
                    'required',
                    Rule::exists('lugares','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'usuario_id' => [
                    'integer',
                    'required',
                    Rule::exists('usuarios','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    new UniqueUserInPlace(
                        $datos['usuario_id'],
                    ),
                ],
                'estado' => 'boolean|required'
            ],  $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'lugar_id.exists'=>'El lugar seleccionado no existe o está en estado inactivo',
                'usuario_id.exists'=>'El usuario seleccionado no existe o está en estado inactivo',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $lugarUsuario = LugarUsuario::modificarOCrear($datos);
            
            if ($lugarUsuario) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El lugar de usuario ha sido creado.", 2], $lugarUsuario),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el lugar de usuario."]), Response::HTTP_CONFLICT);
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
                'id' => 'integer|required|exists:lugares_usuarios,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(LugarUsuario::cargar($id), Response::HTTP_OK);
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
                'id' => [
                    'integer',
                    'required',
                    'exists:lugares_usuarios,id',
                ],
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'lugar_id' => [
                    'integer',
                    'required',
                    Rule::exists('lugares','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'usuario_id' => [
                    'integer',
                    'required',
                    Rule::exists('usuarios','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    new UniqueUserInPlace(
                        $datos['usuario_id'],
                        $datos['id'],
                    ),
                ],
                'estado' => 'boolean|required'
            ],  $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'lugar_id.exists'=>'El lugar seleccionado no existe o está en estado inactivo',
                'usuario_id.exists'=>'El usuario seleccionado no existe o está en estado inactivo',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $lugarUsuario = LugarUsuario::modificarOCrear($datos);
            if($lugarUsuario){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El lugar de usuario ha sido modificado.", 1], $lugarUsuario),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el lugar de usuario."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:lugares_usuarios,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = LugarUsuario::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El lugar de usuario ha sido elimado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el lugar de usuario."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
