<?php

namespace App\Http\Controllers\Indicator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Indicator\IndicatorGroupRequest;
use App\Models\Indicator\IndicatorGroup;
use App\Models\Resolution\Resolution;
use Illuminate\Http\Request;

class IndicatorGroupController extends Controller
{
    public function index()
    {
        $groups = IndicatorGroup::with('resolution')->get();

        return view('indicator_group.index', compact('groups'));
    }

    public function create(Request $request)
    {
        $resolution = Resolution::findOrFail($request->resolution_id);
        return view('indicator_group.create', compact('resolution'));
    }

    public function store(IndicatorGroupRequest $request)
    {
        IndicatorGroup::create($request->validated());
        
        return redirect()
            ->route('indicators.index', $request->resolution_id)
            ->with('success', 'Thêm nhóm chỉ tiêu thành công');
    }

    public function edit(IndicatorGroup $indicatorGroup)
    {
        $resolution = $indicatorGroup->resolution;

        return view('indicator_group.edit', compact('indicatorGroup', 'resolution'));
    }

    public function update(
        IndicatorGroupRequest $request,
        IndicatorGroup $indicatorGroup
    ) {
        $indicatorGroup->update($request->validated());

        return redirect()
            ->route('indicators.index', $indicatorGroup->resolution_id)
            ->with('success', 'Cập nhật nhóm chỉ tiêu thành công');
    }

    public function destroy(IndicatorGroup $indicatorGroup)
    {
        $resolutionId = $indicatorGroup->resolution_id;

        $indicatorGroup->delete();

        return redirect()
            ->route('indicators.index', $resolutionId)
            ->with('success', 'Xóa nhóm chỉ tiêu thành công');
    }
}
