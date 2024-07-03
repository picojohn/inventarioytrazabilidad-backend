<?php

namespace App\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;
use App\Models\Parametrizacion\ProductoCliente;

class UniqueSerial implements Rule
{
    public $data;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if(isset($this->data['producto_id'])){
            $producto = ProductoCliente::find($this->data['producto_id']);
            $consecutivo = $this->data['consecutivo_serie_inicial'];
            $cantidad = $this->data['cantidad'];
            $seriales = DB::table('pedidos_detalle AS t1')
                ->join('pedidos AS t2', 't2.id', 't1.pedido_id')
                ->where('t2.cliente_id', $producto->cliente->id)
                ->where('t1.producto_id', $this->data['producto_id'])
                ->where('t1.id', '<>' ,$this->data['id'])
                ->where('t1.posfijo', $this->data['posfijo'])
                ->where('t1.prefijo', $this->data['prefijo'])
                ->where('t1.longitud_serial', $this->data['longitud_serial'])
                ->where('t1.estado', 1)
                ->where(function($query) use($consecutivo, $cantidad){
                    $query->whereRaw(
                            '? BETWEEN t1.consecutivo_serie_inicial AND t1.consecutivo_serie_inicial+t1.cantidad-1', 
                            [intval($consecutivo)]
                        )
                        ->orWhereRaw(
                            '? BETWEEN t1.consecutivo_serie_inicial AND t1.consecutivo_serie_inicial+t1.cantidad-1', 
                            [intval($consecutivo)+intval($cantidad)-1]
                        );
                })
                ->count();
            return $seriales == 0;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Las series a generar ya han sido asignadas a este producto para este cliente';
    }
}
