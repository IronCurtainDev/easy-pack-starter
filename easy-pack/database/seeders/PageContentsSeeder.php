<?php

namespace EasyPack\Database\Seeders;

use Illuminate\Database\Seeder;
use EasyPack\Models\PageContent;

class PageContentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => PageContent::getDefaultContent('privacy-policy'),
                'is_active' => true,
            ],
            [
                'slug' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'content' => PageContent::getDefaultContent('terms-conditions'),
                'is_active' => true,
            ],
        ];

        foreach ($pages as $page) {
            PageContent::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
