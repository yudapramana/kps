<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        

         $roles = [
            ['name' => 'SUPERADMIN',        'slug' => 'superadmin'],
            ['name' => 'ADMIN',             'slug' => 'admin'],
            ['name' => 'COMMITTEE',         'slug' => 'committee'],
            ['name' => 'PARTICIPANT',       'slug' => 'participant'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
