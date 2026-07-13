<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(Config $config): void
    {
        $password = $config->get('admin.user.password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('ADMIN_USER_PASSWORD must be set before seeding the admin user.');
        }

        User::query()->updateOrCreate(
            ['email' => $config->get('admin.user.email')],
            [
                'name' => $config->get('admin.user.name'),
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );
    }
}
