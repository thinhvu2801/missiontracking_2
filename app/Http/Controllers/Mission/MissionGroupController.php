<?php

namespace App\Http\Controllers\Mission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\MissionGroupRequest;
use App\Models\Mission\MissionGroup;
use App\Models\Resolution\Resolution;
use Illuminate\Http\Request;

class MissionGroupController extends Controller
{
    public function index()
    {
        $groups = MissionGroup::with('resolution')->get();

        return view('mission_group.index', compact('groups'));
    }

    public function create(Request $request)
    {
        $resolution = Resolution::findOrFail($request->resolution_id);

        return view('mission_group.create', compact('resolution'));
    }

    public function store(MissionGroupRequest $request)
    {
        MissionGroup::create($request->validated());

        return redirect()
            ->route('missions.index', $request->resolution_id)
            ->with('success', 'Thêm nhóm nhiệm vụ thành công');
    }

    public function edit(MissionGroup $missionGroup)
    {
        $resolution = $missionGroup->resolution;

        return view(
            'mission_group.edit',
            compact('missionGroup', 'resolution')
        );
    }

    public function update(
        MissionGroupRequest $request,
        MissionGroup $missionGroup
    ) {
        $missionGroup->update($request->validated());

        return redirect()
            ->route('missions.index', $missionGroup->resolution_id)
            ->with('success', 'Cập nhật nhóm nhiệm vụ thành công');
    }

    public function destroy(MissionGroup $missionGroup)
    {
        $resolutionId = $missionGroup->resolution_id;

        $missionGroup->delete();

        return redirect()
            ->route('missions.index', $resolutionId)
            ->with('success', 'Xóa nhóm nhiệm vụ thành công');
    }
}
