@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
<div class="container" style="max-width: 560px;">
    <div class="card">
        <div class="card-h">
            <div><b>Đổi mật khẩu</b></div>
        </div>

        <div class="card-b">

            @if(session('success'))
                <div class="alert alert-success mb-3">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('users.password.update') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Mật khẩu hiện tại</label>
                    <input type="password"
                           name="current_password"
                           class="form-control @error('current_password') is-invalid @enderror"
                           required>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password"
                           name="new_password"
                           class="form-control @error('new_password') is-invalid @enderror"
                           required minlength="6">
                    @error('new_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Nhập lại mật khẩu mới</label>
                    <input type="password"
                           name="new_password_confirmation"
                           class="form-control"
                           required minlength="6">
                </div>

                <button class="btn btn-primary">Cập nhật mật khẩu</button>
            </form>

        </div>
    </div>
</div>
@endsection