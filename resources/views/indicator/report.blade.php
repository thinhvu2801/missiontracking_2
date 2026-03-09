@extends('layouts.app')

@section('title', 'Báo cáo chỉ tiêu')

@section('content')

@php
    $prefillYear   = request('report_year');
    $prefillType   = request('period_type');
    $prefillPeriod = request('report_period_id');

    $hasPrefill = $prefillYear && $prefillType && $prefillPeriod;
@endphp


<h3 class="mb15">BÁO CÁO CHỈ TIÊU</h3>

<form method="POST" action="{{ route('indicators.report.store', $indicator->id) }}">
    @csrf

    {{-- ================== THÔNG TIN CHUNG ================== --}}
    <div class="row">
        <div class="col-md-6 form-group">
            <label>Văn bản</label>
            <input class="form-control"
                   value="{{ $indicator->group->resolution->resolution_code }}"
                   readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Nhóm chỉ tiêu</label>
            <input class="form-control"
                   value="{{ $indicator->group->group_name }}"
                   readonly>
        </div>
    </div>

    <div class="form-group">
        <label>Tên chỉ tiêu</label>
        <input class="form-control"
               value="{{ $indicator->indicator_name }}"
               readonly>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label>Kết quả yêu cầu</label>
            <input class="form-control"
                   value="{{ $indicator->getExpectedResult() }}"
                   readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Đơn vị</label>
            <input class="form-control"
                   value="{{ $indicator->unit_of_measure }}"
                   readonly>
        </div>
    </div>

    {{-- ================== KỲ BÁO CÁO ================== --}}
    @php
        $periodOrder = ['week','month','quarter','half_year','year'];
        $grouped = $reportPeriods->groupBy(fn($p) => $p->period_type->value());
    @endphp

    <div class="row">
        <div class="col-md-4 form-group">
            <label>Năm báo cáo</label>
            <select id="report_year" class="form-control" required>
                <option value="">-- Chọn năm --</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 form-group">
            <label>Kỳ báo cáo</label>
            <select id="period_type" class="form-control" required>
                <option value="">-- Chọn kỳ --</option>
                @foreach ($periodOrder as $t)
                    @if ($grouped->has($t))
                        <option value="{{ $t }}">
                            {{ \App\Enums\PeriodTypeEnum::labelFor($t) }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="col-md-4 form-group" id="period_time_wrapper">
            <label>Thời gian báo cáo</label>
            <select name="report_period_id"
                    id="report_period_id"
                    class="form-control"
                    required>
                <option value="">-- Chọn thời gian --</option>
            </select>
        </div>
    </div>

    {{-- ================== KẾT QUẢ ================== --}}
    <div class="form-group">
        <label>Kết quả thực hiện</label>

        @if ($indicator->indicator_type->value() === 'quantitative')
            <input type="number" step="0.01"
                   name="quantitive_result"
                   class="form-control">
        @else
            <select name="qualitive_result" class="form-control">
                <option value="">-- Chọn --</option>
                <option value="1">Đã hoàn thành</option>
                <option value="0">Chưa hoàn thành</option>
            </select>
        @endif
    </div>

    <div class="form-group">
        <label>Ghi chú</label>
        <textarea name="note" class="form-control"></textarea>
    </div>
    <div class="form-group text-right">
        <a href="{{ route('indicators.report.dashboard', array_filter([
            'resolution_id'   => $indicator->group->resolution->id,
            'report_year'     => request('report_year'),
            'period_type'     => request('period_type'),
            'report_period_id'=> request('report_period_id'),
            'agency_id'       => request('agency_id'),
        ])) }}"
            class="btn btn-default">
            ⬅ Quay lại
        </a>
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>

{{-- ================== SCRIPT DATA ================== --}}
@php
    $periodsJson = $reportPeriods->map(fn($p) => [
        'id'     => $p->id,
        'type'   => $p->period_type->value(),
        'number' => $p->period_number,
        'year'   => $p->report_year,
        'start'  => $p->start_date,
        'end'    => $p->end_date,
    ])->values();

    $reportsJson = $reports->keyBy('report_period_id')->map(fn($r) => [
        'quantitive_result' => $r->quantitive_result,
        'qualitive_result'  => $r->qualitive_result,
        'note'              => $r->note,
    ]);
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {

    const periods = @json($periodsJson);
    const reports = @json($reportsJson);
    const indicatorType = "{{ $indicator->indicator_type->value() }}";

    const prefill = {
        year:   "{{ $prefillYear }}",
        type:   "{{ $prefillType }}",
        period: "{{ $prefillPeriod }}",
        active: {{ $hasPrefill ? 'true' : 'false' }}
    };

    const yearSel = document.getElementById('report_year');
    const typeSel = document.getElementById('period_type');
    const pSel    = document.getElementById('report_period_id');
    const wrap    = document.getElementById('period_time_wrapper');

    const qInput  = document.querySelector('[name="quantitive_result"]');
    const qlInput = document.querySelector('[name="qualitive_result"]');
    const noteInp = document.querySelector('[name="note"]');

    function clearResult() {
        if (indicatorType === 'quantitative') {
            if (qInput) qInput.value = '';
        } else {
            if (qlInput) qlInput.value = '';
        }
        noteInp.value = '';
    }

    function fillResult(pid) {
        const r = reports[pid];
        if (!r) return clearResult();

        if (indicatorType === 'quantitative') {
            if (qInput) qInput.value = r.quantitive_result ?? '';
        } else {
            if (qlInput) {
                qlInput.value =
                    r.qualitive_result == 1 ? '1'
                  : r.qualitive_result == 0 ? '0'
                  : '';
            }
        }
        noteInp.value = r.note ?? '';
    }

    function reloadPeriods(autoSelect = false) {
        pSel.innerHTML = '<option value="">-- Chọn thời gian --</option>';
        clearResult();

        const y = yearSel.value;
        const t = typeSel.value;
        if (!y || !t) return;

        const list = periods.filter(p => p.year == y && p.type === t);

        if (t === 'year') {
            wrap.style.display = 'none';
            if (list[0]) {
                pSel.innerHTML = `<option value="${list[0].id}" selected></option>`;
                fillResult(list[0].id);
            }
            return;
        }

        wrap.style.display = 'block';

        list.forEach(p => {
            let label =
                t === 'week'    ? `Tuần ${p.number}` :
                t === 'month'   ? `Tháng ${p.number}` :
                t === 'quarter' ? `Quý ${p.number}` :
                p.number == 1   ? '6 tháng đầu năm' : '6 tháng cuối năm';

            pSel.add(new Option(
                `${label} (${p.start} → ${p.end})`,
                p.id
            ));
        });

        if (autoSelect && prefill.active) {
            pSel.value = prefill.period;
            fillResult(prefill.period);
        }
    }

    // ================== MODE 1: KHÔNG PREFILL ==================
    if (!prefill.active) {
        yearSel.addEventListener('change', () => reloadPeriods(false));
        typeSel.addEventListener('change', () => reloadPeriods(false));
        pSel.addEventListener('change', () => fillResult(pSel.value));
        return;
    }

    // ================== MODE 2: PREFILL ==================
    yearSel.value = prefill.year;
    typeSel.value = prefill.type;

    reloadPeriods(true);

    yearSel.addEventListener('change', () => reloadPeriods(false));
    typeSel.addEventListener('change', () => reloadPeriods(false));
    pSel.addEventListener('change', () => fillResult(pSel.value));
});
</script>


@endsection
