<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class AgentCallHistorySheet implements FromCollection, WithHeadings, WithTitle, WithEvents, WithCustomStartCell
{
    protected $callHistory;
    protected $agentName;
    protected $dateString;

    public function __construct($callHistory, $agentName, $dateString)
    {
        $this->callHistory = $callHistory;
        $this->agentName = $agentName;
        $this->dateString = $dateString;
    }

    public function collection()
    {
        $data = collect();
        foreach ($this->callHistory as $call) {
            $callTime = Carbon::parse($call->event_time);
            
            $isAnswered = $call->is_answered;
            if ($isAnswered) {
                $statusText = 'Answered';
            } else {
                $statusText = $call->status ?? 'No Answer';
            }

            $data->push([
                'Date' => $callTime->format('M d, Y'),
                'Time' => $callTime->format('h:i A'),
                'List ID' => $call->list_id,
                'Call Status' => $statusText,
                'Duration' => gmdate('i:s', (int)($call->talk_sec ?? 0))
            ]);
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Time',
            'List ID',
            'Call Status',
            'Duration'
        ];
    }

    public function title(): string
    {
        return 'Call History';
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

                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'Call History');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $sheet->mergeCells('A2:E2');
                $sheet->setCellValue('A2', 'Agent: ' . $this->agentName);
                $sheet->getStyle('A2')->getFont()->setBold(true);

                $sheet->mergeCells('A3:E3');
                $sheet->setCellValue('A3', 'Period: ' . $this->dateString);
                $sheet->getStyle('A3')->getFont()->setItalic(true);

                $sheet->getStyle('A4:E4')->getFont()->setBold(true);
                
                foreach(range('A', 'E') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}
