<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class AgentDailyPerformanceSheet implements FromCollection, WithHeadings, WithTitle, WithEvents, WithCustomStartCell
{
    protected $dailyPerformance;
    protected $agentName;
    protected $dateString;

    public function __construct($dailyPerformance, $agentName, $dateString)
    {
        $this->dailyPerformance = $dailyPerformance;
        $this->agentName = $agentName;
        $this->dateString = $dateString;
    }

    public function collection()
    {
        $data = collect();
        foreach ($this->dailyPerformance as $day) {
            $dayAnswerRate = $day->total_calls > 0 ? ($day->answered_calls / $day->total_calls) * 100 : 0;
            $dayAvgDuration = $day->answered_calls > 0 ? ($day->talk_sec / $day->answered_calls) : 0;
            $dayLoginSec = $day->pause_sec + $day->wait_sec + $day->talk_sec + $day->dispo_sec + $day->dead_sec;

            $data->push([
                'Date' => Carbon::parse($day->date)->format('M d, Y'),
                'Calls' => $day->total_calls,
                'Answered' => $day->answered_calls,
                'Answer Rate' => number_format($dayAnswerRate, 2) . '%',
                'Avg. Duration' => gmdate('i:s', (int)$dayAvgDuration),
                'Login Hours' => floor($dayLoginSec / 3600) . 'h ' . gmdate('i\m', (int)$dayLoginSec),
                'Pause Time' => floor($day->pause_sec / 3600) . 'h ' . gmdate('i\m', (int)$day->pause_sec),
                'Talk Time' => floor($day->talk_sec / 3600) . 'h ' . gmdate('i\m', (int)$day->talk_sec),
            ]);
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Calls',
            'Answered',
            'Answer Rate',
            'Avg. Duration',
            'Login Hours',
            'Pause Time',
            'Talk Time'
        ];
    }

    public function title(): string
    {
        return 'Daily Performance';
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', 'Daily Performance Report');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $sheet->mergeCells('A2:H2');
                $sheet->setCellValue('A2', 'Agent: ' . $this->agentName);
                $sheet->getStyle('A2')->getFont()->setBold(true);

                $sheet->mergeCells('A3:H3');
                $sheet->setCellValue('A3', 'Period: ' . $this->dateString);
                $sheet->getStyle('A3')->getFont()->setItalic(true);

                $sheet->getStyle('A4:H4')->getFont()->setBold(true);
                
                foreach(range('A', 'H') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}
