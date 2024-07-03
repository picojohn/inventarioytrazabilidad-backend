<?php

namespace App\Models\Parametrizacion;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Color extends Model
{
    protected $connection = 'mysql2';

    protected $table = 'colores';

    public static function obtenerColeccionLigera($dto){
        $query = Color::select(
            'id',
            'nombre',
            'estado',
        );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    use HasFactory;
}
