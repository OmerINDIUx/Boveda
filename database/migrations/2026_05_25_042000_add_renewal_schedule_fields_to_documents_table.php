<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedTinyInteger('renewal_weekday')->nullable()->after('renewal_frequency');
            $table->unsignedTinyInteger('renewal_month_day')->nullable()->after('renewal_weekday');
            $table->unsignedTinyInteger('renewal_month')->nullable()->after('renewal_month_day');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['renewal_weekday', 'renewal_month_day', 'renewal_month']);
        });
    }
};
