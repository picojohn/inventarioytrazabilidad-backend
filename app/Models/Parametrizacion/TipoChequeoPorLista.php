<?php

namespace App\Models\Parametrizacion;

use Exception;
use App\Enum\AccionAuditoriaEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Parametrizacion\TipoChequeo;
use App\Models\Parametrizacion\TipoListaChequeo;
use App\Models\Parametrizacion\UnidadCargaTransporte;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoChequeoPorLista extends Model
{
    use HasFactory;

    protected $table = 'tipos_chequeos_por_lista';

    protected $fillable = [
        'nombre',
        'unidad_carga_id',
        'lista_chequeo_id',
        'tipo_chequeo_id',
        'cliente_id',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
        'usuario_modificacion_id',
        'usuario_modificacion_nombre'
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function unidadCarga(){
        return $this->belongsTo(UnidadCargaTransporte::class, 'unidad_carga_id');
    }

    public function tipoChequeoPorLista(){
        return $this->belongsTo(TipoListaChequeo::class, 'lista_chequeo_id');
    }

    public function tipoChequeo(){
        return $this->belongsTo(TipoChequeo::class, 'tipo_chequeo_id');
    }

    public static function obtenerColeccion($dto){
        $lista_id = $dto['lista_chequeo_id'];
        $lista = TipoListaChequeo::find($lista_id);
        $query = DB::table('tipos_chequeos AS t1')
            ->select(
                't1.id',
                't1.nombre',
                DB::raw("IFNULL((SELECT s1.id
                    FROM tipos_chequeos_por_lista AS s1
                    WHERE s1.lista_chequeo_id = '$lista_id'
                    AND s1.tipo_chequeo_id = t1.id
                    ), 0) AS checked"
                ),
            )
            ->where('t1.cliente_id', $lista->cliente_id);

        if(isset($dto['nombre'])){
            $query->where('t1.nombre', 'like', '%' . $dto['nombre'] . '%');
        }
        
        if(isset($dto['soloSeleccionados']) && $dto['soloSeleccionados'] === 'true'){
            $query->whereRaw("IFNULL((SELECT s1.id
                FROM tipos_chequeos_por_lista AS s1
                WHERE s1.lista_chequeo_id = '$lista_id'
                AND s1.tipo_chequeo_id = t1.id
                ), 0) <> 0"
            );
        }
        
        if (isset($dto['ordenar_por'])){
            $array = explode(':', $dto['ordenar_por']); 
            $attribute = $array[0]; 
            $value = $array[1];
            if($attribute == 'nombre'){
                $query->orderBy('nombre', $value);
            }
        }else{
            $query->orderBy("nombre", "asc");
        }

        $tiposChequeosPorLista = $query->paginate($dto['limite'] ?? 100);
        $data = [];
        foreach ($tiposChequeosPorLista ?? [] as $tipoChequeoPorLista){
            array_push($data, $tipoChequeoPorLista);
        }

        $cantidaTiposChequeosPorLista = count($tiposChequeosPorLista);
        $to = isset($tiposChequeosPorLista) && $cantidaTiposChequeosPorLista > 0 ? $tiposChequeosPorLista->currentPage() * $tiposChequeosPorLista->perPage() : null;
        $to = isset($to) && isset($tiposChequeosPorLista) && $to > $tiposChequeosPorLista->total() && $cantidaTiposChequeosPorLista> 0 ? $tiposChequeosPorLista->total() : $to;
        $from = isset($to) && isset($tiposChequeosPorLista) && $cantidaTiposChequeosPorLista > 0 ?
            ( $tiposChequeosPorLista->perPage() > $to ? 1 : ($to - $cantidaTiposChequeosPorLista) + 1 )
            : null;
        return [
            'datos' => $data,
            'desde' => $from,
            'hasta' => $to,
            'por_pagina' => isset($tiposChequeosPorLista) && $cantidaTiposChequeosPorLista > 0 ? +$tiposChequeosPorLista->perPage() : 0,
            'pagina_actual' => isset($tiposChequeosPorLista) && $cantidaTiposChequeosPorLista > 0 ? $tiposChequeosPorLista->currentPage() : 1,
            'ultima_pagina' => isset($tiposChequeosPorLista) && $cantidaTiposChequeosPorLista > 0 ? $tiposChequeosPorLista->lastPage() : 0,
            'total' => isset($tiposChequeosPorLista) && $cantidaTiposChequeosPorLista > 0 ? $tiposChequeosPorLista->total() : 0
        ];
    }

    public static function toogleCheck($dto)
    {
        $user = Auth::user();
        $usuario = $user->usuario();
        $checked = TipoChequeoPorLista::where('unidad_carga_id', $dto['unidad_carga_id'])
            ->where('lista_chequeo_id', $dto['lista_chequeo_id'])
            ->where('tipo_chequeo_id', $dto['tipo_chequeo_id'])
            ->first();
        
        if($checked){
            $checked->delete();
            return 'deleted';
        }

        $dto['usuario_creacion_id'] = $usuario->id ?? null;
        $dto['usuario_creacion_nombre'] = $usuario->nombre ??  null;
        $dto['usuario_modificacion_id'] = $usuario->id ??  null;
        $dto['usuario_modificacion_nombre'] = $usuario->nombre ??  null;
        $dto['cliente_id'] = $usuario->asociado_id;
        $tipoChequeoPorLista = new TipoChequeoPorLista();
        $tipoChequeoPorLista->fill($dto);
        $tipoChequeoPorLista->save();

        // Guardar auditoria
        $auditoriaDto = array(
            'id_recurso' => $tipoChequeoPorLista->id,
            'nombre_recurso' => TipoChequeoPorLista::class,
            'descripcion_recurso' => $tipoChequeoPorLista->nombre,
            'accion' => AccionAuditoriaEnum::CREAR,
            'recurso_original' => $tipoChequeoPorLista->toJson(),
            'recurso_resultante' => null
        );
        AuditoriaTabla::crear($auditoriaDto);

        return 'added';
    }
}
