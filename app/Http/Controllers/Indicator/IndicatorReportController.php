<?php

namespace App\Http\Controllers\Indicator;

use App\Http\Controllers\Controller;
use App\Models\Agency\Agency;
use App\Models\Indicator\Indicator;
use App\Models\Indicator\IndicatorAgency;
use App\Models\Indicator\IndicatorGroup;
use App\Models\Indicator\IndicatorReport;
use App\Models\Indicator\IndicatorReportPeriod;
use App\Models\ReportPeriod;
use App\Models\Resolution\Resolution;
use Illuminate\Http\Request;

class IndicatorReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);

        /* ================== DỮ LIỆU CHUNG ================== */
        $resolutions   = Resolution::orderBy('issued_date', 'desc')->get();
        $agencyGroups  = collect();
        $resolution    = null;
        $reportPeriod  = null;
        $reportPeriods = collect();
        $groups        = collect();
        $reportsMap    = collect();

        /* ================== AGENCY ĐANG XEM ================== */
        $selectedAgencyId = $isAdminOrSupervisor
            ? $request->agency_id
            : $user->agency_id;

        /* ================== LOAD AGENCY (ADMIN / SUPERVISOR) ================== */
        if ($isAdminOrSupervisor) {
            $agencyGroups = Agency::with('group')
                ->get()
                ->groupBy('agency_group_id');
        }

        /* ================== CHƯA CHỌN VĂN BẢN ================== */
        if (! $request->filled('resolution_id')) {
            return view('indicator.dashboard', compact(
                'resolutions',
                'resolution',
                'reportPeriods',
                'reportPeriod',
                'groups',
                'reportsMap',
                'agencyGroups',
                'selectedAgencyId',
                'isAdminOrSupervisor'
            ));
        }

        /* ================== LOAD VĂN BẢN ================== */
        $resolution = Resolution::findOrFail($request->resolution_id);

        /* ================== LOAD KỲ CỦA VĂN BẢN (INDICATOR) ================== */
        $resolutionPeriodTypes = $resolution->reports
            ->where('unit_type', 'indicator')
            ->pluck('period_type')
            ->unique()
            ->toArray();

        $indicatorPeriodTypes = IndicatorReportPeriod::pluck('period_type')->unique()->toArray();

        $allPeriodTypes = collect($resolutionPeriodTypes)
            ->merge($indicatorPeriodTypes)
            ->unique()
            ->values();

        $reportPeriods = ReportPeriod::whereIn('period_type', $allPeriodTypes)
            ->orderBy('report_year')
            ->orderBy('start_date')
            ->get();

        /* ================== CHƯA CHỌN KỲ ================== */
        if (! $request->filled('report_period_id')) {
            return view('indicator.dashboard', compact(
                'resolutions',
                'resolution',
                'reportPeriods',
                'reportPeriod',
                'groups',
                'reportsMap',
                'agencyGroups',
                'selectedAgencyId',
                'isAdminOrSupervisor'
            ));
        }

        /* ================== LOAD KỲ ĐANG CHỌN ================== */
        $reportPeriod = ReportPeriod::findOrFail($request->report_period_id);
        $selectedPeriodType = $reportPeriod->period_type->value();

        /* ================== KIỂM TRA: VĂN BẢN CÓ KỲ NÀY KHÔNG ================== */
        $resolutionHasPeriod = in_array(
            $selectedPeriodType,
            $resolutionPeriodTypes
        );

        /* ================== LOAD CHỈ TIÊU ================== */
        $groups = IndicatorGroup::with([
            'indicators' => function ($q) use (
                $selectedAgencyId,
                $selectedPeriodType,
                $resolutionHasPeriod
            ) {

                $q->whereNull('parent_indicator_id')
                  ->orderBy('id');

                /* ===== LỌC THEO AGENCY ===== */
                if ($selectedAgencyId) {
                    $q->whereHas('agencies', fn ($qa) =>
                        $qa->where('agencies.id', $selectedAgencyId)
                    );
                }

                /* ===== LỌC THEO LOGIC KỲ (CHUẨN) ===== */
                $q->where(function ($qq) use (
                    $selectedPeriodType,
                    $resolutionHasPeriod
                ) {

                    if ($resolutionHasPeriod) {
                        // CASE 1: Văn bản có kỳ này
                        $qq->whereDoesntHave('reportPeriods')
                           ->orWhereHas('reportPeriods', fn ($p) =>
                                $p->where('period_type', $selectedPeriodType)
                           );
                    } else {
                        // CASE 2: Kỳ riêng, văn bản KHÔNG có
                        $qq->whereHas('reportPeriods', fn ($p) =>
                            $p->where('period_type', $selectedPeriodType)
                        );
                    }
                });

                $q->with([
                    'agencies',
                    'getChildren',
                    'reportPeriods',
                ]);
            }
        ])
        ->where('resolution_id', $resolution->id)
        ->orderBy('id')
        ->get();

        /* ================== LOAD REPORT MAP ================== */
        $reportsMap = IndicatorAgency::with([
                'reports' => fn ($q) =>
                    $q->where('report_period_id', $reportPeriod->id)
            ])
            ->when($selectedAgencyId, fn ($q) =>
                $q->where('agency_id', $selectedAgencyId)
            )
            ->whereHas('indicator.group', fn ($q) =>
                $q->where('resolution_id', $resolution->id)
            )
            ->get()
            ->mapWithKeys(fn ($ia) => [
                $ia->id => $ia->reports->first()
            ]);

        return view('indicator.dashboard', compact(
            'resolutions',
            'resolution',
            'reportPeriods',
            'reportPeriod',
            'groups',
            'reportsMap',
            'agencyGroups',
            'selectedAgencyId',
            'isAdminOrSupervisor'
        ));
    }



    /* ========================================================= */

    public function create(Request $request, Indicator $indicator)
    {
        $user = auth()->user();

        $indicatorAgency = IndicatorAgency::where('indicator_id', $indicator->id)
            ->where('agency_id', $user->agency_id)
            ->firstOrFail();

        // 🔴 LOAD KỲ BÁO CÁO HIỆU LỰC CỦA CHỈ TIÊU
        $periodTypes = $indicator->getEffectivePeriodTypes();

        $reportPeriods = ReportPeriod::whereIn('period_type', $periodTypes)
            ->orderBy('report_year')
            ->orderBy('start_date')
            ->get();

        $years = $reportPeriods->pluck('report_year')->unique()->values();

        $reports = IndicatorReport::where('indicator_agency_id', $indicatorAgency->id)
            ->get()
            ->keyBy('report_period_id');

        return view('indicator.report', [
            'indicator'       => $indicator,
            'indicatorAgency' => $indicatorAgency,
            'reportPeriods'   => $reportPeriods,
            'years'           => $years,
            'reports'         => $reports,
        ]);
    }

    /* ========================================================= */

    public function store(Request $request, Indicator $indicator)
    {
        $user = auth()->user();
        abort_if(! $user->hasRole('reporter'), 403);

        $indicatorAgency = IndicatorAgency::where('indicator_id', $indicator->id)
            ->where('agency_id', $user->agency_id)
            ->firstOrFail();

        $rules = [
            'report_period_id' => ['required', 'exists:report_periods,id'],
            'note'             => ['nullable', 'string'],
        ];

        if ($indicator->indicator_type->value() === 'quantitative') {
            $rules['quantitive_result'] = ['required', 'numeric'];
        } else {
            $rules['qualitive_result'] = ['required', 'boolean'];
        }

        $data = $request->validate($rules);

        IndicatorReport::updateOrCreate(
            [
                'indicator_agency_id' => $indicatorAgency->id,
                'report_period_id'    => $data['report_period_id'],
            ],
            [
                'quantitive_result' => $data['quantitive_result'] ?? null,
                'qualitive_result'  => $data['qualitive_result'] ?? null,
                'note'              => $data['note'] ?? null,
            ]
        );

        $reportPeriod = ReportPeriod::findOrFail($data['report_period_id']);

        return redirect()
            ->route('indicators.report.dashboard', [
                'resolution_id'    => $indicator->group->resolution_id,
                'report_period_id' => $reportPeriod->id,
                'report_year'      => $reportPeriod->report_year,
                'period_type'      => $reportPeriod->period_type->value(),
            ])
            ->with('success', 'Báo cáo đã được lưu');

    }

    /* ========================================================= */

    public function details(Request $request, Indicator $indicator)
    {
        $user = auth()->user();
        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);

        $resolutionId   = $request->resolution_id;
        $reportPeriodId = $request->report_period_id;

        $resolution = $indicator->group->resolution;
        $group      = $indicator->group;

        $reportPeriod = $reportPeriodId
            ? ReportPeriod::findOrFail($reportPeriodId)
            : null;

        // 🔴 DÙNG KỲ HIỆU LỰC CỦA CHỈ TIÊU
        $periodTypes = $indicator->getEffectivePeriodTypes();

        $reportPeriods = ReportPeriod::whereIn('period_type', $periodTypes)
            ->orderBy('report_year')
            ->orderBy('start_date')
            ->get();

        $indicatorAgencies = IndicatorAgency::with([
                'agency',
                'reports' => fn ($q) =>
                    $reportPeriod
                        ? $q->where('report_period_id', $reportPeriod->id)
                        : $q
            ])
            ->where('indicator_id', $indicator->id)
            ->get();

        $reportsMap = $indicatorAgencies
            ->mapWithKeys(fn ($ia) => [
                $ia->id => $ia->reports->first()
            ]);

        return view('indicator.details', [
            'indicator'        => $indicator,
            'group'            => $group,
            'resolution'       => $resolution,
            'reportPeriods'    => $reportPeriods,
            'reportPeriod'     => $reportPeriod,
            'indicatorAgencies'=> $indicatorAgencies,
            'reportsMap'       => $reportsMap,
            'request'          => $request,
            'isAdminOrSupervisor' => $isAdminOrSupervisor,
        ]);
    }
}
