<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enum\AccionAuditoriaEnum;
use App\Models\Seguridad\AuditoriaTabla;
use App\Models\Operaciones\OperacionEmbarque;

class UpdateEmbOp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:operacion-embarque';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estado de operaciones de embarque vencidas';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $vencidas = OperacionEmbarque::vencidas();
        foreach($vencidas as $vencida){
            $opEm = OperacionEmbarque::find($vencida->id);
            $opEmOriginal = $opEm->toJson();
            $opEm->estado = 'ARC';
            $opEm->save();

            // Guardar auditoria
            $auditoriaDto = array(
                'id_recurso' => $opEm->id,
                'nombre_recurso' => OperacionEmbarque::class,
                'descripcion_recurso' => $opEm->nombre,
                'accion' => AccionAuditoriaEnum::MODIFICAR,
                'recurso_original' => $opEmOriginal,
                'recurso_resultante' => $opEm,
                'responsable_id' => 0,
                'responsable_nombre' => 'Proceso Vigencia Operacion Embarque',
            );
            AuditoriaTabla::create($auditoriaDto);
        }
    }
}
