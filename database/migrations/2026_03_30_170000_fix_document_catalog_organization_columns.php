<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'document_issuing_agencies',
        'document_issuing_levels',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'organization_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropForeign(['organization_id']);
                    $tableBlueprint->dropColumn('organization_id');
                });
            }
        }
    }
};
