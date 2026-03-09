@extends('layouts.app')

@section('title', 'Cập nhật người dùng')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h2>Cập nhật người dùng</h2>
            </div>

            @include('partials.error')

            <div class="ibox-content">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    {{-- ================= THÔNG TIN CƠ BẢN ================= --}}
                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input type="text"
                               class="form-control"
                               value="{{ $user->username }}"
                               disabled>
                    </div>

                    <div class="form-group">
                        <label>Họ tên <span class="text-danger">*</span></label>
                        <input type="text"
                               name="full_name"
                               class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', $user->full_name) }}"
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
                               value="{{ old('email', $user->email) }}">
                        @error('email')
                            <div class="text-danger m-t-xs">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="is_active" class="form-control">
                            <option value="1" @selected($user->is_active == 1)>Hoạt động</option>
                            <option value="0" @selected($user->is_active == 0)>Khóa</option>
                        </select>
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
                                    @selected(old('role_code', $userRoleCode) === $role->code)>
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
                        <label>Cơ quan cấp 1</label>
                        <select id="agency_parent" class="form-control select2">
                            <option value="">-- Chọn cơ quan cấp 1 --</option>
                            @foreach ($agenciesLevel1 as $a)
                                <option value="{{ $a->id }}"
                                    @selected($parentAgencyId == $a->id)>
                                    {{ $a->agency_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="row-agency-level-2" style="display:none">
                        <label>Cơ quan cấp 2</label>
                        <select name="agency_id"
                                id="agency_child"
                                class="form-control select2 @error('agency_id') is-invalid @enderror">
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
                                    @selected(old('agency_id', $user->agency_id) == $a->id)>
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

    const selectedAgencyId = @json(old('agency_id', $user->agency_id));

    $('.select2').select2({ width: '100%' });

    function resetChild() {
        $child.html('<option value="">-- Chọn cơ quan cấp 2 --</option>');
    }

    function toggleByRole() {
        const role = $role.val();

        // reset name trước
        $parent.removeAttr('name');
        $child.removeAttr('name');

        if (role === 'reporter') {
            $rowLv1.show();
            $rowLv2.show();

            $parent.prop('required', true);
            $child.prop('required', true);

            $child.attr('name', 'agency_id');
        }
        else if (role === 'sub_admin') {
            $rowLv1.show();
            $rowLv2.hide();

            $parent.prop('required', true);
            $child.prop('required', false);

            $parent.attr('name', 'agency_id');
            resetChild();
        }
        else {
            $rowLv1.hide();
            $rowLv2.hide();

            $parent.prop('required', false);
            $child.prop('required', false);

            resetChild();
        }
    }

    // load agency cấp 2
    $parent.on('change', function () {
        const parentId = $(this).val();
        resetChild();
        if (!parentId) return;

        $.get('{{ route('agencies.by-parent', ':id') }}'.replace(':id', parentId))
            .done(function (data) {
                let html = '<option value="">-- Chọn cơ quan cấp 2 --</option>';
                data.forEach(a => {
                    const selected = selectedAgencyId == a.id ? 'selected' : '';
                    html += `<option value="${a.id}" ${selected}>${a.agency_name}</option>`;
                });
                $child.html(html).trigger('change');
            });
    });

    $role.on('change', toggleByRole);

    toggleByRole();

    // prefill cấp 2
    if ($parent.val()) {
        $parent.trigger('change');
    }
});
</script>
@endpush
