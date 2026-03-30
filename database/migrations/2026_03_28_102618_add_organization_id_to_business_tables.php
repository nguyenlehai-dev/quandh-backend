<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'posts', 'post_categories',
        'documents', 'document_types', 'document_fields', 'document_signers', 'issuing_agencies', 'issuing_levels',
        'm_meetings', 'm_participants', 'm_agendas', 'm_documents', 'm_personal_notes', 'm_speech_requests', 'm_votings', 'm_vote_results', 'm_conclusions', 'm_attendee_groups', 'm_meeting_types'
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