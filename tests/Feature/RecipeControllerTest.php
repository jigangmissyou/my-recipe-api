<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RecipeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $category;
    protected $tags;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建测试用户
        $this->user = User::factory()->create();
        
        // 创建测试分类
        $this->category = RecipeCategory::create([
            'name' => 'Test Category'
        ]);
        
        // 创建测试标签
        $this->tags = [
            RecipeTag::create(['name' => 'Test Tag 1']),
            RecipeTag::create(['name' => 'Test Tag 2'])
        ];
    }

    /** @test */
    public function it_can_list_recipes()
    {
        // 创建测试菜谱
        $recipes = Recipe::factory()
            ->count(3)
            ->create([
                'user_id' => $this->user->id,
                'category_id' => $this->category->id
            ]);

        // 为每个菜谱添加标签
        foreach ($recipes as $recipe) {
            $recipe->tags()->attach($this->tags[0]->id);
        }

        $response = $this->getJson('/api/v1/recipes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'user',
                        'category',
                        'tags',
                        'ingredients_count',
                        'steps_count',
                        'favorites_count'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
    }

    /** @test */
    public function it_can_filter_recipes_by_category()
    {
        // 创建不同分类的菜谱
        $recipe1 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $recipe2 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => RecipeCategory::create(['name' => 'Other Category'])->id
        ]);

        $response = $this->getJson("/api/v1/recipes?category_id={$this->category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recipe1->id);
    }

    /** @test */
    public function it_can_filter_recipes_by_tag()
    {
        $recipe1 = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);
        $recipe1->tags()->attach($this->tags[0]->id);

        $recipe2 = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);
        $recipe2->tags()->attach($this->tags[1]->id);

        $response = $this->getJson("/api/v1/recipes?tag_id={$this->tags[0]->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recipe1->id);
    }

    /** @test */
    public function it_can_search_recipes()
    {
        $recipe1 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Special Recipe Name'
        ]);

        $recipe2 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Another Recipe'
        ]);

        $response = $this->getJson('/api/v1/recipes?search=Special');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recipe1->id);
    }

    /** @test */
    public function it_can_create_recipe()
    {
        $recipeData = [
            'category_id' => $this->category->id,
            'name' => 'Test Recipe',
            'description' => 'Test Description',
            'difficulty' => 'Easy',
            'prep_time' => '10',
            'cook_time' => '20',
            'ingredients' => [
                [
                    'name' => 'Ingredient 1',
                    'quantity' => '1',
                    'unit' => 'cup'
                ]
            ],
            'steps' => [
                [
                    'step_order' => 1,
                    'description' => 'Step 1'
                ]
            ],
            'tags' => [$this->tags[0]->name]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $recipeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'category',
                'tags',
                'ingredients',
                'steps'
            ]);

        $this->assertDatabaseHas('recipes', [
            'name' => 'Test Recipe',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_toggle_favorite_recipe()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);

        // 测试添加收藏
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/recipes/{$recipe->id}/favorite");

        $response->assertStatus(200)
            ->assertJson([
                'is_favorited' => true
            ]);

        // 测试取消收藏
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/recipes/{$recipe->id}/favorite");

        $response->assertStatus(200)
            ->assertJson([
                'is_favorited' => false
            ]);
    }

    /** @test */
    public function it_can_list_favorite_recipes()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);

        // 添加收藏
        $this->actingAs($this->user)
            ->postJson("/api/v1/recipes/{$recipe->id}/favorite");

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/recipes/favorites');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'user',
                        'category',
                        'tags',
                        'ingredients_count',
                        'steps_count',
                        'favorites_count'
                    ]
                ]
            ])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_get_recipe_details()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);
        $recipe->tags()->attach($this->tags[0]->id);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'user',
                'category',
                'ingredients',
                'steps',
                'tags',
                'favorites_count',
                'is_favorited'
            ]);
    }
}
