<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transmittals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique(); // Ej: TRANS-2024-001
            $table->string('subject');
            $table->text('message')->nullable();
            $table->string('sender_name');
            $table->string('recipient_name');
            $table->string('recipient_email');
            $table->string('status')->default('SENT'); // SENT, RECEIVED, ACKNOWLEDGED
            $table->timestamps();
        });

        Schema::create('transmittal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->onDelete('cascade');
            $table->foreignId('file_revision_id')->constrained('file_revisions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transmittal_items');
        Schema::dropIfExists('transmittals');
    }
};
