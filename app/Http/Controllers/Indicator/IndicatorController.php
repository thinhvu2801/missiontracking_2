<?php

namespace App\Http\Controllers\Indicator;

use App\Enums\IndicatorTypeEnum;
use App\Enums\PeriodTypeEnum;
use App\Enums\UnitTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Indicator\IndicatorRequest;
use App\Models\Indicator\Indicator;
use App\Models\Indicator\IndicatorGroup;
use App\Models\Resolution\Resolution;
use App\Models\Agency\AgencyGroup;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index(Resolution $resolution)
    {
        $user = auth()->user();

        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);

        $groups = IndicatorGroup::with([
            'indicators' => function ($q) use ($user, $isAdminOrSupervisor) {
                $q->whereNull('parent_indicator_id')->orderBy('id');

                if (! $isAdminOrSupervisor) {
                    $q->whereHas('agencies', function ($qa) use ($user) {
                        $qa->where('agencies.id', $user->agency_id);
                    });
                }
                
                $q->with([
                    'getChildren' => function ($qc) use ($user, $isAdminOrSupervisor) {
                        if (! $isAdminOrSupervisor) {
                            $qc->whereHas('agencies', function ($qa) use ($user) {
                                $qa->where('agencies.id', $user->agency_id);
                            });
                        }
                        $qc->with('getChildren');
                    },
                    'agencies.group'
                ]);
            }
        ])
        ->where('resolution_id', $resolution->id)
        ->orderBy('id')
        ->get();

        return view('indicator.index', [
            'resolution' => $resolution,
            'groups'     => $groups,
        ]);
    }

    public function create(Request $request)
    {
        $resolution = Resolution::with('reports')
            ->findOrFail($request->resolution_id);

        $selectedPeriods = $resolution->reports
            ->where('unit_type', 'indicator')
            ->pluck('period_type')
            ->unique()
            ->values()
            ->toArray();

        return view('indicator.create', [
            'resolution'      => $resolution,
            'groups'          => IndicatorGroup::where('resolution_id', $resolution->id)->get(),
            'types'           => IndicatorTypeEnum::options(),
            'agencyGroups'    => AgencyGroup::with('agencies')->orderBy('id')->get(),
            'periodTypes'     => PeriodTypeEnum::options(),
            'selectedPeriods' => $selectedPeriods,         
        ]);
    }

    public function store(IndicatorRequest $request)
    {
        $data = $request->validated();
        unset($data['resolution_id']);

        $indicator = Indicator::create($data);

        $indicator->agencies()->sync($request->agency_ids ?? []);

        $selectedPeriods = collect($request->input('period_types', []))
            ->sort()
            ->values();

        $resolutionPeriods = $indicator->group
            ->resolution
            ->reports
            ->where('unit_type', 'indicator')
            ->pluck('period_type')
            ->unique()
            ->sort()
            ->values();

        if ($selectedPeriods->diff($resolutionPeriods)->isNotEmpty()
            || $resolutionPeriods->diff($selectedPeriods)->isNotEmpty()
        ) {
            $rows = $selectedPeriods->map(fn ($p) => [
                'period_type' => $p,
            ]);

            $indicator->reportPeriods()->createMany($rows->toArray());
        }

        return redirect()
            ->route('indicators.index', $indicator->group->resolution_id)
            ->with('success', 'Thêm chỉ tiêu thành công');
    }

    public function edit(Indicator $indicator)
    {
        $resolution = $indicator->group->resolution;

        $periodTypes = PeriodTypeEnum::options();

        if ($indicator->reportPeriods()->exists()) {
            $selectedPeriods = $indicator->reportPeriods
                ->pluck('period_type')
                ->toArray();
        } else {
            $selectedPeriods = $resolution->reports
                ->where('unit_type', 'indicator')
                ->pluck('period_type')
                ->unique()
                ->toArray();
        }

        return view('indicator.edit', [
            'indicator'        => $indicator,
            'resolution'       => $resolution,
            'groups'           => IndicatorGroup::where('resolution_id', $resolution->id)->get(),
            'types'            => IndicatorTypeEnum::options(),
            'agencyGroups'     => AgencyGroup::with('agencies')->orderBy('id')->get(),
            'assignedAgencies' => $indicator->agencies()->pluck('agencies.id')->toArray(),
            'periodTypes'      => $periodTypes,
            'selectedPeriods'  => $selectedPeriods,
        ]);
    }


    public function update(IndicatorRequest $request, Indicator $indicator)
    {
        $data = $request->validated();
        unset($data['resolution_id']);

        $indicator->update($data);

        $indicator->agencies()->sync($request->agency_ids ?? []);

        $selectedPeriods = collect($request->input('period_types', []))
            ->sort()
            ->values();

        $resolutionPeriods = $indicator->group
            ->resolution
            ->reports
            ->where('unit_type', 'indicator')
            ->pluck('period_type')
            ->unique()
            ->sort()
            ->values();

        if ($selectedPeriods->diff($resolutionPeriods)->isEmpty()
            && $resolutionPeriods->diff($selectedPeriods)->isEmpty()
        ) {
            $indicator->reportPeriods()->delete();
        } else {
            $indicator->reportPeriods()->delete();

            $rows = $selectedPeriods->map(fn ($p) => [
                'period_type' => $p,
            ]);

            $indicator->reportPeriods()->createMany($rows->toArray());
        }

        return redirect()
            ->route('indicators.index', $indicator->group->resolution_id)
            ->with('success', 'Cập nhật chỉ tiêu thành công');
    }


    public function destroy(Indicator $indicator)
    {
        $resolutionId = $indicator->group->resolution_id;

        $indicator->delete();

        return redirect()
            ->route('indicators.index', $resolutionId)
            ->with('success', 'Xóa chỉ tiêu thành công');
    }

    public function getParentsByGroup(Request $request)
    {
        $groupId = $request->indicator_group_id;

        return Indicator::where('indicator_group_id', $groupId)
            ->whereNull('parent_indicator_id')
            ->orderBy('indicator_name')
            ->get(['id', 'indicator_name']);
    }

}
