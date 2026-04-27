<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prefix'); // Ej: CIV, ARQ, MEC
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('discipline_id')->constrained();
            $table->string('document_number')->unique(); // Ej: PROJ-CIV-DWG-001
            $table->string('title');
            $table->string('status')->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('file_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('revision_code'); // Ej: A, B, 0, 1
            $table->string('status'); // Ej: Draft, For Review, Approved, Issued for Construction
            $table->string('file_path');
            $table->string('original_name');
            $table->string('extension');
            $table->bigInteger('size');
            $table->foreignId('user_id')->nullable();
            $table->text('change_notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_revisions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('disciplines');
    }
};
