<?php

namespace App\Http\Requests\Mission;

use Illuminate\Foundation\Http\FormRequest;

class MissionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_id' => 'required|exists:resolutions,id',
            'group_code'    => 'nullable|string|max:50',
            'group_name'    => 'required|string|max:255',
        ];
    }
}
