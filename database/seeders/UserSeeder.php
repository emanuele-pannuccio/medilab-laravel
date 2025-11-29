<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'email' => "agent@mediclinic.org",
        ],[
            'name' => "AI Agent",
            'password' => Hash::make('12345678'),
            'departmentId' => Department::inRandomOrder()->first()->id
        ]);
        User::firstOrCreate([
            'email' => "pannuccioe31@gmail.com",
        ],[
            'name' => "Emanuele Pannuccio",
            'password' => Hash::make('12345678'),
            'departmentId' => Department::inRandomOrder()->first()->id
        ]);
    }
}
