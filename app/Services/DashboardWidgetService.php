<?php

namespace App\Services;

use App\Models\DashboardWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Expression;

class DashboardWidgetService
{
    public function generate(DashboardWidget $widget, ?string $period = null, $user = null, array $visibleUserIds = [])
    {
        switch ($widget->chart_type) {
            case 'card':     return $this->card($widget, $user, $visibleUserIds);
            case 'pie':      return $this->pie($widget, $user, $visibleUserIds);
            case 'doughnut': return $this->doughnut($widget, $user, $visibleUserIds);
            case 'bar':      return $this->bar($widget, $period, $user, $visibleUserIds);
            case 'line':     return $this->line($widget, $period, $user, $visibleUserIds);
            case 'area':     return $this->area($widget, $period, $user, $visibleUserIds);
            default:         return [];
        }
    }

    protected function applyHierarchyFilter($query, $user, array $visibleUserIds)
    {
        if ($user && !$user->hasRole('Admin')) {
            $query->where(function ($q) use ($visibleUserIds) {
                $q->whereIn('assigned_to', $visibleUserIds)
                  ->orWhereIn('added_by', $visibleUserIds);
            });
        }
        return $query;
    }

    protected function card($widget, $user = null, array $visibleUserIds = [])
    {
        $query = DB::table('leads');
        if (! $widget->is_global) {
            $query->where('list_id', $widget->list_id);
        }
        $query = $this->applyHierarchyFilter($query, $user, $visibleUserIds);

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

    protected function pie($widget, $user = null, array $visibleUserIds = [])
    {
        $rows = $this->groupedRows($widget, $user, $visibleUserIds);
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

    protected function doughnut($widget, $user = null, array $visibleUserIds = [])
    {
        $d = $this->pie($widget, $user, $visibleUserIds);
        $d['type'] = 'donut';
        $d['chart']['type'] = 'donut';
        return $d;
    }

    protected function bar(DashboardWidget $widget, ?string $period = null, $user = null, array $visibleUserIds = [])
    {
        // $period from UI select overrides the widget's saved group_by
        $effectivePeriod = $period ?? $widget->group_by;
        $rows = $effectivePeriod
            ? $this->timeSeriesRows($widget, $effectivePeriod, $user, $visibleUserIds)
            : $this->groupedRows($widget, $user, $visibleUserIds);

        return [
            'type'        => 'bar',
            'chart'       => ['type' => 'bar', 'height' => (int)($widget->height ?? 264), 'toolbar' => ['show' => false]],
            'series'      => [['name' => $widget->title, 'data' => $rows->pluck('total')->values()->toArray()]],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => 10, 'endingShape' => 'rounded']],
            'dataLabels'  => ['enabled' => false],
            'stroke'      => ['show' => true, 'width' => 2, 'colors' => ['transparent']],
            'grid'        => ['show' => true, 'borderColor' => '#D1D5DB', 'strokeDashArray' => 4, 'position' => 'back'],
            'xaxis'       => [
                'categories' => $rows->pluck('label')->values()->toArray(),
                'axisBorder' => ['show' => false],
                'labels'     => ['style' => ['fontSize' => '12px']],
            ],
            'yaxis'       => ['labels' => ['style' => ['fontSize' => '12px']]],
            'fill'        => ['opacity' => 1],
            'colors'      => ['#7c56c0'],
        ];
    }


    protected function line(DashboardWidget $widget, ?string $period = null, $user = null, array $visibleUserIds = [])
    {
        $effectivePeriod = $period ?? $widget->group_by ?? 'month';
        $rows = $this->timeSeriesRows($widget, $effectivePeriod, $user, $visibleUserIds);
        return [
            'type'       => 'line',
            'chart'      => ['type' => 'line', 'height' => (int)($widget->height ?? 264), 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false]],
            'series'     => [['name' => $widget->title, 'data' => $rows->pluck('total')->values()->toArray()]],
            'stroke'     => ['curve' => 'smooth', 'colors' => ['#7c56c0'], 'width' => 4],
            'markers'    => ['size' => 0, 'strokeWidth' => 3, 'hover' => ['size' => 8]],
            'dataLabels' => ['enabled' => false],
            'grid'       => ['borderColor' => '#D1D5DB', 'strokeDashArray' => 3, 'row' => ['colors' => ['transparent', 'transparent'], 'opacity' => 0.5]],
            'xaxis'      => ['categories' => $rows->pluck('label')->values()->toArray(), 'axisBorder' => ['show' => false], 'tooltip' => ['enabled' => false], 'labels' => ['style' => ['fontSize' => '13px']]],
            'yaxis'      => ['labels' => ['style' => ['fontSize' => '13px']]],
            'tooltip'    => ['enabled' => true],
            'colors'     => ['#7c56c0'],
        ];
    }

    protected function area(DashboardWidget $widget, ?string $period = null, $user = null, array $visibleUserIds = [])
    {
        $effectivePeriod = $period ?? $widget->group_by ?? 'month';
        $rows = $this->timeSeriesRows($widget, $effectivePeriod, $user, $visibleUserIds);
        return [
            'type'       => 'area',
            'chart'      => ['type' => 'area', 'height' => (int)($widget->height ?? 264), 'toolbar' => ['show' => false]],
            'series'     => [['name' => $widget->title, 'data' => $rows->pluck('total')->values()->toArray()]],
            'stroke'     => ['curve' => 'straight', 'width' => 4, 'colors' => ['#7c56c0'], 'lineCap' => 'round'],
            'fill'       => ['type' => 'gradient', 'colors' => ['#7c56c0'], 'gradient' => ['shade' => 'light', 'type' => 'vertical', 'shadeIntensity' => 0.5, 'gradientToColors' => ['#7c56c000'], 'inverseColors' => false, 'opacityFrom' => 0.6, 'opacityTo' => 0.3, 'stops' => [0, 100]]],
            'markers'    => ['colors' => ['#7c56c0'], 'strokeWidth' => 3, 'size' => 0, 'hover' => ['size' => 10]],
            'dataLabels' => ['enabled' => false],
            'grid'       => ['show' => true, 'borderColor' => '#D1D5DB', 'strokeDashArray' => 3, 'position' => 'back', 'xaxis' => ['lines' => ['show' => false]], 'yaxis' => ['lines' => ['show' => true]]],
            'xaxis'      => ['categories' => $rows->pluck('label')->values()->toArray(), 'tooltip' => ['enabled' => false], 'labels' => ['style' => ['fontSize' => '13px']]],
            'yaxis'      => ['labels' => ['style' => ['fontSize' => '13px']]],
            'colors'     => ['#7c56c0'],
        ];
    }

    protected function groupedRows($widget, $user = null, array $visibleUserIds = [])
    {
        $query = DB::table('leads');
        if (! $widget->is_global) {
            $query->where('list_id', $widget->list_id);
        }
        $query = $this->applyHierarchyFilter($query, $user, $visibleUserIds);
        $aggExpr   = $this->aggregateExpression($widget);

        // Group by status (categorical dimension), apply aggregate on the value field
        return $query
            ->select(DB::raw('COALESCE(status, "unknown") AS label'), DB::raw("{$aggExpr} AS total"))
            ->groupBy('status')
            ->orderByDesc('total')
            ->limit(15)
            ->get();
    }

    protected function timeSeriesRows($widget, ?string $groupBy = null, $user = null, array $visibleUserIds = [])
    {
        $query = DB::table('leads');
        if (! $widget->is_global) {
            $query->where('list_id', $widget->list_id);
        }
        $query = $this->applyHierarchyFilter($query, $user, $visibleUserIds);
        $groupBy = $groupBy ?? $widget->group_by ?? 'month';
        $aggExpr = $this->aggregateExpression($widget);
        $groups  = [
            'day' => [
                'label' => "DATE_FORMAT(created_at, '%d %b')",
                'sort' => 'DATE(created_at)',
            ],
            'week' => [
                'label' => "CONCAT(DATE_FORMAT(DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY), '%d %b'), ' - ', DATE_FORMAT(DATE_ADD(DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY), INTERVAL 6 DAY), '%d %b'))",
                'sort' => 'DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY)',
            ],
            'month' => [
                'label' => "DATE_FORMAT(created_at, '%b %Y')",
                'sort' => "DATE_FORMAT(created_at, '%Y-%m')",
            ],
            'year' => [
                'label' => "DATE_FORMAT(created_at, '%Y')",
                'sort' => 'YEAR(created_at)',
            ],
        ];
        $group = $groups[$groupBy] ?? $groups['month'];

        return $query
            ->select(DB::raw("{$group['label']} AS label"), DB::raw("{$aggExpr} AS total"), DB::raw("{$group['sort']} AS sort_order"))
            ->groupBy('label', 'sort_order')
            ->orderBy('sort_order')
            ->get();
    }

    protected function aggregateExpression($widget): string
    {
        $aggregate = $widget->aggregate ?? 'count';

        if ($aggregate !== 'count' && $widget->field_id) {
            $field = DB::table('lead_fields')->where('id', $widget->field_id)->first();

            if ($field) {
                $slug = str_replace("'", "\\'", $field->slug);
                $expr = "CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$slug}\"')) AS DECIMAL(15,2))";

                return strtoupper($aggregate) . "({$expr})";
            }
        }

        return 'COUNT(*)';
    }

    protected function fieldColumn($widget): Expression|string
    {
        if ($widget->field_id) {
            $field = DB::table('lead_fields')->where('id', $widget->field_id)->first();

            if ($field) {
                return DB::raw(
                    "CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$field->slug}\"')) AS DECIMAL(15,2))"
                );
            }
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
