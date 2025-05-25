<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('recipe_categories')->insert([
            ['name' => 'Main Courses', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desserts', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Breakfast', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Appetizers', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Side Dishes', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Salads', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Soups', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Baking', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Drinks', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sauces & Dips', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}