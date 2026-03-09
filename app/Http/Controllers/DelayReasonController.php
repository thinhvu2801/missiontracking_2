<?php

namespace App\Http\Controllers;

use App\Http\Requests\DelayReasonRequest;
use App\Models\DelayReason;
use Illuminate\Http\Request;

class DelayReasonController extends Controller
{
    public function index()
    {
        $reasons = DelayReason::orderBy('reason_name')->paginate(15);

        return view('delay_reason.index', compact('reasons'));
    }

    public function create()
    {
        return view('delay_reason.create');
    }

    public function store(DelayReasonRequest $request)
    {
        DelayReason::create($request->validated());

        return redirect()
            ->route('delay-reasons.index')
            ->with('success', 'Thêm nguyên nhân trễ hạn thành công');
    }

    public function edit(DelayReason $delayReason)
    {
        if ($delayReason->reason_code === 'others') {
            return redirect()->route('delay-reasons.index');
        }

        return view('delay_reason.edit', compact('delayReason'));
    }

    public function update(
        DelayReasonRequest $request,
        DelayReason $delayReason
    ) {
        $delayReason->update($request->validated());

        return redirect()
            ->route('delay-reasons.index')
            ->with('success', 'Cập nhật nguyên nhân trễ hạn thành công');
    }

    public function destroy(DelayReason $delayReason)
    {
        if ($delayReason->reason_code == 'others') {
            return back()->withErrors(
                'Nguyên nhân đang được sử dụng, không thể xoá!'
            );
        }

        $delayReason->delete();

        return redirect()
            ->route('delay-reasons.index')
            ->with('success', 'Xoá nguyên nhân trễ hạn thành công');
    }
}
