<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class ItemQuantityExport implements FromArray, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $items;
    protected $branchName;
    protected $branchCode;
    protected $date;

    public function __construct($items, $branchName, $branchCode, $date)
    {
        $this->items = $items;
        $this->branchName = $branchName;
        $this->branchCode = $branchCode;
        $this->date = $date;
    }

    public function array(): array
    {
        $data = [];
        $slNo = 1;

        foreach ($this->items as $item) {
            $data[] = [
                $slNo++,
                $item->item_name,
                $item->total_quantity,
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Branch Name:', $this->branchName],  // First Row
            ['Branch Code:', $this->branchCode],  // Second Row
            ['Date:', $this->date],               // Third Row
            [],                                   // Empty Row
            ['Sl. No', 'Item Name', 'Quantity'],  // Column headings
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Bold the title rows
                $event->sheet->getStyle('A1:B3')->getFont()->setBold(true);
                $event->sheet->mergeCells('A1:C1');
                $event->sheet->mergeCells('A2:C2');
                $event->sheet->mergeCells('A3:C3');

                // Align center
                $event->sheet->getStyle('A1:C1')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A2:C2')->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('A3:C3')->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
