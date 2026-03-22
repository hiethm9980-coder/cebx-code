<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * SimpleArrayExport — Generic Excel export from a 2D array.
 * Used by ReportService to generate .xlsx files for any report type.
 */
class SimpleArrayExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private array  $headers,
        private array  $rows,
        private string $title = 'Report',
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31); // Excel sheet name max 31 chars
    }
}
