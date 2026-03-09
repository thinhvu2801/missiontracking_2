<?php

namespace App\Http\Requests\Resolution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\UnitTypeEnum;
use App\Enums\PeriodTypeEnum;

class ResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('resolutions', 'resolution_code')
                    ->ignore($this->resolution),
            ],
            'resolution_name' => 'required|string|max:500',
            'issued_date'     => 'required|date',

            'report_periods' => ['required', 'array'],

            'report_periods.indicator' => ['required', 'array', 'min:1'],
            'report_periods.mission'   => ['required', 'array', 'min:1'],
            'report_periods.*.*' => [
                'string',
                Rule::in(PeriodTypeEnum::values()),
            ],
        ];
    }
    public function messages()
    {
        return [
            'report_periods.required' => 'Bạn phải chọn kỳ báo cáo.',
            'report_periods.indicator.required' => 'Chỉ tiêu phải chọn ít nhất 1 kỳ báo cáo.',
            'report_periods.indicator.min'      => 'Chỉ tiêu phải chọn ít nhất 1 kỳ báo cáo.',
            'report_periods.mission.required'   => 'Nhiệm vụ phải chọn ít nhất 1 kỳ báo cáo.',
            'report_periods.mission.min'        => 'Nhiệm vụ phải chọn ít nhất 1 kỳ báo cáo.',
        ];
    }
}
