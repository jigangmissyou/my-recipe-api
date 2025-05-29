<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * 获取当前登录用户的 profile。
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'nickname' => $user->nickname,
            'bio' => $user->bio,
            'email' => $user->email,
            'provider' => $user->provider,
            'provider_id' => $user->provider_id,
        ]);
    }
    /**
     * 更新用户的 profile。
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nickname' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $user->id,
            // bio
            'bio' => 'sometimes|nullable|string|max:500',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
}
?>
