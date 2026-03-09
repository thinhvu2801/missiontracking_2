<?php

use App\Models\ReportPeriod;
use App\Models\Resolution\Resolution;
use Carbon\Carbon;

if (! function_exists('menu_active')) {
    function menu_active($routes = [])
    {
        if (empty($routes)) {
            return false;
        }

        foreach ((array) $routes as $route) {
            if (request()->routeIs($route)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('roman')) {
    function roman(int $number): string
    {
        $map = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1,
        ];

        $result = '';

        foreach ($map as $roman => $value) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }
}

if (! function_exists('format_number')) {
    function format_number(?float $value, int $decimal = 2): string
    {
        if ($value === null) {
            return '';
        }

        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0)
            : number_format($value, $decimal);
    }
}

if (! function_exists('default_report_params')) {
    function default_report_params(string $unitType): array
    {
        $now = Carbon::now();

        /* ===== VĂN BẢN MỚI NHẤT ===== */
        $resolution = Resolution::latest('id')->first();
        if (! $resolution) {
            return [];
        }

        /* ===== PERIOD TYPE ƯU TIÊN ===== */
        $priorityTypes = ['week', 'month', 'quarter', 'half_year', 'year'];

        $availableTypes = $resolution->reports
            ->where('unit_type', $unitType)
            ->pluck('period_type')
            ->unique()
            ->values();

        $periodType = collect($priorityTypes)
            ->first(fn ($type) => $availableTypes->contains($type));

        if (! $periodType) {
            return [
                'resolution_id' => $resolution->id,
                'report_year'   => $now->year,
            ];
        }

        /* ===== KỲ CHỨA NGÀY HIỆN TẠI ===== */
        $reportPeriod = ReportPeriod::where('period_type', $periodType)
            ->where('report_year', $now->year)
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->first();

        return array_filter([
            'resolution_id'    => $resolution->id,
            'report_year'      => $now->year,
            'period_type'      => $periodType,
            'report_period_id' => optional($reportPeriod)->id,
        ]);
    }
}

if (! function_exists('latest_report_period')) {
    function latest_report_period(int $id): ?ReportPeriod
    {
        return ReportPeriod::whereIn('id', function ($q) use ($id) {
                $q->select('report_period_id')
                  ->from('mission_reports')
                  ->where('mission_agency_id', $id);
            })
            ->orderByDesc('start_date')
            ->orderByRaw("
                FIELD(period_type, 'week', 'month', 'quarter', 'half_year', 'year')
            ")
            ->first();
    }
}