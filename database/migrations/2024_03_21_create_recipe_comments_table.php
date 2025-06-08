<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipe_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('recipe_comments')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
            
            // 添加索引
            $table->index(['recipe_id', 'created_at']);
            $table->index('user_id');
            $table->index('parent_id');
        });

        // Insert fake comments for recipe ID 20
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_comments');
    }
}; 