<?php

namespace App\Rules;

use App\Models\Parametrizacion\Producto;
use Illuminate\Contracts\Validation\Rule;
use App\Models\Parametrizacion\KitProducto;
use App\Models\Parametrizacion\ProductoCliente;

class LastBagInKit implements Rule
{
    public $id;
    public $kit_id;
    public $component_id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($id, $kit_id, $component_id)
    {
        $this->id = $id;
        $this->kit_id = $kit_id;
        $this->component_id = $component_id;
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
        $kitProducto = KitProducto::find($this->id);
        $wasABag = ProductoCliente::select('id')->where('indicativo_producto_empaque', 'S')->where('id', $kitProducto->producto_id)->first();
        $isABag = ProductoCliente::select('id')->where('indicativo_producto_empaque', 'S')->where('id', $this->component_id)->first();
        if($wasABag && !$isABag){
            $products = ProductoCliente::select('id')->where('indicativo_producto_empaque', 'S');
            $bagsInKit = KitProducto::where('kit_id', $this->kit_id)
                ->where('producto_id', '<>', $kitProducto->producto_id)
                ->whereIn('producto_id', $products)
                ->count();
            return $bagsInKit > 0;
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
        return 'No se puede quitar el único componente tipo bolsa del kit.';
    }
}
