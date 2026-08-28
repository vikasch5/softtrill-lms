<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AgentPerformanceExport implements FromCollection, WithHeadings, WithTitle, WithEvents, WithCustomStartCell
{
    protected $data;
    protected $dateString;
    protected $aggregate;

    public function __construct($data, $dateString, $aggregate)
    {
        $this->data = $data;
        $this->dateString = $dateString;
        $this->aggregate = $aggregate;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Agent Name',
            'Employee ID',
            'Cluster',
            'Manager',
            'Team Leader',
            'Total Calls',
            'Answered',
            'Answer Rate',
            'Avg. Duration',
            'Login Hours',
            'Pause',
            'Talk Time'
        ];
    }

    public function title(): string
    {
        return 'Agent Performance';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add Title and Period Info at the top
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'Agent Performance Report');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue('A2', 'Period: ' . $this->dateString);
                $sheet->getStyle('A2')->getFont()->setItalic(true);

                // Add Aggregate Metrics (Total Agents, Total Calls, Answered, Answer Rate, Avg Duration, Total Talk Time)
                $sheet->setCellValue('A4', 'Total Agents:');
                $sheet->setCellValue('B4', $this->aggregate['total_agents']);
                
                $sheet->setCellValue('C4', 'Total Calls:');
                $sheet->setCellValue('D4', number_format($this->aggregate['total_calls']));
                
                $sheet->setCellValue('E4', 'Answered:');
                $sheet->setCellValue('F4', number_format($this->aggregate['answered_calls']));
                
                $sheet->setCellValue('G4', 'Answer Rate:');
                $sheet->setCellValue('H4', number_format($this->aggregate['answer_rate'], 2) . '%');
                
                $sheet->setCellValue('I4', 'Avg Duration:');
                $sheet->setCellValue('J4', gmdate('i:s', (int)$this->aggregate['avg_duration']));
                
                $sheet->setCellValue('K4', 'Total Talk Time:');
                $sheet->setCellValue('L4', floor((int)$this->aggregate['talk_sec'] / 3600) . 'h ' . gmdate('i\m', (int)$this->aggregate['talk_sec']));

                // Style the aggregates
                $cellsToBold = ['A4', 'C4', 'E4', 'G4', 'I4', 'K4'];
                foreach ($cellsToBold as $cell) {
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                }
                
                // Box the KPI section with colorful "cards"
                $colors = [
                    'A4:B4' => 'FFE3ECF6', // Total Agents (Light Blue)
                    'C4:D4' => 'FFDEF4F8', // Total Calls (Light Cyan)
                    'E4:F4' => 'FFE1F2E4', // Answered (Light Green)
                    'G4:H4' => 'FFEAE3F6', // Answer Rate (Light Purple)
                    'I4:J4' => 'FFDEF0F4', // Avg Duration (Light Teal)
                    'K4:L4' => 'FFFFF6D9', // Total Talk Time (Light Yellow)
                ];

                foreach ($colors as $range => $color) {
                    $sheet->getStyle($range)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $color],
                        ],
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_THICK,
                                'color' => ['argb' => 'FFFFFFFF'], // Thick white borders to act as spacing
                            ],
                        ],
                    ]);
                }
                
                // Add a black outline around the entire KPI row
                $sheet->getStyle('A4:L4')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Style the headings
                $sheet->getStyle('A6:L6')->getFont()->setBold(true);
                
                // Add a border below the headings for a professional look
                $sheet->getStyle('A6:L6')->applyFromArray([
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
                
                // Auto-size columns
                foreach(range('A', 'L') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}
