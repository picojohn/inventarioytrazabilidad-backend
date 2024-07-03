<?php

namespace App\Models\Pedidos;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoS3 extends Model
{
    protected $connection = 'mysql2';

    protected $table = 'pedidos';

    public static function obtenerColeccionLigera($dto){
        $query = PedidoS3::select(
                'id',
                DB::Raw('CAST(numero_pedido AS CHAR) AS numero_pedido'),
                'fecha_pedido',
                'fecha_entrega_pedido',
                'asociado_id',
                'estado',
            )
            ->where('estado_pedido', 'CON');
        $query->orderBy('numero_pedido', 'asc');
        return $query->get();
    }

    use HasFactory;
}
