<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('type');
            $table->boolean('is_important')->default(false)->after('is_read');
            $table->timestamp('read_at')->nullable()->after('is_important');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['is_read', 'is_important', 'read_at']);
        });
    }
};
