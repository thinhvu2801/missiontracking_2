<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Agency\Agency;
use App\Models\Mission\MissionReportPeriod;
use App\Models\ReportPeriod;
use App\Models\Resolution\Resolution;
use App\Models\Resolution\ResolutionReport;

class OverviewDashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.overview');
    }

    public function filters(Request $request)
    {
        $now = Carbon::now();

        $resolutions = Resolution::query()
            ->select(['id', 'resolution_code', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        if ($resolutions->isEmpty()) {
            return response()->json([
                'resolutions' => [],
                'reportPeriods' => [],
                'allowedPeriodTypes' => [],
                'defaultResolutionId' => null,
                'defaultReportPeriodId' => null,
            ]);
        }

        $requestedResolutionId = $request->integer('resolution_id');
        $selectedResolutionId = $requestedResolutionId && $resolutions->firstWhere('id', $requestedResolutionId)
            ? $requestedResolutionId
            : $resolutions->first()->id;

        // Giống MissionReportController: lấy cả period type từ resolution và MissionReportPeriod
        $resolutionPeriodTypes = ResolutionReport::query()
            ->where('resolution_id', $selectedResolutionId)
            ->where('unit_type', 'mission')
            ->pluck('period_type')
            ->unique();

        $missionPeriodTypes = MissionReportPeriod::query()
            ->pluck('period_type')
            ->unique();

        $allowedPeriodTypes = $resolutionPeriodTypes
            ->merge($missionPeriodTypes)
            ->unique()
            ->values()
            ->all();

        $reportPeriods = collect();
        $defaultReportPeriodId = null;

        if (!empty($allowedPeriodTypes)) {
            $reportPeriods = ReportPeriod::query()
                ->select(['id', 'period_type', 'report_year', 'period_number', 'start_date', 'end_date'])
                ->whereIn('period_type', $allowedPeriodTypes)
                ->orderBy('report_year')
                ->orderBy('start_date')
                ->get();

            $current = $reportPeriods->first(function ($p) use ($now) {
                return $now->between($p->start_date, $p->end_date);
            });

            $defaultReportPeriodId = $current?->id ?? $reportPeriods->first()?->id;
        }

        return response()->json([
            'resolutions' => $resolutions,
            'reportPeriods' => $reportPeriods,
            'allowedPeriodTypes' => array_values($allowedPeriodTypes),
            'defaultResolutionId' => $selectedResolutionId,
            'defaultReportPeriodId' => $defaultReportPeriodId,
        ]);
    }

    public function data(Request $request)
    {
        $v = Validator::make($request->all(), [
            'resolution_id' => ['required', 'integer', 'min:1'],
            'report_period_id' => ['required', 'integer', 'min:1'],
            'agency_id' => ['nullable', 'integer', 'min:1'],
            'children_agency_id' => ['nullable', 'integer', 'min:1'],
            'report_status' => ['nullable', 'in:reported,not_reported'],
            'complete_status' => ['nullable', 'in:completed,not_completed'],
            'deadline_status' => ['nullable', 'in:on_time,overdue'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Tham số không hợp lệ',
                'errors' => $v->errors(),
            ], 422);
        }

        $user = auth()->user();
        $isReporter = $user && $user->hasRole('reporter');
        $isSubAdmin = $user && $user->hasRole('sub_admin');
        $isAdminOrSupervisor = $user && $user->hasRole(['admin', 'supervisor']);

        $resolutionId = (int) $request->integer('resolution_id');
        $reportPeriodId = (int) $request->integer('report_period_id');
        $period = ReportPeriod::query()->findOrFail($reportPeriodId);

        $now = Carbon::now();
        $today = Carbon::today();

        $selectedAgencyId = $request->integer('agency_id');
        $selectedChildrenAgency = $request->integer('children_agency_id');
        $filterReportStatus = $request->input('report_status');
        $filterCompleteStatus = $request->input('complete_status');
        $filterDeadlineStatus = $request->input('deadline_status');

        if ($isSubAdmin) {
            $selectedAgencyId = (int) $user->agency_id;
        }

        $selectedPeriodType = is_object($period->period_type) && method_exists($period->period_type, 'value')
            ? $period->period_type->value()
            : (string) $period->period_type;

        $resolutionPeriodTypes = ResolutionReport::query()
            ->where('resolution_id', $resolutionId)
            ->where('unit_type', 'mission')
            ->pluck('period_type')
            ->unique()
            ->values();

        $resolutionHasPeriod = $resolutionPeriodTypes->contains($selectedPeriodType);

        $applyAgencyScopeOnMissionAgency = function ($query, string $maAlias = 'ma') use (
            $isAdminOrSupervisor,
            $isSubAdmin,
            $isReporter,
            $user,
            $selectedAgencyId,
            $selectedChildrenAgency
        ) {
            if ($isAdminOrSupervisor) {
                if ($selectedAgencyId) {
                    $query->where("{$maAlias}.agency_id", $selectedAgencyId);
                }
            } elseif ($isSubAdmin) {
                $query->where("{$maAlias}.agency_id", (int) $user->agency_id);
                if ($selectedChildrenAgency) {
                    $query->where("{$maAlias}.children_agency_id", (int) $selectedChildrenAgency);
                }
            } elseif ($isReporter) {
                $query->where("{$maAlias}.children_agency_id", (int) $user->agency_id);
            }
        };

        $applyPeriodScopeOnMission = function ($query, string $missionAlias = 'm') use ($selectedPeriodType, $resolutionHasPeriod) {
            if ($resolutionHasPeriod) {
                $query->where(function ($qq) use ($selectedPeriodType, $missionAlias) {
                    $qq->whereNotExists(function ($p) use ($missionAlias) {
                        $p->select(DB::raw(1))
                            ->from('mission_report_periods as mrp')
                            ->whereColumn('mrp.mission_id', "{$missionAlias}.id");
                    })->orWhereExists(function ($p) use ($selectedPeriodType, $missionAlias) {
                        $p->select(DB::raw(1))
                            ->from('mission_report_periods as mrp')
                            ->whereColumn('mrp.mission_id', "{$missionAlias}.id")
                            ->where('mrp.period_type', $selectedPeriodType);
                    });
                });
            } else {
                $query->whereExists(function ($p) use ($selectedPeriodType, $missionAlias) {
                    $p->select(DB::raw(1))
                        ->from('mission_report_periods as mrp')
                        ->whereColumn('mrp.mission_id', "{$missionAlias}.id")
                        ->where('mrp.period_type', $selectedPeriodType);
                });
            }
        };

        // Mission list gốc, chỉ lọc theo resolution + agency scope + period scope.
        $baseMissionIds = DB::table('missions as m')
            ->join('mission_groups as mg', 'mg.id', '=', 'm.mission_group_id')
            ->where('mg.resolution_id', $resolutionId)
            ->whereNull('m.parent_mission_id')
            ->whereExists(function ($q) use ($applyAgencyScopeOnMissionAgency) {
                $q->select(DB::raw(1))
                    ->from('mission_agency as ma')
                    ->whereColumn('ma.mission_id', 'm.id');

                $applyAgencyScopeOnMissionAgency($q, 'ma');
            });

        $applyPeriodScopeOnMission($baseMissionIds, 'm');

        $missionIds = $baseMissionIds
            ->select('m.id')
            ->distinct()
            ->pluck('m.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($missionIds)) {
            return response()->json([
                'meta' => [
                    'resolution_id' => $resolutionId,
                    'report_period_id' => $reportPeriodId,
                    'period_type' => $selectedPeriodType,
                    'report_year' => $period->report_year ?? null,
                    'report_period_number' => $period->period_number ?? null,
                    'agency_id' => $selectedAgencyId ?: null,
                    'children_agency_id' => $selectedChildrenAgency ?: null,
                    'report_status' => $filterReportStatus ?: null,
                    'complete_status' => $filterCompleteStatus ?: null,
                    'deadline_status' => $filterDeadlineStatus ?: null,
                ],
                'mission' => [
                    'summary' => [
                        'total_missions' => 0,
                        'completed_missions' => 0,
                        'not_started_missions' => 0,
                        'in_progress_missions' => 0,
                        'reported_all_missions' => 0,
                        'on_time_missions' => 0,
                        'late_missions' => 0,
                    ],
                    'by_group' => [],
                    'top_backlog_agencies' => [],
                ],
                'indicator' => [
                    'summary' => [
                        'total_agencies' => 0,
                        'reported_count' => 0,
                        'completed_count' => 0,
                        'on_time_count' => 0,
                    ],
                ],
            ]);
        }

        // Aggregate theo mission, lấy Mission Report làm chuẩn.
        $perMission = DB::table('missions as m')
            ->join('mission_groups as mg', 'mg.id', '=', 'm.mission_group_id')
            ->join('mission_agency as ma', 'ma.mission_id', '=', 'm.id')
            ->leftJoin('mission_reports as mr', function ($join) use ($reportPeriodId) {
                $join->on('mr.mission_agency_id', '=', 'ma.id')
                    ->where('mr.report_period_id', '=', $reportPeriodId);
            })
            ->whereIn('m.id', $missionIds)
            ->where('mg.resolution_id', $resolutionId)
            ->whereNull('m.parent_mission_id')
            ->tap(function ($q) use ($applyAgencyScopeOnMissionAgency) {
                $applyAgencyScopeOnMissionAgency($q, 'ma');
            })
            ->groupBy(
                'm.id',
                'm.mission_group_id',
                'mg.group_name',
                'm.deadline_date',
                'm.is_completed',
                'm.completed_at'
            )
            ->selectRaw('m.id as mission_id')
            ->selectRaw('m.mission_group_id')
            ->selectRaw('mg.group_name')
            ->selectRaw('m.deadline_date')
            ->selectRaw('m.is_completed')
            ->selectRaw('m.completed_at')
            ->selectRaw('COUNT(DISTINCT ma.id) as total_assignments')
            ->selectRaw('COUNT(DISTINCT CASE WHEN NOT (ma.is_completed = 1 AND ma.completed_at IS NOT NULL AND ma.completed_at < ?) THEN ma.id END) as required_report_count', [$period->start_date])
            ->selectRaw('COUNT(DISTINCT CASE WHEN mr.id IS NOT NULL THEN ma.id END) as reported_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN ma.is_completed = 1 THEN ma.id END) as completed_assignment_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN ma.is_completed = 1 AND DATE(ma.completed_at) <= m.deadline_date THEN ma.id WHEN ma.is_completed = 0 AND m.deadline_date >= ? THEN ma.id END) as on_time_assignment_count', [$today])
            ->selectRaw('COUNT(DISTINCT CASE WHEN ma.is_completed = 1 AND DATE(ma.completed_at) > m.deadline_date THEN ma.id WHEN ma.is_completed = 0 AND m.deadline_date < ? THEN ma.id END) as overdue_assignment_count', [$today])
            ->get();

        $missions = $perMission->filter(function ($row) use ($filterReportStatus, $filterCompleteStatus, $filterDeadlineStatus, $today, $period, $isAdminOrSupervisor) {
            $requiredReportCount = (int) $row->required_report_count;
            $reportedCount = (int) $row->reported_count;
            $isReportedMission = $requiredReportCount === 0 || $reportedCount >= $requiredReportCount;
            $isNotReportedMission = $reportedCount < $requiredReportCount && ((int) $row->is_completed === 0 || ($row->completed_at && Carbon::parse($row->completed_at)->gte($period->start_date)));

            if ($filterReportStatus === 'reported' && !$isReportedMission) {
                return false;
            }
            if ($filterReportStatus === 'not_reported' && !$isNotReportedMission) {
                return false;
            }

            if ($isAdminOrSupervisor) {
                if ($filterCompleteStatus === 'completed' && (int) $row->is_completed !== 1) {
                    return false;
                }
                if ($filterCompleteStatus === 'not_completed' && (int) $row->is_completed !== 0) {
                    return false;
                }

                if ($filterDeadlineStatus === 'on_time') {
                    $deadline = $row->deadline_date ? Carbon::parse($row->deadline_date) : null;
                    $completedAt = $row->completed_at ? Carbon::parse($row->completed_at) : null;
                    $pass = false;
                    if ((int) $row->is_completed === 1) {
                        $pass = $deadline && $completedAt && $completedAt->toDateString() <= $deadline->toDateString();
                    } else {
                        $pass = $deadline && $deadline->gte($today);
                    }
                    if (!$pass) {
                        return false;
                    }
                }

                if ($filterDeadlineStatus === 'overdue') {
                    $deadline = $row->deadline_date ? Carbon::parse($row->deadline_date) : null;
                    $completedAt = $row->completed_at ? Carbon::parse($row->completed_at) : null;
                    $pass = false;
                    if ((int) $row->is_completed === 1) {
                        $pass = $deadline && $completedAt && $completedAt->toDateString() > $deadline->toDateString();
                    } else {
                        $pass = $deadline && $deadline->lt($today);
                    }
                    if (!$pass) {
                        return false;
                    }
                }
            } else {
                if ($filterCompleteStatus === 'completed' && (int) $row->completed_assignment_count <= 0) {
                    return false;
                }
                if ($filterCompleteStatus === 'not_completed' && (int) $row->completed_assignment_count >= (int) $row->total_assignments) {
                    return false;
                }
                if ($filterDeadlineStatus === 'on_time' && (int) $row->on_time_assignment_count <= 0) {
                    return false;
                }
                if ($filterDeadlineStatus === 'overdue' && (int) $row->overdue_assignment_count <= 0) {
                    return false;
                }
            }

            return true;
        })->values();

        $totalM = (int) $missions->count();

        $completedM = $isAdminOrSupervisor
            ? (int) $missions->where('is_completed', 1)->count()
            : (int) $missions->filter(fn ($m) => (int) $m->completed_assignment_count > 0)->count();

        $reportedAllM = (int) $missions->filter(function ($m) {
            return (int) $m->required_report_count === 0 || (int) $m->reported_count >= (int) $m->required_report_count;
        })->count();

        $notStartedM = (int) $missions->filter(function ($m) use ($period) {
            return (int) $m->reported_count < (int) $m->required_report_count
                && ((int) $m->is_completed === 0 || ($m->completed_at && Carbon::parse($m->completed_at)->gte($period->start_date)));
        })->count();

        $onTimeM = $isAdminOrSupervisor
            ? (int) $missions->filter(function ($m) use ($today) {
                $deadline = $m->deadline_date ? Carbon::parse($m->deadline_date) : null;
                $completedAt = $m->completed_at ? Carbon::parse($m->completed_at) : null;
                if ((int) $m->is_completed === 1) {
                    return $deadline && $completedAt && $completedAt->toDateString() <= $deadline->toDateString();
                }
                return $deadline && $deadline->gte($today);
            })->count()
            : (int) $missions->filter(fn ($m) => (int) $m->on_time_assignment_count > 0)->count();

        $lateM = $isAdminOrSupervisor
            ? (int) $missions->filter(function ($m) use ($today) {
                $deadline = $m->deadline_date ? Carbon::parse($m->deadline_date) : null;
                $completedAt = $m->completed_at ? Carbon::parse($m->completed_at) : null;
                if ((int) $m->is_completed === 1) {
                    return $deadline && $completedAt && $completedAt->toDateString() > $deadline->toDateString();
                }
                return $deadline && $deadline->lt($today);
            })->count()
            : (int) $missions->filter(fn ($m) => (int) $m->overdue_assignment_count > 0)->count();

        $inProgressM = max(0, $totalM - $completedM - $notStartedM);

        $missionSummary = [
            'total_missions' => $totalM,
            'completed_missions' => $completedM,
            'not_started_missions' => $notStartedM,
            'in_progress_missions' => $inProgressM,
            'reported_all_missions' => $reportedAllM,
            'on_time_missions' => $onTimeM,
            'late_missions' => $lateM,
        ];

        $missionByGroup = $missions
            ->groupBy('mission_group_id')
            ->map(function ($rows) use ($isAdminOrSupervisor) {
                $groupName = $rows->first()->group_name ?? '';
                $total = (int) $rows->count();

                $completed = $isAdminOrSupervisor
                    ? (int) $rows->where('is_completed', 1)->count()
                    : (int) $rows->filter(fn ($row) => (int) $row->completed_assignment_count > 0)->count();

                return [
                    'mission_group_id' => (int) $rows->first()->mission_group_id,
                    'group_name' => $groupName,
                    'total_missions' => $total,
                    'completed_missions' => $completed,
                    'completed_percent' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                ];
            })
            ->values()
            ->sortByDesc('completed_percent')
            ->take(10)
            ->values();

        $topBacklogAgenciesQ = DB::table('mission_agency as ma')
            ->join('missions as m2', 'm2.id', '=', 'ma.mission_id')
            ->join('mission_groups as mg2', 'mg2.id', '=', 'm2.mission_group_id')
            ->join('agencies as a', 'a.id', '=', 'ma.agency_id')
            ->where('mg2.resolution_id', $resolutionId)
            ->whereNull('m2.parent_mission_id')
            ->where('ma.is_completed', 0)
            ->whereNotNull('m2.deadline_date')
            ->where('m2.deadline_date', '<', $today)
            ->whereIn('m2.id', $missions->pluck('mission_id')->all());

        $applyAgencyScopeOnMissionAgency($topBacklogAgenciesQ, 'ma');

        $topBacklogAgencies = $topBacklogAgenciesQ
            ->selectRaw('ma.agency_id, a.agency_name, COUNT(*) as backlog_count')
            ->groupBy('ma.agency_id', 'a.agency_name')
            ->orderByDesc('backlog_count')
            ->limit(5)
            ->get();

        return response()->json([
            'meta' => [
                'resolution_id' => $resolutionId,
                'report_period_id' => $reportPeriodId,
                'period_type' => $selectedPeriodType,
                'report_year' => $period->report_year ?? null,
                'report_period_number' => $period->period_number ?? null,
                'agency_id' => $selectedAgencyId ?: null,
                'children_agency_id' => $selectedChildrenAgency ?: null,
                'report_status' => $filterReportStatus ?: null,
                'complete_status' => $filterCompleteStatus ?: null,
                'deadline_status' => $filterDeadlineStatus ?: null,
            ],
            'mission' => [
                'summary' => $missionSummary,
                'by_group' => $missionByGroup,
                'top_backlog_agencies' => $topBacklogAgencies,
            ],
            'indicator' => [
                'summary' => [
                    'total_agencies' => 0,
                    'reported_count' => 0,
                    'completed_count' => 0,
                    'on_time_count' => 0,
                ],
            ],
        ]);
    }
}
