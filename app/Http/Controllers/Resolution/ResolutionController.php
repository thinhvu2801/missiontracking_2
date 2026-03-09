<?php

namespace App\Http\Controllers\Resolution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resolution\ResolutionRequest;
use App\Models\Resolution\Resolution;
use App\Enums\UnitTypeEnum;
use App\Enums\PeriodTypeEnum;
use Illuminate\Support\Facades\DB;

class ResolutionController extends Controller
{
    public function index()
    {
        $resolutions = Resolution::orderByDesc('issued_date')->get();

        return view('resolution.index', compact('resolutions'));
    }

    public function create()
    {
        return view('resolution.create', [
            'unitTypes'   => UnitTypeEnum::options(),
            'periodTypes'=> PeriodTypeEnum::options(),
            'selected'   => [],
        ]);
    }

    public function store(ResolutionRequest $request)
    {
        DB::transaction(function () use ($request) {
            $resolution = Resolution::create($request->validated());

            $this->syncResolutionReports(
                $resolution,
                $request->input('report_periods', [])
            );
        });

        return redirect()
            ->route('resolutions.index')
            ->with('success', 'Thêm mới văn bản thành công');
    }

    public function edit(Resolution $resolution)
    {
        $selected = $resolution->reports
            ->groupBy('unit_type')
            ->map(fn ($items) => $items->pluck('period_type')->toArray())
            ->toArray();

        return view('resolution.edit', [
            'resolution' => $resolution,
            'unitTypes'  => UnitTypeEnum::options(),
            'periodTypes'=> PeriodTypeEnum::options(),
            'selected'   => $selected,
        ]);
    }

    public function update(ResolutionRequest $request, Resolution $resolution)
    {
        DB::transaction(function () use ($request, $resolution) {
            $resolution->update($request->validated());

            $resolution->reports()->delete();

            $this->syncResolutionReports(
                $resolution,
                $request->input('report_periods', [])
            );
        });

        return redirect()
            ->route('resolutions.index')
            ->with('success', 'Cập nhật văn bản thành công');
    }

    public function destroy(Resolution $resolution)
    {
        $resolution->delete();

        return redirect()
            ->route('resolutions.index')
            ->with('success', 'Đã xóa văn bản');
    }

    protected function syncResolutionReports(
        Resolution $resolution,
        array $reportPeriods
    ): void {
        foreach ($reportPeriods as $unitType => $periodTypes) {
            foreach ($periodTypes as $periodType) {
                $resolution->reports()->create([
                    'unit_type'   => $unitType,
                    'period_type' => $periodType,
                ]);
            }
        }
    }
}
