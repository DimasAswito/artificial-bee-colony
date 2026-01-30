<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Kampus',
            'email' => 'polije@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => '00',
            'created_at' => now(),
        ]);
        User::create([
            'name' => 'Admin Kampus 2',
            'email' => 'polije2@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => '00',
            'created_at' => now(),
        ]);
        User::create([
            'name' => 'Admin Kampus 3',
            'email' => 'polije3@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => '00',
            'created_at' => now(),
        ]);
    }
}
