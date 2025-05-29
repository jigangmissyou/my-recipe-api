<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecipeController extends Controller
{
    /**
     * Store a newly created recipe in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:recipe_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty' => 'nullable|string|max:50',
            'prep_time' => 'nullable|string|max:50',
            'cook_time' => 'nullable|string|max:50',
            'cover_image' => 'nullable|string|max:255',
            'ingredients' => 'required|array',
            'ingredients.*.name' => 'required|string|max:255',
            'ingredients.*.quantity' => 'nullable|string|max:255',
            'ingredients.*.unit' => 'nullable|string|max:50',
            'steps' => 'required|array',
            'steps.*.step_order' => 'required|integer|min:1',
            'steps.*.description' => 'required|string',
            'steps.*.image_url' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255|exists:recipe_tags,name', // Validate against existing tag names
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $recipe = $request->user()->recipes()->create($request->only([
                    'category_id',
                    'name',
                    'description',
                    'difficulty',
                    'prep_time',
                    'cook_time',
                    'cover_image',
                ]));

                foreach ($request->input('ingredients') as $ingredientData) {
                    $recipe->ingredients()->create($ingredientData);
                }

                foreach ($request->input('steps') as $stepData) {
                    $recipe->steps()->create($stepData);
                }

                if ($request->has('tags')) {
                    $tagIds = [];
                    foreach ($request->input('tags') as $tagName) {
                        $tag = RecipeTag::where('name', $tagName)->first();
                        if ($tag) {
                            $tagIds[] = $tag->id;
                        }
                    }
                    $recipe->tags()->attach($tagIds);
                }

                $recipe->load(['user', 'category', 'ingredients', 'steps', 'tags']);

                return response()->json($recipe, 201);
            });
        } catch (QueryException $e) {            
            // Handle database query exceptions
            return response()->json(['error' => 'Failed to save recipe due to a database error.'], 500);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json(['error' => 'Failed to create recipe.'], 500);
        }
    }
}