@extends('layouts.app')

@section('title', 'Cơ quan')

@section('content')
<h2>Cơ quan</h2>

<div class="text-right mb10 mr5">
    <a href="{{ route('agencies.create') }}" class="btn btn-primary">
        @include('partials.button.add')
    </a>
</div>

<table id="agencyTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tên cơ quan</th>
            <th>Nhóm</th>
            <th>Trực thuộc</th>
            <th width="120">Hành động</th>
        </tr>
    </thead>
    <tbody>
    @foreach($agencies as $agency)
        <tr>
            <td>{{ $agency->id }}</td>
            <td>{{ $agency->agency_name }}</td>
            <td>{{ $agency->group?->group_name }}</td>
            <td>{{ $agency->parent?->agency_name }}</td>
            <td>
                <a href="{{ route('agencies.edit', $agency) }}">
                    @include('partials.button.edit')
                </a>        
                <form method="POST"
                      action="{{ route('agencies.destroy', $agency) }}"
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
        $('#agencyTable').DataTable({
            pageLength: 20,
            lengthMenu: [20, 50, 100],
            language: {
                url: "{{ asset('backend/dataTables/vi.json') }}"
            }
        });
    });
</script>
@endpush
