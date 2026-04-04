<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_attendee_groups')) {
            Schema::create('m_attendee_groups', function (Blueprint $table) {
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

        if (! Schema::hasTable('m_attendee_group_members')) {
            Schema::create('m_attendee_group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attendee_group_id')->constrained('m_attendee_groups')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('position')->nullable();
                $table->timestamps();
                $table->unique(['attendee_group_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_attendee_group_members');
        Schema::dropIfExists('m_attendee_groups');
    }
};
