<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class AgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency_name' => 'required|max:255',
            'parent_agency_id' => 'nullable|integer',
            'agency_group_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ];
    }
}
