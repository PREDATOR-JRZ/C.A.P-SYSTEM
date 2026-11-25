<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cics.test'],
            ['name' => 'CICS Admin', 'password' => 'ChangeMe123!', 'role' => 'admin']
        );
    }
}
