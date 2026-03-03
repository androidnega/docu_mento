<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedAdminAccount extends Command
{
    protected $signature = 'staff:seed-admin';

    protected $description = 'Create or update the super admin account (username: admin, password: admin123).';

    public function handle(): int
    {
        $user = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'role' => User::ROLE_SUPER_ADMIN,
                'password' => Hash::make('admin123'),
            ]
        );

        $this->info('Super admin account created/updated.');
        $this->line('Username: admin');
        $this->line('Password: admin123');
        $this->newLine();
        $this->line('Log in at: ' . route('login'));

        return self::SUCCESS;
    }
}
