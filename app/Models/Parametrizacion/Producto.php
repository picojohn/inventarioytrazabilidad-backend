<?php

namespace App\Models\Parametrizacion;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
    protected $connection = 'mysql2';

    protected $table = 'productos';

    public static function obtenerColeccionLigera($dto){
        $query = Producto::select(
            'id',
            'nombre',
            'alias_producto',
            'codigo_producto',
            'producto_empaque',
            'estado',
        );
        $query->orderBy('nombre', 'asc');
        return $query->get();
    }

    use HasFactory;
}
