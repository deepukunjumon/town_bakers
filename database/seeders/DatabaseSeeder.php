<?php

namespace Database\Seeders;

use App\Models\Designations;
use App\Models\Settings;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Users
        User::create([
            'username' => 'superadmin',
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make(DEFAULT_PASSWORD),
            'role' => ROLES['super_admin'],
            'status' => DEFAULT_STATUSES['active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Designations
        Designations::create(['designation' => 'Sales Manager']);
        Designations::create(['designation' => 'Salesman']);

        // Settings
        Settings::create(['key' => 'email_notifications_enabled', 'value' => true, 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Enable email notifications']);
        Settings::create(['key' => 'whatsapp_notifications_enabled', 'value' => true, 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Enable whatsapp notifications']);
        Settings::create(['key' => 'daily_stock_summary', 'value' => true, 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Enable daily stock summary emails']);
    }
}
