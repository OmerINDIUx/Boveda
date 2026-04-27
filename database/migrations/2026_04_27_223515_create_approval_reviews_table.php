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
        Schema::create('approval_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('approval_step_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['aprobado', 'aprobado_comentarios', 'rechazado']);
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_reviews');
    }
};
