<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecipeController extends Controller
{
    /**
     * Display the user's recipes page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function myRecipesPage(Request $request)
    {
        $recipes = $request->user()->recipes()
            ->with(['category', 'tags'])
            ->withCount(['ingredients', 'steps'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->input('category_id'), function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->input('difficulty'), function ($query, $difficulty) {
                $query->where('difficulty', $difficulty);
            })
            ->when($request->input('tags'), function ($query, $tags) {
                $tags = is_array($tags) ? $tags : explode(',', $tags);
                $query->whereHas('tags', function($q) use ($tags) {
                    $q->whereIn('name', $tags);
                });
            })
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_direction', 'desc'))
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('MyRecipes', [
            'recipes' => $recipes,
            'categories' => RecipeCategory::all(),
            'filters' => $request->only(['search', 'category_id', 'difficulty', 'sort_by', 'sort_direction']),
        ]);
    }
} 