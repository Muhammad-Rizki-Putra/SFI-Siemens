<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $productionDept = Department::firstOrCreate(['name' => 'Production Line A']);
        $maintenanceDept = Department::firstOrCreate(['name' => 'Maintenance & Engineering']);
        $spsDept = Department::firstOrCreate(['name' => 'SPS (Siemens Production System)']);

        User::updateOrCreate(
            ['email' => 'admin@siemens.test'], 
            [
                'name' => 'SPS Admin (Jeanne)',
                'password' => Hash::make('password123'),
                'department_id' => $spsDept->id,
                'role' => 'sps',
                'entra_id' => 'ENTRA-SPS-001',
                'supervisor_email' => 'management@siemens.test',
            ]
        );

        User::updateOrCreate(
            ['email' => 'tech@siemens.test'],
            [
                'name' => 'Tech Reviewer (Rizki)',
                'password' => Hash::make('password123'),
                'department_id' => $maintenanceDept->id,
                'role' => 'technical_reviewer',
                'entra_id' => 'ENTRA-TECH-001',
                'supervisor_email' => 'admin@siemens.test',
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@siemens.test'],
            [
                'name' => 'Line Operator',
                'password' => Hash::make('password123'),
                'department_id' => $productionDept->id,
                'role' => 'user',
                'entra_id' => 'ENTRA-OP-001',
                'supervisor_email' => 'admin@siemens.test',
            ]
        );
    }
}