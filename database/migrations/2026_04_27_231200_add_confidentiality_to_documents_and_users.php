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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('clearance_level', ['standard', 'internal', 'manager', 'admin'])->default('standard')->after('email');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->enum('confidentiality_level', ['public', 'internal', 'restricted', 'confidential'])->default('public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('clearance_level');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('confidentiality_level');
        });
    }
};
