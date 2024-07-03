<?php

namespace App\Exports\Pedidos;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventarioExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithStrictNullComparison,WithTitle
{
   /**
   * @return \Illuminate\Support\Collection
   */
   use Exportable;

   public function __construct($dto)
   {
        $this->dto = $dto;
        $this->userTotales = [];
        $this->totales = [];
   }

   public function array(): array
   {
        // DB::statement('SET GLOBAL group_concat_max_len = 1000000');
        $query = DB::table('sellos')
            ->leftJoin('lugares', 'lugares.id', 'sellos.lugar_id')
            ->leftJoin('usuarios', 'usuarios.id', 'sellos.user_id')
            ->leftJoin('productos_clientes', 'productos_clientes.id', 'sellos.producto_id')
            ->leftJoin('kits', 'kits.id', 'sellos.kit_id')
            ->leftJoin('inventario_minimo AS im1', function ($join) {
                $join->on('im1.producto_cliente_id', '=', 'productos_clientes.id')
                    ->on('im1.lugar_id', '=', 'lugares.id');
            })
            ->leftJoin('inventario_minimo AS im2', function ($join) {
                $join->on('im2.kit_id', '=', 'kits.id')
                    ->on('im2.lugar_id', '=', 'lugares.id');
            })
            ->select(
                'lugares.nombre as lugar',
                'usuarios.nombre as usuario',
                DB::raw("
                    CASE WHEN kits.id IS NULL 
                    THEN CONCAT(lugares.id, '-P-', productos_clientes.id)
                    ELSE CONCAT(lugares.id, '-K-', kits.id)
                    END as producto_kit_id
                "),
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        productos_clientes.nombre_producto_cliente
                        ,kits.nombre
                    ) AS nombre"
                ),
                DB::Raw(
                    "IF(GROUP_CONCAT(DISTINCT sellos.tipo_empaque_despacho SEPARATOR ', ') ='I',
                        GROUP_CONCAT(DISTINCT im1.cantidad_inventario_minimo SEPARATOR ', '),
                        GROUP_CONCAT(DISTINCT im2.cantidad_inventario_minimo SEPARATOR ', ')
                    ) AS stock_minimo"
                ),
                DB::Raw(
                    "count(*) AS cantidad"
                ),
                DB::Raw("GROUP_CONCAT(DISTINCT CONCAT(sellos.serial,'&',COALESCE(sellos.fecha_ultima_recepcion,'')) ORDER BY sellos.serial ASC SEPARATOR ',')
                    AS seriales"
                ),
            )
            ->whereIn('sellos.estado_sello', ['STO', 'TTO', 'DEV'])
            ->where(function($query1)  {
                $query1->where('sellos.tipo_empaque_despacho', '=', 'I')
                    ->orWhere(function($query2)  {
                        $query2->where('sellos.tipo_empaque_despacho', '=', 'K')
                            ->whereNotNull('sellos.kit_id');
                        });
            })
            ->groupBy('lugares.id')
            ->groupBy('usuarios.id')
            ->groupBy('productos_clientes.id')
            ->groupBy('kits.id');
    
        if(isset($this->dto['cliente'])){
            $query->where('lugares.cliente_id', $this->dto['cliente'] );
        }

        if(isset($this->dto['lugar'])){
            $query->where('lugares.id', '=',$this->dto['lugar'] );
        }

        if(isset($this->dto['usuario'])){
            $query->where('usuarios.id', '=',$this->dto['usuario'] );
        }

        $query->orderBy('lugar')
            ->orderBy('nombre')
            ->orderBy('usuario');
        $rows = $query->get();
        $fecha= Carbon::now();
        $export=[];
        $sum = 0;
        $cantidad = count($rows);
        if($cantidad > 0){
            $first = $rows[0]->producto_kit_id;
        }
        $cuenta = 1;
        foreach($rows as $i => $group){
            $temp=[];
            if($first != $group->producto_kit_id){
                array_push($temp,[
                    $fecha,
                    'Total Lugar:',
                    '',
                    '',
                    '',
                    $sum,
                    $rows[$i-1]->stock_minimo,
                ]);
                $first = $group->producto_kit_id;
                $sum = 0;
                $cuenta+=1;
                $this->totales[] = $cuenta;
            }
            foreach (explode(',',$group->seriales) as $j => $serieFecha) {
                $serieYFecha = explode('&',$serieFecha);
                array_push($temp,[
                    $fecha,
                    $group->lugar,
                    $group->nombre,
                    $group->usuario,
                    $serieYFecha[1],
                    $serieYFecha[0],
                    '',
                ]);
                $cuenta+=1;
                if(count(explode(',',$group->seriales))-1 == $j){
                    array_push($temp,[
                        $fecha,
                        'Total Usuario:',
                        '',
                        '',
                        '',
                        $group->cantidad,
                        '',
                    ]);
                    $sum+=$group->cantidad;
                    $cuenta+=1;
                    $this->userTotales[] = $cuenta;
                }
            };
            if($cantidad-1 == $i){
                array_push($temp,[
                    $fecha,
                    'Total Lugar:',
                    '',
                    '',
                    '',
                    $sum,
                    $rows[$i]->stock_minimo,
                ]);
                $cuenta+=1;
                $this->totales[] = $cuenta;
            }
            array_push($export,$temp);
        };

        return $export;
   }

   public function styles(Worksheet $sheet)
   {
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        foreach($this->userTotales as $row){
           $sheet->getStyle('A'.$row.':G'.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
           $sheet->getStyle('A'.$row.':G'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
           $sheet->getStyle('B'.$row.':G'.$row)->getFont()->setBold(true);
        }
        foreach($this->totales as $row){
            $sheet->getStyle('A'.$row.':G'.$row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
            $sheet->getStyle('A'.$row.':G'.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
            $sheet->getStyle('B'.$row.':G'.$row)->getFont()->setBold(true);
        }
   }

   public function headings(): array
   {
        return [
            "Fecha",
            "Lugar",
            "Descripción Producto",
            "Nombre Usuario",
            "Fecha Recepción",
            "Número de Serie",
            "Stock Mínimo",
        ];
   }

   public function title(): string
   {
       return 'Informe Inventario';
   }
}
