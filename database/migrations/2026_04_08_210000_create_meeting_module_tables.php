<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_meeting_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_attendee_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_type_id')->nullable()->constrained('m_meeting_types')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_attendee_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_group_id')->constrained('m_attendee_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->timestamps();
            $table->unique(['attendee_group_id', 'user_id'], 'm_attendee_group_members_unique');
        });

        Schema::create('m_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_type_id')->nullable()->constrained('m_meeting_types')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_document_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_document_signers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_issuing_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('meeting_type_id')->nullable()->constrained('m_meeting_types')->nullOnDelete();
            $table->string('code')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->uuid('qr_token')->nullable()->unique();
            $table->unsignedBigInteger('active_agenda_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('delegate')->index();
            $table->string('position')->nullable();
            $table->string('status')->default('pending')->index();
            $table->dateTime('checkin_at')->nullable();
            $table->text('absence_reason')->nullable();
            $table->foreignId('delegated_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['meeting_id', 'user_id'], 'm_participants_meeting_user_unique');
        });

        Schema::create('m_agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->foreignId('presenter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });

        Schema::table('m_meetings', function (Blueprint $table) {
            $table->foreign('active_agenda_id')->references('id')->on('m_agendas')->nullOnDelete();
        });

        Schema::create('m_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('m_agendas')->nullOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained('m_document_types')->nullOnDelete();
            $table->foreignId('document_field_id')->nullable()->constrained('m_document_fields')->nullOnDelete();
            $table->foreignId('issuing_agency_id')->nullable()->constrained('m_issuing_agencies')->nullOnDelete();
            $table->foreignId('document_signer_id')->nullable()->constrained('m_document_signers')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_conclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('m_agendas')->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('m_speech_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('m_agendas')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->string('status')->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('m_votings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('m_agendas')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('public')->index();
            $table->string('status')->default('pending')->index();
            $table->json('options')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('m_vote_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voting_id')->constrained('m_votings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('option');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['voting_id', 'user_id'], 'm_vote_results_voting_user_unique');
        });

        Schema::create('m_personal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('m_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('m_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->dateTime('remind_at');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_reminders');
        Schema::dropIfExists('m_personal_notes');
        Schema::dropIfExists('m_vote_results');
        Schema::dropIfExists('m_votings');
        Schema::dropIfExists('m_speech_requests');
        Schema::dropIfExists('m_conclusions');
        Schema::dropIfExists('m_documents');
        Schema::table('m_meetings', function (Blueprint $table) {
            $table->dropForeign(['active_agenda_id']);
        });
        Schema::dropIfExists('m_agendas');
        Schema::dropIfExists('m_participants');
        Schema::dropIfExists('m_meetings');
        Schema::dropIfExists('m_issuing_agencies');
        Schema::dropIfExists('m_document_signers');
        Schema::dropIfExists('m_document_fields');
        Schema::dropIfExists('m_document_types');
        Schema::dropIfExists('m_attendee_group_members');
        Schema::dropIfExists('m_attendee_groups');
        Schema::dropIfExists('m_meeting_types');
    }
};
