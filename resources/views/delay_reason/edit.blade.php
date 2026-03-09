@extends('layouts.app')

@section('title', 'Cập nhật nguyên nhân trễ hạn')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Cập nhật nguyên nhân</h2>
            </div>

            @include('partials.error')

            <div class="ibox-content">
                <form method="POST" action="{{ route('delay-reasons.update', $delayReason) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="id" value="{{ $delayReason->id }}">

                    {{-- Mã nguyên nhân --}}
                    <div class="form-group">
                        <label for="reason_code">
                            Mã nguyên nhân
                        </label>

                        <input type="text"
                               id="reason_code"
                               name="reason_code"
                               class="form-control @error('reason_code') is-invalid @enderror"
                               value="{{ old('reason_code', $delayReason->reason_code) }}">

                        @error('reason_code')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tên nguyên nhân --}}
                    <div class="form-group">
                        <label for="reason_name">
                            Tên nguyên nhân <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               id="reason_name"
                               name="reason_name"
                               class="form-control @error('reason_name') is-invalid @enderror"
                               value="{{ old('reason_name', $delayReason->reason_name) }}"
                               required>

                        @error('reason_name')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Mô tả --}}
                    <div class="form-group">
                        <label for="description">Mô tả</label>

                        <textarea id="description"
                                  name="description"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Nhập mô tả">{{ old('description', $delayReason->description) }}</textarea>
                    </div>

                    <div class="hr-line-dashed"></div>

                    {{-- Buttons --}}
                    <div class="form-group text-right">
                        <a href="{{ route('delay-reasons.index') }}" class="btn btn-default">
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
