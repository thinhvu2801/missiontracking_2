@extends('layouts.app')

@section('title', 'Văn bản')

@section('content')
<h2>Văn bản</h2>

<div class="text-right mb10 mr5">
    <a href="{{ route('resolutions.create') }}" class="btn btn-primary">
        @include('partials.button.add')
    </a>
</div>

<table id="resolutionTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Số Văn bản</th>
            <th>Tên Văn bản</th>
            <th>Ngày ban hành</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        @foreach($resolutions as $resolution)
            <tr>
                <td>{{ $resolution->id }}</td>
                <td>{{ $resolution->resolution_code }}</td>
                <td>{{ $resolution->resolution_name }}</td>
                <td>{{ $resolution->issued_date }}</td>
                <td>
                    <a href="{{ route('resolutions.edit', $resolution) }}">
                        @include('partials.button.edit')
                    </a>
                    <form method="POST"
                          action="{{ route('resolutions.destroy', $resolution) }}"
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
@endsection

@push('styles')
<link href="{{ asset('backend/css/plugins/dataTables/jquery.dataTables.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('backend/js/plugins/dataTables/jquery.dataTables.min.js') }}"></script>
<script>
    $(function () {
        $('#resolutionTable').DataTable({
            pageLength: 20,
            lengthMenu: [20, 50, 100],
            language: {
                url: "{{ asset('backend/dataTables/vi.json') }}",
            }
        });
    });
</script>
@endpush
