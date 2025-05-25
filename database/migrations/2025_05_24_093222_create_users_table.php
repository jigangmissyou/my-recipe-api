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
        // Create the users table with the specified columns and constraints
        DB::statement("
            CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nickname VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NULL,
                phone VARCHAR(20) UNIQUE NULL,
                avatar VARCHAR(255) NULL,
                password VARCHAR(255) NOT NULL,
                bio TEXT NULL,
                provider VARCHAR(255) NULL,
                provider_id VARCHAR(255) NULL,
                email_verified_at TIMESTAMP NULL,
                remember_token VARCHAR(100) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (provider, provider_id)
            );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};