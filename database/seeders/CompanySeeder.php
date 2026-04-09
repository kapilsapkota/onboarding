<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data to seed (only name, slug, and logo)
        $serviceProviders = [
            [
                'name' => 'All in IT Solutions',
                'slug' => 'all-in-it-solutions',
                'logo' => 'All-in-IT-Solutions-Logo-SVG.svg',
            ],
            [
                'name' => 'Fyre Digital',
                'slug' => 'fyre-digital',
                'logo' => 'Asset-4.svg',
            ],
            [
                'name' => 'Ryze IT',
                'slug' => 'ryze-it',
                'logo' => 'Ryze-IT.webp',
            ],
            [
                'name' => 'Five Point Agency',
                'slug' => 'five-point-agency',
                'logo' => 'Five-Point-Agency-Site-Logo.webp',
            ],
            [
                'name' => 'Meerkat Marketing',
                'slug' => 'meerkat-marketing',
                'logo' => 'meerkat-marketing-.webp',
            ],
            [
                'name' => 'WLD Marketing',
                'slug' => 'wld-marketing',
                'logo' => 'edm.webp',
            ],
            [
                'name' => 'Print 360',
                'slug' => 'print-360',
                'logo' => 'Print360.webp',
            ],
            [
                'name' => 'All Business Ads',
                'slug' => 'all-business-ads',
                'logo' => 'Allbusinessads.jpg',
            ],
            [
                'name' => 'Starset Pty Ltd',
                'slug' => 'starset-pty-ltd',
                'logo' => 'Starset.png',
            ],
        ];

        foreach ($serviceProviders as $provider) {
            Company::create($provider);
        }
    }
}
