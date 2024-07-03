<?php

namespace App\Rules;

use App\Models\Parametrizacion\Producto;
use Illuminate\Contracts\Validation\Rule;
use App\Models\Parametrizacion\KitProducto;
use App\Models\Parametrizacion\ProductoCliente;

class DestroyLastBag implements Rule
{
    public $id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
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
        $isABag = ProductoCliente::select('id')->where('indicativo_producto_empaque', 'S')->where('id', $kitProducto->producto_id)->first();
        if($isABag){
            $products = ProductoCliente::select('id')->where('indicativo_producto_empaque', 'S');
            $bagsInKit = KitProducto::where('kit_id', $kitProducto->kit_id)
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
        return 'No se puede eliminar el único producto tipo bolsa del kit';
    }
}
