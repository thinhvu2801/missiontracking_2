<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indicator_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id');
            $table->string('group_code', 50)->nullable();
            $table->string('group_name', 255);
            $table->timestamps();
        });

        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_group_id');
            $table->string('indicator_code', 50)->nullable()->unique();
            $table->string('indicator_name', 500);
            $table->string('unit_of_measure', 50)->nullable();
            $table->string('indicator_type');
            $table->string('expected_result', 200)->nullable();
            $table->decimal('target_min', 10, 2)->nullable();
            $table->decimal('target_max', 10, 2)->nullable();
            $table->boolean('is_target_min_equal')->nullable();
            $table->boolean('is_target_max_equal')->nullable();
            $table->foreignId('parent_indicator_id')->nullable();
            $table->timestamps();
        });

        Schema::create('indicator_agency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id');
            $table->foreignId('agency_id');
            $table->timestamps();
            $table->unique(
                ['indicator_id', 'agency_id'],
                'uq_indicator_assignment'
            );
        });
        
        Schema::create('indicator_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_agency_id');
            $table->foreignId('report_period_id');
            $table->decimal('quantitive_result', 10, 2)->nullable();
            $table->boolean('qualitive_result')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(
                ['indicator_agency_id', 'report_period_id'],
                'uq_indicator_report'
            );
        });

        Schema::create('indicator_report_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id');
            $table->string('period_type');
            $table->timestamps();
            $table->unique(['indicator_id', 'period_type'],'uq_indicator_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_report_periods');
        Schema::dropIfExists('indicator_reports');
        Schema::dropIfExists('indicator_agency');
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('indicator_groups');
    }
};
