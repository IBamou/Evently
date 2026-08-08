<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    /**
     * Seed the six event categories.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Music', 'description' => 'Concerts, festivals, and live music events.'],
            ['name' => 'Business', 'description' => 'Conferences, networking, and business workshops.'],
            ['name' => 'Tech', 'description' => 'Tech talks, hackathons, and developer meetups.'],
            ['name' => 'Art', 'description' => 'Exhibitions, galleries, and creative showcases.'],
            ['name' => 'Sports', 'description' => 'Tournaments, matches, and sporting events.'],
            ['name' => 'Food & Drinks', 'description' => 'Food festivals, tastings, and culinary experiences.'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
            ]);
        }
    }
}
