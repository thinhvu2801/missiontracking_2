<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('mission_name', 1000)->change();
        });
        Schema::table('mission_reports', function (Blueprint $table) {
            $table->decimal('progress_percent', 10, 2);
            $table->text('recommendation')->nullable()->after('progress_percent');
        });
        Schema::create('delay_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason_code', 50)->unique()->nullable();
            $table->string('reason_name', 255);
            $table->timestamps();
        });
        Schema::create('mission_report_delay_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_report_id');
            $table->foreignId('delay_reason_id');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(
                ['mission_report_id', 'delay_reason_id'],
                'uq_report_delay_reason'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_report_delay_reasons');
        Schema::dropIfExists('delay_reasons');
        Schema::table('mission_reports', function (Blueprint $table) {
            $table->dropColumn([
                'progress_percent',
                'recommendation',
            ]);
        });
        Schema::table('missions', function (Blueprint $table) {
            $table->string('mission_name', 500)->change();
        });
    }
};
