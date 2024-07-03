<?php

namespace App\Exports\Parametrizacion;

use Carbon\Carbon;
use App\Models\AsociadoNegocio;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ProductoClienteExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithStrictNullComparison
{
   /**
   * @return \Illuminate\Support\Collection
   */
   use Exportable;

   public function __construct($dto)
   {
      $this->dto = $dto;
   }
  
   public function query()
   {
      $query = DB::table('productos_clientes')
         ->select(
            'productos_clientes.id',
            'productos_clientes.nombre_producto_cliente',
            'productos_clientes.codigo_externo_producto',
            DB::Raw('CASE productos_clientes.indicativo_producto_empaque
                     WHEN "S" THEN "Si"
                     ELSE "No" END AS indicativo_producto_empaque'
            ),
            DB::Raw('CASE productos_clientes.estado
                     WHEN 0 THEN "Inactivo"
                     WHEN 1 THEN "Activo"
                     ELSE "" END AS estado'
            ),
         );

         if(isset($this->dto['nombre'])){
            $query->where('productos_clientes.nombre_producto_cliente', 'like', '%' . $this->dto['nombre'] . '%');
         }

         if(isset($this->dto['cliente'])){
            $query->where('productos_clientes.cliente_id', $this->dto['cliente']);
        }

      $query->orderBy('productos_clientes.nombre_producto_cliente', 'asc');
      
      return $query;
   }
   
   public function styles(Worksheet $sheet)
   {
      $sheet->getStyle('A1:E1')->getFont()->setBold(true);
   }
   
   public function headings(): array
   {
      return [
         "Identificación producto", 
         "Nombre producto",     
         "Codigo producto cliente",   
         "Indicativo empaque", 
         "Estado",  
      ];
   } 
}
