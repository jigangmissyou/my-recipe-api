<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeCommentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('recipe_comments')->insert([
            [
                'recipe_id' => 20,
                'user_id' => 1,
                'content' => 'This is a great recipe!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipe_id' => 20,
                'user_id' => 2,
                'content' => 'I love the ingredients used here.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipe_id' => 20,
                'user_id' => 3,
                'content' => 'Can\'t wait to try this!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 