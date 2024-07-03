<?php

namespace App\Rules;

use App\Models\Pedidos\PedidoDetalle;
use Illuminate\Contracts\Validation\Rule;

class UniqueKitInOrder implements Rule
{
    public $id;
    public $pedido_id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($id, $pedido_id)
    {
        $this->id = $id;
        $this->pedido_id = $pedido_id;
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
        if ($value == null) return true;
        $currentRow = PedidoDetalle::find($this->id);
        if($currentRow->kit_id == $value) return true;
        $dif = PedidoDetalle::where('pedido_id', $this->pedido_id)
            ->where('kit_id', $value)
            ->whereNull('producto_id')
            ->where('id', '<>', $this->id)
            ->count();
        return $dif <= 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Ya se ha agregado este kit a este pedido';
    }
}
