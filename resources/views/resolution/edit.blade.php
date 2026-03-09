@extends('layouts.app')

@section('title', 'Cập nhật văn bản')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Cập nhật văn bản</h2>
            </div>
            @include('partials.error')
            <div class="ibox-content">
                <form method="POST"
                      action="{{ route('resolutions.update', $resolution) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="id" value="{{ $resolution->id }}">

                    <div class="form-group">
                        <label>Số văn bản <span class="text-danger">*</span></label>
                        <input type="text"
                               name="resolution_code"
                               class="form-control"
                               value="{{ old('resolution_code', $resolution->resolution_code) }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Tên văn bản <span class="text-danger">*</span></label>
                        <textarea
                            name="resolution_name"
                            class="form-control"
                            rows="3"
                            required>{{ old('resolution_name', $resolution->resolution_name) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Ngày ban hành</label>
                        <input type="date"
                               name="issued_date"
                               class="form-control"
                               value="{{ old('issued_date', $resolution->issued_date) }}"
                               required>
                    </div>

                    <div class="form-group">
                        @foreach ($unitTypes as $unit)
                            <div class="row m-b-sm">
                                <div class="col-md-3">
                                    <label>
                                        Kỳ báo cáo – {{ $unit['label'] }}:
                                    </label>
                                </div>

                                <div>
                                    @foreach ($periodTypes as $period)
                                        <span class="m-r-md">
                                            <input type="checkbox"
                                                name="report_periods[{{ $unit['value'] }}][]"
                                                value="{{ $period['value'] }}"
                                                {{ in_array(
                                                        $period['value'],
                                                        old(
                                                            'report_periods.' . $unit['value'],
                                                            $selected[$unit['value']] ?? []
                                                        )
                                                ) ? 'checked' : '' }}>
                                            {{ $period['label'] }}
                                        </span>
                                    @endforeach

                                    @if ($errors->has('report_periods.' . $unit['value']))
                                        <div>
                                            <span class="text-left text-danger">
                                                *{{ $errors->first('report_periods.' . $unit['value']) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="hr-line-dashed"></div>

                    <div class="form-group text-right">
                        <a href="{{ route('resolutions.index') }}" class="btn btn-default">
                            ⬅ Quay lại
                        </a>
                        <button class="btn btn-primary">
                            Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
