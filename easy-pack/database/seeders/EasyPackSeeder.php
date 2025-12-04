<?php

namespace EasyPack\Database\Seeders;

use Illuminate\Database\Seeder;

class EasyPackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            UsersSeeder::class,
        ]);
    }
}
