<?php

namespace App\Http\Controllers\Pedidos;

use Exception;
use App\Rules\ReadKit;
use App\Rules\ExistsS3;
use App\Rules\ReadSeal;
use Illuminate\Http\Request;
use App\Models\Pedidos\Sello;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Rules\AvailableForInstall;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Exports\Pedidos\InventarioExport;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\TipoEvento;
use App\Models\Parametrizacion\ParametroConstante;


class SelloController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, $numero_pedido)
    {
        try{
            $datos = $request->all();
            $datos['numero_pedido'] = $numero_pedido;
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
                $sellos = Sello::ordenLecturaKit($datos);
            } else {
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $sellos = Sello::obtenerColeccionLeidos($datos);
            }
            return response($sellos, Response::HTTP_OK);
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
            if($datos['tipo'] === 'I'){
                $validator = Validator::make($datos, [
                    'numero_pedido' => 'integer|required',
                    'serie' => 'string|required',
                    'producto_s3_id' => [
                        'integer',
                        'required',
                        new ReadSeal(
                            $datos['numero_pedido'],
                            $datos['producto_s3_id'],
                            $datos['serie'],
                        )
                    ],
                ]);

                if ($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            } else {
                if(!$request->total){
                    $validator = Validator::make($datos, [
                        'numero_pedido' => 'integer|required',
                        'serie' => 'string|required',
                        'producto_s3_id' => [
                            'integer',
                            'required',
                            new ReadKit(
                                $datos['numero_pedido'],
                                $datos['producto_s3_id'],
                                $datos['serie'],
                            )
                        ],
                    ]);
        
                    if ($validator->fails()) {
                        return response(
                            get_response_body(format_messages_validator($validator))
                            , Response::HTTP_BAD_REQUEST
                        );
                    }
                }
            }
            if($datos['tipo'] === 'I'){
                $sello = Sello::leerSello($datos);
                if ($sello) {
                    DB::commit(); // Se cierra la transacción correctamente
                    return response(
                        get_response_body(["El sello ha sido leído correctamente.", 2], $sello),
                        Response::HTTP_CREATED
                    );
                } else {
                    DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                    return response(get_response_body(["Ocurrió un error al intentar leer el sello."]), Response::HTTP_CONFLICT);
                }
            } else {
                if(!$request->total){
                    $sello = Sello::leerSellodeKit($datos);
                    if ($sello) {
                        DB::commit(); // Se cierra la transacción correctamente
                        return response($sello, Response::HTTP_OK);
                    } else {
                        DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                        return response(get_response_body(["Ocurrió un error al intentar leer el sello."]), Response::HTTP_CONFLICT);
                    }
                } else {
                    $sello = Sello::guardarLecturaKit($datos);
                    if ($sello) {
                        DB::commit(); // Se cierra la transacción correctamente
                        return response(
                            get_response_body(["El kit ha sido leído correctamente.", 2], $sello),
                            Response::HTTP_CREATED
                        );
                    } else {
                        DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                        return response(get_response_body(["Ocurrió un error al intentar leer el kit."]), Response::HTTP_CONFLICT);
                    }
                }
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

            return response(Sello::cargar($id), Response::HTTP_OK);
        }catch (Exception $e){
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
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
                'id' => 'integer|required|exists:sellos,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = Sello::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El sello/kit ha sido devuelto para lectura.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar des-leer el sello."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importar(Request $request){
        DB::beginTransaction(); // Se abre la transacción
        try{
            $validator = Validator::make($request->all(), [
                'usuario' => 'integer|required|exists:usuarios,id',
                'archivo' => 'file|required',
            ]);
            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }
            $archivo = $request->file('archivo');
            $usuario =  $request->all()['usuario'];
            $errores = Sello::importar($archivo,$usuario);
            DB::commit(); // Se cierra la transacción correctamente
            return response(
                get_response_body(["Los datos de ordenes de servicio se han importado."], $errores),
                Response::HTTP_CREATED
            );
        // }catch (ModelException $e){
        //     DB::rollback(); // Se devuelven los cambios, por que la transacción falla
        //     return response(get_response_body([$e->getMessage()]), Response::HTTP_CONFLICT);
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(get_response_body(["Revisar archivo de carga. Estructura de información no corresponde."],$e), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function consultar(Request $request)
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

            if(isset($datos['ordenar_por'])){
                $datos['ordenar_por'] = format_order_by_attributes($datos);
            }
            $sellos = Sello::consultar($datos);

            return response($sellos, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function consultarStockMinimo(Request $request)
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

            if(isset($datos['ordenar_por'])){
                $datos['ordenar_por'] = format_order_by_attributes($datos);
            }
            $sellos = Sello::consultarStockMinimo($datos);

            return response($sellos, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function consultarStockMinimoPorLugar(Request $request)
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

            if(isset($datos['ordenar_por'])){
                $datos['ordenar_por'] = format_order_by_attributes($datos);
            }
            $sellos = Sello::consultarStockMinimoPorLugar($datos);

            return response($sellos, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportarInventario(Request $request)
    {
        $nombreArchivo = 'Informe-Inventarios-' . time() . '.xlsx';
        return (new InventarioExport($request->all()))->download($nombreArchivo);
    }

    public function leerSelloParaInstalar(Request $request){
        try{
            $datos = $request->all();
            $validator = Validator::make($datos, [
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
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
                'serial' => [
                    'string',
                    'required',
                    new AvailableForInstall(
                        $datos['cliente_id'],
                        $datos['producto_id'],
                    )
                ],
            ],  $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'producto_id.integer'=>'Debe seleccionar un producto',
                'producto_id.exists'=>'El producto seleccionado no existe o está en estado inactivo',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $sello = Sello::leerSelloParaInstalar($datos);
            if($sello){
                return response($sello, Response::HTTP_OK);
            }
            return null;
        }catch (Exception $e){
            return response(get_response_body([$e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function instalar(Request $request)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $validator = Validator::make($datos, [
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'contenedor_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('contenedores','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'lugar_instalacion_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('lugares','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'zona_instalacion_id' => [
                    'integer',
                    'nullable',
                    Rule::exists('zonas_contenedores','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'operacion_embarque_id' => [
                    'integer',
                    'nullable',
                    // Rule::exists('zonas_contenedores','id')->where(function ($query) {
                    //     $query->where('estado', 1);
                    // }),
                ],
                'documento_referencia' => 'string|nullable|max:25',
            ],  $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'contenedor_id.exists'=>'El contenedore seleccionado no existe o está en estado inactivo',
                'lugar_instalacion_id.exists'=>'El lugar seleccionado no existe o está en estado inactivo',
                'zona_instalacion_id.exists'=>'La zona seleccionada no existe o está en estado inactivo',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eventoInstalacion = TipoEvento::find(
                ParametroConstante::where('codigo_parametro', 'ID_TIPO_EVENTO_INSTALACION')->first()->valor_parametro??0
            );
            $rutaEvidencias = ParametroConstante::where('codigo_parametro', 'RUTA_EVIDENCIAS_INSTALACION')->first();
            if(!$eventoInstalacion || !$rutaEvidencias){
                return response(get_response_body('Faltan parámetros por definir'), Response::HTTP_BAD_REQUEST);
            }
            
            $instalar = Sello::instalar($datos, $request);
            if($instalar){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El/los sello(s) se ha(n) instalado correctamente.", 2]),
                    Response::HTTP_CREATED
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar instalar el/los sello(s)."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexInstalacion(Request $request)
    {
        try{
            $datos = $request->all();
            if($request->automatico){
                $sellos = Sello::getRandomSeal($datos);
            } else {
                $sellos = Sello::listaSellosParaInstalar($datos);
            }
            if($sellos){
                return response($sellos, Response::HTTP_OK);
            } else {
                return response(get_response_body("No hay seriales de este producto para instalar"), Response::HTTP_BAD_REQUEST);
            }
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexActualizarEstado(Request $request)
    {
        try{
            $datos = $request->all();
            $sellos = Sello::indexActualizarEstado($datos);
            return response($sellos, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function actualizarEstado(Request $request)
    {
        DB::beginTransaction(); // Se abre la transacción
        try{
            $datos = $request->all();
            $validator = Validator::make($datos, [
                'id' => [
                    'integer',
                    'required',
                    Rule::exists('sellos','id')->where(function ($query) {
                        $query->whereIn('estado_sello', ['STO', 'DEV', 'INS']);
                    }),
                ],
                'evento_id' => [
                    'integer',
                    'required',
                    Rule::exists('tipos_eventos','id')->where(function ($query) {
                        $query->where('estado', 1)
                            ->where('indicativo_evento_manual', 'S');
                    }),
                ],
                'observaciones_evento' => [
                    'string',
                    'required',
                ],
            ],  $messages = [
                'id.exists'=>'El sello seleccionado no existe o está en un estado no permitido para esta acción',
                'evento_id.exists'=>'El evento seleccionado no existe o no es aplicable manualmente',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }
            
            $actualizar = Sello::actualizarEstado($datos);
            if($actualizar){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El estado del sello ha sido actualizado correctamente.", 2]),
                    Response::HTTP_CREATED
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar actualizar el estado del sello."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
