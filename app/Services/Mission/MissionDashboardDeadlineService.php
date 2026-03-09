<?php

namespace App\Services\Mission;

use App\Models\Mission\Mission;
use App\Models\Mission\MissionAgency;
use App\Models\Mission\MissionDashboardStat;
use Carbon\Carbon;

class MissionDashboardDeadlineService
{
    public function execute(Mission $mission, int $reportPeriodId): void
    {
        if (! $mission->deadline_date) {
            return;
        }

        $deadline = Carbon::parse($mission->deadline_date);

        if (now()->lte($deadline)) {
            return; // chưa quá hạn → khỏi làm gì
        }

        $stat = MissionDashboardStat::where('mission_id', $mission->id)
            ->where('report_period_id', $reportPeriodId)
            ->first();

        if (! $stat) {
            return;
        }

        // Những agency đã báo cáo
        $reportedAgencyIds = MissionAgency::where('mission_id', $mission->id)
            ->whereHas('reports', function ($q) use ($reportPeriodId) {
                $q->where('report_period_id', $reportPeriodId);
            })
            ->pluck('id');

        // Những agency CHƯA báo cáo → trễ hạn
        $lateCount = MissionAgency::where('mission_id', $mission->id)
            ->whereNotIn('id', $reportedAgencyIds)
            ->count();

        if ($lateCount <= 0) {
            return;
        }

        // đảm bảo không âm
        $stat->update([
            'on_time_count' => max(
                0,
                $stat->total_agencies - $lateCount
            )
        ]);
    }
}
