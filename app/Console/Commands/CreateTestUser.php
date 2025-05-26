<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'user:create-test';
    protected $description = 'Create a test user for development';

    public function handle()
    {
        $user = User::where('email', 'test@example.com')->first();

        if ($user) {
            // Update the existing user's password
            $user->password = Hash::make('password');
            $user->save();
            $this->info('Test user password has been reset.');
        } else {
            // Create a new test user
            User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
            ]);
            $this->info('Test user has been created.');
        }

        $this->info('Login credentials:');
        $this->info('Email: test@example.com');
        $this->info('Password: password');
    }
} 