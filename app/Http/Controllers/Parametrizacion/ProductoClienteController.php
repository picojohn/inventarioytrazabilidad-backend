<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use App\Rules\ExistsS3;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\ProductoCliente;
use App\Exports\Parametrizacion\ProductoClienteExport;

class ProductoClienteController extends Controller
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
                $productos = ProductoCliente::obtenerColeccionLigera($datos);
            }else if($request->maximo){
                $id = $datos['id'];
                $productos = ProductoCliente::maximoValorARestar($id);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $productos = ProductoCliente::obtenerColeccion($datos);
            }
            return response($productos, Response::HTTP_OK);
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
            $signos = ['+','-','*'];
            $validator = Validator::make($datos, [
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'producto_s3_id' => [
                    'integer',
                    'required',
                    new ExistsS3(
                        'productos',
                        $datos['producto_s3_id']
                    ),
                    Rule::unique('productos_clientes')
                        ->where(fn ($query) => 
                            $query->where('cliente_id', $datos['cliente_id'])
                                ->where('producto_s3_id', $datos['producto_s3_id']) 
                        )
                ],
                'indicativo_producto_externo' => 'string|required|max:1',
                'indicativo_producto_empaque' => 'string|required|max:1',
                'valor_serial_interno' => 'integer|nullable',
                'valor_serial_qr' => 'integer|nullable',
                'valor_serial_datamatrix' => 'integer|nullable',
                'valor_serial_pdf' => 'integer|nullable',
                'operador_serial_interno' => 'string|nullable|in:'.join(',', $signos),
                'operador_serial_qr' => 'string|nullable|in:'.join(',', $signos),
                'operador_serial_datamatrix' => 'string|nullable|in:'.join(',', $signos),
                'operador_serial_pdf' => 'string|nullable|in:'.join(',', $signos),
                'nombre_producto_cliente' => 'string|required|max:128',
                'codigo_externo_producto' => 'string|nullable|max:128',
                'estado' => 'boolean|required'
            ], $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'producto_s3_id.unique'=>'El producto seleccionado ya se asignó a este cliente',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $producto = ProductoCliente::modificarOCrear($datos);
            
            if ($producto) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El producto ha sido creado.", 2], $producto),
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar crear el producto."]), Response::HTTP_CONFLICT);
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
                'id' => 'integer|required|exists:productos_clientes,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(ProductoCliente::cargar($id), Response::HTTP_OK);
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
            $signos = ['+','-','*'];
            $validator = Validator::make($datos, [
                'id' => 'integer|required|exists:productos_clientes,id',
                'cliente_id' => [
                    'integer',
                    'required',
                    Rule::exists('clientes','id')->where(function ($query) {
                        $query->where('estado', 1);
                    }),
                ],
                'producto_s3_id' => [
                    'integer',
                    'required',
                    new ExistsS3(
                        'productos',
                        $datos['producto_s3_id']
                    ),
                    Rule::unique('productos_clientes')
                        ->where(fn ($query) => 
                            $query->where('cliente_id', $datos['cliente_id'])
                                ->where('producto_s3_id', $datos['producto_s3_id']) 
                        )->ignore(ProductoCliente::find($id))
                ],
                'indicativo_producto_externo' => 'string|required|max:1',
                'indicativo_producto_empaque' => 'string|required|max:1',
                'valor_serial_interno' => 'integer|nullable',
                'valor_serial_qr' => 'integer|nullable',
                'valor_serial_datamatrix' => 'integer|nullable',
                'valor_serial_pdf' => 'integer|nullable',
                'operador_serial_interno' => 'string|nullable|in:'.join(',', $signos),
                'operador_serial_qr' => 'string|nullable|in:'.join(',', $signos),
                'operador_serial_datamatrix' => 'string|nullable|in:'.join(',', $signos),
                'operador_serial_pdf' => 'string|nullable|in:'.join(',', $signos),
                'nombre_producto_cliente' => 'string|required|max:128',
                'codigo_externo_producto' => 'string|nullable|max:128',
                'estado' => 'boolean|required'
            ], $messages = [
                'cliente_id.exists'=>'El cliente seleccionado no existe o está en estado inactivo',
                'producto_s3_id.unique'=>'El producto seleccionado ya se asignó a este cliente',
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $producto = ProductoCliente::modificarOCrear($datos);
            if($producto){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El producto ha sido modificado.", 1], $producto),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar el producto."]), Response::HTTP_CONFLICT);;
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
                'id' => 'integer|required|exists:productos_clientes,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $eliminado = ProductoCliente::eliminar($id);
            if($eliminado){
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body(["El producto ha sido elimado.", 3]),
                    Response::HTTP_OK
                );
            }else{
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar eliminar el producto."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function productoClienteExport(Request $request)
    {
        $nombreArchivo = 'ProductosCliente-' . time() . '.xlsx';
        return (new ProductoClienteExport($request->all()))->download($nombreArchivo);
    }
}
