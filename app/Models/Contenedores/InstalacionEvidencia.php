<?php

namespace App\Models\Contenedores;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Parametrizacion\ParametroConstante;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstalacionEvidencia extends Model
{
    protected $table = 'instalacion_evidencias';

    protected $fillable = [
        'numero_instalacion_evidencia',
        'evidencia',
        'usuario_creacion_id',
        'usuario_creacion_nombre',
    ];

    public static function evidencias($ins_evd){
        $images = InstalacionEvidencia::where('numero_instalacion_evidencia', $ins_evd)->get();
        $rutaEvidencias = ParametroConstante::where('codigo_parametro', 'RUTA_EVIDENCIAS_INSTALACION')->first();
        $array = [];
        foreach($images as $image){
            $array[] = Storage::temporaryUrl(
                $rutaEvidencias->valor_parametro.$ins_evd.'/'.$image->evidencia, 
                now()->addMinutes(30),
                [
                    'ResponseContentDisposition' => 'form-data'
                ]
            );
        }
        return $array;
    }

    use HasFactory;
}
