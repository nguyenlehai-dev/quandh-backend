<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('view_count')
                ->constrained('organizations')
                ->nullOnDelete();
            $table->index('organization_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('noi_dung')
                ->constrained('organizations')
                ->nullOnDelete();
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
