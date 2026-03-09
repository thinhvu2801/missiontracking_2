<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('created_at')->constrained('users');
            $table->boolean('is_completed')->default(false)->after('deadline_date');
            $table->timestamp('completed_at')->nullable()->after('is_completed');
            $table->timestamp('editable_until')->nullable();
        });

        DB::table('missions')->update([
            'created_by' => 1
        ]);

        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'created_by',
                'is_completed',
                'completed_at',
                'editable_until',
            ]);
        });
    }
};
