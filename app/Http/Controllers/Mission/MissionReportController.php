<?php

namespace App\Http\Controllers\Mission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\MissionReportRequest;
use App\Models\Agency\Agency;
use App\Models\DelayReason;
use App\Models\Mission\Mission;
use App\Models\Mission\MissionAgency;
use App\Models\Mission\MissionDashboardStat;
use App\Models\Mission\MissionGroup;
use App\Models\Mission\MissionReport;
use App\Models\Mission\MissionReportPeriod;
use App\Models\ReportPeriod;
use App\Models\Resolution\Resolution;
use App\Services\Mission\MissionDashboardDeadlineService;
use App\Services\Mission\MissionDashboardOnReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MissionReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $isReporter = $user->hasRole('reporter');
        $isSubAdmin = $user->hasRole('sub_admin');
        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);


        if ($user->hasRole('sub_admin')) {
            $request->merge([
                'agency_id' => $user->agency_id
            ]);
        }        

        /* ================== DATA CHUNG ================== */
        $resolutions   = Resolution::orderBy('issued_date', 'desc')->get();
        $resolution    = null;
        $reportPeriod  = null;
        $reportPeriods = collect();
        $groups        = collect();
        $reportsMap    = collect();
        $dashboardStats = collect();

        /* ================== FILTER PARAM ================== */
        $selectedAgencyId       = $request->agency_id;
        $selectedChildrenAgency = $request->children_agency_id;
        $filterReportStatus   = $request->report_status;
        $filterCompleteStatus = $request->complete_status;
        $filterDeadlineStatus = $request->deadline_status;
        $now = now();

        /* ================== LOAD AGENCY FILTER ================== */
        if ($isAdminOrSupervisor) {
            // Admin: danh sách agency cấp 1
            $agencyGroups = Agency::with('group')
                ->whereNull('parent_agency_id')
                ->orderBy('agency_name')
                ->get()
                ->groupBy('agency_group_id');
        } else {
            // Subadmin: chỉ agency cấp 2 thuộc agency cấp 1 của nó
            $agencyGroups = Agency::where('parent_agency_id', $user->agency_id)
                ->orderBy('agency_name')
                ->get()
                ->groupBy('agency_group_id');
        }

        /* ================== CHƯA CHỌN VĂN BẢN ================== */
        if (! $request->filled('resolution_id')) {
            return view('mission.dashboard', compact(
                'resolutions',
                'resolution',
                'reportPeriods',
                'reportPeriod',
                'groups',
                'reportsMap',
                'agencyGroups',
                'selectedAgencyId',
                'selectedChildrenAgency',
                'isAdminOrSupervisor',
                'dashboardStats'
            ));
        }

        /* ================== LOAD VĂN BẢN ================== */
        $resolution = Resolution::findOrFail($request->resolution_id);

        /* ================== LOAD PERIOD TYPE ================== */
        $resolutionPeriodTypes = $resolution->reports
            ->where('unit_type', 'mission')
            ->pluck('period_type')
            ->unique();

        $missionPeriodTypes = MissionReportPeriod::pluck('period_type')->unique();

        $allPeriodTypes = $resolutionPeriodTypes
            ->merge($missionPeriodTypes)
            ->unique()
            ->values();

        $reportPeriods = ReportPeriod::whereIn('period_type', $allPeriodTypes)
            ->orderBy('report_year')
            ->orderBy('start_date')
            ->get();

        if (! $request->filled('report_period_id')) {
            return view('mission.dashboard', compact(
                'resolutions',
                'resolution',
                'reportPeriods',
                'reportPeriod',
                'groups',
                'reportsMap',
                'agencyGroups',
                'selectedAgencyId',
                'selectedChildrenAgency',
                'isAdminOrSupervisor',
                'dashboardStats'
            ));
        }

        /* ================== KỲ ĐANG CHỌN ================== */
        $reportPeriod = ReportPeriod::findOrFail($request->report_period_id);

        $isSubmit = $request->filled('resolution_id') && $request->filled('report_period_id');
        $dashboardStats = collect();

        if ($isAdminOrSupervisor && $isSubmit) {
            foreach ($groups as $group) {
                foreach ($group->missions as $mission) {
                    app(MissionDashboardDeadlineService::class)
                        ->execute($mission, $reportPeriod->id);
                }
            }
            $dashboardStats = MissionDashboardStat::where('report_period_id', $reportPeriod->id)
                ->whereHas('mission.group', fn ($q) =>
                    $q->where('resolution_id', $resolution->id)
                )
                ->get()
                ->keyBy('mission_id');
        }

        $selectedPeriodType = $reportPeriod->period_type->value();
        $resolutionHasPeriod = $resolutionPeriodTypes->contains($selectedPeriodType);

        /* ================== LOAD MISSION ================== */
        $groups = MissionGroup::with([
            'missions' => function ($q) use ( 
                $isAdminOrSupervisor,
                $user,
                $selectedAgencyId,
                $selectedChildrenAgency,
                $selectedPeriodType,
                $filterReportStatus,
                $filterCompleteStatus,
                $filterDeadlineStatus,  
                $reportPeriod,              
                $resolutionHasPeriod,
                $now
            ) {
                $q->whereNull('parent_mission_id')->orderBy('id');

                /* ===== FILTER THEO AGENCY (CHỐT LOGIC) ===== */
                $q->whereHas('missionAgencies', function ($qa) use (
                    $isAdminOrSupervisor,
                    $user,
                    $selectedAgencyId,
                    $selectedChildrenAgency
                ) {
                    if ($isAdminOrSupervisor) {
                        if ($selectedAgencyId) {
                            $qa->where('agency_id', $selectedAgencyId);
                        }
                    } elseif ($user->hasRole('sub_admin')) {
                        $qa->where('agency_id', $user->agency_id);
                        if ($selectedChildrenAgency) {
                            $qa->where('children_agency_id', $selectedChildrenAgency);
                        }
                    } elseif ($user->hasRole('reporter')) {
                        $qa->where('children_agency_id', $user->agency_id);
                    }
                });

                /* ===== FILTER PERIOD ===== */
                $q->where(function ($qq) use ($selectedPeriodType, $resolutionHasPeriod) {
                    if ($resolutionHasPeriod) {
                        $qq->whereDoesntHave('reportPeriods')
                        ->orWhereHas('reportPeriods', fn ($p) =>
                                $p->where('period_type', $selectedPeriodType)
                        );
                    } else {
                        $qq->whereHas('reportPeriods', fn ($p) =>
                            $p->where('period_type', $selectedPeriodType)
                        );
                    }
                });

                $q->with(['agencies', 'children', 'reportPeriods']);
                /* ===== FILTER TRẠNG THÁI BÁO CÁO / HOÀN THÀNH / TIẾN ĐỘ ===== */
                if ($filterReportStatus || $filterCompleteStatus || $filterDeadlineStatus) {

                    /* ========== ADMIN / SUPERVISOR ========== */
                    if ($isAdminOrSupervisor) {
                        if ($filterCompleteStatus === 'completed') {
                            $q->where('is_completed', true);
                        }

                        if ($filterCompleteStatus === 'not_completed') {
                            $q->where('is_completed', false);
                        }
                        if ($filterDeadlineStatus === 'on_time') {
                            $q->where(function ($qq) use ($now) {
                                $qq->where(function ($a) use ($now) {
                                    // Đã hoàn thành đúng hạn
                                    $a->where('is_completed', true)
                                    ->whereColumn('completed_at', '<=', 'deadline_date');
                                })
                                ->orWhere(function ($b) use ($now) {
                                    // Chưa hoàn thành nhưng chưa quá hạn
                                    $b->where('is_completed', false)
                                    ->where('deadline_date', '>=', $now);
                                });
                            });
                        }

                        if ($filterDeadlineStatus === 'overdue') {
                            $q->where(function ($qq) use ($now) {
                                $qq->where(function ($a) use ($now) {
                                    // Hoàn thành trễ
                                    $a->where('is_completed', true)
                                    ->whereColumn('completed_at', '>', 'deadline_date');
                                })
                                ->orWhere(function ($b) use ($now) {
                                    // Chưa hoàn thành và đã quá hạn
                                    $b->where('is_completed', false)
                                    ->where('deadline_date', '<', $now);
                                });
                            });
                        }
                        $q->whereHas('dashboardStats', function ($qs) use (
                            $filterReportStatus,
                            $reportPeriod
                        ) {
                            $qs->where('report_period_id', $reportPeriod->id);

                            if ($filterReportStatus === 'reported') {
                                $qs->whereColumn('reported_count', '=', 'total_agencies');
                            }

                            if ($filterReportStatus === 'not_reported') {
                                $qs->whereColumn('reported_count', '<', 'total_agencies')
                                ->whereHas('mission', function ($qm) use ($reportPeriod) {
                                    $qm->where(function ($qq) use ($reportPeriod) {
                                        $qq->where('is_completed', false)
                                            ->orWhere('completed_at', '>=', $reportPeriod->start_date);
                                    });
                                });
                            }
                        });
                    }

                    /* ========== SUB ADMIN ========== */
                    elseif ($user->hasRole('sub_admin')) {

                        $q->whereHas('missionAgencies', function ($qa) use (
                            $user,
                            $reportPeriod,
                            $filterReportStatus,
                            $filterCompleteStatus,
                            $filterDeadlineStatus,
                            $now,
                        ) {
                            $qa->where('agency_id', $user->agency_id);

                            if ($filterReportStatus === 'reported') {
                                $qa->whereHas('reports', fn ($r) =>
                                    $r->where('report_period_id', $reportPeriod->id)
                                );
                            }

                            if ($filterReportStatus === 'not_reported') {
                                $qa->where(function ($qq) use ($reportPeriod) {
                                    $qq
                                        // 1. Không có report kỳ này
                                        ->whereDoesntHave('reports', fn ($r) =>
                                            $r->where('report_period_id', $reportPeriod->id)
                                        )
                                        // 2. VÀ CHƯA hoàn thành trước kỳ này
                                        ->where(function ($q2) use ($reportPeriod) {
                                            $q2->where('is_completed', false)
                                            ->orWhere('completed_at', '>=', $reportPeriod->start_date);
                                        });
                                });
                            }

                            if ($filterCompleteStatus === 'completed') {
                                $qa->where('is_completed', true);
                            }

                            if ($filterCompleteStatus === 'not_completed') {
                                $qa->where('is_completed', false);
                            }

                            if ($filterDeadlineStatus === 'on_time') {
                                $qa->where(function ($qq) use ($now) {
                                    $qq->where(function ($a) {
                                        // Đã hoàn thành đúng hạn
                                        $a->where('is_completed', true)
                                        ->whereColumn('completed_at', '<=', 'deadline_date');
                                    })
                                    ->orWhere(function ($b) use ($now) {
                                        // Chưa hoàn thành nhưng chưa quá hạn
                                        $b->where('is_completed', false)
                                        ->where('deadline_date', '>=', $now);
                                    });
                                });
                            }

                            if ($filterDeadlineStatus === 'overdue') {
                                $qa->where(function ($qq) use ($now) {
                                    $qq->where(function ($a) {
                                        // Hoàn thành trễ
                                        $a->where('is_completed', true)
                                        ->whereColumn('completed_at', '>', 'deadline_date');
                                    })
                                    ->orWhere(function ($b) use ($now) {
                                        // Chưa hoàn thành và đã quá hạn
                                        $b->where('is_completed', false)
                                        ->where('deadline_date', '<', $now);
                                    });
                                });
                            }                            
                        });
                    }

                    /* ========== REPORTER ========== */
                    elseif ($user->hasRole('reporter')) {

                        $q->whereHas('missionAgencies', function ($qa) use (
                            $user,
                            $reportPeriod,
                            $filterReportStatus,
                            $filterCompleteStatus,
                            $filterDeadlineStatus,
                            $now
                        ) {
                            $qa->where('children_agency_id', $user->agency_id);

                            if ($filterReportStatus === 'reported') {
                                $qa->whereHas('reports', fn ($r) =>
                                    $r->where('report_period_id', $reportPeriod->id)
                                );
                            }

                            if ($filterReportStatus === 'not_reported') {
                                $qa->where(function ($qq) use ($reportPeriod) {
                                    $qq
                                        // 1. Không có report kỳ này
                                        ->whereDoesntHave('reports', fn ($r) =>
                                            $r->where('report_period_id', $reportPeriod->id)
                                        )
                                        // 2. VÀ CHƯA hoàn thành trước kỳ này
                                        ->where(function ($q2) use ($reportPeriod) {
                                            $q2->where('is_completed', false)
                                            ->orWhere('completed_at', '>=', $reportPeriod->start_date);
                                        });
                                });
                            }

                            if ($filterCompleteStatus === 'completed') {
                                $qa->where('is_completed', true);
                            }

                            if ($filterCompleteStatus === 'not_completed') {
                                $qa->where('is_completed', false);
                            }

                            if ($filterDeadlineStatus === 'on_time') {
                                $qa->where(function ($qq) use ($now) {
                                    $qq->where(function ($a) {
                                        // Đã hoàn thành đúng hạn
                                        $a->where('is_completed', true)
                                        ->whereColumn('completed_at', '<=', 'deadline_date');
                                    })
                                    ->orWhere(function ($b) use ($now) {
                                        // Chưa hoàn thành nhưng chưa quá hạn
                                        $b->where('is_completed', false)
                                        ->where('deadline_date', '>=', $now);
                                    });
                                });
                            }

                            if ($filterDeadlineStatus === 'overdue') {
                                $qa->where(function ($qq) use ($now) {
                                    $qq->where(function ($a) {
                                        // Hoàn thành trễ
                                        $a->where('is_completed', true)
                                        ->whereColumn('completed_at', '>', 'deadline_date');
                                    })
                                    ->orWhere(function ($b) use ($now) {
                                        // Chưa hoàn thành và đã quá hạn
                                        $b->where('is_completed', false)
                                        ->where('deadline_date', '<', $now);
                                    });
                                });
                            }                            
                        });
                    }
                }
            }
        ])
        ->where('resolution_id', $resolution->id)
        ->orderBy('id')
        ->get();

        /* ================== MAP BÁO CÁO ================== */
        $reportsMap = MissionAgency::with([
                'reports' => fn ($q) =>
                    $q->where('report_period_id', $reportPeriod->id)
            ])
            ->whereHas('mission.group', fn ($q) =>
                $q->where('resolution_id', $resolution->id)
            )
            ->where(function ($q) use (
                $isAdminOrSupervisor,
                $isSubAdmin,
                $isReporter,
                $user,
                $selectedAgencyId,
                $selectedChildrenAgency
            ) {
                if ($isAdminOrSupervisor) {

                    if ($selectedAgencyId) {
                        $q->where('agency_id', $selectedAgencyId);
                    }

                } elseif ($isSubAdmin) {

                    $q->where('agency_id', $user->agency_id);

                    if ($selectedChildrenAgency) {
                        $q->where('children_agency_id', $selectedChildrenAgency);
                    }

                } elseif ($isReporter) {

                    $q->where('children_agency_id', $user->agency_id);
                }
            })
            ->get()
            ->mapWithKeys(fn ($ma) => [
                $ma->id => $ma->reports->first()
            ]);

        return view('mission.dashboard', compact(
            'resolutions',
            'resolution',
            'reportPeriods',
            'reportPeriod',
            'groups',
            'reportsMap',
            'agencyGroups',
            'selectedAgencyId',
            'selectedChildrenAgency',
            'isAdminOrSupervisor',
            'dashboardStats'
        ));
    }

    public function create(Request $request, Mission $mission)
    {
        $user = auth()->user();

        $missionAgencyQuery = MissionAgency::where('mission_id', $mission->id);

        if ($user->hasRole('reporter')) {
            $missionAgencyQuery->where('children_agency_id', $user->agency_id);
        } else {
            // sub_admin / admin / supervisor
            $missionAgencyQuery->where('agency_id', $user->agency_id);
        }

        $missionAgency = $missionAgencyQuery->firstOrFail();

        $periodTypes = $mission->getEffectivePeriodTypes();

        $reportPeriods = ReportPeriod::whereIn('period_type', $periodTypes)
            ->orderBy('report_year')
            ->orderBy('start_date')
            ->get();

        $selectedReportPeriod = $reportPeriods->firstWhere('id', request('report_period_id'));

        $canEdit = false;

        if ($missionAgency->canEdit()
            && $selectedReportPeriod
            && $selectedReportPeriod->canReport()
        ) {
            $canEdit = true;
        }

        if ($missionAgency->is_completed && !$missionAgency->canEdit()) {

            // Lấy danh sách period đã có báo cáo
            $reportedPeriodIds = MissionReport::where('mission_agency_id', $missionAgency->id)
                ->pluck('report_period_id')
                ->unique();

            // Chỉ giữ lại các kỳ đã có report
            $reportPeriods = $reportPeriods->whereIn('id', $reportedPeriodIds);
        }

        $years = $reportPeriods->pluck('report_year')->unique()->values();

        $reports = MissionReport::where('mission_agency_id', $missionAgency->id)
            ->with('delayReasons')
            ->get()
            ->keyBy('report_period_id');

        $delayReasons = DelayReason::get();

        return view('mission.report', [
            'mission'        => $mission,
            'missionAgency'  => $missionAgency,
            'reportPeriods'  => $reportPeriods,
            'years'          => $years,
            'reports'        => $reports,
            'delayReasons'   => $delayReasons,
            'canEdit'        => $canEdit,
        ]);
    }

    public function store(
        MissionReportRequest $request,
        Mission $mission
    ) {
        $user = auth()->user();

        $missionAgencyQuery = MissionAgency::where('mission_id', $mission->id);

        if ($user->hasRole('reporter')) {
            $missionAgencyQuery->where('children_agency_id', $user->agency_id);
        } else {
            // sub_admin / admin / supervisor
            $missionAgencyQuery->where('agency_id', $user->agency_id);
        }

        $missionAgency = $missionAgencyQuery->firstOrFail();

        $data = $request->validated();

        $reportPeriod = ReportPeriod::findOrFail($data['report_period_id']);

        $canEdit = $missionAgency->canEdit() && $reportPeriod->canReport();

        if(!$canEdit || $user->hasRole('sub_admin')){
            return back();
        }
        else{
            if ($data['progress_percent'] == 100) {
                $data['status'] = 1;
            }

            $report = MissionReport::updateOrCreate(
                [
                    'mission_agency_id' => $missionAgency->id,
                    'report_period_id'  => $data['report_period_id'],
                ],
                [
                    'status'           => $data['status'],
                    'progress_percent' => $data['progress_percent'],
                    'execution_result' => $data['execution_result'] ?? null,
                    'recommendation'   => $data['recommendation'] ?? null,
                ]
            );

            if (!empty($data['delay_reasons'])) {
                $otherReasonId = DelayReason::where('reason_code', 'others')->value('id');
                $syncData = [];
                foreach ($data['delay_reasons'] as $reasonId) {
                    $syncData[$reasonId] = [
                        'description' => $reasonId == $otherReasonId
                            ? ($data['delay_reason_other_description'] ?? null)
                            : null,
                    ];
                }
                $report->delayReasons()->sync($syncData);
            } else {
                $report->delayReasons()->detach();
            }

            $isNowCompleted = $report->status == 1 && ($report->progress_percent ?? 100) == 100;
            DB::transaction(function () use ($missionAgency, $report, $isNowCompleted) {

                if ($isNowCompleted) {
                    $missionAgency->update([
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]);
                } else {
                    $missionAgency->update([
                        'is_completed' => false,
                        'completed_at' => null,
                    ]);
                }

                // ====== Re-calc toàn mission ======
                $mission = $missionAgency->mission;

                $hasUncompleted = $mission->missionAgencies()
                    ->where('is_completed', false)
                    ->exists();

                if ($hasUncompleted) {
                    $mission->update([
                        'is_completed' => false,
                        'completed_at' => null,
                    ]);
                } else {
                    $mission->update([
                        'is_completed' => true,
                        'completed_at' => $mission->missionAgencies()->max('completed_at'),
                    ]);
                }
            });
            
            app(MissionDashboardOnReportService::class)->execute($report);
        }

        return redirect()
            ->route('missions.report.dashboard', [
                'resolution_id'    => $mission->group->resolution_id,
                'report_period_id' => $reportPeriod->id,
                'report_year'      => $reportPeriod->report_year,
                'period_type'      => $reportPeriod->period_type->value(),
            ])
            ->with('success', 'Báo cáo nhiệm vụ đã được lưu');
    }

    public function details(Request $request, Mission $mission)
    {
        $user = auth()->user();
        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);

        $resolution = $mission->group->resolution;
        $group      = $mission->group;

        $reportPeriod = $request->report_period_id
            ? ReportPeriod::findOrFail($request->report_period_id)
            : null;

        $periodTypes = $mission->getEffectivePeriodTypes();

        $reportPeriods = ReportPeriod::whereIn('period_type', $periodTypes)
            ->orderBy('report_year')
            ->orderBy('start_date')
            ->get();

        $missionAgencies = MissionAgency::with([
                'agency',
                'reports' => fn ($q) =>
                    $reportPeriod
                        ? $q->where('report_period_id', $reportPeriod->id)
                        : $q
            ])
            ->where('mission_id', $mission->id)
            ->get();

        $missionAgencies->each(function ($ma) use ($reportPeriod) {

            $report = $ma->reports->first();

            $completedBeforePeriod =
                $ma->is_completed &&
                $reportPeriod &&
                $ma->completed_at &&
                $ma->completed_at < $reportPeriod->start_date;

            // ===== TRẠNG THÁI BÁO CÁO =====
            if ($report) {
                $ma->display_report_status = 'reported';
                $ma->display_report = $report;
            } elseif ($completedBeforePeriod) {
                $ma->display_report_status = 'na';
                $ma->display_report = null;
            } else {
                $ma->display_report_status = 'not_reported';
                $ma->display_report = null;
            }

            // ===== HOÀN THÀNH =====
            if ($completedBeforePeriod) {
                $ma->display_complete_status = 'completed';
            } else {
                $ma->display_complete_status = $ma->is_completed
                    ? 'completed'
                    : 'not_completed';
            }

            // ===== TIẾN ĐỘ (ĐÚNG HẠN / TRỄ HẠN) =====
            if ($ma->display_complete_status === 'completed') {

                if ($ma->deadline_date && $ma->completed_at > $ma->deadline_date) {
                    $ma->display_progress_status = 'overdue'; // hoàn thành trễ
                } else {
                    $ma->display_progress_status = 'on_time'; // đúng hạn
                }

            } else {
                // chưa hoàn thành
                if ($ma->deadline_date && now()->gt($ma->deadline_date)) {
                    $ma->display_progress_status = 'overdue'; // đang trễ
                } else {
                    $ma->display_progress_status = 'on_time'; // còn hạn
                }
            }
        });

        $reportsMap = $missionAgencies
            ->mapWithKeys(fn ($ma) => [
                $ma->id => $ma->reports->first()
            ]);

        return view('mission.details', compact(
            'mission',
            'group',
            'resolution',
            'reportPeriods',
            'reportPeriod',
            'missionAgencies',
            'reportsMap',
            'isAdminOrSupervisor'
        ));
    }
}
