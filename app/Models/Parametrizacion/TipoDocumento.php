<?php

namespace App\Models\Parametrizacion;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoDocumento extends Model
{
    protected $connection = 'mysql2';

    protected $table = 'tipos_de_documentos';

    public static function obtenerColeccionLigera($dto){
        $query = TipoDocumento::select(
                'id',
                'codigo',
                'nombre',
                'estado',
            );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    use HasFactory;
}
