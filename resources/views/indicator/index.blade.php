@extends('layouts.app')

@section('title', 'Chỉ tiêu - ' . $resolution->resolution_code)

@section('content')

<h2 class="text-center">
    Chỉ tiêu theo Văn bản:
    <strong>{{ $resolution->resolution_code }}</strong>
</h2>

<div class="text-right mb10">
    <a href="{{ route('indicator-groups.create', ['resolution_id' => $resolution->id]) }}"
       class="btn btn-success">
        <i class="fa fa-plus"></i> Thêm mới nhóm
    </a>

    <a href="{{ route('indicators.create', ['resolution_id' => $resolution->id]) }}"
       class="btn btn-primary">
        <i class="fa fa-plus"></i> Thêm mới chỉ tiêu
    </a>
</div>

@foreach ($groups as $group)
    <div class="ibox">
        <div class="ibox-title clearfix">
            <h3 class="pull-left">
                {{ roman($loop->iteration) }}. {{ $group->group_name }}
            </h3>

            <div class="pull-right">
                <a href="{{ route('indicator-groups.edit', $group) }}">
                    @include('partials.button.edit')
                </a>

                <form method="POST"
                      action="{{ route('indicator-groups.destroy', $group) }}"
                      style="display:inline">
                    @csrf
                    @method('DELETE')
                    @include('partials.button.delete')
                </form>
            </div>
        </div>

        <div class="ibox-content">
            <table class="table table-striped table-bordered indicator-table">
                <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên chỉ tiêu</th>
                    <th>Đơn vị</th>
                    <th>Kết quả yêu cầu</th>
                    <th>Cơ quan chủ trì</th>
                    <th class="text-center">Hành động</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($group->indicators as $i => $indicator)
                    @include('indicator.index_row', [
                        'indicator' => $indicator,
                        'level' => 1,
                        'index' => $i + 1
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
        $('.indicator-table').DataTable({
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