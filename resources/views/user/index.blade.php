@extends('layouts.app')

@section('title', 'Người dùng')

@section('content')
<h2>Người dùng</h2>

<div class="text-right mb10 mr5">
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        @include('partials.button.add')
    </a>
</div>

@php
    $user = auth()->user();
@endphp

<table id="userTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Họ tên</th>
            <th>Email</th>
            <th>
                    @if ($user->hasRole(['admin','supervisor']))
                        Cơ quan
                    @else
                        Phòng ban/Đơn vị
                    @endif                
            </th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $u)
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->username }}</td>
                <td>{{ $u->full_name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                    @if ($user->hasRole(['admin','supervisor']))
                        {{ $u->agency?->parent?->agency_name ?? $u->agency?->agency_name }}
                    @else
                        {{ $u->agency?->agency_name }}
                    @endif
                </td>
                <td>{{ $u->roles->pluck('name')->join(', ') }}</td>
                <td>
                    @if($u->is_active)
                        <span class="label label-primary">Active</span>
                    @else
                        <span class="label label-default">Inactive</span>
                    @endif
                </td>
                <td class="text-center">
                    <a href="{{ route('users.edit', $u) }}">
                        @include('partials.button.edit')
                    </a>
                    @if ($user->hasRole(['admin','supervisor']))
                        <form method="POST"
                            action="{{ route('users.destroy', $u) }}"
                            style="display:inline">
                            @csrf
                            @method('DELETE')
                            @include('partials.button.delete')
                        </form>
                    @endif
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
        $('#userTable').DataTable({
            pageLength: 20,
            lengthMenu: [20, 50, 100],
            language: {
                url: "{{ asset('backend/dataTables/vi.json') }}"
            }
        });
    });
</script>
@endpush