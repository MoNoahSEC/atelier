<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@atelier.com']);
        $admin->name = 'Admin';
        $admin->is_admin = true;
        $admin->password = Hash::make('12345678');
        $admin->save();
    }
}
