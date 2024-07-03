<?php

namespace App\Rules;

use App\Models\Pedidos\Sello;
use Illuminate\Contracts\Validation\Rule;

class AvailableForReceive implements Rule
{
    public $numero_remision;
    public $producto_id;
    public $tipo;
    public $serieF;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($numero_remision, $producto_id, $tipo, $serieF)
    {
        $this->numero_remision = $numero_remision;
        $this->producto_id = $producto_id;
        $this->tipo = $tipo;
        $this->serieF = $serieF;
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
        $sellos = Sello::where('numero_ultima_remision', $this->numero_remision)
            ->where('estado_sello', 'TTO');
        if(isset($this->serieF)){
            $serieF = $this->serieF;
            $sellos->where(function($filter) use($value){
                $filter->where('serial', '>=', $value)
                    ->orWhere('serial_interno', '>=', $value)
                    ->orWhere('serial_qr', '>=', $value)
                    ->orWhere('serial_datamatrix', '>=', $value)
                    ->orWhere('serial_pdf', '>=', $value);
            })->where(function($filter2) use($serieF){
                $filter2->where('serial', '<=', $value)
                    ->orWhere('serial_interno', '<=', $value)
                    ->orWhere('serial_qr', '<=', $value)
                    ->orWhere('serial_datamatrix', '<=', $value)
                    ->orWhere('serial_pdf', '<=', $value);
            });
        } else {
            $sellos->where(function($filter) use($value){
                $filter->where('serial', $value)
                    ->orWhere('serial_interno', $value)
                    ->orWhere('serial_qr', $value)
                    ->orWhere('serial_datamatrix', $value)
                    ->orWhere('serial_pdf', $value);
            });
        }
        if($this->tipo === 'P'){
            $sellos->where('tipo_empaque_despacho', 'I')
                ->where('producto_id', $this->producto_id);
        } else {
            $sellos->whereNotNull('kit_id')
                ->where('kit_id', $this->producto_id);
        }
            
        return $sellos->count() > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Seriales incorrectos';
    }
}
