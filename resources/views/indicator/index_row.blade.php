<tr class="{{ $level > 1 ? 'table-secondary' : '' }}">
    <td class="text-center">{{ $index }}</td>
    <td>{{ $indicator->indicator_name }}</td>
    <td>{{ $indicator->unit_of_measure }}</td>
    <td>{{ $indicator->getExpectedResult() }}</td>
    <td>{{ $indicator->getAgenciesDisplay() }}</td>
    <td class="text-center">
        <a href="{{ route('indicators.report.create', $indicator) }}">
            @include('partials.button.report')
        </a>        
        <a href="{{ route('indicators.edit', $indicator) }}">
            @include('partials.button.edit')
        </a>
        <form method="POST"
              action="{{ route('indicators.destroy', $indicator) }}"
              style="display:inline">
            @csrf
            @method('DELETE')
            @include('partials.button.delete')
        </form>
    </td>
</tr>

@if ($indicator->getChildren->count())
    @foreach ($indicator->getChildren as $i => $child)
        @include('indicator.index_row', [
            'indicator' => $child,
            'level' => $level + 1,
            'index' => $index . '.' . ($i + 1)
        ])
    @endforeach
@endif
