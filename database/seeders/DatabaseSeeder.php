<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Discipline::create(['name' => 'Arquitectura', 'prefix' => 'ARQ']);
        \App\Models\Discipline::create(['name' => 'Civil', 'prefix' => 'CIV']);
        \App\Models\Discipline::create(['name' => 'Mecánica', 'prefix' => 'MEC']);
        \App\Models\Discipline::create(['name' => 'Eléctrica', 'prefix' => 'ELE']);
        \App\Models\Discipline::create(['name' => 'Legal', 'prefix' => 'LEG']);
        \App\Models\Discipline::create(['name' => 'Administración', 'prefix' => 'ADM']);

        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@boveda.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'clearance_level' => 'admin',
        ]);
    }
}
