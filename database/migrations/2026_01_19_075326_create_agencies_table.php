<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('agency_name');
            $table->foreignId('parent_agency_id')->nullable();
            $table->foreignId('agency_group_id');
            $table->timestamps();
            $table->index('parent_agency_id');
            $table->index('agency_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
        Schema::dropIfExists('agency_groups');
    }
};
