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
use Illuminate\Support\Facades\Storage;

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

    /**
     * Upload an image for a recipe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, Recipe $recipe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'required|string|in:cover,step',
            'step_order' => 'required_if:type,step|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($request->type === 'cover') {
                // Handle cover image upload
                if ($recipe->cover_image) {
                    // Delete old cover image
                    Storage::disk('public')->delete($recipe->cover_image);
                }

                $path = $request->file('image')->store('recipes/covers', 'public');
                $recipe->update(['cover_image' => $path]);

                return response()->json([
                    'message' => 'Cover image uploaded successfully',
                    'image_url' => Storage::url($path)
                ]);
            } else {
                // Handle step image upload
                $step = $recipe->steps()->where('step_order', $request->step_order)->first();
                
                if (!$step) {
                    return response()->json(['error' => 'Step not found'], 404);
                }

                if ($step->image_url) {
                    // Delete old step image
                    Storage::disk('public')->delete($step->image_url);
                }

                $path = $request->file('image')->store('recipes/steps', 'public');
                $step->update(['image_url' => $path]);

                return response()->json([
                    'message' => 'Step image uploaded successfully',
                    'image_url' => Storage::url($path)
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to upload image'], 500);
        }
    }

    /**
     * Upload a temporary image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadTemporaryImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'type' => 'nullable|string|in:cover,step',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $path = $request->file('image')->store('recipes/temp', 'public');
            
            return response()->json([
                'message' => 'Image uploaded successfully',
                'image_url' => Storage::url($path)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to upload image: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a list of recipes with pagination and filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:recipe_categories,id',
            'search' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'difficulty' => 'nullable|string|max:50',
            'sort_by' => 'nullable|string|in:created_at,views,name',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Recipe::with(['user', 'category', 'tags'])
            ->withCount(['ingredients', 'steps']);

        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $query->whereHas('tags', function($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Apply sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Get paginated results
        $perPage = $request->input('per_page', 12);
        $recipes = $query->paginate($perPage);

        return response()->json($recipes);
    }

    /**
     * Get a specific recipe by ID or slug.
     *
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Recipe $recipe): JsonResponse
    {
        $recipe->load([
            'user:id,nickname,avatar',
            'category:id,name',
            'ingredients',
            'steps' => function($query) {
                $query->orderBy('step_order');
            },
            'tags:id,name'
        ]);

        // Increment view count
        // $recipe->increment('views');

        return response()->json($recipe);
    }

    /**
     * Get the authenticated user's recipes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myRecipes(Request $request): JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:recipe_categories,id',
            'search' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'difficulty' => 'nullable|string|max:50',
            'sort_by' => 'nullable|string|in:created_at,views,name',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = $request->user()->recipes()
            ->with(['category:id,name', 'tags:id,name'])
            ->withCount(['ingredients', 'steps']);

        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $query->whereHas('tags', function($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Apply sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Get paginated results
        $perPage = $request->input('per_page', 10);
        $recipes = $query->paginate($perPage);

        return response()->json($recipes);
    }
}