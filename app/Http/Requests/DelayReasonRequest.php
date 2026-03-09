<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DelayReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $delayReasonId = $this->route('delay_reason');
        return [
            'reason_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('delay_reasons', 'reason_code')
                    ->ignore($delayReasonId),
            ],
            'reason_name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
