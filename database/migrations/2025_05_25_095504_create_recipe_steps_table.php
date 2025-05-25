<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS recipe_steps (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                recipe_id BIGINT UNSIGNED NOT NULL,
                step_order SMALLINT UNSIGNED NOT NULL,
                description TEXT NOT NULL,
                image_url VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (recipe_id) REFERENCES recipes(id),
                INDEX (recipe_id, step_order),
                UNIQUE (recipe_id, step_order)
            );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_steps');
    }
};