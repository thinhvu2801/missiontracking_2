@extends('layouts.app')

@section('title', 'Cập nhật nhóm nhiệm vụ')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Cập nhật nhóm nhiệm vụ</h2>
            </div>

            @include('partials.error')

            <div class="ibox-content">
                <form method="POST"
                      action="{{ route('mission-groups.update', $missionGroup) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="id" value="{{ $missionGroup->id }}">

                    {{-- Văn bản --}}
                    <div class="form-group">
                        <label>Văn bản</label>
                        <input type="hidden"
                               name="resolution_id"
                               value="{{ $missionGroup->resolution_id }}">
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
                            value="{{ old('group_name', $missionGroup->group_name) }}"
                            placeholder="Nhập tên nhóm nhiệm vụ"
                            required
                        >
                    </div>

                    <div class="hr-line-dashed"></div>

                    {{-- Buttons --}}
                    <div class="form-group text-right">
                        <a href="{{ route('missions.index', $missionGroup->resolution_id) }}"
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
