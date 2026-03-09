@extends('layouts.app')

@section('title', 'Chi tiết thực hiện nhiệm vụ')

@section('content')

<h2 class="text-center">CHI TIẾT THỰC HIỆN NHIỆM VỤ</h2>

{{-- ================== FORM LỌC (GIỐNG INDICATOR) ================== --}}
<form method="GET">

    <input type="hidden" name="resolution_id" value="{{ request('resolution_id') }}">
    <input type="hidden" name="mission_id" value="{{ $mission->id }}">

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
                    <option value="{{ $y }}" @selected(request('report_year') == $y)>
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
                        <option value="{{ $t }}" @selected(request('period_type') === $t)>
                            {{ \App\Enums\PeriodTypeEnum::labelFor($t) }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>

        {{-- ===== THỜI GIAN ===== --}}
        <div class="col-md-3 form-group">
            <label>Thời gian báo cáo</label>
            <select id="report_period_id" name="report_period_id" class="form-control">
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

{{-- ================== THÔNG TIN NHIỆM VỤ ================== --}}
<div class="ibox">
    <div class="ibox-title">
        <h3>Thông tin nhiệm vụ</h3>
    </div>

    <div class="ibox-content">
        <p>
            <strong>Nhóm nhiệm vụ:</strong>
            {{ $group->group_name }}
        </p>

        <p>
            <strong>Tên nhiệm vụ:</strong>
            {{ $mission->mission_name }}
        </p>

        <p>
            <strong>Kết quả yêu cầu:</strong>
            {{ $mission->expected_result }}
        </p>

        {{-- ===== HẠN HOÀN THÀNH / THƯỜNG XUYÊN ===== --}}
        <p>
            <strong>Hạn hoàn thành:</strong>

            @if ($mission->mission_type == 'regular')
                <span class="label label-info">Thường xuyên</span>

            @elseif ($mission->mission_type == 'time_limited' && $mission->deadline_date)
                <span class="label label-danger">
                    {{ \Carbon\Carbon::parse($mission->deadline_date)->format('d/m/Y') }}
                </span>
            @else
                <span></span>
            @endif
        </p>
    </div>
</div>



{{-- ================== BẢNG THỰC HIỆN ================== --}}
<div class="ibox">
    <div class="ibox-title">
        <h3>Tình hình thực hiện theo cơ quan</h3>
    </div>

    <div class="ibox-content">
        <table class="table table-bordered table-striped mission-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Cơ quan</th>
                    <th>Kết quả thực hiện</th>
                    <th>Khó khăn, vướng mắc</th>
                    <th>Đề xuất, kiến nghị</th>
                    <th class="text-center">Hoàn thành</th>
                    <th class="text-center">Tiến độ</th>
                </tr>
            </thead>
    <tbody>
    @foreach ($missionAgencies as $ma)
        @php
            $report = $reportsMap->get($ma->id);
        @endphp
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>

            <td>{{ $ma->agency->agency_name }}</td>

            {{-- ===== KẾT QUẢ THỰC HIỆN ===== --}}
            <td>
                @if ($ma->display_report_status === 'reported')
                    {{ $ma->display_report->execution_result }}
                @elseif ($ma->display_report_status === 'na')
                    -
                @else
                    <span class="label label-danger">Chưa báo cáo</span>
                @endif
            </td>

            {{-- ===== KHÓ KHĂN / VƯỚNG MẮC ===== --}}
            <td>
                @if ($ma->display_report?->delayReasons?->count())
                    <ul>
                        @foreach ($ma->display_report->delayReasons as $reason)
                            <li>
                                @if ($reason->reason_code == 'others' )
                                    {{ $reason->pivot->description }}
                                @else
                                    {{ $reason->reason_name }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    -
                @endif
            </td>

            {{-- ===== ĐỀ XUẤT, KIẾN NGHỊ ===== --}}
            <td>
                @if ($ma->display_report?->recommendation)
                    {{ $ma->display_report->recommendation }}
                @else
                    -
                @endif
            </td>
            
            {{-- ===== HOÀN THÀNH ===== --}}
            <td class="text-center">
                @if ($ma->display_complete_status === 'completed')
                    <span class="label label-success">
                        {{ \Carbon\Carbon::parse($ma->completed_at)->format('d/m/Y') }}
                    </span>
                @else
                    <span class="label label-danger">Chưa hoàn thành</span>
                @endif
            </td>

            {{-- ===== TIẾN ĐỘ ===== --}}
            <td class="text-center">
                @if ($ma->display_progress_status === 'on_time')
                    <span class="label label-success">Đúng hạn</span>
                @else
                    <span class="label label-danger">Trễ hạn</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>

        </table>

        {{-- ===== QUAY LẠI DASHBOARD ===== --}}
        <div class="form-group text-right">
            <a href="{{ route('missions.report.dashboard', array_filter([
                'resolution_id'    => request('resolution_id'),
                'report_year'      => request('report_year'),
                'period_type'      => request('period_type'),
                'report_period_id' => request('report_period_id'),
                'agency_id'        => request('agency_id'),
            ])) }}" class="btn btn-default">
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
        $('.mission-table').DataTable({
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
