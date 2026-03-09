<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_agency', function (Blueprint $table) {
            $table->foreignId('children_agency_id')->nullable()->after('agency_id');
        });
    }

    public function down(): void
    {
        Schema::table('mission_agency', function (Blueprint $table) {
            $table->dropColumn('children_agency_id');
        });
    }
};
