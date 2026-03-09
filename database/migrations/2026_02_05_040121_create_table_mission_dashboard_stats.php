<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_dashboard_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id');
            $table->foreignId('report_period_id');
            $table->unsignedSmallInteger('total_agencies');
            $table->unsignedSmallInteger('reported_count');
            $table->unsignedSmallInteger('completed_count');
            $table->unsignedSmallInteger('on_time_count');
            $table->timestamps();
            $table->unique(
                ['mission_id', 'report_period_id'],
                'uq_mission_dashboard_stats'
            );
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('mission_dashboard_stats');
    }
};
