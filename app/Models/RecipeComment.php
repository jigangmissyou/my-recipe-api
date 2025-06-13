<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipeComment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
        'user_id',
        'parent_id',
        'content',
    ];

    /**
     * Get the recipe that owns the comment.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the user that owns the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(RecipeComment::class, 'parent_id');
    }

    /**
     * Get the replies for the comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(RecipeComment::class, 'parent_id')->with('user:id,nickname,avatar')->latest();
    }
}
