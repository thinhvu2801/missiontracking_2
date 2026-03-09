@extends('layouts.app')

@section('title', 'Thêm người dùng')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Thêm người dùng</h2>
            </div>

            @include('partials.error')

            <div class="ibox-content">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf

                    {{-- ================= THÔNG TIN CƠ BẢN ================= --}}
                    <div class="form-group">
                        <label>Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text"
                               name="username"
                               class="form-control @error('username') is-invalid @enderror"
                               value="{{ old('username') }}"
                               required>
                        @error('username')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required>
                        @error('password')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <input type="password"
                            name="password_confirmation"
                            class="form-control"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text"
                               name="full_name"
                               class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name') }}"
                               required>
                        @error('full_name')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}">
                        @error('email')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ================= ROLE ================= --}}
                    @if ($mode === 'admin')
                    <div class="form-group">
                        <label>Vai trò <span class="text-danger">*</span></label>
                        <select name="role_code"
                                id="role_code"
                                class="form-control @error('role_code') is-invalid @enderror"
                                required>
                            <option value="">-- Chọn vai trò --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->code }}"
                                    @selected(old('role_code') === $role->code)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_code')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if ($mode === 'sub_admin')
                        <input type="hidden" name="role_code" value="reporter">
                    @endif

                    {{-- ================= AGENCY LEVEL 1 ================= --}}
                    @if ($mode === 'admin')
                    <div class="form-group" id="row-agency-level-1" style="display:none">
                        <label>Cơ quan cấp 1<span class="text-danger">*</span></label>
                        <select id="agency_parent"
                                class="form-control select2"
                                data-role="parent"
                                data-name="agency_id">
                            <option value="">-- Chọn cơ quan cấp 1 --</option>
                            @foreach ($agenciesLevel1 as $a)
                                <option value="{{ $a->id }}">
                                    {{ $a->agency_name }}
                                </option>
                            @endforeach
                        </select>
                    <input type="hidden"
                        name="agency_parent"
                        id="agency_parent_hidden"
                        value="{{ old('agency_parent') }}">                        
                    </div>

                    <div class="form-group" id="row-agency-level-2" style="display:none">
                        <label>Cơ quan cấp 2<span class="text-danger">*</span></label>
                        <select name="agency_id"
                                id="agency_child"
                                class="form-control select2 @error('agency_id') is-invalid @enderror"
                                required>
                            <option value="">-- Chọn cơ quan cấp 2 --</option>
                        </select>
                        @error('agency_id')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    {{-- ================= SUB ADMIN ================= --}}
                    @if ($mode === 'sub_admin')
                    <div class="form-group">
                        <label>Cơ quan <span class="text-danger">*</span></label>
                        <select name="agency_id"
                                class="form-control @error('agency_id') is-invalid @enderror"
                                required>
                            <option value="">-- Chọn cơ quan --</option>
                            @foreach ($agencies as $a)
                                <option value="{{ $a->id }}"
                                    @selected(old('agency_id') == $a->id)>
                                    {{ $a->agency_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('agency_id')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <div class="hr-line-dashed"></div>

                    <div class="form-group text-right">
                        <a href="{{ route('users.index') }}" class="btn btn-default">
                            ⬅ Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const $role   = $('#role_code');
    const $rowLv1 = $('#row-agency-level-1');
    const $rowLv2 = $('#row-agency-level-2');
    const $parent = $('#agency_parent');
    const $child  = $('#agency_child');

    $('.select2').select2({ width: '100%' });

    const oldParent = '{{ old('agency_parent') }}';
    const oldChild  = '{{ old('agency_id') }}';

    /* ================= UTIL ================= */

    function resetChild() {
        $child.empty().append('<option value="">-- Chọn cơ quan cấp 2 --</option>');
        $child.val(null).trigger('change');
    }

    function toggleByRole() {
        const role = $role.val();

        // reset trạng thái
        $rowLv1.hide();
        $rowLv2.hide();

        $parent.prop('required', false).removeAttr('name');
        $child.prop('required', false).removeAttr('name');

        if (role === 'reporter') {
            // reporter → bắt buộc chọn cấp 2
            $rowLv1.show();
            $rowLv2.show();

            $parent.prop('required', true);
            $child.prop('required', true).attr('name', 'agency_id');
        }

        if (role === 'sub_admin') {
            // sub_admin → chỉ chọn cấp 1
            $rowLv1.show();

            $parent.prop('required', true).attr('name', 'agency_id');
            resetChild();
        }
    }

    function loadChildren(parentId, selectedChild = null) {
        resetChild();
        if (!parentId) return;

        $.get('{{ route('agencies.by-parent', ':id') }}'.replace(':id', parentId))
            .done(function (data) {
                data.forEach(a => {
                    $child.append(
                        `<option value="${a.id}">${a.agency_name}</option>`
                    );
                });

                if (selectedChild) {
                    $child.val(selectedChild).trigger('change');
                }
            });
    }

    /* ================= EVENTS ================= */

    $role.on('change', function () {
        toggleByRole();

        // nếu đổi role mà đang là reporter thì reload lại cấp 2
        if ($role.val() === 'reporter') {
            const parentId = $parent.val();
            loadChildren(parentId, null);
        }
    });

    $parent.on('change', function () {
        const parentId = $(this).val();
        $('#agency_parent_hidden').val(parentId);

        loadChildren(parentId, null);
    });

    /* ================= INIT ================= */

    // 1️⃣ UI theo role trước
    toggleByRole();

    // 2️⃣ restore agency cấp 1
    if (oldParent) {
        $parent.val(oldParent).trigger('change.select2');

        // 3️⃣ load & restore agency cấp 2
        loadChildren(oldParent, oldChild);
    }
});
</script>
@endpush


