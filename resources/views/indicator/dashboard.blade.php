@extends('layouts.app')

@section('title', 'Dashboard báo cáo chỉ tiêu')

@section('content')

<h2 class="text-center">DASHBOARD BÁO CÁO CHỈ TIÊU</h2>

<form method="GET">

@php
    $periodOrder = ['week','month','quarter','half_year','year'];
    $grouped = $reportPeriods->groupBy(fn($p) => $p->period_type->value());
@endphp

<div class="row">

    {{-- ===== VĂN BẢN ===== --}}
    <div class="col-md-3 form-group">
        <label>Văn bản</label>
        <select name="resolution_id"
                class="form-control"
                onchange="this.form.submit()">
            <option value="">-- Chọn văn bản --</option>
            @foreach ($resolutions as $r)
                <option value="{{ $r->id }}"
                    @selected(request('resolution_id') == $r->id)>
                    {{ $r->resolution_code }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- ===== NĂM ===== --}}
    <div class="col-md-3 form-group">
        <label>Năm báo cáo</label>
        <select id="report_year"
                name="report_year"
                class="form-control"
                {{ request('resolution_id') ? '' : 'disabled' }}>
            <option value="">-- Chọn năm --</option>
            @foreach ($reportPeriods->pluck('report_year')->unique() as $y)
                <option value="{{ $y }}"
                    @selected(request('report_year') == $y)>
                    {{ $y }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- ===== KỲ ===== --}}
    <div class="col-md-3 form-group">
        <label>Kỳ báo cáo</label>
        <select id="period_type"
                name="period_type"
                class="form-control"
                {{ request('resolution_id') ? '' : 'disabled' }}>
            <option value="">-- Chọn kỳ --</option>
            @foreach ($periodOrder as $t)
                @if ($grouped->has($t))
                    <option value="{{ $t }}"
                        @selected(request('period_type') === $t)>
                        {{ \App\Enums\PeriodTypeEnum::labelFor($t) }}
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    {{-- ===== THỜI GIAN ===== --}}
    <div class="col-md-3 form-group" id="period_time_wrapper">
        <label>Thời gian báo cáo</label>
        <select name="report_period_id"
                id="report_period_id"
                class="form-control"
                {{ request('resolution_id') ? '' : 'disabled' }}>
            <option value="">-- Chọn thời gian --</option>
        </select>
    </div>

</div>

<div class="row">
    @if ($isAdminOrSupervisor)
        <div class="col-md-6 form-group">
            <label>Cơ quan</label>
            <select name="agency_id" class="form-control select2">
                <option value="">-- Tất cả cơ quan --</option>
                @foreach ($agencyGroups as $gid => $agencies)
                    <optgroup label="{{ optional($agencies->first()->group)->group_name }}">
                        @foreach ($agencies as $a)
                            <option value="{{ $a->id }}"
                                @selected($selectedAgencyId == $a->id)>
                                {{ $a->agency_name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    @else
        <div class="col-md-6"></div>
    @endif

    <div class="col-md-6 text-right">
        <button class="btn btn-primary" {{ request('resolution_id') ? '' : 'disabled' }}>
            <i class="fa fa-search"></i> Thống kê
        </button>
    </div>
</div>

</form>

@if (! request('report_period_id'))
<div class="alert alert-info">
    Vui lòng chọn <b>Văn bản</b> và <b>Thời gian báo cáo</b>.
</div>
@endif

{{-- ================== KẾT QUẢ ================== --}}
@foreach ($groups as $group)
<div class="ibox">
    <div class="ibox-title">
        <h3>{{ roman($loop->iteration) }}. {{ $group->group_name }}</h3>
    </div>
    <div class="ibox-content">
        <table class="table table-bordered table-striped indicator-table">
            <thead>
            <tr>
                <th width="60">STT</th>
                <th>Tên chỉ tiêu</th>
                <th width="220">Cơ quan</th>
                <th width="120" class="text-center">Báo cáo</th>
                <th width="140" class="text-center">Hoàn thành</th>
                <th width="140" class="text-center">Hành động</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($group->indicators as $i => $indicator)
                @include('indicator.dashboard_row', [
                    'indicator' => $indicator,
                    'level'     => 1,
                    'index'     => $i + 1,
                    'reportsMap'=> $reportsMap
                ])
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

@endsection
@php
    $periodsJson = $reportPeriods->map(fn($p) => [
        'id'     => $p->id,
        'type'   => $p->period_type->value(),
        'number' => $p->period_number,
        'year'   => $p->report_year,
        'start'  => $p->start_date,
        'end'    => $p->end_date,
    ])->values();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {

    const periods = @json($periodsJson);

    const yearSel = document.getElementById('report_year');
    const typeSel = document.getElementById('period_type');
    const pSel    = document.getElementById('report_period_id');
    const wrap    = document.getElementById('period_time_wrapper');

    const selectedPeriodId = '{{ request('report_period_id') }}';

    function reloadPeriods() {
        pSel.innerHTML = '<option value="">-- Chọn thời gian --</option>';

        const y = yearSel.value;
        const t = typeSel.value;
        if (!y || !t) return;

        const list = periods.filter(p => p.year == y && p.type === t);

        if (t === 'year') {
            wrap.style.display = 'none';
            if (list[0]) {
                pSel.innerHTML = `<option value="${list[0].id}" selected></option>`;
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

            const opt = new Option(
                `${label} (${p.start} → ${p.end})`,
                p.id
            );

            if (String(p.id) === selectedPeriodId) {
                opt.selected = true;
            }

            pSel.add(opt);
        });
    }

    yearSel?.addEventListener('change', reloadPeriods);
    typeSel?.addEventListener('change', reloadPeriods);

    if (yearSel.value && typeSel.value) {
        reloadPeriods();
    }
});
</script>

@push('styles')
<link href="{{ asset('backend/css/plugins/dataTables/jquery.dataTables.min.css') }}" rel="stylesheet">
@endpush
@push('scripts')
<script src="{{ asset('backend/js/plugins/dataTables/jquery.dataTables.min.js') }}"></script>
<script>
    $(function () {
        $('.indicator-table').DataTable({
            paging: true,
            searching: true,
            ordering: false,
            pageLength: 20,
            lengthMenu: [20, 50, 100],
            language: {
                url: "{{ asset('backend/dataTables/vi.json') }}",
            }
        });

        $('.select2').select2({ width: '100%' });
    });
</script>
@endpush
