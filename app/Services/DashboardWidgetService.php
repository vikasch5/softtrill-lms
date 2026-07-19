<?php

namespace App\Services;

use App\Models\DashboardWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardWidgetService
{
    /** Cache TTL in seconds (10 minutes) */
    const CACHE_TTL = 600;

    public function generate(DashboardWidget $widget)
    {
        // Cache key is busted whenever widget settings change
        $key = 'widget_chart_' . $widget->id . '_' . md5(
            $widget->chart_type . $widget->list_id . $widget->field_id .
            $widget->aggregate . $widget->group_by . $widget->height
        );

        return Cache::remember($key, self::CACHE_TTL, function () use ($widget) {
            switch ($widget->chart_type) {
                case 'card':     return $this->card($widget);
                case 'pie':      return $this->pie($widget);
                case 'doughnut': return $this->doughnut($widget);
                case 'bar':      return $this->bar($widget);
                case 'line':     return $this->line($widget);
                case 'area':     return $this->area($widget);
                default:         return [];
            }
        });
    }

    protected function card($widget)
    {
        $query = DB::table('leads')->where('list_id', $widget->list_id);
        switch ($widget->aggregate) {
            case 'count': $value = $query->count(); break;
            case 'sum':   $value = $query->sum($this->fieldColumn($widget)); break;
            case 'avg':   $value = round($query->avg($this->fieldColumn($widget)), 2); break;
            case 'min':   $value = $query->min($this->fieldColumn($widget)); break;
            case 'max':   $value = $query->max($this->fieldColumn($widget)); break;
            default:      $value = 0;
        }
        return ['type' => 'card', 'value' => $value];
    }

    protected function pie($widget)
    {
        $rows = $this->groupedRows($widget);
        return [
            'type'       => 'pie',
            'chart'      => ['type' => 'pie', 'height' => (int)($widget->height ?? 264), 'toolbar' => ['show' => false]],
            'series'     => $rows->pluck('total')->values()->toArray(),
            'labels'     => $rows->pluck('label')->values()->toArray(),
            'colors'     => $this->palette(count($rows)),
            'legend'     => ['position' => 'bottom'],
            'dataLabels' => ['enabled' => true],
            'stroke'     => ['width' => 2],
        ];
    }

    protected function doughnut($widget)
    {
        $d = $this->pie($widget);
        $d['type'] = 'donut';
        $d['chart']['type'] = 'donut';
        return $d;
    }

    protected function bar($widget)
    {
        $rows = $this->groupedRows($widget);
        return [
            'type'        => 'bar',
            'chart'       => ['type' => 'bar', 'height' => (int)($widget->height ?? 264), 'toolbar' => ['show' => false]],
            'series'      => [['name' => $widget->title, 'data' => $rows->pluck('total')->values()->toArray()]],
            'plotOptions' => ['bar' => ['borderRadius' => 8, 'columnWidth' => '23%', 'borderRadiusApplication' => 'end', 'endingShape' => 'rounded']],
            'fill'        => ['type' => 'gradient', 'colors' => ['#487FFF'], 'gradient' => ['shade' => 'light', 'type' => 'vertical', 'shadeIntensity' => 0.5, 'gradientToColors' => ['#487FFF'], 'inverseColors' => false, 'opacityFrom' => 1, 'opacityTo' => 1, 'stops' => [0, 100]]],
            'dataLabels'  => ['enabled' => false],
            'grid'        => ['show' => true, 'borderColor' => '#D1D5DB', 'strokeDashArray' => 4, 'position' => 'back'],
            'xaxis'       => ['categories' => $rows->pluck('label')->values()->toArray(), 'labels' => ['style' => ['fontSize' => '13px']]],
            'yaxis'       => ['labels' => ['style' => ['fontSize' => '13px']]],
            'colors'      => ['#487FFF'],
        ];
    }

    protected function line($widget)
    {
        $rows = $this->timeSeriesRows($widget);
        return [
            'type'       => 'line',
            'chart'      => ['type' => 'line', 'height' => (int)($widget->height ?? 264), 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false]],
            'series'     => [['name' => $widget->title, 'data' => $rows->pluck('total')->values()->toArray()]],
            'stroke'     => ['curve' => 'smooth', 'colors' => ['#487FFF'], 'width' => 4],
            'markers'    => ['size' => 0, 'strokeWidth' => 3, 'hover' => ['size' => 8]],
            'dataLabels' => ['enabled' => false],
            'grid'       => ['borderColor' => '#D1D5DB', 'strokeDashArray' => 3, 'row' => ['colors' => ['transparent', 'transparent'], 'opacity' => 0.5]],
            'xaxis'      => ['categories' => $rows->pluck('label')->values()->toArray(), 'axisBorder' => ['show' => false], 'tooltip' => ['enabled' => false], 'labels' => ['style' => ['fontSize' => '13px']]],
            'yaxis'      => ['labels' => ['style' => ['fontSize' => '13px']]],
            'tooltip'    => ['enabled' => true],
            'colors'     => ['#487FFF'],
        ];
    }

    protected function area($widget)
    {
        $rows = $this->timeSeriesRows($widget);
        return [
            'type'       => 'area',
            'chart'      => ['type' => 'area', 'height' => (int)($widget->height ?? 264), 'toolbar' => ['show' => false]],
            'series'     => [['name' => $widget->title, 'data' => $rows->pluck('total')->values()->toArray()]],
            'stroke'     => ['curve' => 'straight', 'width' => 4, 'colors' => ['#487FFF'], 'lineCap' => 'round'],
            'fill'       => ['type' => 'gradient', 'colors' => ['#487FFF'], 'gradient' => ['shade' => 'light', 'type' => 'vertical', 'shadeIntensity' => 0.5, 'gradientToColors' => ['#487FFF00'], 'inverseColors' => false, 'opacityFrom' => 0.6, 'opacityTo' => 0.3, 'stops' => [0, 100]]],
            'markers'    => ['colors' => ['#487FFF'], 'strokeWidth' => 3, 'size' => 0, 'hover' => ['size' => 10]],
            'dataLabels' => ['enabled' => false],
            'grid'       => ['show' => true, 'borderColor' => '#D1D5DB', 'strokeDashArray' => 3, 'position' => 'back', 'xaxis' => ['lines' => ['show' => false]], 'yaxis' => ['lines' => ['show' => true]]],
            'xaxis'      => ['categories' => $rows->pluck('label')->values()->toArray(), 'tooltip' => ['enabled' => false], 'labels' => ['style' => ['fontSize' => '13px']]],
            'yaxis'      => ['labels' => ['style' => ['fontSize' => '13px']]],
            'colors'     => ['#487FFF'],
        ];
    }

    protected function groupedRows($widget)
    {
        $query     = DB::table('leads')->where('list_id', $widget->list_id);
        $aggregate = $widget->aggregate ?? 'count';

        // Build the aggregate SQL expression
        if ($aggregate !== 'count' && $widget->field_id) {
            $field = DB::table('lead_fields')->where('id', $widget->field_id)->first();
            if ($field) {
                $slug    = $field->slug;
                $expr    = "CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$slug}')) AS DECIMAL(15,2))";
                $aggExpr = strtoupper($aggregate) . "({$expr})";
            } else {
                $aggExpr = 'COUNT(*)';
            }
        } else {
            $aggExpr = 'COUNT(*)';
        }

        // Group by status (categorical dimension), apply aggregate on the value field
        return $query
            ->select(DB::raw('COALESCE(status, "unknown") AS label'), DB::raw("{$aggExpr} AS total"))
            ->groupBy('status')
            ->orderByDesc('total')
            ->limit(15)
            ->get();
    }

    protected function timeSeriesRows($widget)
    {
        $query = DB::table('leads')->where('list_id', $widget->list_id);
        $fmt = ['day' => '%Y-%m-%d','week' => '%x-W%v','month' => '%Y-%m','year' => '%Y'][$widget->group_by ?? 'month'] ?? '%Y-%m';
        return $query->select(DB::raw("DATE_FORMAT(created_at, '$fmt') AS label"), DB::raw('COUNT(*) AS total'))->groupBy('label')->orderBy('label')->get();
    }

    protected function fieldColumn($widget): string
    {
        if ($widget->field_id) {
            $field = DB::table('lead_fields')->where('id', $widget->field_id)->first();
            if ($field) return DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.$field->slug')) AS DECIMAL(15,2))");
        }
        return 'id';
    }

    protected function palette(int $count): array
    {
        $base = ['#487FFF','#FF9F29','#48AB69','#EF4A00','#45B369','#7c3aed','#00b8f2','#dc2626','#16a34a','#ff9f29'];
        $colors = [];
        for ($i = 0; $i < $count; $i++) { $colors[] = $base[$i % count($base)]; }
        return $colors;
    }
}