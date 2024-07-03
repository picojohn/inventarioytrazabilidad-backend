<?php

namespace App\Rules;

use App\Models\Pedidos\PedidoS3;
use App\Models\Parametrizacion\Color;
use App\Models\Parametrizacion\Tercero;
use App\Models\Parametrizacion\Asociado;
use App\Models\Parametrizacion\Producto;
use Illuminate\Contracts\Validation\Rule;

class ExistsS3 implements Rule
{
    public $table;
    public $id;
    public $fieldName;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($table, $id)
    {
        $this->table = $table;
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
        $count = 0;
        switch ($this->table){
            case 'asociados_negocios':
                $count = Asociado::where('id', $this->id)->where('estado',1)->count();
                $this->fieldName = 'asociado de negocios';
                break;
            case 'productos':
                $count = Producto::where('id', $this->id)->where('estado',1)->count();
                $this->fieldName = 'producto';
                break;
            case 'pedidos':
                $count = PedidoS3::where('numero_pedido', $this->id)->where('estado',1)->count();
                $this->fieldName = 'pedido';
                break;
            case 'colores':
                $count = Color::where('id', $this->id)->where('estado',1)->count();
                $this->fieldName = 'color';
                break;
            case 'terceros_servicios':
                $count = Tercero::where('id', $this->id)->where('estado',1)->count();
                $this->fieldName = 'tercero';
                break;
        }
        return $count > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'El '.$this->fieldName.' seleccionado no existe o está en estado inactivo';
    }
}
