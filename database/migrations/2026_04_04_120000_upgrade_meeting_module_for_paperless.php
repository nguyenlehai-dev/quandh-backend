<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_meeting_types')) {
            Schema::create('m_meeting_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('m_document_types')) {
            Schema::create('m_document_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('meeting_type_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('m_document_fields')) {
            Schema::create('m_document_fields', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('m_checkins')) {
            Schema::create('m_checkins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
                $table->foreignId('meeting_participant_id')->constrained('m_participants')->cascadeOnDelete();
                $table->string('type', 20)->default('manual');
                $table->unsignedBigInteger('checked_in_by')->nullable();
                $table->dateTime('checked_in_at');
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('m_reminders')) {
            Schema::create('m_reminders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
                $table->string('channel', 50);
                $table->dateTime('remind_at');
                $table->string('status', 50)->default('pending');
                $table->json('payload')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        $this->upgradeMeetingsTable();
        $this->upgradeParticipantsTable();
        $this->upgradeAgendasTable();
        $this->upgradeDocumentsTable();
        $this->upgradePersonalNotesTable();
        $this->upgradeSpeechRequestsTable();
        $this->upgradeVotingsTable();
        $this->upgradeVoteResultsTable();
        $this->upgradeConclusionsTable();

        $defaultOrganizationId = DB::table('organizations')->orderBy('id')->value('id');
        if ($defaultOrganizationId) {
            foreach ([
                'm_meeting_types',
                'm_document_types',
                'm_document_fields',
                'm_meetings',
                'm_participants',
                'm_agendas',
                'm_documents',
                'm_personal_notes',
                'm_speech_requests',
                'm_votings',
                'm_vote_results',
                'm_conclusions',
                'm_checkins',
                'm_reminders',
            ] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id')) {
                    DB::table($table)->whereNull('organization_id')->update(['organization_id' => $defaultOrganizationId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_reminders');
        Schema::dropIfExists('m_checkins');
        Schema::dropIfExists('m_document_fields');
        Schema::dropIfExists('m_document_types');
        Schema::dropIfExists('m_meeting_types');
    }

    private function upgradeMeetingsTable(): void
    {
        Schema::table('m_meetings', function (Blueprint $table) {
            if (! Schema::hasColumn('m_meetings', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_meetings', 'meeting_type_id')) {
                $table->unsignedBigInteger('meeting_type_id')->nullable()->after('organization_id')->index();
            }
            if (! Schema::hasColumn('m_meetings', 'code')) {
                $table->string('code')->nullable()->after('meeting_type_id');
            }
            if (! Schema::hasColumn('m_meetings', 'active_agenda_id')) {
                $table->unsignedBigInteger('active_agenda_id')->nullable()->after('status')->index();
            }
            if (! Schema::hasColumn('m_meetings', 'qr_token')) {
                $table->string('qr_token')->nullable()->after('active_agenda_id')->index();
            }
            if (! Schema::hasColumn('m_meetings', 'checkin_opened_at')) {
                $table->dateTime('checkin_opened_at')->nullable()->after('qr_token');
            }
        });
    }

    private function upgradeParticipantsTable(): void
    {
        Schema::table('m_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('m_participants', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_participants', 'delegated_to_id')) {
                $table->unsignedBigInteger('delegated_to_id')->nullable()->after('absence_reason')->index();
            }
            if (! Schema::hasColumn('m_participants', 'is_guest')) {
                $table->boolean('is_guest')->default(false)->after('delegated_to_id');
            }
            if (! Schema::hasColumn('m_participants', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_guest');
            }
            if (! Schema::hasColumn('m_participants', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    private function upgradeAgendasTable(): void
    {
        Schema::table('m_agendas', function (Blueprint $table) {
            if (! Schema::hasColumn('m_agendas', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_agendas', 'presenter_id')) {
                $table->unsignedBigInteger('presenter_id')->nullable()->after('duration')->index();
            }
            if (! Schema::hasColumn('m_agendas', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('presenter_id');
            }
            if (! Schema::hasColumn('m_agendas', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('m_agendas', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    private function upgradeDocumentsTable(): void
    {
        Schema::table('m_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('m_documents', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_documents', 'meeting_agenda_id')) {
                $table->unsignedBigInteger('meeting_agenda_id')->nullable()->after('meeting_id')->index();
            }
            if (! Schema::hasColumn('m_documents', 'document_type_id')) {
                $table->unsignedBigInteger('document_type_id')->nullable()->after('meeting_agenda_id')->index();
            }
            if (! Schema::hasColumn('m_documents', 'document_field_id')) {
                $table->unsignedBigInteger('document_field_id')->nullable()->after('document_type_id')->index();
            }
            if (! Schema::hasColumn('m_documents', 'status')) {
                $table->string('status')->default('active')->after('description');
            }
        });
    }

    private function upgradePersonalNotesTable(): void
    {
        Schema::table('m_personal_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('m_personal_notes', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_personal_notes', 'last_synced_at')) {
                $table->dateTime('last_synced_at')->nullable()->after('content');
            }
        });
    }

    private function upgradeSpeechRequestsTable(): void
    {
        Schema::table('m_speech_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('m_speech_requests', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_speech_requests', 'meeting_id')) {
                $table->unsignedBigInteger('meeting_id')->nullable()->after('organization_id')->index();
            }
            if (! Schema::hasColumn('m_speech_requests', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('m_speech_requests', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('m_speech_requests', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('approved_at');
            }
        });

        DB::table('m_speech_requests')
            ->whereNull('meeting_id')
            ->orderBy('id')
            ->get(['id', 'meeting_participant_id'])
            ->each(function ($row) {
                $meetingId = DB::table('m_participants')
                    ->where('id', $row->meeting_participant_id)
                    ->value('meeting_id');

                if ($meetingId) {
                    DB::table('m_speech_requests')
                        ->where('id', $row->id)
                        ->update(['meeting_id' => $meetingId]);
                }
            });
    }

    private function upgradeVotingsTable(): void
    {
        Schema::table('m_votings', function (Blueprint $table) {
            if (! Schema::hasColumn('m_votings', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_votings', 'opened_at')) {
                $table->dateTime('opened_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('m_votings', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('opened_at');
            }
            if (! Schema::hasColumn('m_votings', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('closed_at');
            }
            if (! Schema::hasColumn('m_votings', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    private function upgradeVoteResultsTable(): void
    {
        Schema::table('m_vote_results', function (Blueprint $table) {
            if (! Schema::hasColumn('m_vote_results', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
        });
    }

    private function upgradeConclusionsTable(): void
    {
        Schema::table('m_conclusions', function (Blueprint $table) {
            if (! Schema::hasColumn('m_conclusions', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('m_conclusions', 'status')) {
                $table->string('status')->default('active')->after('content');
            }
        });
    }
};
