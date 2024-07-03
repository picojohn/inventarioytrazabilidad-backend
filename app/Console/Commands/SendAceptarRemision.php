<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Remisiones\Remision;
use Illuminate\Support\Facades\Mail;
use App\Models\Parametrizacion\ParametroCorreo;
use App\Models\Parametrizacion\ParametroConstante;

class SendAceptarRemision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:aceptar-remision';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifica a clientes cuando remisión no ha sido aceptada después de determinado tiempo';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $rows = Remision::obtenerRemisionesAtrasadas();
        foreach($rows as $row){
            $table = '<table  style="width: 50%;border: 1px solid black" cellspacing=0>
                <tr>
                    <th style="width:150px;border: 1px solid black">Núm. Remisión</th>
                    <th style="width:250px;border: 1px solid black">Fecha Remisión</th>
                    <th style="width:350px;border: 1px solid black">Enviada Desde</th>
                    <th style="width:350px;border: 1px solid black">Enviada Por</th>
                </tr>
                <tbody>
            ';
            foreach (explode(',', $row->remisiones) as $object) {
                $data = explode('&', $object);
                $table .= "<tr>
                    <td style='padding: 0px 5px; width:150px;border: 1px solid black; text-align: right'>$data[0]</td>
                    <td style='padding: 0px 5px; width:250px;border: 1px solid black'>$data[1]</td>
                    <td style='padding: 0px 5px; width:350px;border: 1px solid black;'>$data[3]</td>
                    <td style='padding: 0px 5px; width:350px;border: 1px solid black;'>$data[2]</td>
                </tr>";
            }
            $table .= "</tbody>
                </table>
            <br>
            ";

            $parametroCorreo = ParametroCorreo::find((
                ParametroConstante::where('codigo_parametro', 'ID_CORREO_ALERTA_ACEPTACION_REMISION')
                    ->first()->valor_parametro)??0
            );
            
            if (!$parametroCorreo) break;

            $parametroCorreo->texto = str_replace('&amp;1', $row->usuario_destino, $parametroCorreo->texto);
            $parametroCorreo->texto = str_replace('&amp;2 &amp;3 &amp;4 &amp;5', $table, $parametroCorreo->texto);
    
            Mail::send('mail.aceptar-remision',
                [
                    'texto' => $parametroCorreo->texto,
                ], 
                function($message) use($parametroCorreo, $row){
                    $message->from(env('MAIL_USERNAME'))
                        ->to($row->correo_electronico)
                        ->cc($row->correo_admin??env('MAIL_USERNAME'))
                        ->subject($parametroCorreo->asunto);
            });
        }
    }
}
