<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ============================
        // AMBIL ROLE
        // ============================
        $superadminRole = Role::where('name', 'superadmin')->first();
        $adminRole      = Role::where('name', 'admin')->first();
        $committeeRole  = Role::where('name', 'committee')->first();

        // ============================
        // SUPERADMIN
        // ============================
        User::updateOrCreate(
            ['email' => 'superadmin@padangsymcard.com'],
            [
                'name'     => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'role_id'  => $superadminRole->id,
            ]
        );

        // ============================
        // ADMIN
        // ============================
        User::updateOrCreate(
            ['email' => 'admin@padangsymcard.com'],
            [
                'name'     => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role_id'  => $adminRole->id,
            ]
        );

        // ============================
        // COMMITTEE
        // ============================
        User::updateOrCreate(
            ['email' => 'committee@padangsymcard.com'],
            [
                'name'     => 'Committee',
                'username' => 'committee',
                'password' => Hash::make('password'),
                'role_id'  => $committeeRole->id,
            ]
        );
    }
}
