<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JadwalExport implements FromView, WithTitle, WithStyles
{
  protected $data;
  protected $title;

  public function __construct($data, $title)
  {
    $this->data = $data;
    $this->title = $title;
  }

  public function view(): View
  {
    return view('exports.jadwal', $this->data);
  }

  public function title(): string
  {
    return substr($this->title, 0, 30); // Excel title max 31 chars
  }

  public function styles(Worksheet $sheet)
  {
    // Set widths manually to force wrapping
    $sheet->getColumnDimension('A')->setWidth(15); // Hari
    $sheet->getColumnDimension('B')->setWidth(20); // Jam
    foreach (range('C', 'Z') as $col) {
      $sheet->getColumnDimension($col)->setWidth(35); // Ruangan columns
    }

    return [
      // Style the first row as bold text
      1 => ['font' => ['bold' => true, 'size' => 14]],
      // Style header row
      3 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center']],
      4 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center']],
      // Default style for all cells: Wrap Text enabled & Vertical Center
      'A:Z' => ['alignment' => ['wrapText' => true, 'vertical' => 'center']],
    ];
  }
}
