<?php

namespace App\Http\Controllers\Remisiones;

use App\Rules\ExistsS3;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Remisiones\Remision;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Parametrizacion\ParametroConstante;

class RemisionController extends Controller
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
                $remisiones = Remision::obtenerColeccionLigera($datos);
            } else if($request->sellos){
                if($request->remisionados){
                    $remisiones = Remision::obtenerColeccionSellosRemisionados($datos);
                } else {
                    $remisiones = Remision::obtenerColeccionSellos($datos);
                }
            } else {
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $remisiones = Remision::obtenerColeccion($datos);
            }
            return response($remisiones, Response::HTTP_OK);
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
                'numero_remision' => 'integer|nullable',
                'cliente_envio_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'cliente_destino_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'fecha_remision' => 'date|required',
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
                'guia_transporte' => 'string|nullable',
                'transportador' => 'string|nullable',
                'indicativo_confirmacion_recepcion' => 'string|required',
                'fecha_aceptacion' => 'date|nullable',
                'fecha_rechazo' => 'date|nullable',
                'fecha_anulacion' => 'date|nullable',
                'observaciones_remision' => 'string|nullable|max:128',
                'observaciones_rechazo' => 'string|nullable|max:128',
            ],  $messages = [
                'cliente_envio_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'cliente_destino_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
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

            $remision = Remision::modificarOCrear($datos);
            
            if ($remision) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La remisión ha sido creada.", 2], $remision),
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

            return response(Remision::cargar($id), Response::HTTP_OK);
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
                'cliente_envio_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'cliente_destino_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'fecha_remision' => 'date|required',
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
                'hora_estimada_envio' => 'date_format:H:i:s|required',
                'guia_transporte' => 'string|nullable',
                'transportador' => 'string|nullable',
                'indicativo_confirmacion_recepcion' => 'string|required',
                'fecha_aceptacion' => 'date|nullable',
                'fecha_rechazo' => 'date|nullable',
                'fecha_anulacion' => 'date|nullable',
                'observaciones_remision' => 'string|nullable|max:128',
                'observaciones_rechazo' => 'string|nullable|max:128',
            ],  $messages = [
                'cliente_envio_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'cliente_destino_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
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

            $remision = Remision::modificarOCrear($datos);
            if($remision){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La remisión ha sido modificada.", 1], $remision),
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
    public function destroy(Request $request, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
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

            $eventoAnularRemision = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_ANULAR_REMISION')->first()->valor_parametro??0
            );
            if(!$eventoAnularRemision){
                return response(get_response_body('Faltan parámetros por definir'), Response::HTTP_BAD_REQUEST);
            }

            $eliminado = Remision::eliminar($id, $datos);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["La remisión ".Remision::find($id)->numero_remision." ha sido anulada.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar anular la remisión."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function confirmarORechazar(Request $request, $id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $datos['id'] = $id;
            $validator = Validator::make($datos, [
                'id' => [
                    'integer',
                    'required',
                    Rule::exists('remisiones','id')->where(function ($query) {
                        $query->where('estado_remision', 'GEN');
                    }),
                ],
                'action' => 'string|required',
                'observaciones_rechazo' => 'string|nullable|max:128',
            ],  $messages = [
                'id.exists'=>'La remisión seleccionada no existe o está en estado incorrecto',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eventoRechazarRemisionS = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECHAZAR_REMISION_SERIAL_SI')->first()->valor_parametro??0
            );
            $eventoRechazarRemisionN = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECHAZAR_REMISION_SERIAL_NO')->first()->valor_parametro??0
            );
            $eventoRecepcionRemision = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_RECIBIR_REMISION')->first()->valor_parametro??0
            );
            if(!$eventoRechazarRemisionS || !$eventoRechazarRemisionN || !$eventoRecepcionRemision){
                return response(get_response_body('Faltan parámetros por definir'), Response::HTTP_BAD_REQUEST);
            }

            $remision = Remision::confirmarORechazar($datos);

            if ($remision) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body([
                        "La remisión ha sido ".($request->action == 'Confirm'?"aceptada":"rechazada"), 
                        $request->action == 'Confirm'?5:6
                    ], $remision),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body([
                    "Ocurrió un error al intentar ".$request->action == 'Confirm'?"aceptar":"rechazar"." la remisión."
                ]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
