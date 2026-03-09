@extends('layouts.app')

@section('title', 'Báo cáo nhiệm vụ')

@section('content')

@php
    $prefillYear   = request('report_year');
    $prefillType   = request('period_type');
    $prefillPeriod = request('report_period_id');
    $hasPrefill = $prefillYear && $prefillType && $prefillPeriod;
    $user = auth()->user();
@endphp

<h3 class="mb15">BÁO CÁO NHIỆM VỤ</h3>
@include('partials.error')
<form method="POST" action="{{ route('missions.report.store', $mission->id) }}">
@csrf

{{-- ================== THÔNG TIN CHUNG ================== --}}
<div class="row">
    <div class="col-md-6 form-group">
        <label>Văn bản</label>
        <input class="form-control"
               value="{{ $mission->group->resolution->resolution_code }}"
               readonly>
    </div>

    <div class="col-md-6 form-group">
        <label>Nhóm nhiệm vụ</label>
        <input class="form-control"
               value="{{ $mission->group->group_name }}"
               readonly>
    </div>
</div>

<div class="form-group">
    <label>Tên nhiệm vụ</label>
    <textarea class="form-control"
              rows="3"
              readonly>{{ $mission->mission_name }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label>Loại nhiệm vụ</label>
        <input class="form-control"
               value="{{ \App\Enums\MissionTypeEnum::labelFor($mission->mission_type) }}"
               readonly>
    </div>
    <div class="col-md-6 form-group">
        <label>Hạn hoàn thành</label>
        <input type="text"
            class="form-control"
            readonly
            value="{{ 
                $mission->mission_type == 'regular'
                    ? 'Thường xuyên'
                    : ($mission->mission_type == 'time_limited' && $mission->deadline_date
                        ? \Carbon\Carbon::parse($mission->deadline_date)->format('d/m/Y')
                        : '')
            }}">
    </div>
</div>

<div class="form-group">
    <label>Kết quả yêu cầu</label>
    <input class="form-control"
            value="{{ $mission->expected_result }}"
            readonly>
</div>

{{-- ================== KỲ BÁO CÁO ================== --}}
@php
    $periodOrder = ['week','month','quarter','half_year','year'];
    $grouped = $reportPeriods->groupBy(fn($p) => $p->period_type->value());
@endphp

<div class="row">
    <div class="col-md-4 form-group">
        <label>Năm báo cáo<span class="text-danger">*</span></label>
        <select id="report_year" class="form-control" required>
            <option value="">-- Chọn năm --</option>
            @foreach ($years as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 form-group">
        <label>Kỳ báo cáo<span class="text-danger">*</span></label>
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
        <label>Thời gian báo cáo<span class="text-danger">*</span></label>
        <select name="report_period_id"
                id="report_period_id"
                class="form-control"
                required>
            <option value="">-- Chọn thời gian --</option>
        </select>
    </div>
</div>

{{-- ================== KẾT QUẢ THỰC HIỆN ================== --}}
<div class="row">
    <div class="col-md-4 form-group">
        <label>Trạng thái thực hiện<span class="text-danger">*</span></label>
        <select name="status" id="status" class="form-control" required>
            <option value="1">Đã hoàn thành</option>
            <option value="0">Chưa hoàn thành</option>
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>Tiến độ lũy kế (%)<span class="text-danger">*</span></label>
        <input type="number"
            name="progress_percent"
            class="form-control"
            min="0"
            max="100"
            step="0.01"
            required>
    </div>
    <div class="col-md-4 form-group">
        <label>Phòng ban / Đơn vị</label>
        <input class="form-control"
                value="{{ $mission->childrenAgencies->first()?->agency_name }}"
                readonly>
    </div>
</div>

<div class="form-group">
    <label>Kết quả thực hiện<span class="text-danger">*</span></label>
    <textarea name="execution_result"
              id="execution_result"
              rows="4"
              class="form-control"
              placeholder="Mô tả kết quả thực hiện nhiệm vụ..."
              required>
            </textarea>
</div>

<div class="form-group">
    <label>Khó khăn, vướng mắc (nếu có)</label>

    <select name="delay_reasons[]"
            id="delay_reasons"
            class="form-control select2"
            multiple>
        @foreach($delayReasons as $reason)
            <option value="{{ $reason->id }}"
                {{ $reason->reason_code === 'others' ? 'data-others=1' : '' }}>
                {{ $reason->reason_name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group"
     id="delay_reason_other_wrapper"
     style="display:none">
    <label>Mô tả nguyên nhân khác</label>
    <textarea name="delay_reason_other_description"
              class="form-control"
              rows="3"
              placeholder="Nhập mô tả chi tiết"></textarea>
</div>

<div class="form-group">
    <label>Đề xuất, kiến nghị (nếu có)</label>
    <textarea name="recommendation"
              class="form-control"
              rows="3"
              placeholder="Nhập đề xuất, kiến nghị (nếu có)"></textarea>
</div>

<div class="text-right">
    <a href="{{ route('missions.report.dashboard', array_filter([
        'resolution_id'    => $mission->group->resolution->id,
        'report_year'      => request('report_year'),
        'period_type'      => request('period_type'),
        'report_period_id' => request('report_period_id'),
        'agency_id'        => request('agency_id'),
    ])) }}" class="btn btn-default">
        ⬅ Quay lại
    </a>
    <button
        id="btn-save"
        class="btn btn-primary"
        {{ ($missionAgency->canEdit() && $user->hasRole('reporter')) ? '' : 'disabled' }}>Lưu</button>
</div>

</form>

<hr>

{{-- ================== SCRIPT DATA ================== --}}
@php
    $periodsJson = $reportPeriods->map(fn($p) => [
        'id'     => $p->id,
        'type'   => $p->period_type->value(),
        'number' => $p->period_number,
        'year'   => $p->report_year,
        'start'  => $p->start_date,
        'end'    => $p->end_date,
        'canReport' => $p->canReport()
    ])->values();

    $reportsJson = $reports->keyBy('report_period_id')->map(fn($r) => [
        'status' => $r->status,
        'execution_result' => $r->execution_result,
        'progress_percent' => $r->progress_percent,
        'recommendation'   => $r->recommendation,
        'delay_reasons' => $r->delayReasons->map(fn($d) => [
            'id' => $d->id,
            'description' => $d->pivot->description,
        ]),
    ]);

@endphp

<script>
/* ================== DELAY OTHER ================== */
const isReporter = {{ auth()->user()?->hasRole('reporter') ? 'true' : 'false' }};
function toggleOther() {
    const values  = $('#delay_reasons').val() || [];
    const otherId = $('#delay_reasons option[data-others="1"]').val();

    const show = values.includes(otherId);
    $('#delay_reason_other_wrapper').toggle(show);

    if (!show) {
        $('textarea[name="delay_reason_other_description"]').val('');
    }
}

document.addEventListener('DOMContentLoaded', function () {

    /* ================== DATA ================== */
    const periods = @json($periodsJson);
    const reports = @json($reportsJson);

    const prefill = {
        year:   "{{ $prefillYear }}",
        type:   "{{ $prefillType }}",
        period: "{{ $prefillPeriod }}",
        active: {{ $hasPrefill ? 'true' : 'false' }}
    };

    /* ================== ELEMENTS ================== */
    const yearSel   = document.getElementById('report_year');
    const typeSel   = document.getElementById('period_type');
    const pSel      = document.getElementById('report_period_id');
    const wrap      = document.getElementById('period_time_wrapper');

    const statusSel = document.getElementById('status');
    const resultInp = document.getElementById('execution_result');
    const progressInp = $('input[name="progress_percent"]');

    /* ================== STATE ================== */
    let lastProgress = null;
    let lastResult   = null;

    /* ================== HELPERS ================== */
    function clearResult() {
        statusSel.value = '0';
        resultInp.value = '';
        progressInp.val('');
        $('textarea[name="recommendation"]').val('');

        $('#delay_reasons').val([]).trigger('change.select2');
        toggleOther();

        lastProgress = null;
        lastResult   = null;
    }

    function getPreviousReport(currentId) {
        const ids = Object.keys(reports)
            .map(id => parseInt(id))
            .filter(id => id < currentId)
            .sort((a, b) => b - a);

        if (!ids.length) return null;
        return reports[ids[0]];
    }

    function updateCanEdit(pid) {
        const btn = document.getElementById('btn-save');

        if (!btn) return;

        if (!isReporter) {
            btn.disabled = true;
            return;
        }

        if (!pid) {
            btn.disabled = true;
            return;
        }

        const period = periods.find(p => p.id == pid);
        if (!period) {
            btn.disabled = true;
            return;
        }

        btn.disabled = !period.canReport;
    }

    /* ================== FILL RESULT ================== */
    function fillResult(pid) {
        pid = parseInt(pid);
        updateCanEdit(pid);
        if (!pid) return clearResult();

        const current = reports[pid];
        const prev    = getPreviousReport(pid);

        // ===== ĐÃ CÓ BÁO CÁO KỲ NÀY =====
        if (current) {
            lastProgress = current.progress_percent;
            lastResult   = current.execution_result;

            statusSel.value = current.status == 1 ? '1' : '0';
            resultInp.value = current.execution_result ?? '';
            progressInp.val(current.progress_percent ?? '');

            $('textarea[name="recommendation"]').val(current.recommendation ?? '');

            if (current.delay_reasons) {
                const ids = current.delay_reasons.map(d => String(d.id));
                $('#delay_reasons').val(ids).trigger('change');

                const other = current.delay_reasons.find(d =>
                    $('#delay_reasons option[value="' + d.id + '"]').data('others') == 1
                );

                $('textarea[name="delay_reason_other_description"]')
                    .val(other ? other.description : '');
            }

            toggleOther();
            return;
        }

        // ===== CHƯA CÓ → LẤY KỲ TRƯỚC =====
        if (prev) {
            lastProgress = prev.progress_percent;
            lastResult   = prev.execution_result;

            progressInp.val(prev.progress_percent);
            resultInp.value = prev.execution_result ?? '';
            statusSel.value = prev.progress_percent >= 100 ? '1' : '0';

            return;
        }

        // ===== CHƯA CÓ GÌ =====
        clearResult();
    }

    /* ================== PERIOD ================== */
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
            updateCanEdit(prefill.period);
        }
    }

    /* ================== EVENTS ================== */
    progressInp.on('input', function () {
        const val = parseFloat(this.value);

        if (val >= 100) {
            statusSel.value = '1';
        } else {
            statusSel.value = '0';
        }
    });

    statusSel.addEventListener('change', function () {
        if (this.value == '1') {
            progressInp.val(100);
        } else if (lastProgress !== null && lastProgress < 100) {
            progressInp.val(lastProgress);
        }
    });

    /* ================== INIT ================== */
    if (prefill.active) {
        yearSel.value = prefill.year;
        typeSel.value = prefill.type;
        reloadPeriods(true);
    }

    yearSel.addEventListener('change', () => reloadPeriods(false));
    typeSel.addEventListener('change', () => reloadPeriods(false));
    pSel.addEventListener('change', () => fillResult(pSel.value));
});
</script>

@push('scripts')
<script>
$(function () {
    $('#delay_reasons').select2({
        width: '100%',
        placeholder: 'Chọn nguyên nhân trễ hạn'
    });

    $('#delay_reasons').on('change', toggleOther);
    toggleOther();
});
</script>
@endpush
@endsection
