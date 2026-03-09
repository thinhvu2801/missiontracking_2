@extends('layouts.app')

@section('title', 'Nhóm cơ quan')

@section('content')
<h2>Nhóm cơ quan</h2>
<div class="text-right mb10 mr5">
    <a href="{{ route('agency-groups.create') }}" class="btn btn-primary">
        @include('partials.button.add')
    </a>
</div>
<table id="groupTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tên nhóm</th>
            <th>Mô tả</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
    @foreach(\App\Models\Agency\AgencyGroup::all() as $group)
        <tr>
            <td>{{ $group->id }}</td>
            <td>{{ $group->group_name }}</td>
            <td>{{ $group->description }}</td>
            <td>
                <a href="{{ route('agency-groups.edit', $group) }}">
                    @include('partials.button.edit')
                </a>
                <form method="POST"
                      action="{{ route('agency-groups.destroy', $group) }}"
                      style="display:inline">
                    @csrf @method('DELETE')
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
        $('#groupTable').DataTable({
            pageLength: 20,
            lengthMenu: [20, 50, 100],
            language: {
                url: "{{ asset('backend/dataTables/vi.json') }}",
            }
        });
    });
</script>
@endpush
