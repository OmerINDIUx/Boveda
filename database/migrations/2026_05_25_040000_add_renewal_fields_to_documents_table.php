<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('is_renewable')->default(false)->after('confidentiality_level');
            $table->date('renewal_due_date')->nullable()->after('is_renewable');
            $table->text('renewal_notes')->nullable()->after('renewal_due_date');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['is_renewable', 'renewal_due_date', 'renewal_notes']);
        });
    }
};
