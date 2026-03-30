<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Gắn Nhóm người dự họp vào Loại cuộc họp
        Schema::table('m_attendee_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('meeting_type_id')->nullable()->after('id');
            $table->foreign('meeting_type_id')->references('id')->on('m_meeting_types')->nullOnDelete();
        });

        // 2. Bảng thành viên trong nhóm (pivot: nhóm <-> user)
        Schema::create('m_attendee_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_group_id')->constrained('m_attendee_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('position')->nullable(); // Chức vụ trong nhóm
            $table->timestamps();

            $table->unique(['attendee_group_id', 'user_id']);
        });

        // 3. Gắn Loại tài liệu vào Loại cuộc họp (nullable = vẫn dùng được cho module Document chung)
        Schema::table('document_types', function (Blueprint $table) {
            $table->unsignedBigInteger('meeting_type_id')->nullable()->after('id');
            $table->foreign('meeting_type_id')->references('id')->on('m_meeting_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropForeign(['meeting_type_id']);
            $table->dropColumn('meeting_type_id');
        });

        Schema::dropIfExists('m_attendee_group_members');

        Schema::table('m_attendee_groups', function (Blueprint $table) {
            $table->dropForeign(['meeting_type_id']);
            $table->dropColumn('meeting_type_id');
        });
    }
};
