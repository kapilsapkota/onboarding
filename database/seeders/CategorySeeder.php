<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'  => 'Web Design & Development',
                'color' => '#4ECDC4',
            ],
            [
                'name'  => 'Logo Design & Branding',
                'color' => '#FF6B6B',
            ],
            [
                'name'  => 'Managed IT',
                'color' => '#45B7D1',
            ],
            [
                'name'  => 'Online Marketing',
                'color' => '#F7B731',
            ],
            [
                'name'  => 'AI Development',
                'color' => '#6C5CE7',
            ],
            [
                'name'  => 'Printing Solutions',
                'color' => '#2ECC71',
            ],
            [
                'name'  => 'Videography & Photography',
                'color' => '#E84393',
            ],
            [
                'name'  => 'Telephony & Internet',
                'color' => '#0984E3',
            ],
            [
                'name'  => 'Web Hosting',
                'color' => '#A29BFE',
            ],
            [
                'name'  => 'Software & Licences',
                'color' => '#FD79A8',
            ],
        ];

        foreach ($categories as $index => $category) {
            $data = [
                'name'       => $category['name'],
                'slug'       => Str::slug($category['name']),
                'color'      => $category['color'],
                'sort_order' => $index + 1,
                'is_active'  => true,
            ];

            Category::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
