<?php

namespace App\Http\Controllers\Pedidos;

use Exception;
use App\Rules\ExistsS3;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Pedidos\Pedido;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Parametrizacion\ParametroConstante;

class PedidoController extends Controller
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
                $pedidos = Pedido::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $pedidos = Pedido::obtenerColeccion($datos);
            }
            return response($pedidos, Response::HTTP_OK);
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
                'numero_pedido' => 'integer|nullable',
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'numero_pedido_s3' => [
                    'integer',
                    'required',
                    new ExistsS3(
                        'pedidos',
                        $datos['numero_pedido_s3']
                    )
                ],
                'fecha_pedido' => 'date|required',
                'fecha_entrega_pedido' => 'date|required',
                'orden_compra_cliente' => 'string|required|max:128',
                'numero_lote' => 'integer|nullable',
                'fecha_confirmacion' => 'date|nullable',
                'fecha_ejecucion' => 'date|nullable',
                'fecha_despacho' => 'date|nullable',
                'fecha_anulacion' => 'date|nullable',
                'estado_pedido' => 'string|required|max:3',
                'observaciones' => 'string|nullable|max:128',
                'estado' => 'boolean|required',
            ],  $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }
            $parametros = ParametroConstante::cargarParametros();
            if(!isset($parametros['CONSECUTIVO_PEDIDO']) || !isset($parametros['CONSECUTIVO_LOTE'])){
                return response('Debe definir parámetros de consecutivo de pedido y lote', Response::HTTP_BAD_REQUEST);
            }

            $pedido = Pedido::modificarOCrear($datos);
            
            if ($pedido) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El pedido ha sido creado.", 2], $pedido),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el pedido."]), Response::HTTP_CONFLICT);
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
                'id' => 'integer|required|exists:pedidos,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(Pedido::cargar($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:pedidos,id',
                'numero_pedido' => 'integer|required',
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'numero_pedido_s3' => [
                    'integer',
                    'required',
                    new ExistsS3(
                        'pedidos',
                        $datos['numero_pedido_s3']
                    )
                ],
                'fecha_pedido' => 'date|required',
                'fecha_entrega_pedido' => 'date|required',
                'orden_compra_cliente' => 'string|required|max:128',
                'numero_lote' => 'integer|required',
                'fecha_confirmacion' => 'date|nullable',
                'fecha_ejecucion' => 'date|nullable',
                'fecha_despacho' => 'date|nullable',
                'fecha_anulacion' => 'date|nullable',
                'estado_pedido' => 'string|required|max:3',
                'observaciones' => 'string|nullable|max:128',
                'estado' => 'boolean|required',
            ],  $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $pedido = Pedido::modificarOCrear($datos);
            if($pedido){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El pedido ha sido modificado.", 1], $pedido),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el pedido."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:pedidos,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = Pedido::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El pedido ha sido anulado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el pedido."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function confirmar(Request $request, $id){
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:pedidos,id',
                'latitude' => 'numeric|required',
                'longitude' => 'numeric|required',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eventoConfirmarPedido = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_CONFIRMAR_PEDIDO')->first()->valor_parametro??0
            );
            if(!$eventoConfirmarPedido){
                return response(get_response_body('Faltan parámetros por definir'), Response::HTTP_BAD_REQUEST);
            }

            $confirmado = Pedido::confirmar($id, $datos);
            if($confirmado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El pedido ha sido confirmado.", 5]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar confirmar el pedido."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
