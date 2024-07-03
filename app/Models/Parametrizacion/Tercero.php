<?php

namespace App\Models\Parametrizacion;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tercero extends Model
{
    protected $connection = 'mysql2';

    protected $table = 'terceros_servicios';

    public static function obtenerColeccionLigera($dto){
        $query = Tercero::select(
            'id',
            DB::raw("IF(tipo_persona ='N',
                CONCAT(
                    IFNULL(CONCAT(nombre),''),
                    IFNULL(CONCAT(' ',segundo_nombre),''),
                    IFNULL(CONCAT(' ',primer_apellido),''),
                    IFNULL(CONCAT(' ',segundo_apellido),'')
                )
                ,nombre
            ) AS nombre"),
            'estado',
        );

        if(isset($dto['transportador'])){
            $query->where('tipo', 'TR');
        }

        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    use HasFactory;
}
