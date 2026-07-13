<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        // Category: Website Design & Development
        // ------------------------------------------------------------------
        $webCategory = Category::firstOrCreate(
            ['slug' => 'website-design-development'],
            [
                'name'       => 'Website Design & Development',
                'icon'       => 'globe-alt',
                'color'      => 'blue',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );

        // ------------------------------------------------------------------
        // Product 1: Service Page Website
        // ------------------------------------------------------------------
        Product::firstOrCreate(
            ['category_id' => $webCategory->id, 'name' => 'Website Design & Development — Service Page'],
            [
                'short_name'        => 'Service Page Website',
                'description'       => 'A professional, mobile-responsive WordPress service-page website tailored to your brand.',
                'scope_items'       => [
                    'WordPress website',
                    'Customised template design',
                    'Up to 25 standard modules/plugins',
                    'Mobile responsive design',
                    'Up to 25 pages',
                    'Multiple contact forms',
                    'Images provided by client',
                    'Shutterstock images included',
                    'Social media integration',
                    'Google Analytics integration',
                    'Hours of website development (based on selected price)',
                    'Existing online ordering link integration',
                    'Facebook Live Chat',
                    'SEO ready',
                    'Website training & documentation',
                ],
                'key_scope_keyword' => 'WordPress • Responsive • SEO Ready',
                'price_type'        => 'dropdown',
                'price_min'         => 3000.00,
                'price_max'         => 9000.00,
                'price_increment'   => 500.00,
                'hourly_rate'       => 90.00,
                'frequency'         => 'once_off',
                'notes'             => 'Hours of development are calculated at $90 ex-GST per hour based on the selected price.',
                'is_active'         => true,
                'sort_order'        => 1,
            ]
        );

        // ------------------------------------------------------------------
        // Product 2: Ecommerce Website
        // ------------------------------------------------------------------
        Product::firstOrCreate(
            ['category_id' => $webCategory->id, 'name' => 'Website Design & Development — Ecommerce'],
            [
                'short_name'        => 'Ecommerce Website',
                'description'       => 'A full-featured ecommerce store built on WordPress/WooCommerce, tailored to sell your products online.',
                'scope_items'       => [
                    'WordPress / WooCommerce website',
                    'Customised template design',
                    'Up to 25 standard modules/plugins',
                    'Mobile responsive design',
                    'Up to 25 pages',
                    'Product catalogue setup',
                    'Shopping cart & checkout',
                    'Payment gateway integration',
                    'Multiple contact forms',
                    'Images provided by client',
                    'Shutterstock images included',
                    'Social media integration',
                    'Google Analytics integration',
                    'Hours of website development (based on selected price)',
                    'SEO ready',
                    'Website training & documentation',
                ],
                'key_scope_keyword' => 'WooCommerce • Shopping Cart • Payment Gateway',
                'price_type'        => 'dropdown',
                'price_min'         => 3000.00,
                'price_max'         => 9000.00,
                'price_increment'   => 500.00,
                'hourly_rate'       => 90.00,
                'frequency'         => 'once_off',
                'notes'             => 'Hours of development are calculated at $90 ex-GST per hour based on the selected price.',
                'is_active'         => true,
                'sort_order'        => 2,
            ]
        );

        // ------------------------------------------------------------------
        // Product 3: Landing Page
        // ------------------------------------------------------------------
        Product::firstOrCreate(
            ['category_id' => $webCategory->id, 'name' => 'Website Design & Development — Landing Page'],
            [
                'short_name'        => 'Landing Page',
                'description'       => 'A high-converting single-page website to capture leads or promote a campaign.',
                'scope_items'       => [
                    'Single-page WordPress website',
                    'Customised template design',
                    'Mobile responsive design',
                    'Hero section with call-to-action',
                    'Lead capture / contact form',
                    'Images provided by client',
                    'Shutterstock images included',
                    'Social media integration',
                    'Google Analytics integration',
                    'SEO ready',
                ],
                'key_scope_keyword' => 'Landing Page • Lead Capture • CTA',
                'price_type'        => 'fixed',
                'fixed_price'       => 1500.00,
                'hourly_rate'       => 90.00,
                'frequency'         => 'once_off',
                'notes'             => null,
                'is_active'         => true,
                'sort_order'        => 3,
            ]
        );

        $this->command->info('Product seeder completed — Website Design & Development products seeded.');
    }
}
