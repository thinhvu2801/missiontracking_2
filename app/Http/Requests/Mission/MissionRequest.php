<?php

namespace App\Http\Requests\Mission;

use App\Enums\MissionTypeEnum;
use App\Enums\PeriodTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mission_group_id' => 'required|exists:mission_groups,id',

            'mission_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('missions', 'mission_code')
                    ->ignore($this->input('id')),
            ],

            'mission_name' => 'required|string|max:1000',

            'mission_type' => [
                'required',
                'in:' . implode(',', MissionTypeEnum::values()),
            ],

            'expected_result' => 'nullable|string|max:255',

            'deadline_date' => [
                'nullable',
                'date',
                Rule::requiredIf(
                    $this->input('mission_type') == 'time_limited'
                ),
            ],

            'parent_mission_id' => 'nullable|exists:missions,id',

            'period_types' => ['required', 'array'],

            'period_types.*' => [
                'string',
                Rule::in(PeriodTypeEnum::values()),
            ],
            'editable_until' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period_types.required' => 'Bạn phải chọn ít nhất 1 kỳ báo cáo.',
        ];
    }
}
