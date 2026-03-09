@php
    $totalAgencies  = $indicator->agencies->count();
    $reportedCount  = 0;
    $completedCount = 0;

    $user = auth()->user();

    // Nếu admin chọn agency → dùng agency_id trên request
    if (request()->filled('agency_id')) {
        $selectedAgencyId = request('agency_id');
    }
    // Nếu là reporter → tự động lấy agency của user
    elseif ($user && $user->hasRole('reporter')) {
        $selectedAgencyId = $user->agency_id;
    }
    // Còn lại (admin chưa chọn) → null
    else {
        $selectedAgencyId = null;
    }

    $singleAgencyReport = null;
@endphp

@foreach ($indicator->agencies as $agency)
    @php
        $iaId   = $agency->pivot->id;
        $report = $reportsMap[$iaId] ?? null;

        // Nếu đang lọc theo 1 cơ quan → giữ report của cơ quan đó
        if ($selectedAgencyId && $agency->id == $selectedAgencyId) {
            $singleAgencyReport = $report;
        }
    @endphp

    {{-- ===== ĐÃ BÁO CÁO (TỔNG HỢP) ===== --}}
    @if ($report)
        @php
            $reportedCount++;
            $isCompleted = false;
        @endphp

        {{-- ===== CHỈ TIÊU ĐỊNH LƯỢNG ===== --}}
        @if ($indicator->indicator_type->value() === 'quantitative')
            @php
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
            @endphp
        @else
            {{-- ===== CHỈ TIÊU ĐỊNH TÍNH ===== --}}
            @php
                $isCompleted = ($report->qualitive_result === true);
            @endphp
        @endif

        @if ($isCompleted)
            @php $completedCount++; @endphp
        @endif
    @endif
@endforeach

<tr class="{{ $level > 1 ? 'table-secondary' : '' }}">
    <td class="text-center">{{ $index }}</td>

    <td>
        {{ $indicator->indicator_name }}
    </td>

    <td>
        {{ $indicator->getAgenciesDisplay() }}
    </td>

    {{-- ================== CỘT BÁO CÁO ================== --}}
    <td class="text-center">
        @if ($selectedAgencyId)
            @if ($singleAgencyReport)
                <span class="label label-success">Đã báo cáo</span>
            @else
                <span class="label label-danger">Chưa báo cáo</span>
            @endif
        @else
            <strong>{{ $reportedCount }}</strong> / {{ $totalAgencies }}
        @endif
    </td>

    {{-- ================== CỘT HOÀN THÀNH ================== --}}
    <td class="text-center">
        @if ($selectedAgencyId)
            @php
                $isCompleted = false;

                if ($singleAgencyReport) {
                    if ($indicator->indicator_type->value() === 'quantitative') {
                        $value = $singleAgencyReport->quantitive_result;

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
                    } else {
                        $isCompleted = ($singleAgencyReport->qualitive_result === true);
                    }
                }
            @endphp

            @if ($isCompleted)
                <span class="label label-success">Hoàn thành</span>
            @else
                <span class="label label-danger">Chưa hoàn thành</span>
            @endif
        @else
            <strong>{{ $completedCount }}</strong> / {{ $totalAgencies }}
        @endif
    </td> 
    <td class="text-center">
        @if ($user && $user->hasRole('reporter')) 
            <a href="{{ route('indicators.report.create', [
                'indicator'        => $indicator->id,
                'report_year'      => request('report_year'),
                'period_type'      => request('period_type'),
                'report_period_id' => request('report_period_id'),
            ]) }}">
                @include('partials.button.report')
            </a>
        @else
            <a href="{{ route('indicators.details', [
                'indicator'        => $indicator->id,
                'resolution_id'    => request('resolution_id'),
                'report_year'      => request('report_year'),
                'period_type'      => request('period_type'),
                'report_period_id' => request('report_period_id'),
                'agency_id'        => request('agency_id'),
            ]) }}">
                @include('partials.button.details')
            </a>
        @endif
    </td>
</tr>

@if ($indicator->getChildren->count())
    @foreach ($indicator->getChildren as $i => $child)
        @include('indicator.dashboard_row', [
            'indicator' => $child,
            'level'     => $level + 1,
            'index'     => $index . '.' . ($i + 1),
            'reportsMap'=> $reportsMap
        ])
    @endforeach
@endif
