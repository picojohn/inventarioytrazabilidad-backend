<?php

namespace App\Models\Parametrizacion;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asociado extends Model
{
    protected $connection = 'mysql2';

    protected $table = 'asociados_negocios';

    public static function obtenerColeccionLigera($dto){
        $query = Asociado::select(
                'id',
                DB::Raw(
                    "IF(asociados_negocios.tipo_persona ='N',
                        CONCAT(
                            IFNULL(CONCAT(asociados_negocios.nombre),''),
                            IFNULL(CONCAT(' ',asociados_negocios.segundo_nombre),''),
                            IFNULL(CONCAT(' ',asociados_negocios.primer_apellido),''),
                            IFNULL(CONCAT(' ',asociados_negocios.segundo_apellido),'')
                        )
                        ,asociados_negocios.nombre
                    ) AS nombre"
                ),
                'estado',
            );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    use HasFactory;
}
