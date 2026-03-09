@extends('layouts.app')

@section('title', 'Nguyên nhân trễ hạn')

@section('content')
<h2>Nguyên nhân khó khăn, vướng mắc</h2>

<div class="text-right mb10 mr5">
    <a href="{{ route('delay-reasons.create') }}" class="btn btn-primary">
        @include('partials.button.add')
    </a>
</div>

<table id="delayReasonTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Mã nguyên nhân</th>
            <th>Tên nguyên nhân</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reasons as $reason)
            <tr>
                <td>{{ $reason->id }}</td>
                <td>{{ $reason->reason_code }}</td>
                <td>{{ $reason->reason_name }}</td>
                <td>
                    <a href="{{ route('delay-reasons.edit', $reason) }}">
                        @include('partials.button.edit')
                    </a>

                    <form method="POST"
                          action="{{ route('delay-reasons.destroy', $reason) }}"
                          style="display:inline">
                        @csrf
                        @method('DELETE')
                        @include('partials.button.delete')
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

{{ $reasons->links() }}
@endsection

@push('styles')
<link href="{{ asset('backend/css/plugins/dataTables/jquery.dataTables.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('backend/js/plugins/dataTables/jquery.dataTables.min.js') }}"></script>
<script>
    $(function () {
        $('#delayReasonTable').DataTable({
            pageLength: 20,
            lengthMenu: [20, 50, 100],
            language: {
                url: "{{ asset('backend/dataTables/vi.json') }}",
            }
        });
    });
</script>
@endpush
