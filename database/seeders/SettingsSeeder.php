<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // General Settings

            // Notification Settings
            [
                'key' => 'email_notifications_enabled',
                'value' => true,
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable email notifications'
            ],
            [
                'key' => 'whatsapp_notifications_enabled',
                'value' => false,
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable WhatsApp notifications'
            ],
            [
                'key' => 'daily_stock_summary',
                'value' => true,
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable daily stock summary emails'
            ]
        ];

        foreach ($defaultSettings as $setting) {
            Settings::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
