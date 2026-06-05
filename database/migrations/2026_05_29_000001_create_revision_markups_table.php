<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_markups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_revision_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedInteger('page_number')->default(1);
            $table->string('tool', 40);
            $table->string('label')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('x_percent', 6, 3)->nullable();
            $table->decimal('y_percent', 6, 3)->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('stroke_width')->nullable();
            $table->string('snapshot_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_markups');
    }
};
