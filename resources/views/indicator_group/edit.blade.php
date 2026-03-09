@extends('layouts.app')

@section('title', 'Cập nhật nhóm chỉ tiêu')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Cập nhật nhóm chỉ tiêu</h2>
            </div>
            @include('partials.error')
            <div class="ibox-content">
                <form method="POST"
                      action="{{ route('indicator-groups.update', $indicatorGroup) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="id" value="{{ $indicatorGroup->id }}">

                    {{-- Văn bản --}}
                    <div class="form-group">
                        <label>Văn bản</label>
                        <input type="hidden"
                               name="resolution_id"
                               value="{{ $indicatorGroup->resolution_id }}">
                        <input type="text"
                               class="form-control"
                               value="{{ $resolution->resolution_code }}"
                               readonly>
                    </div>

                    {{-- Tên nhóm --}}
                    <div class="form-group">
                        <label for="group_name">
                            Tên nhóm <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="group_name"
                            name="group_name"
                            class="form-control"
                            value="{{ old('group_name', $indicatorGroup->group_name) }}"
                            placeholder="Nhập tên nhóm chỉ tiêu"
                            required
                        >
                    </div>

                    <div class="hr-line-dashed"></div>

                    {{-- Buttons --}}
                    <div class="form-group text-right">
                        <a href="{{ route('indicators.index', $indicatorGroup->resolution_id) }}"
                           class="btn btn-default">
                            ⬅ Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
