<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Seguridad\Usuario;
use Illuminate\Support\Facades\Mail;
use App\Models\Parametrizacion\ParametroCorreo;
use App\Models\Parametrizacion\InventarioMinimo;
use App\Models\Parametrizacion\ParametroConstante;

class SendStockMinimo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:stock-minimo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifica a cliente sobre el stock mínimo';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clientes = InventarioMinimo::obtenerInventario();
        foreach($clientes as $cliente){
            $table = '<table  style="width: 50%;border: 1px solid black" cellspacing=0>
                <tr>
                    <th style="width:200px;border: 1px solid black">Lugar</th>
                    <th style="width:300px;border: 1px solid black">Producto/Kit</th>
                    <th style="width:100px;border: 1px solid black">Cantidad Inventario</th>
                    <th style="width:100px;border: 1px solid black">Stock Mínimo</th>
                </tr>
                <tbody>
            ';
            foreach ($cliente['data'] as $object) {
                $table .= "<tr>
                    <td style='padding: 0px 5px; width:200px;border: 1px solid black'>$object->lugar</td>
                    <td style='padding: 0px 5px; width:300px;border: 1px solid black'>$object->producto_kit</td>
                    <td style='padding: 0px 5px; width:100px;border: 1px solid black; text-align: right'>$object->inventario</td>
                    <td style='padding: 0px 5px; width:100px;border: 1px solid black; text-align: right'>$object->inventario_minimo</td>
                </tr>";
            }
            $table .= "</tbody>
                </table>
            <br>
            ";

            $parametroCorreo = ParametroCorreo::find((
                ParametroConstante::where('codigo_parametro', 'ID_CORREO_ALERTA_INVENTARIO_MINIMO')
                    ->first()->valor_parametro)??0
            );

            $usuarioProduccion = Usuario::find((
                ParametroConstante::where('codigo_parametro', 'ID_USUARIO_PRODUCCION')
                    ->first()->valor_parametro)??0
            );
            
            if (!$parametroCorreo) break;

            $parametroCorreo->texto = str_replace('&amp;1', $table, $parametroCorreo->texto);
    
            Mail::send('mail.stock-minimo',
                [
                    'texto' => $parametroCorreo->texto,
                    'message' => $this
                ], 
                function($message) use($parametroCorreo, $cliente){
                    $message->from(env('MAIL_USERNAME'))
                        ->to($cliente['email'])
                        ->cc($usuarioProduccion->correo_elecronico??env('MAIL_USERNAME'))
                        ->subject($parametroCorreo->asunto);
            });
        }
    }
}
