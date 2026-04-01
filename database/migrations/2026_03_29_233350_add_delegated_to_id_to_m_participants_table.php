<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('m_participants', function (Blueprint $table) {
            $table->unsignedBigInteger('delegated_to_id')->nullable()->after('absence_reason')->comment('ID người được ủy quyền (nếu vắng và ủy quyền)');
            $table->string('attendance_status')->change()->comment('Trạng thái: pending, present, absent, delegated');
            $table->foreign('delegated_to_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_participants', function (Blueprint $table) {
            $table->dropForeign(['delegated_to_id']);
            $table->dropColumn('delegated_to_id');
        });
    }
};
