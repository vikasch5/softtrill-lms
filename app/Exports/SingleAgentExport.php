<?php

namespace App\Exports;

use App\Exports\Sheets\AgentDailyPerformanceSheet;
use App\Exports\Sheets\AgentCallHistorySheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SingleAgentExport implements WithMultipleSheets
{
    use Exportable;

    protected $agentName;
    protected $dailyPerformance;
    protected $callHistory;
    protected $dateString;

    public function __construct($agentName, $dailyPerformance, $callHistory, $dateString)
    {
        $this->agentName = $agentName;
        $this->dailyPerformance = $dailyPerformance;
        $this->callHistory = $callHistory;
        $this->dateString = $dateString;
    }

    public function sheets(): array
    {
        return [
            new AgentDailyPerformanceSheet($this->dailyPerformance, $this->agentName, $this->dateString),
            new AgentCallHistorySheet($this->callHistory, $this->agentName, $this->dateString),
        ];
    }
}
