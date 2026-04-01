<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->boolean('notify_email')->default(true)->after('current_organization_id');
            $table->boolean('notify_system')->default(true)->after('notify_email');
            $table->boolean('notify_meeting_reminder')->default(true)->after('notify_system');
            $table->boolean('notify_vote')->default(true)->after('notify_meeting_reminder');
            $table->boolean('notify_document')->default(false)->after('notify_vote');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'notify_email',
                'notify_system',
                'notify_meeting_reminder',
                'notify_vote',
                'notify_document',
            ]);
        });
    }
};
