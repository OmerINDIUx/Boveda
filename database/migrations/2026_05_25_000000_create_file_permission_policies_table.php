<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_permission_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role_name');
            $table->json('permissions');
            $table->string('scope')->default('own');
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_permission_policies');
    }
};
