<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@jobboard.com',
            'password' => Hash::make('password'),
            'description' => 'System administrator with full access to the job board.',
            'is_admin' => true,
            'is_employer' => false,
        ]);

        // Create employer user
        User::create([
            'name' => 'Employer User',
            'email' => 'employer@company.com',
            'password' => Hash::make('password'),
            'description' => 'HR manager at a tech company looking for talented developers.',
            'is_admin' => false,
            'is_employer' => true,
        ]);

        // Create regular user
        User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'description' => 'Software developer looking for new opportunities.',
            'is_admin' => false,
            'is_employer' => false,
        ]);
    }
}