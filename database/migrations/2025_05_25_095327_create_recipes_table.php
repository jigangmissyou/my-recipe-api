<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('recipe_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->enum('difficulty', ['Easy', 'Medium', 'Hard']);
            $table->integer('prep_time');
            $table->integer('cook_time');
            $table->string('cover_image')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 按照依赖关系的反序删除表
        Schema::dropIfExists('recipe_comments');
        Schema::dropIfExists('recipe_favorites');
        Schema::dropIfExists('recipe_tag_relations');
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};