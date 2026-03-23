<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng cuộc họp chính
        Schema::create('m_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->string('status')->default('draft'); // draft, active, in_progress, completed
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Bảng pivot mở rộng: người tham gia cuộc họp
        Schema::create('m_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('position')->nullable(); // Chức vụ trong cuộc họp (vd: Giám đốc)
            $table->string('meeting_role', 50)->default('delegate'); // chair, secretary, delegate
            $table->string('attendance_status', 50)->default('pending'); // pending, present, absent
            $table->dateTime('checkin_at')->nullable();
            $table->text('absence_reason')->nullable();
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
        });

        // Bảng chương trình nghị sự
        Schema::create('m_agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->unsignedInteger('duration')->nullable(); // Thời lượng (phút)
            $table->timestamps();
        });

        // Bảng tài liệu cuộc họp (file đính kèm qua Spatie MediaLibrary)
        Schema::create('m_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // Bảng ghi chú cá nhân (cô lập theo user_id)
        Schema::create('m_personal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('meeting_document_id')->nullable()->constrained('m_documents')->nullOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        // Bảng đăng ký phát biểu
        Schema::create('m_speech_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_participant_id')->constrained('m_participants')->cascadeOnDelete();
            $table->foreignId('meeting_agenda_id')->nullable()->constrained('m_agendas')->nullOnDelete();
            $table->text('content')->nullable();
            $table->string('status', 50)->default('pending'); // pending, approved, rejected
            $table->timestamps();
        });

        // Bảng phiên biểu quyết
        Schema::create('m_votings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('meeting_agenda_id')->nullable()->constrained('m_agendas')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 50)->default('public'); // public, anonymous
            $table->string('status', 50)->default('pending'); // pending, open, closed
            $table->timestamps();
        });

        // Bảng kết quả bỏ phiếu
        Schema::create('m_vote_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_voting_id')->constrained('m_votings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('choice', 50); // agree, disagree, abstain
            $table->timestamps();

            $table->unique(['meeting_voting_id', 'user_id']); // Mỗi user chỉ bỏ 1 phiếu
        });

        // Bảng kết luận cuộc họp (1:N với meeting)
        Schema::create('m_conclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('m_meetings')->cascadeOnDelete();
            $table->foreignId('meeting_agenda_id')->nullable()->constrained('m_agendas')->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_conclusions');
        Schema::dropIfExists('m_vote_results');
        Schema::dropIfExists('m_votings');
        Schema::dropIfExists('m_speech_requests');
        Schema::dropIfExists('m_personal_notes');
        Schema::dropIfExists('m_documents');
        Schema::dropIfExists('m_agendas');
        Schema::dropIfExists('m_participants');
        Schema::dropIfExists('m_meetings');
    }
};
