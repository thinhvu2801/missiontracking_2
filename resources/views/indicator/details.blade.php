@extends('layouts.app')

@section('title', 'Chi tiết thực hiện chỉ tiêu')

@section('content')

<h2 class="text-center">CHI TIẾT THỰC HIỆN CHỈ TIÊU</h2>

{{-- ================== FORM LỌC (GIỐNG DASHBOARD) ================== --}}
<form method="GET">

    <input type="hidden" name="resolution_id" value="{{ request('resolution_id') }}">

    @php
        $periodOrder = ['week','month','quarter','half_year','year'];
        $grouped = $reportPeriods->groupBy(fn($p) => $p->period_type->value());
    @endphp

    <div class="row">

        {{-- ===== VĂN BẢN ===== --}}
        <div class="col-md-3 form-group">
            <label>Văn bản</label>
            <select class="form-control" disabled>
                <option>{{ $resolution->resolution_code }}</option>
            </select>
        </div>

        {{-- ===== NĂM ===== --}}
        <div class="col-md-3 form-group">
            <label>Năm báo cáo</label>
            <select id="report_year" name="report_year" class="form-control">
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
            <select id="period_type" name="period_type" class="form-control">
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
            <select name="report_period_id" id="report_period_id" class="form-control">
                <option value="">-- Chọn thời gian --</option>
            </select>
        </div>
    </div>

    <div class="text-right">
        <button class="btn btn-primary">
            <i class="fa fa-search"></i> Lọc
        </button>
    </div>
</form>

<hr>

{{-- ================== THÔNG TIN CHỈ TIÊU ================== --}}
<div class="ibox">
    <div class="ibox-title">
        <h3>Thông tin chỉ tiêu</h3>
    </div>
    <div class="ibox-content">
        <p><strong>Nhóm chỉ tiêu:</strong> {{ $group->group_name }}</p>
        <p><strong>Mã chỉ tiêu:</strong> {{ $indicator->indicator_code }}</p>
        <p><strong>Tên chỉ tiêu:</strong> {{ $indicator->indicator_name }}</p>
        <p><strong>Đơn vị:</strong> {{ $indicator->unit_of_measure }}</p>
        <p><strong>Kết quả yêu cầu:</strong> {{ $indicator->getExpectedResult() }}</p>
    </div>
</div>

{{-- ================== BẢNG THỰC HIỆN ================== --}}
<div class="ibox">
    <div class="ibox-title">
        <h3>Tình hình thực hiện theo cơ quan</h3>
    </div>

    <div class="ibox-content">
        <table class="table table-bordered table-striped indicator-table">
            <thead>
            <tr>
                <th width="60">STT</th>
                <th>Cơ quan</th>
                <th width="200">Kết quả báo cáo</th>
                <th width="150" class="text-center">Trạng thái</th>
                <th>Ghi chú</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($indicatorAgencies as $ia)
                @php
                    $report = $reportsMap->get($ia->id);
                    $isReported  = false;
                    $isCompleted = false;

                    if ($report) {
                        $isReported = true;

                        // ===== CHỈ TIÊU ĐỊNH LƯỢNG =====
                        if ($indicator->indicator_type->value() === 'quantitative') {
                            $value = $report->quantitive_result;

                            if (!is_null($value)) {
                                $isCompleted = true;

                                if (!is_null($indicator->target_min)) {
                                    $isCompleted = $isCompleted && (
                                        $indicator->is_target_min_equal
                                            ? $value >= $indicator->target_min
                                            : $value >  $indicator->target_min
                                    );
                                }

                                if (!is_null($indicator->target_max)) {
                                    $isCompleted = $isCompleted && (
                                        $indicator->is_target_max_equal
                                            ? $value <= $indicator->target_max
                                            : $value <  $indicator->target_max
                                    );
                                }
                            }
                        }
                        // ===== CHỈ TIÊU ĐỊNH TÍNH =====
                        else {
                            $isCompleted = ($report->qualitive_result === true);
                        }
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $ia->agency->agency_name }}</td>
                    <td>
                        @if (! $isReported)
                            <span class="label label-danger">Chưa báo cáo</span>
                        @else
                            @if ($indicator->indicator_type->value() === 'quantitative')
                                {{ $report->quantitive_result }}
                            @else
                                {{ $report->qualitive_result ? 'Đã hoàn thành' : "Chưa hoàn thành" }}                          
                            @endif
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($isCompleted)
                            <span class="label label-success">Hoàn thành</span>
                        @else
                            <span class="label label-danger">Chưa hoàn thành</span>
                        @endif
                    </td>
                    <td>{{ $report->note ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{-- ===== QUAY LẠI DASHBOARD ===== --}}
        <div class="form-group text-right">
            <a href="{{ route('indicators.report.dashboard', array_filter([
                'resolution_id'   => request('resolution_id'),
                'report_year'     => request('report_year'),
                'period_type'     => request('period_type'),
                'report_period_id'=> request('report_period_id'),
                'agency_id'       => request('agency_id'),
            ])) }}"
               class="btn btn-default">
                ⬅ Quay lại
            </a>
        </div>
    </div>
</div>

@endsection

{{-- ================== DATA KỲ ================== --}}
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

    const selectedPeriodId = '{{ request('report_period_id') }}';

    function reloadPeriods() {
        pSel.innerHTML = '<option value="">-- Chọn thời gian --</option>';
        if (!yearSel.value || !typeSel.value) return;

        const list = periods.filter(p =>
            p.year == yearSel.value && p.type === typeSel.value
        );

        list.forEach(p => {
            let label =
                typeSel.value === 'week'    ? `Tuần ${p.number}` :
                typeSel.value === 'month'   ? `Tháng ${p.number}` :
                typeSel.value === 'quarter' ? `Quý ${p.number}` :
                p.number == 1 ? '6 tháng đầu năm' : '6 tháng cuối năm';

            const opt = new Option(`${label} (${p.start} → ${p.end})`, p.id);
            if (String(p.id) === selectedPeriodId) opt.selected = true;
            pSel.add(opt);
        });
    }

    yearSel?.addEventListener('change', reloadPeriods);
    typeSel?.addEventListener('change', reloadPeriods);

    if (yearSel.value && typeSel.value) reloadPeriods();
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
                url: "{{ asset('backend/dataTables/vi.json') }}"
            }
        });
    });
</script>
@endpush

