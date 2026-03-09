<?php

namespace App\Http\Requests\Mission;

use App\Models\Mission\MissionAgency;
use App\Models\Mission\MissionReport;
use Illuminate\Foundation\Http\FormRequest;

class MissionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_period_id' => ['required', 'exists:report_periods,id'],
            'status' => ['required', 'boolean'],
            'progress_percent' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'execution_result' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'delay_reasons'   => ['nullable', 'array'],
            'delay_reasons.*' => ['exists:delay_reasons,id'],
            'delay_reason_other_description' => ['nullable', 'string'],
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $user = auth()->user();

            if (!$user) return;

            // ===== Xác định mission agency =====
            $missionId = $this->route('mission')?->id;

            if (!$missionId) return;

            $missionAgency = MissionAgency::where('mission_id', $missionId)
                ->where('agency_id', $user->agency_id)
                ->first();

            if (!$missionAgency) return;

            $currentPeriodId = (int) $this->input('report_period_id');
            $currentProgress = (float) $this->input('progress_percent');

            // ===== Lấy báo cáo kỳ trước =====
            $prevReport = MissionReport::where('mission_agency_id', $missionAgency->id)
                ->where('report_period_id', '<', $currentPeriodId)
                ->orderByDesc('report_period_id')
                ->first();

            // ===== Kiểm tra đã có kỳ sau chưa =====
            $hasNextReport = MissionReport::where('mission_agency_id', $missionAgency->id)
                ->where('report_period_id', '>', $currentPeriodId)
                ->exists();

            /**
             * LUẬT:
             * - Nếu đã có kỳ sau
             * - và kỳ trước là 100
             * - thì không được giảm xuống < 100
             */
            if (
                $hasNextReport &&
                $prevReport &&
                $prevReport->progress_percent == 100 &&
                $currentProgress < 100
            ) {
                $validator->errors()->add(
                    'progress_percent',
                    'Không thể giảm tiến độ vì đã có báo cáo ở kỳ sau'
                );
            }

            /**
             * LUẬT PHỤ:
             * - Nếu chưa có kỳ sau
             * - thì progress hiện tại không được < kỳ trước
             */
            if (
                !$hasNextReport &&
                $prevReport &&
                $currentProgress < $prevReport->progress_percent
            ) {
                $validator->errors()->add(
                    'progress_percent',
                    'Tiến độ kỳ này không được nhỏ hơn kỳ trước (' .
                    $prevReport->progress_percent . '%)'
                );
            }
        });
    }    
}
