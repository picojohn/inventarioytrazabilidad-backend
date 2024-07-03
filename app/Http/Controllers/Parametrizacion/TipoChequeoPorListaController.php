<?php

namespace App\Http\Controllers\Parametrizacion;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Parametrizacion\TipoChequeoPorLista;

class TipoChequeoPorListaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, $lista_chequeo_id)
    {
        try{
            $datos = $request->all();
            $datos['lista_chequeo_id'] = $lista_chequeo_id;
            if(!$request->ligera){
                $validator = Validator::make($datos, [
                    'limite' => 'integer|between:1,500',
                    'lista_chequeo_id' => 'integer|required|exists:tipos_listas_chequeo,id',
                ]);

                if($validator->fails()) {
                    return response(
                        get_response_body(format_messages_validator($validator))
                        , Response::HTTP_BAD_REQUEST
                    );
                }
            }
            $tiposChequeoPorLista = TipoChequeoPorLista::obtenerColeccion($datos);
            return response($tiposChequeoPorLista, Response::HTTP_OK);
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
    public function store(Request $request, $lista_chequeo_id)
    {
        DB::beginTransaction(); // Se abre la transacción
        try {
            $datos = $request->all();
            $datos['lista_chequeo_id'] = $lista_chequeo_id;
            $validator = Validator::make($datos, [
                'unidad_carga_id' => 'integer|required|exists:unidades_carga_transporte,id',
                'lista_chequeo_id' => 'integer|required|exists:tipos_listas_chequeo,id',
                'tipo_chequeo_id' => 'integer|required|exists:tipos_chequeos,id',
            ]);

            if ($validator->fails()) {
                return response(
                    get_response_body(format_messages_validator($validator))
                    , Response::HTTP_BAD_REQUEST
                );
            }

            $tipoChequeoPorLista = TipoChequeoPorLista::toogleCheck($datos);
            $added = 'Se ha añadido el tipo de chequeo a la lista';
            $deleted = 'Se ha desañadido el tipo de chequeo a la lista';
            if ($tipoChequeoPorLista) {
                DB::commit(); // Se cierra la transacción correctamente
                return response(
                    get_response_body([($tipoChequeoPorLista==='deleted'?$deleted:$added), 1]),
                    Response::HTTP_OK
                );
            } else {
                DB::rollback(); // Se devuelven los cambios, por que la transacción falla
                return response(get_response_body(["Ocurrió un error al intentar modificar la lista de chequeo."]), Response::HTTP_CONFLICT);
            }
        }catch (Exception $e){
            DB::rollback(); // Se devuelven los cambios, por que la transacción falla
            return response($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
