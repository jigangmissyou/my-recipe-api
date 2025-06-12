<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeCommentSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create three users
        $users = collect();
        for ($i = 1; $i <= 3; $i++) {
            $email = "user{$i}@example.com";
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'nickname' => "User {$i}",
                    'password' => bcrypt('password'),
                    'email' => $email
                ]
            );
            $users->push($user);
        }
        
        // Ensure we have a recipe category
        $categoryId = DB::table('recipe_categories')->insertGetId([
            'name' => 'Test Category',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Ensure we have a recipe
        $recipe = Recipe::firstOrCreate(
            ['slug' => 'test-recipe'],
            [
                'user_id' => $users[0]->id,
                'category_id' => $categoryId,
                'name' => 'Test Recipe',
                'description' => 'A test recipe for comments',
                'difficulty' => 'Medium',
                'prep_time' => 15,
                'cook_time' => 30,
                'slug' => 'test-recipe',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Insert comments
        DB::table('recipe_comments')->insert([
            [
                'recipe_id' => $recipe->id,
                'user_id' => $users[0]->id,
                'content' => 'This is a great recipe!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipe_id' => $recipe->id,
                'user_id' => $users[1]->id,
                'content' => 'I love the ingredients used here.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'recipe_id' => $recipe->id,
                'user_id' => $users[2]->id,
                'content' => 'Can\'t wait to try this!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 