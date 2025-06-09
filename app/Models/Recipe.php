<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'difficulty',
        'prep_time',
        'cook_time',
        'cover_image',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($recipe) {
        $recipe->slug = Str::slug($recipe->name);
        $originalSlug = $recipe->slug;
        $count = 2;
        while (static::where('slug', $recipe->slug)->exists()) {
            $recipe->slug = $originalSlug . '-' . $count;
            $count++;
        }
    });

    static::updating(function ($recipe) {
        $recipe->slug = Str::slug($recipe->name);
        $originalSlug = $recipe->slug;
        $count = 2;
        while (static::where('slug', $recipe->slug)->where('id', '!=', $recipe->id)->exists()) {
            $recipe->slug = $originalSlug . '-' . $count;
            $count++;
        }
    });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(RecipeCategory::class);
    }

    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function steps()
    {
        return $this->hasMany(RecipeStep::class)->orderBy('step_order');
    }

    public function tags()
    {
        return $this->belongsToMany(RecipeTag::class, 'recipe_tag_relations', 'recipe_id', 'tag_id');
    }

    /**
     * Get the comments for the recipe.
     */
    public function comments()
    {
        return $this->hasMany(RecipeComment::class)
            ->whereNull('parent_id')
            ->with(['user:id,nickname,avatar', 'replies'])
            ->latest();
    }

    /**
     * Get the average rating for the recipe.
     */
    public function getAverageRatingAttribute()
    {
        return $this->comments()->whereNotNull('rating')->avg('rating');
    }

    /**
     * Get the users who favorited this recipe.
     */
    public function favorites()
    {
        return $this->belongsToMany(User::class, 'recipe_favorites')
            ->withTimestamps();
    }

    /**
     * Check if the recipe is favorited by a user.
     */
    public function isFavoritedBy(User $user)
    {
        return DB::table('recipe_favorites')
            ->where('recipe_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}