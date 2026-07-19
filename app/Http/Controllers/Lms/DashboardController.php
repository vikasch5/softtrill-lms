<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\DashboardWidget;
use App\Models\Lead;
use App\Models\LeadField;
use App\Models\LeadList;
use App\Models\User;
use App\Models\UserDetails;
use App\Services\DashboardWidgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stats = $this->getDashboardStats($user);
        $widgets = DashboardWidget::where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
        foreach ($widgets as $widget) {
            $widget->chart = app(DashboardWidgetService::class)
                ->generate($widget);
        }

        return view('lms.pages.dashboard', compact('stats', 'widgets'));
    }

    public function getDashboardStats($user): array
    {
        $visibleUserIds = $this->getVisibleUserIds($user);

        $leadQuery = Lead::query();
        if (!$user->hasRole('Admin')) {
            $leadQuery->whereIn('assigned_to', $visibleUserIds);
        }

        $totalLeads = (clone $leadQuery)->count();

        $convertedLeads = (clone $leadQuery)
            ->whereIn('status', ['completed', 'converted', 'closed', 'won'])
            ->count();

        $pendingLeads = (clone $leadQuery)
            ->whereIn('status', ['pending', 'waiting', 'hold', 'on_hold'])
            ->count();

        $activeLeads = max(0, $totalLeads - $convertedLeads - $pendingLeads);

        $agentQuery = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'Agent');
        });

        if (!$user->hasRole('Admin')) {
            $agentQuery->whereIn('id', $visibleUserIds);
        }

        $totalAgents = $agentQuery->count();

        return [
            'totalLeads' => $totalLeads,
            'totalAgents' => $totalAgents,
            'activeLeads' => $activeLeads,
            'convertedLeads' => $convertedLeads,
            'pendingLeads' => $pendingLeads,
        ];
    }

    private function getVisibleUserIds($user): array
    {
        $userId = $user->id;

        if ($user->hasRole('Admin')) {
            return [];
        }

        $visibleIds = [$userId];

        if ($user->hasRole('Manager')) {
            $teamLeaderIds = UserDetails::where('manager_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $teamLeaderIds);

            if (!empty($teamLeaderIds)) {
                $agentIds = UserDetails::whereIn('teamleader_id', $teamLeaderIds)
                    ->pluck('user_id')
                    ->toArray();

                $visibleIds = array_merge($visibleIds, $agentIds);
            }

            $directAgentIds = UserDetails::where('manager_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $directAgentIds);
        } elseif ($user->hasRole('Cluster')) {
            $clusterUserIds = UserDetails::where('cluster_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $clusterUserIds);
        } elseif ($user->hasRole('TeamLeader')) {
            $agentIds = UserDetails::where('teamleader_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $agentIds);
        }

        return array_values(array_unique($visibleIds));
    }

    public function widgetsList()
    {
        $widgets = DashboardWidget::orderBy('sort_order')->get();
        return view('lms.pages.dashboard-widget-list', compact('widgets'));
    }

    public function dashboardWidget()
    {
        $lists = LeadList::get();
        return view('lms.pages.dashboard-widget', compact('lists'));
    }

    public function editWidget($id)
    {
        $widget = DashboardWidget::findOrFail($id);
        $lists  = LeadList::get();
        return view('lms.pages.dashboard-widget', compact('widget', 'lists'));
    }

    public function dashboardWidgetStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'list_id' => 'required|exists:lead_lists,id',
            'field_id' => 'nullable|exists:lead_fields,id',
            'chart_type' => 'required|in:card,bar,line,pie,doughnut,area',
            'aggregate' => 'required|in:count,sum,avg,min,max',
            'width' => 'required|integer|min:3|max:12',
            'height' => 'required|integer|min:200',
            'sort_order' => 'nullable|integer',
            'group_by' => 'nullable|in:day,week,month,year',
        ]);

        $widget = DashboardWidget::updateOrCreate(
            [
                'id' => $request->widget_id
            ],
            [
                'added_by' => Auth::id(),
                'tenant_id' => '0',
                'title' => $request->title,
                'list_id' => $request->list_id,
                'field_id' => $request->field_id,
                'chart_type' => $request->chart_type,
                'aggregate' => $request->aggregate,
                'group_by' => $request->group_by,
                'width' => $request->width,
                'height' => $request->height,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => $request->is_active ?? 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $request->widget_id
                ? 'Dashboard Widget Updated Successfully.'
                : 'Dashboard Widget Created Successfully.',
            'redirect' => route('lms.dashboard.widgets.list')
        ]);
    }

    public function getFields($listId)
    {
        $fields = LeadField::where('list_id', $listId)
            ->orderBy('sort_order')
            ->get();

        $html = '<option value="">Select Field</option>';

        foreach ($fields as $field) {
            $html .= '<option value="' . $field->id . '">' . $field->name . ' (' . $field->type . ')</option>';
        }

        return response($html);
    }

    public function widgetData($id)
    {
        $widget = DashboardWidget::findOrFail($id);

        return response()->json(
            app(DashboardWidgetService::class)->generate($widget)
        );
    }
}
