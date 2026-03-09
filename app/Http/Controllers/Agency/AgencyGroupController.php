<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\AgencyGroupRequest;
use App\Models\Agency\AgencyGroup;

class AgencyGroupController extends Controller
{
    public function index()
    {
        return redirect()->route('agencies.manage');
    }

    public function create()
    {
        return view('agency_group.create');
    }

    public function store(AgencyGroupRequest $request)
    {
        AgencyGroup::create($request->validated());

        if ((int)$request->input('modal', 0) === 1) {
            return view('agency.modal_done', ['message' => 'Tạo nhóm cơ quan thành công']);
        }

        return redirect()->route('agencies.manage')->with('success', 'Tạo nhóm cơ quan thành công');
    }

    public function edit(AgencyGroup $agencyGroup)
    {
        return view('agency_group.edit', compact('agencyGroup'));
    }

    public function update(AgencyGroupRequest $request, AgencyGroup $agencyGroup)
    {
        $agencyGroup->update($request->validated());

        if ((int)$request->input('modal', 0) === 1) {
            return view('agency.modal_done', ['message' => 'Cập nhật thành công']);
        }

        return redirect()->route('agencies.manage')->with('success', 'Cập nhật thành công');
    }

    public function destroy(AgencyGroup $agencyGroup)
    {
        $agencyGroup->delete();

        return redirect()->route('agencies.manage')
            ->with('success', 'Đã xóa');
    }
}
