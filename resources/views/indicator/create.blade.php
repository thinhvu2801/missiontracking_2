@extends('layouts.app')

@section('title', 'Thêm chỉ tiêu')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Thêm chỉ tiêu</h2>
            </div>
            @include('partials.error')
            <div class="ibox-content">
                <form method="POST" action="{{ route('indicators.store') }}">
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

                    {{-- Nhóm chỉ tiêu --}}
                    <div class="form-group">
                        <label>Nhóm chỉ tiêu <span class="text-danger">*</span></label>
                        <select name="indicator_group_id" class="form-control" required>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}"
                                    @selected(old('indicator_group_id') == $group->id)>
                                    {{ $group->group_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tên chỉ tiêu --}}
                    <div class="form-group">
                        <label>Tên chỉ tiêu <span class="text-danger">*</span></label>
                        <textarea name="indicator_name"
                                class="form-control"
                                rows="4"
                                required>{{ old('indicator_name') }}</textarea>
                    </div>

                    {{-- Đơn vị đo --}}
                    <div class="form-group">
                        <label>Đơn vị</label>
                        <input type="text"
                               name="unit_of_measure"
                               class="form-control"
                               value="{{ old('unit_of_measure') }}">
                    </div>

                    {{-- Loại chỉ tiêu --}}
                    <div class="form-group">
                        <label>Loại chỉ tiêu <span class="text-danger">*</span></label>
                        <div style="white-space: nowrap;">
                            @foreach($types as $type)
                                <label class="radio-label" style="margin-right: 1.5rem; font-weight: normal;">
                                    <input type="radio"
                                           name="indicator_type"
                                           value="{{ $type['value'] }}"
                                           required
                                           {{ old('indicator_type', 'quantitative') == $type['value'] ? 'checked' : '' }}>
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

                    {{-- Chỉ tiêu định lượng --}}
                    <div id="quantitative-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Giá trị nhỏ nhất</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <select name="is_target_min_equal">
                                                <option value="0">&gt;</option>
                                                <option value="1">&ge;</option>
                                            </select>
                                        </span>
                                        <input type="number"
                                               step="0.01"
                                               name="target_min"
                                               class="form-control"
                                               value="{{ old('target_min') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Giá trị lớn nhất</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <select name="is_target_max_equal">
                                                <option value="0">&lt;</option>
                                                <option value="1">&le;</option>
                                            </select>
                                        </span>
                                        <input type="number"
                                               step="0.01"
                                               name="target_max"
                                               class="form-control"
                                               value="{{ old('target_max') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Chỉ tiêu cha --}}
                    <div class="form-group">
                        <label>Chỉ tiêu cha</label>
                        <select name="parent_indicator_id"
                                class="form-control select2"
                                id="parent-indicator">
                            <option>-- Không --</option>
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

                    {{-- Phân công cơ quan --}}
                    <div class="form-group">
                        <label>Phân công cơ quan thực hiện</label>

                        {{-- Search --}}
                        <div class="input-group mb10 col-md-3">
                            <input type="text"
                                id="search-agency"
                                class="form-control"
                                placeholder="Tìm cơ quan...">
                        </div>

                        <div id="agency-wrapper">

                            @foreach($agencyGroups as $group)
                                <div class="agency-group" data-name="{{ mb_strtolower($group->group_name) }}">

                                    {{-- Nhóm --}}
                                    <div style="margin-bottom: 5px;">
                                        <label style="font-weight: bold;">
                                            <input type="checkbox"
                                                class="check-group"
                                                data-group="{{ $group->id }}">
                                            {{ $group->group_name }}
                                        </label>
                                    </div>

                                    {{-- Danh sách cơ quan (3 cột) --}}
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

                    <div class="form-group text-right">
                        <a href="{{ route('indicators.index', $resolution) }}"
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
<script src={{ asset('backend/js/custom/indicator.js') }}></script>
<script>
    const parentIndicatorUrl = "{{ route('indicators.parents-by-group') }}";
    const currentParentIndicatorId = "{{ old('parent_indicator_id') }}";
</script>
@endpush
