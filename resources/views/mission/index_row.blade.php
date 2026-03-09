<tr class="{{ $level > 1 ? 'table-secondary' : '' }}">
    <td class="text-center">{{ $index }}</td>

    <td>
        {{ $mission->mission_name }}
    </td>

    <td>
        @if ($mission->mission_type === \App\Enums\MissionTypeEnum::REGULAR)
            Thường xuyên
        @else
            {{ $mission->deadline_date
                ? \Carbon\Carbon::parse($mission->deadline_date)->format('Y/m/d')
                : '—' }}
        @endif
    </td>

    <td>
        {{ $mission->expected_result }}
    </td>

    <td>
        @if($user->hasRole(['admin', 'supervisor']))
            {{ $mission->getAgenciesDisplay() }}
        @else
            {{ $mission->missionAgencies
                ->pluck('childrenAgency.agency_name')
                ->filter()
                ->unique()
                ->implode('; ')
            }}
        @endif
    </td>

    <td class="text-center">
        @php
            $user = auth()->user();
            $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);

            $isCreator = $mission->created_by == $user->id;

            $isAssigned = $mission->missionAgencies
                ->where('agency_id', $user->agency_id)
                ->isNotEmpty();
        @endphp

        @if (
            $isAdminOrSupervisor ||
            (
                ! $isAdminOrSupervisor &&
                $mission->canEdit() &&
                ($isCreator || $isAssigned)
            )
        )
            <a href="{{ route('missions.edit', $mission) }}">
                @include('partials.button.edit')
            </a>
        @endif

        @if ($isAdminOrSupervisor)
            <form method="POST"
                action="{{ route('missions.destroy', $mission) }}"
                style="display:inline">
                @csrf
                @method('DELETE')
                @include('partials.button.delete')
            </form>
        @endif
    </td>
</tr>

@if ($mission->getChildren->count())
    @foreach ($mission->getChildren as $i => $child)
        @include('mission.index_row', [
            'mission' => $child,
            'level'   => $level + 1,
            'index'   => $index . '.' . ($i + 1)
        ])
    @endforeach
@endif
