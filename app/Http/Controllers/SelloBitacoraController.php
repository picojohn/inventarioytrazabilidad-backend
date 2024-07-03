<?php

namespace App\Http\Controllers;

use Exception;
use App\Rules\ExistsS3;
use Illuminate\Http\Request;
use App\Models\SelloBitacora;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Exports\SellosBitacoraExport;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\ParametroConstante;

class SelloBitacoraController extends Controller
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
                $pedidos = SelloBitacora::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $pedidos = SelloBitacora::obtenerColeccion($datos);
            }
            return response($pedidos, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
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
                'id' => 'integer|required|exists:sellos_bitacora,id'
            ]);

            if($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            return response(SelloBitacora::cargar($id), Response::HTTP_OK);
        }catch (Exception $e){
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportarBitacora(Request $request)
    {
        $nombreArchivo = 'trazabilidad-' . time() . '.xlsx';
        return (new SellosBitacoraExport($request->all()))->download($nombreArchivo);
    }

    public function indexConsulta(Request $request){
        try{
            $datos = $request->all();
            switch ($request->tipo) {
                case 'ipp':
                    $pedidos = SelloBitacora::instalacionesPorProducto($datos);
                    break;
                case 'ilp':
                    $pedidos = SelloBitacora::instalacionesXLugarXProducto($datos);
                    break;
                case 'epl':
                    $pedidos = SelloBitacora::eventosPorLugar($datos);
                    break;
                default:
                    $pedidos = [];
                    break;
            }
            return response($pedidos, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
