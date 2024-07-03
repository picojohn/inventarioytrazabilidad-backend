<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use App\Rules\LastBagInKit;
use Illuminate\Http\Request;
use App\Rules\DestroyLastBag;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\KitProducto;

class KitProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, $kit_id)
    {
        try{
            $datos = $request->all();
            $datos['kit_id'] = $kit_id;
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
                $productosKit = KitProducto::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $productosKit = KitProducto::obtenerColeccion($datos);
            }
            return response($productosKit, Response::HTTP_OK);
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
    public function store(Request $request, $kit_id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $productosKit = $request->all();
            foreach($productosKit as $datos){
                $datos['kit_id'] = $kit_id;
                $validator = Validator::make($datos, [
                    'kit_id' => [
                        'integer',
                        'required',
                        Rule::exists('kits','id')->where(function ($query) {
                            $query->where('estado', 1);
                        }),
                    ],
                    'producto_id' => [
                        'integer',
                        'required',
                        Rule::exists('productos_clientes','id')->where(function ($query) {
                            $query->where('estado', 1);
                        }),
                    ],
                    'cantidad' => 'integer|required',
                    'estado' => 'boolean|required'
                ], $messages = [
                    'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
                    'producto_id.exists'=>'El producto del kit seleccionado no existe o está en estado inactivo',
                ]);
    
                if ($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
    
                $kitProducto = KitProducto::modificarOCrear($datos);
            }
            
            if ($kitProducto) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El producto del kit ha sido creado.", 2], $kitProducto),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el producto del kit."]), Response::HTTP_CONFLICT);
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
    public function show($kit_id, $id)
    {
        try{
            $datos['id'] = $id;
            $datos['kit_id'] = $kit_id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:kits_productos,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(KitProducto::cargar($id), Response::HTTP_OK);
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
    public function update(Request $request, $kit_id, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $datos['id'] = $id;
            $datos['kit_id'] = $kit_id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:kits_productos,id',
                'kit_id' => [
                    'integer',
                    'required',
                    Rule::exists('kits','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'producto_id' => [
                    'integer',
                    'required',
                    Rule::exists('productos_clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    new LastBagInKit(
                        $datos['id'],
                        $datos['kit_id'],
                        $datos['producto_id']
                    ),
                ],
                'cantidad' => 'integer|required',
                'estado' => 'boolean|required'
            ], $messages = [
                'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
                'producto_id.exists'=>'El producto seleccionado no existe o está en estado inactivo',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $kitProducto = KitProducto::modificarOCrear($datos);
            if($kitProducto){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El producto del kit ha sido modificado.", 1], $kitProducto),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el producto del kit."]), Response::HTTP_CONFLICT);;
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
                'id' => [
                    'integer',
                    'required',
                    'exists:kits_productos,id',
                    new DestroyLastBag($datos['id'])
                ]
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = KitProducto::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El producto del kit ha sido elimado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el producto del kit."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
