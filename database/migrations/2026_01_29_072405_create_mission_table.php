<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id');
            $table->string('group_code', 50)->nullable();
            $table->string('group_name', 255);
            $table->timestamps();
        });

        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_group_id');
            $table->string('mission_code', 50)->nullable()->unique();
            $table->string('mission_name', 500);
            $table->string('mission_type', 20);
            $table->string('expected_result', 255)->nullable();
            $table->date('deadline_date')->nullable();
            $table->foreignId('parent_mission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('mission_agency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id');
            $table->foreignId('agency_id');
            $table->timestamps();
            $table->unique(
                ['mission_id', 'agency_id'],
                'uq_mission_assignment'
            );
        });

        Schema::create('mission_report_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id');
            $table->string('period_type'); 
            $table->timestamps();
            $table->unique(
                ['mission_id', 'period_type'],
                'uq_mission_period'
            );
        });

        Schema::create('mission_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_agency_id');
            $table->foreignId('report_period_id');
            $table->boolean('status');
            $table->text('execution_result')->nullable();
            $table->timestamps();
            $table->unique(
                ['mission_agency_id', 'report_period_id'],
                'uq_mission_report'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_reports');
        Schema::dropIfExists('mission_report_periods');
        Schema::dropIfExists('mission_agency');
        Schema::dropIfExists('missions');
        Schema::dropIfExists('mission_groups');
    }
};
