<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserSeeder::insert([
            [
                'name' => 'Ali Taufeek',
                'email' => 'alit@allinit.com.au',
                'password' => Hash::make('Style#354'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kapil Sapkota',
                'email' => 'kapils@allinit.com.au',
                'password' => Hash::make('Style#354'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Aminul Islam',
                'email' => 'aminuli@allinit.com.au',
                'password' => Hash::make('Style#354'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'EA - All in IT',
                'email' => 'ea@allinit.com.au',
                'password' => Hash::make('Style#354'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
