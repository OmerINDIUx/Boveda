<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('client_name')->nullable()->after('description');
            $table->string('construction_location')->nullable()->after('client_name');
            $table->foreignId('owner_user_id')->nullable()->after('construction_location')->constrained('users')->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->after('owner_user_id')->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable()->after('manager_user_id');
            $table->date('target_date')->nullable()->after('start_date');
            $table->string('contract_number', 100)->nullable()->after('target_date');
            $table->string('project_stage')->nullable()->after('contract_number');
            $table->string('priority_level')->nullable()->after('project_stage');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_user_id');
            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropColumn([
                'client_name',
                'construction_location',
                'start_date',
                'target_date',
                'contract_number',
                'project_stage',
                'priority_level',
            ]);
        });
    }
};
