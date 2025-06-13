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
    public function it_validates_required_fields_when_creating_recipe()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'description', 'difficulty', 'prep_time', 'cook_time']);
    }

    /** @test */
    public function it_validates_difficulty_enum_when_creating_recipe()
    {
        $recipeData = [
            'category_id' => $this->category->id,
            'name' => 'Test Recipe',
            'description' => 'Test Description',
            'difficulty' => 'Invalid',
            'prep_time' => '10',
            'cook_time' => '20'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $recipeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['difficulty']);
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

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'user',
                'category',
                'tags',
                'ingredients',
                'steps',
                'favorites_count',
                'comments_count'
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_recipe()
    {
        $response = $this->getJson('/api/v1/recipes/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_add_comment_to_recipe()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);

        $commentData = [
            'content' => 'This is a test comment'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/recipes/{$recipe->id}/comments", $commentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'content',
                'user',
                'created_at'
            ]);

        $this->assertDatabaseHas('recipe_comments', [
            'recipe_id' => $recipe->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment'
        ]);
    }

    /** @test */
    public function it_can_reply_to_comment()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);

        // 创建父评论
        $parentComment = $recipe->comments()->create([
            'user_id' => $this->user->id,
            'content' => 'Parent comment'
        ]);

        $replyData = [
            'content' => 'This is a reply',
            'parent_id' => $parentComment->id
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/recipes/{$recipe->id}/comments", $replyData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'content',
                'user',
                'parent_id',
                'created_at'
            ]);

        $this->assertDatabaseHas('recipe_comments', [
            'recipe_id' => $recipe->id,
            'user_id' => $this->user->id,
            'content' => 'This is a reply',
            'parent_id' => $parentComment->id
        ]);
    }

    /** @test */
    public function it_can_list_recipe_comments()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);

        // 创建一些评论
        $recipe->comments()->createMany([
            [
                'user_id' => $this->user->id,
                'content' => 'First comment'
            ],
            [
                'user_id' => $this->user->id,
                'content' => 'Second comment'
            ]
        ]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'user',
                        'created_at'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_sort_recipes_by_created_at()
    {
        // 创建菜谱，确保创建时间不同
        $recipe1 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);

        $recipe2 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay()
        ]);

        $recipe3 = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        // 测试升序排序
        $response = $this->getJson('/api/v1/recipes?sort_by=created_at&sort_direction=asc');
        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $recipe1->id)
            ->assertJsonPath('data.1.id', $recipe2->id)
            ->assertJsonPath('data.2.id', $recipe3->id);

        // 测试降序排序
        $response = $this->getJson('/api/v1/recipes?sort_by=created_at&sort_direction=desc');
        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $recipe3->id)
            ->assertJsonPath('data.1.id', $recipe2->id)
            ->assertJsonPath('data.2.id', $recipe1->id);
    }

    /** @test */
    public function it_can_paginate_recipes()
    {
        // 创建15个菜谱
        Recipe::factory()
            ->count(15)
            ->create([
                'user_id' => $this->user->id
            ]);

        // 测试第一页
        $response = $this->getJson('/api/v1/recipes?page=1&per_page=10');
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 15);

        // 测试第二页
        $response = $this->getJson('/api/v1/recipes?page=2&per_page=10');
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('current_page', 2);
    }

    /** @test */
    public function it_requires_authentication_for_protected_actions()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);

        // 测试创建菜谱
        $response = $this->postJson('/api/v1/recipes', []);
        $response->assertStatus(401);

        // 测试收藏菜谱
        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/favorite");
        $response->assertStatus(401);

        // 测试添加评论
        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/comments", [
            'content' => 'Test comment'
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_update_recipe()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'difficulty' => 'Medium'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/recipes/{$recipe->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'difficulty' => 'Medium'
            ]);

        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->id,
            'name' => 'Updated Name'
        ]);
    }

    /** @test */
    public function it_can_delete_recipe()
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/recipes/{$recipe->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('recipes', [
            'id' => $recipe->id
        ]);
    }

    /** @test */
    public function it_cannot_update_or_delete_other_users_recipe()
    {
        $otherUser = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'user_id' => $otherUser->id
        ]);

        // 测试更新
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/recipes/{$recipe->id}", [
                'name' => 'Updated Name'
            ]);
        $response->assertStatus(403);

        // 测试删除
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/recipes/{$recipe->id}");
        $response->assertStatus(403);
    }
}
