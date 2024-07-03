<?php

namespace App\Rules;

use App\Models\Pedidos\Sello;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Rule;

class AvailableForInstall implements Rule
{
    public $cliente_id;
    public $producto_id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($cliente_id, $producto_id)
    {
        $this->cliente_id = $cliente_id;
        $this->producto_id = $producto_id;
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
        $user = Auth::user();
        $usuario = $user->usuario();
        $sellos = Sello::where('cliente_id', $this->cliente_id)
            ->where('producto_id', $this->producto_id)
            ->where('user_id', $usuario->id)
            ->where(function($filter) use($value){
                $filter->where('serial', $value)
                    ->orWhere('serial_interno', $value)
                    ->orWhere('serial_qr', $value)
                    ->orWhere('serial_datamatrix', $value)
                    ->orWhere('serial_pdf', $value);
            })
            ->where('estado_sello', 'STO')
            ->count();
        return $sellos > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'No existen sellos disponibles para instalar con el serial y producto dados';
    }
}
