<?php

namespace App\Http\Controllers\Remisiones;

use Exception;
use App\Rules\ExistsS3;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Rules\AvailableForReceive;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Remisiones\RemisionDetalle;
use App\Models\Parametrizacion\ParametroConstante;

class RemisionDetalleController extends Controller
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
                $remisionesDetalles = RemisionDetalle::obtenerColeccionLigera($datos);
            } else {
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $remisionesDetalles = RemisionDetalle::obtenerColeccion($datos);
            }
            return response($remisionesDetalles, Response::HTTP_OK);
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
                'numero_remision' => 'integer|required|exists:remisiones,numero_remision',
                'sello_id' => 'integer|required|exists:sellos,id',
                'producto_id' => [
                    'integer',
                    'required',
                    Rule::exists('productos_clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'kit_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('kits','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'serial' => 'string|required',
                'indicativo_confirmacion_recepcion' => 'string|required',
            ],  $messages = [
                'sello_id.exists'=>'El sello seleccionado no existe o está en estado inactivo',
                'producto_id.exists'=>'El producto seleccionado no existe o está en estado inactivo',
                'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eventoRemision = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_REMISION')->first()->valor_parametro??0
            );
            $eventoRecepcionRemision = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECIBIR_REMISION')->first()->valor_parametro??0
            );
            if(!$eventoRemision || !$eventoRecepcionRemision){
                return response(get_response_body('Faltan parámetros por definir'), Response::HTTP_BAD_REQUEST);
            }

            $remisionDetalle = RemisionDetalle::toogleRemisionar($datos);
            
            if ($remisionDetalle) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["se ha remisionado el sello/kit correctamente.", 2], $remisionDetalle),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear la remisión."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeAll(Request $request)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $validator = Validator::make($datos, [
                'numero_remision' => 'integer|required|exists:remisiones,numero_remision',
                'indicativo_confirmacion_recepcion' => 'string|required',
                'seleccionar' => 'boolean|required',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $rows = $datos['data'];
            foreach($rows as $row){
                $validator = Validator::make($row, [
                    'id' => 'integer|required|exists:sellos,id',
                    'producto_id' => [
                        'integer',
                        'required',
                        Rule::exists('productos_clientes','id')->where(function ($query) {
                            $query->where('estado', 1);
                        }),
                    ],
                    'kit_id' => [
                        'integer',
                        'nullable',
                        Rule::exists('kits','id')->where(function ($query) {
                            $query->where('estado', 1);
                        }),
                    ],
                    'serial' => 'string|required',
                ],  $messages = [
                    'id.exists'=>'El sello seleccionado no existe o está en estado inactivo',
                    'producto_id.exists'=>'El producto seleccionado no existe o está en estado inactivo',
                    'kit_id.exists'=>'El kit seleccionado no existe o está en estado inactivo',
                ]);
    
                if ($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }

            $eventoRemision = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_REMISION')->first()->valor_parametro??0
            );
            $eventoRecepcionRemision = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECIBIR_REMISION')->first()->valor_parametro??0
            );
            if(!$eventoRemision || !$eventoRecepcionRemision){
                return response(get_response_body('Faltan parámetros por definir'), Response::HTTP_BAD_REQUEST);
            }

            $remisionDetalle = RemisionDetalle::toogleRemisionarTodos($datos);
            
            if ($remisionDetalle) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["se han remisionado los sellos/kits correctamente.", 2], $remisionDetalle),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear la remisión."]), Response::HTTP_CONFLICT);
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
                'id' => 'integer|required|exists:remisiones,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(RemisionDetalle::cargar($id), Response::HTTP_OK);
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
                'id' => 'integer|required|exists:remisiones,id',
                'numero_remision' => 'integer|nullable',
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'fecha_remision' => 'date|required',
                'transportador_id' => [
                    'integer',
                    'required',
                    new ExistsS3(
                        'terceros_servicios',
                        $datos['transportador_id']
                    )
                ],
                'lugar_envio_id' => [
                    'integer',
                    'required',
                    Rule::exists('lugares','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'user_envio_id' => [
                    'integer',
                    'required',
                    Rule::exists('usuarios','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'lugar_destino_id' => [
                    'integer',
                    'required',
                    Rule::exists('lugares','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'user_destino_id' => [
                    'integer',
                    'required',
                    Rule::exists('usuarios','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'hora_estimada_envio' => 'date_format:H:i|required',
                'guia_transporte' => 'string|required',
                'indicativo_confirmacion_recepcion' => 'string|required',
                'fecha_aceptacion' => 'date|nullable',
                'fecha_rechazo' => 'date|nullable',
                'fecha_anulacion' => 'date|nullable',
                'observaciones_remision' => 'string|nullable|max:128',
                'observaciones_rechazo' => 'string|nullable|max:128',
            ],  $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'lugar_envio_id.exists'=>'El lugar seleccionado no existe o está en estado inactivo',
                'user_envio_id.exists'=>'El usuario seleccionado no existe o está en estado inactivo',
                'lugar_destino_id.exists'=>'El lugar seleccionado no existe o está en estado inactivo',
                'user_destino_id.exists'=>'El usuario seleccionado no existe o está en estado inactivo',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $parametros = ParametroConstante::cargarParametros();
            if(!isset($parametros['CONSECUTIVO_REMISION'])){
                return response('Debe definir parámetro de consecutivo de remisión', Response::HTTP_BAD_REQUEST);
            }

            $remisionDetalle = RemisionDetalle::modificarOCrear($datos);
            if($remisionDetalle){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La remisión ha sido modificada.", 1], $remisionDetalle),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar la remisión."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:remisiones,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = RemisionDetalle::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La remisión ha sido eliminada.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar la remisión."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function read(Request $request){
        try{
            $datos = $request->all();
            $validator = Validator::make($datos, [
                'numero_remision' => 'integer|required',
                'producto_id' => 'integer|required',
                'tipo' => 'string|required',
                // 'serial_final' => 'string|nullable',
                // 'serial_inicial' => [
                //     'string',
                //     'required',
                //     new AvailableForReceive(
                //         $datos['numero_remision'],
                //         $datos['producto_id'],
                //         $datos['tipo'],
                //         $datos['serie_final']??null,
                //     )
                // ],
            ]);

            if(!$request->todos){
                $addValidator = Validator::make($datos, [
                    'serial_final' => 'string|nullable',
                    'serial_inicial' => [
                        'string',
                        'required',
                        new AvailableForReceive(
                            $datos['numero_remision'],
                            $datos['producto_id'],
                            $datos['tipo'],
                            $datos['serie_final']??null,
                        )
                    ],
                ]);
            }

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            if(!$request->todos){
                if ($addValidator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }
            if(!$request->todos){
                $serial = RemisionDetalle::read($datos);
            } else {
                $serial = RemisionDetalle::readAll($datos);
            }
            if($serial){
                return response($serial, Response::HTTP_OK);
            }
            return null;
        }catch (Exception $e){
            return response(get_response_body([$e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
