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
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., 'Contratista', 'Supervisor'
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // specifically who approves
            $table->integer('order'); // 1, 2, 3...
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
