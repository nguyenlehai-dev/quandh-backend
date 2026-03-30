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
        Schema::table('m_meetings', function (Blueprint $table) {
            $table->unsignedBigInteger('meeting_type_id')->nullable()->after('status');
        });

        Schema::table('m_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('document_type_id')->nullable()->after('meeting_id');
            $table->unsignedBigInteger('document_field_id')->nullable()->after('document_type_id');
            $table->unsignedBigInteger('issuing_agency_id')->nullable()->after('document_field_id');
            $table->unsignedBigInteger('document_signer_id')->nullable()->after('issuing_agency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings_and_documents_tables', function (Blueprint $table) {
            //
        });
    }
};
