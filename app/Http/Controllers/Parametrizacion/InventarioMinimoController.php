<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\InventarioMinimo;

class InventarioMinimoController extends Controller
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
                $inventariosMinimos = InventarioMinimo::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $inventariosMinimos = InventarioMinimo::obtenerColeccion($datos);
            }
            return response($inventariosMinimos, Response::HTTP_OK);
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
                'kit_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('kits','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'producto_cliente_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('productos_clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
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
                    Rule::unique('inventario_minimo')
                        ->where(fn ($query) => 
                            $query->where('cliente_id', $datos['cliente_id'])
                                ->where('lugar_id', $datos['lugar_id'])
                                ->where('kit_id', $datos['kit_id']??null)
                                ->where('producto_cliente_id', $datos['producto_cliente_id']??null)   
                    )
                ],
                'cantidad_inventario_minimo' => 'integer|required',
                'estado' => 'boolean|required'
            ], $messages = [
                'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
                'producto_cliente_id.exists'=>'El inventario mínimo seleccionado no existe o está en estado inactivo',
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'lugar_id.exists'=>'El lugar seleccionado no existe o está en estado inactivo',
                'lugar_id.unique'=>'Ya existe un registro de stock mínimo para esta referencia en en lugar seleccionado',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $inventarioMinimo = InventarioMinimo::modificarOCrear($datos);
            
            if ($inventarioMinimo) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El inventario mínimo ha sido creado.", 2], $inventarioMinimo),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el inventario mínimo."]), Response::HTTP_CONFLICT);
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
    public function show($id)
    {
        try{
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:inventario_minimo,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(InventarioMinimo::cargar($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:inventario_minimo,id',
                'kit_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('kits','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'producto_cliente_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('productos_clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
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
                    Rule::unique('inventario_minimo')
                        ->where(fn ($query) => 
                            $query->where('cliente_id', $datos['cliente_id'])
                                ->where('lugar_id', $datos['lugar_id'])
                                ->where('kit_id', $datos['kit_id']??null)
                                ->where('producto_cliente_id', $datos['producto_cliente_id']??null)   
                        )->ignore(InventarioMinimo::find($id)
                    )
                ],
                'cantidad_inventario_minimo' => 'integer|required',
                'estado' => 'boolean|required'
            ], $messages = [
                'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
                'producto_cliente_id.exists'=>'El inventario mínimo seleccionado no existe o está en estado inactivo',
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'lugar_id.exists'=>'El lugar seleccionado no existe o está en estado inactivo',
                'lugar_id.unique'=>'Ya existe un registro de stock mínimo para esta referencia en en lugar seleccionado',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $inventarioMinimo = InventarioMinimo::modificarOCrear($datos);
            if($inventarioMinimo){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El inventario mínimo ha sido modificado.", 1], $inventarioMinimo),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el inventario mínimo."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:inventario_minimo,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = InventarioMinimo::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El inventario mínimo ha sido elimado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el inventario mínimo."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
