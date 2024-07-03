<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class SellosBitacoraExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithStrictNullComparison
{
  /**
   * @return \Illuminate\Support\Collection
   */
  use Exportable;

  public function __construct($dto){
    $this->dto = $dto;
    $this->rows = [];
  }

  public function query(){
    $query = DB::table('sellos_bitacora AS mt')
      ->join('sellos AS t1', 't1.id', 'mt.sello_id')
      ->join('productos_clientes AS t2', 't2.id', 'mt.producto_id')
      ->join('clientes AS t3', 't3.id', 'mt.cliente_id')
      ->leftJoin('sellos AS t4', 't4.id', 'mt.producto_empaque_id')
      ->leftJoin('kits AS t5', 't5.id', 'mt.kit_id')
      ->join('tipos_eventos AS t6', 't6.id', 'mt.tipo_evento_id')
      ->leftJoin('lugares AS t7', 't7.id', 'mt.lugar_origen_id')
      ->leftJoin('lugares AS t8', 't8.id', 'mt.lugar_destino_id')
      ->leftJoin('usuarios AS t9', 't9.id', 'mt.usuario_destino_id')
      ->leftJoin('contenedores AS t10', 't10.id', 'mt.contenedor_id')
      ->leftJoin('lugares AS t11', 't11.id', 'mt.lugar_instalacion_id')
      ->leftJoin('zonas_contenedores AS t12', 't12.id', 'mt.zona_instalacion_id')
      ->select(
        'mt.created_at',
        't6.nombre AS evento',
        'mt.usuario_creacion_nombre AS usuario_origen',
        't7.nombre AS lugar_origen',
        't2.nombre_producto_cliente AS producto',
        't1.serial',
        't5.nombre AS kit',
        DB::raw("CASE t6.estado_sello
          WHEN 'GEN' THEN 'Generado'
          WHEN 'STO' THEN 'En Stock'
          WHEN 'TTO' THEN 'En tránsito'
          WHEN 'INS' THEN 'Instalado'
          WHEN 'INA' THEN 'Inactivo'
          WHEN 'DEV' THEN 'Devuelto'
          WHEN 'DES' THEN 'Destruido'
          WHEN 'PER' THEN 'Pérdida'
          ELSE '' END AS estado_sello
        "),
        't8.nombre AS lugar_destino',
        't9.nombre AS usuario_destino',
        'mt.documento_referencia',
        't10.numero_contenedor',
        't11.nombre AS lugar_instalacion',
        't12.nombre AS zona_instalacion',
        'mt.operacion_embarque_id',
        DB::raw("CONCAT('https://maps.google.com/?q=', mt.latitud, ',', mt.longitud) AS ubicacion"),
      );

    if(isset($this->dto['fechaInicial'])){
      $query->where('mt.fecha_evento', '>=', $this->dto['fechaInicial']);
    }
    if(isset($this->dto['fechaFinal'])){
      $query->where('mt.fecha_evento', '<=', $this->dto['fechaFinal']);
    }
    if(isset($this->dto['evento'])){
      $query->where('mt.tipo_evento_id', $this->dto['evento']);
    }
    if(isset($this->dto['serial'])){
      $query->where('t1.serial', 'like', '%'.$this->dto['serial'].'%');
    }
    if(isset($this->dto['contenedor'])){
      $query->where('t10.numero_contenedor', 'like', '%'.$this->dto['contenedor'].'%');
    }
    if(isset($this->dto['documentoRef'])){
      $query->where('mt.documento_referencia', 'like', '%'.$this->dto['documentoRef'].'%');
    }
    $dto = $this->dto;
    if(isset($this->dto['lugar'])){
      $query->where(function($filter) use($dto){
        $filter->where('mt.lugar_origen_id', $dto['lugar'])
          ->orWhere('mt.lugar_destino_id', $dto['lugar']);
      });
    }
    if(isset($this->dto['usuario'])){
      $query->where(function($filter) use($dto){
        $filter->where('mt.usuario_creacion_id', $dto['usuario'])
          ->orWhere('mt.usuario_destino_id', $dto['usuario']);
      });
    }
    if(isset($this->dto['operacionEmbarque'])){
      $query->where('mt.operacion_embarque_id', $this->dto['operacionEmbarque']);
    }
    if(isset($this->dto['cliente'])){
      $query->where(function($filter) use($dto){
        $filter->where('t7.cliente_id', $dto['cliente'])
            ->orWhere('t8.cliente_id', $dto['cliente']);
      });
    }

    $query->orderBy('mt.created_at', 'asc');

    $this->rows = clone $query->get();

    return $query;
  }

  public function styles(Worksheet $sheet){
    $sheet->getStyle('A1:P1')->getFont()->setBold(true);
    $i = 2;
    foreach($this->rows as $row){
      $sheet->getCell('P'.$i)->setValue('Ver Ubicación')->getHyperlink()->setUrl($row->ubicacion);
      $i++;
    }
  }

  public function headings(): array{
    return [
      "Fecha",
      "Evento",
      "Responsable",
      "Lugar Envio",
      "Producto",
      "Serial",
      "Kit",
      "Estado",
      "Lugar Destino",
      "Usuario Destino",
      "Dcto. Referencia",
      "Contenedor",
      "Lugar Instalación",
      "Zona Instalación",
      "Operación Embarque",
      "Ubicación",
    ];
  } 
}
