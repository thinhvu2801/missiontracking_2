@extends('layouts.app')

@section('title', 'Cập nhật nhiệm vụ')
@php
    $user = auth()->user();
    $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);
    $lockInfoFields = ! $isAdminOrSupervisor && $isCreatedByAdmin;
@endphp

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Cập nhật nhiệm vụ</h2>
            </div>

            @include('partials.error')

            <div class="ibox-content">
                <form method="POST"
                      action="{{ route('missions.update', $mission) }}">
                    @csrf
                    @method('PUT')

                    {{-- Văn bản --}}
                    <div class="form-group">
                        <label>Văn bản</label>
                        <input type="hidden"
                               name="resolution_id"
                               value="{{ $mission->group->resolution_id }}">
                        <input type="text"
                               class="form-control"
                               value="{{ $mission->group->resolution->resolution_code }}"
                               readonly>
                    </div>

                    {{-- Nhóm nhiệm vụ --}}
                    <div class="form-group">
                        <label>Nhóm nhiệm vụ <span class="text-danger">*</span></label>
                        @if($lockInfoFields)
                            <select class="form-control" disabled>
                                <option>{{ $mission->group->group_name }}</option>
                            </select>
                            <input type="hidden"
                                name="mission_group_id"
                                value="{{ $mission->mission_group_id }}">
                        @else
                            <select name="mission_group_id" class="form-control" required>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}"
                                        @selected($mission->mission_group_id == $group->id)>
                                        {{ $group->group_name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- Tên nhiệm vụ --}}
                    <div class="form-group">
                        <label>Tên nhiệm vụ <span class="text-danger">*</span></label>
                        <textarea name="mission_name"
                                class="form-control"
                                rows="4"
                                @if($lockInfoFields) readonly @endif
                                required>{{ old('mission_name', $mission->mission_name) }}</textarea>
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
                                           @if($lockInfoFields) disabled @endif
                                           required
                                           {{ old('mission_type', $mission->mission_type) == $type['value'] ? 'checked' : '' }}>
                                    {{ $type['label'] }}
                                </label>
                            @endforeach
                        </div>
                        @if($lockInfoFields)
                            <input type="hidden"
                                name="mission_type"
                                value="{{ $mission->mission_type }}">
                        @endif
                    </div>

                    {{-- Kết quả yêu cầu --}}
                    <div class="form-group">
                        <label>Kết quả yêu cầu</label>
                        <input type="text"
                               name="expected_result"
                               class="form-control"
                               @if($lockInfoFields) readonly @endif
                               value="{{ old('expected_result', $mission->expected_result) }}">
                    </div>

                    {{-- Hạn hoàn thành --}}
                    <div class="form-group" id="deadline-wrapper">
                        <label>Hạn hoàn thành</label>
                        <input type="date"
                               name="deadline_date"
                               class="form-control"
                               @if($lockInfoFields) readonly @endif
                               value="{{ old('deadline_date', $mission->deadline_date) }}">
                    </div>

                    {{-- Nhiệm vụ cha --}}
                    <div class="form-group">
                        <label>Nhiệm vụ cha</label>
                        @if($lockInfoFields)
                            <select class="form-control" disabled>
                                <option>
                                    {{ optional($mission->parent)->mission_name ?? '-- Không --' }}
                                </option>
                            </select>
                            @if($mission->parent_mission_id)
                                <input type="hidden"
                                    name="parent_mission_id"
                                    value="{{ $mission->parent_mission_id }}">
                            @endif
                        @else
                            <select name="parent_mission_id"
                                    id="parent-mission"
                                    class="form-control select2">
                                <option value="">-- Không --</option>
                            </select>
                        @endif
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
                                           @if($lockInfoFields) disabled @endif
                                           {{ in_array(
                                                $period['value'],
                                                old('period_types', $selectedPeriods ?? [])
                                           ) ? 'checked' : '' }}>
                                    {{ $period['label'] }}
                                </span>
                            @endforeach
                            @if($lockInfoFields)
                                @foreach($selectedPeriods as $p)
                                    <input type="hidden" name="period_types[]" value="{{ $p }}">
                                @endforeach
                            @endif                            
                        </div>

                        @error('period_types')
                            <div class="text-danger">*{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Thời gian khóa chỉnh sửa --}}
                    @if($isAdminOrSupervisor)
                        <div class="form-group">
                            <label>
                                Thời gian khóa chỉnh sửa
                            </label>
                            <input type="datetime-local"
                                name="editable_until"
                                class="form-control"
                                value="{{ old(
                                        'editable_until',
                                        optional($mission->editable_until)->format('Y-m-d\TH:i')
                                ) }}">
                        </div>
                    @endif
                    {{-- Phân công cơ quan --}}
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
                                                            data-group="{{ $group->id }}"
                                                            @checked(in_array($agency->id, $assignedAgencies))>
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
                                            @selected(in_array($agency->id, $assignedAgencies))>
                                            {{ $agency->agency_name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="form-group text-right">
                        <a href="{{ route('missions.index', $mission->group->resolution_id) }}"
                           class="btn btn-default">
                            ⬅ Quay lại
                        </a>
                        <button class="btn btn-primary">Cập nhật</button>
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
    const currentParentMissionId =
        "{{ old('parent_mission_id', $mission->parent_mission_id) }}";
</script>
@endpush
