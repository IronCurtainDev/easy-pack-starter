<?php

namespace EasyPack\Database\Seeders;

use Illuminate\Database\Seeder;
use EasyPack\Models\Setting;
use EasyPack\Models\SettingGroup;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create General Settings Group
        $generalGroup = SettingGroup::firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General Settings',
                'description' => 'General application settings',
                'sort_order' => 1,
            ]
        );

        // Create App Settings Group
        $appGroup = SettingGroup::firstOrCreate(
            ['slug' => 'app'],
            [
                'name' => 'App Settings',
                'description' => 'Mobile app specific settings',
                'sort_order' => 2,
            ]
        );

        // General Settings
        $generalSettings = [
            [
                'setting_key' => 'app_name',
                'setting_value' => config('app.name', 'My App'),
                'setting_data_type' => Setting::DATA_TYPE_STRING,
                'description' => 'Application name',
            ],
            [
                'setting_key' => 'app_description',
                'setting_value' => 'A powerful API-driven application',
                'setting_data_type' => Setting::DATA_TYPE_STRING,
                'description' => 'Application description',
            ],
            [
                'setting_key' => 'support_email',
                'setting_value' => 'support@example.com',
                'setting_data_type' => Setting::DATA_TYPE_STRING,
                'description' => 'Support email address',
            ],
            [
                'setting_key' => 'maintenance_mode',
                'setting_value' => 'false',
                'setting_data_type' => Setting::DATA_TYPE_BOOLEAN,
                'description' => 'Enable maintenance mode',
            ],
        ];

        foreach ($generalSettings as $setting) {
            Setting::firstOrCreate(
                ['setting_key' => $setting['setting_key']],
                array_merge($setting, ['setting_group_id' => $generalGroup->id])
            );
        }

        // App Settings
        $appSettings = [
            [
                'setting_key' => 'ios_app_version',
                'setting_value' => '1.0.0',
                'setting_data_type' => Setting::DATA_TYPE_STRING,
                'description' => 'Current iOS app version',
            ],
            [
                'setting_key' => 'ios_min_version',
                'setting_value' => '1.0.0',
                'setting_data_type' => Setting::DATA_TYPE_STRING,
                'description' => 'Minimum required iOS app version',
            ],
            [
                'setting_key' => 'android_app_version',
                'setting_value' => '1.0.0',
                'setting_data_type' => Setting::DATA_TYPE_STRING,
                'description' => 'Current Android app version',
            ],
            [
                'setting_key' => 'android_min_version',
                'setting_value' => '1.0.0',
                'setting_data_type' => Setting::DATA_TYPE_STRING,
                'description' => 'Minimum required Android app version',
            ],
            [
                'setting_key' => 'force_update',
                'setting_value' => 'false',
                'setting_data_type' => Setting::DATA_TYPE_BOOLEAN,
                'description' => 'Force users to update the app',
            ],
        ];

        foreach ($appSettings as $setting) {
            Setting::firstOrCreate(
                ['setting_key' => $setting['setting_key']],
                array_merge($setting, ['setting_group_id' => $appGroup->id])
            );
        }
    }
}
