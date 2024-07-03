<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Parametrizacion\LugarUsuario;

class UniqueUserInPlace implements Rule
{
    public $usuario_id;
    public $id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($usuario_id, $id = null)
    {
        $this->usuario_id = $usuario_id;
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
        $query = LugarUsuario::where('usuario_id', $this->usuario_id);
        if($this->id){
            $query->where('id', '<>', $this->id);
        }
        return $query->count() < 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'El usuario ya ha sido asignado a otro lugar';
    }
}