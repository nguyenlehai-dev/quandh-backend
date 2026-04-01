<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm index cho các cột thường được filter/sort để tăng tốc query.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('log_activities', function (Blueprint $table) {
            $table->index('method_type');
            $table->index('status_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('log_activities', function (Blueprint $table) {
            $table->dropIndex(['method_type']);
            $table->dropIndex(['status_code']);
        });
    }
};
