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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_revision_id')->constrained()->onDelete('cascade');
            $table->foreignId('approval_workflow_id')->constrained()->onDelete('cascade');
            $table->foreignId('current_step_id')->nullable()->constrained('approval_steps')->onDelete('set null');
            $table->enum('status', ['en_revision', 'aprobado', 'aprobado_comentarios', 'rechazado'])->default('en_revision');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
