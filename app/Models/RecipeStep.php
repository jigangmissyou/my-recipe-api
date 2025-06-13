<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'step_order',
        'description',
        'image_url',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
