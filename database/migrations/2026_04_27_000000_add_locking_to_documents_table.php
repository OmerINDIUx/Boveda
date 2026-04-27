<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $blueprint) {
            $blueprint->boolean('is_locked')->default(false);
            $blueprint->string('approval_status')->default('DRAFT'); // DRAFT, REVIEW, APPROVED, REJECTED
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['is_locked', 'approval_status']);
        });
    }
};
