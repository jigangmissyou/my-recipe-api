<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        });

        static::updating(function ($recipe) {
            $recipe->slug = Str::slug($recipe->name);
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
}