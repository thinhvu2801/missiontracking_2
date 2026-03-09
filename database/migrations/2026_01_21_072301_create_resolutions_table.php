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
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('resolution_code', 50)->unique();
            $table->string('resolution_name', 500);
            $table->date('issued_date');
            $table->timestamps();
        });
        Schema::create('resolution_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id');
            $table->string('unit_type');
            $table->string('period_type');
            $table->timestamps();
            $table->unique(
                ['resolution_id', 'unit_type', 'period_type'],
                'uq_resolution_unit_period'
            );
        });
        Schema::create('report_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_type');
            $table->integer('report_year');
            $table->integer('period_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(
                ['period_type', 'report_year', 'period_number'],
                'uq_report_period'
            );
            $table->index(['report_year']);
            $table->index(['period_type']);
        });        
    }

    public function down(): void
    {
        Schema::dropIfExists('report_periods');
        Schema::dropIfExists('resolution_reports');
        Schema::dropIfExists('resolutions');
    }
};
