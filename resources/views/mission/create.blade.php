@extends('layouts.app')

@section('title', 'Thêm nhiệm vụ')

@php
    $user = auth()->user();
    $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);
@endphp

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Thêm nhiệm vụ</h2>
            </div>

            @include('partials.error')

            <div class="ibox-content">
                <form method="POST" action="{{ route('missions.store') }}">
                    @csrf

                    {{-- Văn bản --}}
                    <div class="form-group">
                        <label>Văn bản</label>
                        <input type="hidden"
                               name="resolution_id"
                               value="{{ $resolution->id }}">
                        <input type="text"
                               class="form-control"
                               value="{{ $resolution->resolution_code }}"
                               readonly>
                    </div>

                    {{-- Nhóm nhiệm vụ --}}
                    <div class="form-group">
                        <label>Nhóm nhiệm vụ <span class="text-danger">*</span></label>
                        <select name="mission_group_id" class="form-control" required>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}"
                                    @selected(old('mission_group_id') == $group->id)>
                                    {{ $group->group_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tên nhiệm vụ --}}
                    <div class="form-group">
                        <label>Tên nhiệm vụ <span class="text-danger">*</span></label>
                        <textarea name="mission_name"
                                class="form-control"
                                rows="4"
                                required>{{ old('mission_name') }}</textarea>
                    </div>

                    {{-- Loại nhiệm vụ --}}
                    <div class="form-group">
                        <label>Loại nhiệm vụ <span class="text-danger">*</span></label>
                        <div style="white-space: nowrap;">
                            @foreach($types as $type)
                                <label style="margin-right: 1.5rem; font-weight: normal;">
                                    <input type="radio"
                                        name="mission_type"
                                        value="{{ $type['value'] }}"
                                        required
                                        {{ old('mission_type','time_limited') == $type['value'] ? 'checked' : '' }}>
                                    {{ $type['label'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Kết quả yêu cầu --}}
                    <div class="form-group">
                        <label>Kết quả yêu cầu</label>
                        <input type="text"
                               name="expected_result"
                               class="form-control"
                               value="{{ old('expected_result') }}">
                    </div>

                    {{-- Hạn hoàn thành --}}
                    <div class="form-group" id="deadline-wrapper">
                        <label id="deadline-label">
                            Hạn hoàn thành <span class="text-danger d-none" id="deadline-required">*</span>
                        </label>
                        <input type="date"
                            name="deadline_date"
                            id="deadline-input"
                            class="form-control"
                            value="{{ old('deadline_date') }}">
                    </div>

                    {{-- Nhiệm vụ cha --}}
                    <div class="form-group">
                        <label>Nhiệm vụ cha</label>
                        <select name="parent_mission_id"
                                class="form-control select2"
                                id="parent-mission">
                            <option value="">-- Không --</option>
                        </select>
                    </div>

                    {{-- Kỳ báo cáo --}}
                    <div class="form-group">
                        <label>Kỳ báo cáo</label>
                        <div>
                            @foreach ($periodTypes as $period)
                                <span class="m-r-md">
                                    <input type="checkbox"
                                        name="period_types[]"
                                        value="{{ $period['value'] }}"
                                        {{ in_array(
                                                $period['value'],
                                                old('period_types', $selectedPeriods ?? [])
                                        ) ? 'checked' : '' }}>
                                    {{ $period['label'] }}
                                </span>
                            @endforeach
                        </div>

                        @error('period_types')
                            <div class="text-danger">*{{ $message }}</div>
                        @enderror
                    </div>
                    @if($isAdminOrSupervisor)
                        <div class="form-group">
                            <label>
                                Thời gian khóa chỉnh sửa
                            </label>

                            <input type="datetime-local"
                                name="editable_until"
                                class="form-control"
                                value="{{ old('editable_until', now()->addHours(24)->format('Y-m-d\TH:i')) }}">
                        </div>
                    @endif
                    @if($isAdminOrSupervisor)
                        {{-- Phân công cơ quan (ADMIN) --}}
                        <div class="form-group">
                            <label>Phân công cơ quan thực hiện</label>

                            <div class="input-group mb10 col-md-3">
                                <input type="text"
                                    id="search-agency"
                                    class="form-control"
                                    placeholder="Tìm cơ quan...">
                            </div>

                            <div id="agency-wrapper">
                                @foreach($agencyGroups as $group)
                                    <div class="agency-group"
                                        data-name="{{ mb_strtolower($group->group_name) }}">

                                        <div style="margin-bottom: 5px;">
                                            <label style="font-weight: bold;">
                                                <input type="checkbox"
                                                    class="check-group"
                                                    data-group="{{ $group->id }}">
                                                {{ $group->group_name }}
                                            </label>
                                        </div>

                                        <div class="row" style="padding-left: 1rem;">
                                            @foreach($group->agencies as $agency)
                                                <div class="col-md-4 agency-item"
                                                    data-name="{{ mb_strtolower($agency->agency_name) }}">
                                                    <label style="font-weight: normal;">
                                                        <input type="checkbox"
                                                            name="agency_ids[]"
                                                            value="{{ $agency->id }}"
                                                            class="check-agency"
                                                            data-group="{{ $group->id }}">
                                                        {{ $agency->agency_name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="hr-line-dashed"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- Phân công cơ quan (SUB ADMIN) --}}
                        <div class="form-group">
                            <label>Cơ quan thực hiện <span class="text-danger">*</span></label>

                            <select name="agency_ids[]"
                                    class="form-control select2"
                                    required>
                                <option value="">-- Chọn cơ quan --</option>

                                @foreach($agencyGroups as $group)
                                    @foreach($group->agencies as $agency)
                                        <option value="{{ $agency->id }}"
                                            @selected(collect(old('agency_ids'))->contains($agency->id))>
                                            {{ $agency->agency_name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group text-right">
                        <a href="{{ route('missions.index', $resolution) }}"
                           class="btn btn-default">
                            ⬅ Quay lại
                        </a>
                        <button class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('backend/js/custom/mission.js') }}"></script>
<script>
    const parentMissionUrl = "{{ route('missions.parents-by-group') }}";
    const currentParentMissionId = "{{ old('parent_mission_id') }}";
</script>
@endpush
