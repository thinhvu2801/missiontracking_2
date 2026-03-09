@extends('layouts.app')

@section('title', 'Nhiệm vụ - ' . $resolution->resolution_code)

@section('content')
@php
    $user = auth()->user();
@endphp

<h2 class="text-center">
    Nhiệm vụ theo Văn bản:
    <strong>{{ $resolution->resolution_code }}</strong>
</h2>

<div class="text-right mb10">
    @if($user->hasRole('admin'))
        <a href="{{ route('mission-groups.create', ['resolution_id' => $resolution->id]) }}"
        class="btn btn-success">
            <i class="fa fa-plus"></i> Thêm mới nhóm
        </a>
    @endif

    <a href="{{ route('missions.create', ['resolution_id' => $resolution->id]) }}"
       class="btn btn-primary">
        <i class="fa fa-plus"></i> Thêm mới nhiệm vụ
    </a>
</div>

@foreach ($groups as $group)
    <div class="ibox">
        <div class="ibox-title clearfix">
            <h3 class="pull-left">
                {{ roman($loop->iteration) }}. {{ $group->group_name }}
            </h3>
            @if($user->hasRole('admin'))
                <div class="pull-right">
                    <a href="{{ route('mission-groups.edit', $group) }}">
                        @include('partials.button.edit')
                    </a>
                    <form method="POST"
                        action="{{ route('mission-groups.destroy', $group) }}"
                        style="display:inline">
                        @csrf
                        @method('DELETE')
                        @include('partials.button.delete')
                    </form>
                </div>
            @endif
        </div>

        <div class="ibox-content">
            <table class="table table-striped table-bordered mission-table">
                <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên nhiệm vụ</th>
                    <th>Thời hạn hoàn thành</th>
                    <th>Kết quả yêu cầu</th>
                    <th>
                        {{ $user->hasRole(['admin', 'supervisor']) 
                            ? 'Cơ quan chủ trì' 
                            : 'Phòng ban thực hiện' }}
                    </th>

                    <th class="text-center">Hành động</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($group->missions as $i => $mission)
                    @include('mission.index_row', [
                        'mission' => $mission,
                        'level'   => 1,
                        'index'   => $i + 1
                    ])
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection

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
                url: "{{ asset('backend/dataTables/vi.json') }}",
            }
        });
    });
</script>
@endpush
