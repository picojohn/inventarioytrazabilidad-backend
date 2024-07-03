<?php

namespace App\Models\Parametrizacion;

use Illuminate\Database\Eloquent\Model;
use App\Models\Parametrizacion\TipoListaChequeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrizacion\FormatoInspeccionUnidad;

class FormatoInsUndLista extends Model
{
    use HasFactory;

    protected $table = 'formatos_ins_und_listas';

    protected $fillable = [
        'formato_unidad_id',
        'tipo_lista_id',
    ];

    public function formatoUnidad(){
        return $this->belongsTo(FormatoInspeccionUnidad::class, 'formato_unidad_id');
    }

    public function tipoLista(){
        return $this->belongsTo(TipoListaChequeo::class, 'tipo_lista_id');
    }

}
