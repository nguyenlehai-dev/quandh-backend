<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột qr_token vào bảng m_meetings
 * Dùng cho chức năng điểm danh bằng QR code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_meetings', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('m_meetings', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};
