<?php

namespace App\Http\Controllers\Parametrizacion;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\Parametrizacion\Tercero;
use Illuminate\Support\Facades\Validator;

class TerceroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
                $terceros = Tercero::obtenerColeccionLigera($datos);
            }else{
                if(isset($datos['ordenar_por'])){
                    $datos['ordenar_por'] = format_order_by_attributes($datos);
                }
                $terceros = Tercero::obtenerColeccion($datos);
            }
            return response($terceros, Response::HTTP_OK);
        }catch(Exception $e){
            return response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
