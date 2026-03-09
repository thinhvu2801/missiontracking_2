<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && $user->is_active
            && $user->hasRole(['admin', 'sub_admin']);
    }

    public function rules(): array
    {
        $userId = optional($this->route('user'))->id;

        return [
            'full_name' => 'required|string|max:255',
            'email'     => 'nullable|email',
            'agency_id' => [
                'nullable',
                'exists:agencies,id',
                Rule::unique('users', 'agency_id')->ignore($userId),
            ],
            'is_active' => 'nullable|boolean',
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'min:6',
                'confirmed',
            ],
            'role_code' => [
                'sometimes',
                'required',
                Rule::in(['admin', 'sub_admin', 'reporter', 'supervisor']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required'   => 'Vui lòng nhập tên đăng nhập',
            'username.unique'     => 'Tên đăng nhập đã tồn tại',
            'password.required'   => 'Vui lòng nhập mật khẩu',
            'password.min'        => 'Mật khẩu tối thiểu 6 ký tự',
            'role_code.required'  => 'Vui lòng chọn vai trò',
            'agency_id.exists'    => 'Đơn vị không hợp lệ',
            'agency_id.unique'    => 'Cơ quan/Phòng ban/Đơn vị đã có tài khoản',
        ];
    }
}
