<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Recipe::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(3);
        
        // 确保有一个可用的分类
        $category = RecipeCategory::first() ?? RecipeCategory::create([
            'name' => 'Test Category'
        ]);

        return [
            'user_id' => User::factory(),
            'category_id' => $category->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'difficulty' => $this->faker->randomElement(['Easy', 'Medium', 'Hard']),
            'prep_time' => $this->faker->numberBetween(5, 30),
            'cook_time' => $this->faker->numberBetween(15, 120),
            'cover_image' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
