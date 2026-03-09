<?php

namespace App\Http\Requests\Indicator;

use App\Enums\IndicatorTypeEnum;
use App\Enums\PeriodTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'indicator_group_id'   => 'required|exists:indicator_groups,id',
            'indicator_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('indicators', 'indicator_code')->ignore($this->input('id')),
            ],
            'indicator_name'       => 'required|string|max:500',
            'unit_of_measure'      => 'nullable|string|max:50',
            'indicator_type'       => 'required|in:' . implode(',', IndicatorTypeEnum::values()),
            'expected_result'      => 'nullable|string|max:200',
            'target_min'           => 'nullable|numeric',
            'target_max'           => 'nullable|numeric',
            'is_target_min_equal'  => 'nullable|boolean',
            'is_target_max_equal'  => 'nullable|boolean',
            'parent_indicator_id'  => 'nullable|exists:indicators,id',
            'period_types'         => ['required', 'array'],
            'period_types.*' => [
                'string',
                Rule::in(PeriodTypeEnum::values()),
            ],
        ];
    }
    public function messages()
    {
        return [
            'period_types.required' => 'Bạn phải chọn ít nhất 1 kỳ báo cáo.',
        ];
    }
}
