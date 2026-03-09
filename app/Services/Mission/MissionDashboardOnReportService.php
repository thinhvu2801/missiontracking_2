<?php

namespace App\Services\Mission;

use App\Models\Mission\Mission;
use App\Models\Mission\MissionDashboardStat;
use App\Models\Mission\MissionReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MissionDashboardOnReportService
{
    public function execute(MissionReport $report): void
    {
        DB::transaction(function () use ($report) {

            $mission = $report->missionAgency->mission;
            
            app(MissionDashboardCreateService::class)->execute($mission);

            $stat = MissionDashboardStat::where('mission_id', $mission->id)
                ->where('report_period_id', $report->report_period_id)
                ->lockForUpdate()
                ->first();

            if (! $stat) {
                return;
            }

            $deadline = $mission->deadline_date
                ? Carbon::parse($mission->deadline_date)
                : null;

            // 1️⃣ Báo cáo lần đầu
            if ($report->wasRecentlyCreated) {
                $stat->increment('reported_count');

                if ($report->status) {
                    $stat->increment('completed_count');
                }

                // Báo cáo trễ
                if ($deadline && $report->created_at->gt($deadline)) {
                    $stat->decrement('on_time_count');
                }
            }

            // 2️⃣ Update trạng thái (chưa hoàn thành → hoàn thành)
            if ($report->wasChanged('status') && $report->status) {
                $stat->increment('completed_count');
            }
        });
    }
}
