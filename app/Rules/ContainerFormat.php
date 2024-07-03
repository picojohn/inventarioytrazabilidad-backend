<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Parametrizacion\Contenedor;
use App\Models\Operaciones\OperacionEmbarque;
use App\Models\Operaciones\OperacionEmbarqueContenedor;

class ContainerFormat implements Rule
{
    public $errorMessage;
    public $operacionEmbarqueId;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($operacionEmbarqueId)
    {
        $this->operacionEmbarqueId = $operacionEmbarqueId;
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
        $len = strlen($value);
        $num = substr($value, 0, 10);
        $pattern = '/^[a-zA-Z]{3}[u|U|j|J|z|Z][0-9]{6}/';
        $match = preg_match($pattern, $num);
        if($len > 11 || $len < 10 || !$match){
            $this->errorMessage = '2. El formato del número de contenedor es inválido';
            return false;
        }
        if($len == 11) {
            $digito = Contenedor::digitoVerificacion($num);
            if(intval(substr($value, -1)) != $digito){
                $this->errorMessage = '3. Dígito de verificacion incorrecto';
                return false;
            }
        }
        $operacionEmbarque = OperacionEmbarque::find($this->operacionEmbarqueId);
        $contenedor = Contenedor::where('numero_contenedor', $num)
            ->where('cliente_id', $operacionEmbarque->cliente_id)
            ->first();
        if($contenedor){
            $exist = OperacionEmbarqueContenedor::where('contenedor_id', $contenedor->id)
                ->where('operacion_embarque_id', $operacionEmbarque->id)
                ->first();
            if($exist){
                $this->errorMessage = '4. Este contenedor ya fue asignado a esta Operación';
                return false;
            }
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
        return $this->errorMessage;
    }
}
