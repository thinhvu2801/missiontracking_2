@php
    $user = auth()->user();
    $isReporter = $user && $user->hasRole('reporter');

    if ($isReporter) {
        $selectedAgencyId = optional($user->agency)->parent_agency_id;
    } else {
        $selectedAgencyId = request('agency_id');
    }

    $singleReport = null;
    $singleMissionAgency = null;
    $stat = $dashboardStats[$mission->id] ?? null;
    $allReported  = $stat && $stat->reported_count  == $stat->total_agencies;
    $allCompleted = $stat && $stat->completed_count == $stat->total_agencies;
@endphp

@foreach ($mission->agencies as $agency)
    @php
        $maId = $agency->pivot->id;
        $report = $reportsMap[$maId] ?? null;
        if ($selectedAgencyId && $agency->id == $selectedAgencyId) {
            $singleReport = $report;
            $singleMissionAgency = $agency->pivot;
        }
        if ($isAdminOrSupervisor) {
            $deadline     = $mission->deadline_date;
            $completedAt  = $mission->completed_at;
            $isCompleted  = $mission->is_completed;
        } else {
            $deadline     = $mission->deadline_date;
            $completedAt  = $singleMissionAgency?->completed_at;
            $isCompleted  = $singleMissionAgency?->is_completed;
        }

        $isOverdue = false;

        if ($deadline) {
            $deadline = \Carbon\Carbon::parse($deadline)->endOfDay();

            if ($isCompleted && $completedAt) {
                $isOverdue = \Carbon\Carbon::parse($completedAt)->gt($deadline);
            } else {
                $isOverdue = now()->gt($deadline);
            }
        }    

        $notRequiredReport = false;

        if ($isCompleted && $completedAt && $reportPeriod?->start_date) {
            $notRequiredReport = \Carbon\Carbon::parse($completedAt)
                ->lt(\Carbon\Carbon::parse($reportPeriod->start_date));
        }
    @endphp
@endforeach

<tr>
    <td class="text-center">{{ $index }}</td>
    <td>{{ $mission->mission_name }}</td>
    <td>
        @if($isAdminOrSupervisor)
            {{ $mission->getAgenciesDisplay() }}
        @else
            {{ $mission->childrenAgencies->first()?->agency_name }}
        @endif
    </td>
    <td class="text-center">
        {{ $deadline ? \Carbon\Carbon::parse($deadline)->format('d/m/Y') : '-' }}
    </td>

    <td class="text-center">
        {{ $completedAt ? \Carbon\Carbon::parse($completedAt)->format('d/m/Y') : '-' }}
    </td>   
    <td class="text-center">
        @if ($notRequiredReport)
            <span class="label label-default">-</span>

        @elseif ($selectedAgencyId)
            {!! $singleReport
                ? '<span class="label label-success">Đã báo cáo</span>'
                : '<span class="label label-danger">Chưa báo cáo</span>' !!}

        @else
            <span class="label {{ $allReported ? 'label-success' : 'label-danger' }}">
                {{ $allReported ? 'Đã báo cáo' : 'Chưa báo cáo' }}
            </span>
        @endif
    </td>
    <td class="text-center">
        @if ($isAdminOrSupervisor)
            <span class="label {{ $mission->is_completed ? 'label-success' : 'label-danger' }}">
                {{ $mission->is_completed ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}
            </span>
        @else
            <span class="label {{ $singleMissionAgency->is_completed ? 'label-success' : 'label-danger' }}">
                {{ $singleMissionAgency->is_completed ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}
            </span>
        @endif
    </td>
    <td class="text-center">
        @if (! $deadline)
            <span class="label label-default">-</span>
        @elseif ($isOverdue)
            <span class="label label-danger">Trễ hạn</span>
        @else
            <span class="label label-success">Đúng hạn</span>
        @endif
    </td> 
    <td class="text-center">
        @if ($user && $user->hasRole(['reporter','sub_admin']))
            @php
                $reportPeriodId = request('report_period_id');
                $periodType     = request('period_type');
                $reportYear     = request('report_year');

                if ($singleMissionAgency->is_completed) {
                    $latestPeriod = latest_report_period($singleMissionAgency->id);
                    $reportPeriodId = $latestPeriod->id;
                    $periodType     = $latestPeriod->period_type->value();
                    $reportYear     = $latestPeriod->report_year;
                }
            @endphp         
            <a href="{{ route('missions.report.create', [
                'mission'        => $mission->id,
                'report_year'      => $reportYear,
                'period_type'      => $periodType,
                'report_period_id' => $reportPeriodId,
            ]) }}">
                @if ($user && $user->hasRole('reporter'))
                    @include('partials.button.report')
                @else
                    @include('partials.button.details')
                @endif
            </a>
        @else
            <a href="{{ route('missions.details', [
                'mission'        => $mission->id,
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

@if ($mission->children->count())
    @foreach ($mission->children as $i => $child)
        @include('mission.dashboard_row', [
            'mission' => $child,
            'level' => $level + 1,
            'index' => $index . '.' . ($i + 1),
            'reportsMap' => $reportsMap
        ])
    @endforeach
@endif
