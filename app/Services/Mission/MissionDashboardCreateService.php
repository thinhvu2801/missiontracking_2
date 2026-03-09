<?php

namespace App\Services\Mission;

use App\Models\Mission\Mission;
use App\Models\Mission\MissionDashboardStat;
use App\Models\ReportPeriod;

class MissionDashboardCreateService
{
    public function execute(Mission $mission): void
    {
        $totalAgencies = $mission->agencies()->count();

        $effectivePeriods = $this->getEffectiveReportPeriods($mission);
        $effectivePeriodIds = $effectivePeriods->pluck('id')->toArray();
        
        MissionDashboardStat::where('mission_id', $mission->id)
            ->whereNotIn('report_period_id', $effectivePeriodIds)
            ->where('reported_count', 0)
            ->where('completed_count', 0)
            ->delete();

        foreach ($effectivePeriods as $period) {
            MissionDashboardStat::firstOrCreate(
                [
                    'mission_id'       => $mission->id,
                    'report_period_id' => $period->id,
                ],
                [
                    'total_agencies'  => $totalAgencies,
                    'reported_count'  => 0,
                    'completed_count' => 0,
                    'on_time_count'   => $totalAgencies,
                ]
            );
        }
    }

    protected function getEffectiveReportPeriods(Mission $mission)
    {
        if ($mission->reportPeriods()->exists()) {
            $periodTypes = $mission->reportPeriods->pluck('period_type');
        } else {
            $periodTypes = $mission->group
                ->resolution
                ->reports
                ->where('unit_type', 'mission')
                ->pluck('period_type');
        }

        return ReportPeriod::whereIn('period_type', $periodTypes)->get();
    }
}
