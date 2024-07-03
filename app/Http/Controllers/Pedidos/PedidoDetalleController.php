<?php

namespace App\Http\Controllers\Pedidos;

use Exception;
use App\Rules\ExistsS3;
use App\Rules\UniqueSerial;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Rules\UniqueKitInOrder;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Pedidos\PedidoDetalle;
use Illuminate\Support\Facades\Validator;

class PedidoDetalleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, $pedido_id)
    {
        try{
            $datos = $request->all();
            $datos['pedido_id'] = $pedido_id;
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
                $pedidosDetalle = PedidoDetalle::obtenerColeccionLigera($datos);
            }else if($request->informacion){
                $pedidosDetalle = PedidoDetalle::obtenerInformacionUltimoSello($datos, $datos['cantidad']);
            }else if($request->lectura){
                $pedidosDetalle = PedidoDetalle::obtenerColeccionParaLectura($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $pedidosDetalle = PedidoDetalle::obtenerColeccion($datos);
            }
            return response($pedidosDetalle, Response::HTTP_OK);
            // return $pedidosDetalle;
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexKit(Request $request, $pedido_id, $kit_id)
    {
        try{
            $datos = $request->all();
            $datos['pedido_id'] = $pedido_id;
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

            if(isset($datos['ordenar_por'])){
                $datos['ordenar_por'] = format_order_by_attributes($datos);
            }
            $pedidosDetalle = PedidoDetalle::obtenerColeccionKit($datos);
            return response($pedidosDetalle, Response::HTTP_OK);
            // return $pedidosDetalle;
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
    public function store(Request $request, $pedido_id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $datos['pedido_id'] = $pedido_id;
            $validator = Validator::make($datos, [
                'pedido_id' => [
                    'integer',
                    'required',
                    Rule::exists('pedidos','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'producto_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('productos_clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    Rule::unique('pedidos_detalle')->where(function($query) use($datos){
                        $query->where('pedido_id', $datos['pedido_id'])
                            ->whereNull('kit_id')
                            ->where('producto_id', $datos['producto_id']);
                    })
                ],
                'kit_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('kits','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    Rule::unique('pedidos_detalle')->where(function($query) use($datos){
                        $query->where('pedido_id', $datos['pedido_id'])
                            ->where('kit_id', $datos['kit_id']);
                    })
                ],
                'cantidad' => 'integer|required',
                'color_id' => [
                    'integer',
                    'nullable',
                    new ExistsS3(
                        'colores',
                        $datos['color_id']??1
                    )
                ],
                'prefijo' => 'string|nullable',
                'posfijo' => 'string|nullable',
                'longitud_serial' => 'integer|nullable',
                'consecutivo_serie_inicial' => 'integer|nullable',
                'serie_inicial_articulo' => 'string|nullable',
                'serie_final_articulo' => 'string|nullable',
                'longitud_sello' => 'string|nullable',
                'diametro' => 'string|nullable',
                'observaciones' => 'string|nullable',
                'estado' => 'boolean|required',
            ],  $messages = [
                'pedido_id.exists'=>'El pedido seleccionado no existe o está en estado inactivo',
                'producto_id.exists'=>'El producto seleccionado no existe o está en estado inactivo',
                'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
                'producto_id.unique'=>'El producto seleccionado ya se ha agregado a este pedido',
                'kit_id.unique'=>'El kit seleccionado ya se ha agregado a este pedido',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $pedidoDetalle = PedidoDetalle::crear($datos);
            
            if ($pedidoDetalle) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El detalle de pedido ha sido creado.", 2], $pedidoDetalle),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el detalle de pedido."]), Response::HTTP_CONFLICT);
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
    public function show($pedido_id, $id)
    {
        try{
            $datos['id'] = $id;
            $datos['pedido_id'] = $pedido_id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:pedidos_detalle,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(PedidoDetalle::cargar($id), Response::HTTP_OK);
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
    public function update(Request $request, $pedido_id, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $datos['id'] = $id;
            $datos['pedido_id'] = $pedido_id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:pedidos_detalle,id',
                'pedido_id' => [
                    'integer',
                    'required',
                    Rule::exists('pedidos','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'producto_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('productos_clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    Rule::unique('pedidos_detalle')->where(function($query) use($datos){
                        $query->where('pedido_id', $datos['pedido_id'])
                            ->where('kit_id', $datos['kit_id']??null)
                            ->where('producto_id', $datos['producto_id']);
                    })->ignore(PedidoDetalle::find($id))
                ],
                'kit_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('kits','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                    new UniqueKitInOrder($datos['id'], $datos['pedido_id'])
                ],
                'cantidad' => 'integer|required',
                'color_id' => [
                    'integer',
                    'nullable',
                    new ExistsS3(
                        'colores',
                        $datos['color_id']
                    )
                ],
                'prefijo' => 'string|nullable',
                'posfijo' => 'string|nullable',
                'longitud_serial' => 'integer|nullable',
                'consecutivo_serie_inicial' => [
                    'integer',
                    'nullable',
                    new UniqueSerial(
                        $datos
                    )
                ],
                'serie_inicial_articulo' => 'string|nullable',
                'serie_final_articulo' => 'string|nullable',
                'longitud_sello' => 'string|nullable',
                'diametro' => 'string|nullable',
                'observaciones' => 'string|nullable',
                'estado' => 'boolean|required',
            ],  $messages = [
                'pedido_id.exists'=>'El pedido seleccionado no existe o está en estado inactivo',
                'producto_id.exists'=>'El producto seleccionado no existe o está en estado inactivo',
                'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
                'producto_id.unique'=>'El producto seleccionado ya se ha agregado a este pedido',
                'kit_id.unique'=>'El kit seleccionado ya ha sido agregado a este pedido',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $pedidoDetalle = false;

            if($datos['tipo'] === 'P'){
                $pedidoDetalle = PedidoDetalle::modificarProducto($datos);
            } else {
                $pedidoDetalle = PedidoDetalle::modificarKit($datos);
            }

            if($pedidoDetalle){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El detalle de pedido ha sido modificado.", 1], $pedidoDetalle),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el detalle de pedido."]), Response::HTTP_CONFLICT);;
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
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:pedidos_detalle,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = PedidoDetalle::eliminar($datos);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El detalle de pedido ha sido elimado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el detalle de pedido."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
