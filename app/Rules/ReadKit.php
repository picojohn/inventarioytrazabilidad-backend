<?php

namespace App\Rules;

use App\Models\Pedidos\Sello;
use App\Models\Pedidos\Pedido;
use App\Models\Parametrizacion\Cliente;
use Illuminate\Contracts\Validation\Rule;

class ReadKit implements Rule
{
    public $numero_pedido;
    public $producto_s3_id;
    public $serie;
    public $error = '';
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($numero_pedido, $producto_s3_id, $serie)
    {
        $this->numero_pedido = $numero_pedido;
        $this->producto_s3_id = $producto_s3_id;
        $this->serie = $serie;
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
        $leido = Sello::where('numero_pedido', $this->numero_pedido)
            ->where('producto_s3_id', $this->producto_s3_id)
            ->where('tipo_empaque_despacho', 'K')
            ->where('estado_sello', 'LEC')
            ->where(function($query){
                $query->where('serial', $this->serie)
                    ->orWhere('serial_interno', $this->serie)
                    ->orWhere('serial_qr', $this->serie)
                    ->orWhere('serial_datamatrix', $this->serie)
                    ->orWhere('serial_pdf', $this->serie);
            })
            ->count();
        if($leido > 0){
            $this->error = 'Esta serie ya fue empacada para este producto';
            return false;
        }
        $pedido = Pedido::where("numero_pedido", $this->numero_pedido)->first();
        $asignacionLectura = "N";
        if($pedido){
            $cliente = Cliente::find($pedido->cliente_id);
            if($cliente->asignacion_sellos_lectura == "S"){
                $asignacionLectura = "S";
            }
        }
        if($asignacionLectura != "S"){
            $existe = Sello::where('numero_pedido', $this->numero_pedido)
            ->where('producto_s3_id', $this->producto_s3_id)
            ->where('tipo_empaque_despacho', 'K')
            ->whereIn('estado_sello', ['GEN', 'DEV'])
            ->where(function($query){
                $query->where('serial', $this->serie)
                    ->orWhere('serial_interno', $this->serie)
                    ->orWhere('serial_qr', $this->serie)
                    ->orWhere('serial_datamatrix', $this->serie)
                    ->orWhere('serial_pdf', $this->serie);
            })
            ->count();
        } else {
            $existe = Sello::where('numero_pedido', $this->numero_pedido)
            ->where('producto_s3_id', $this->producto_s3_id)
            ->where('tipo_empaque_despacho', 'K')
            ->whereIn('estado_sello', ['GEN', 'DEV'])
            ->count();
        }
        if($existe < 1){
            $this->error = 'Serie no corresponde al articulo';
            return false;
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
        return $this->error;
    }
}
