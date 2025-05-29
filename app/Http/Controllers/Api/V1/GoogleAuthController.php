<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class GoogleAuthController extends Controller
{
    /**
     * 使用 Google access_token 登录或注册用户.
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $accessToken = $request->input('credential');

        if (!$accessToken) {
            return response()->json(['message' => 'Google access token missing'], 400);
        }

        try {
            // 用 access token 向 Google 请求用户信息
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$response->ok()) {
                return response()->json(['message' => 'Failed to fetch user info from Google'], 401);
            }

            $googleUser = $response->json();

            $googleId = $googleUser['sub'] ?? null;
            $email = $googleUser['email'] ?? null;
            $name = $googleUser['name'] ?? null;

            if (!$googleId || !$email) {
                return response()->json(['message' => 'Incomplete user info from Google'], 422);
            }

            $user = User::where('provider', 'google')
                ->where('provider_id', $googleId)
                ->first();

            if ($user) {
                // Google 账号已存在，直接登录
                Auth::login($user);
            } else {
                // 尝试查找已有邮箱用户
                $user = User::where('email', $email)->first();

                if ($user) {
                    // 邮箱存在，但未绑定 Google，绑定它
                    $user->update([
                        'provider' => 'google',
                        'provider_id' => $googleId,
                    ]);
                    Auth::login($user);
                } else {
                    // 创建新用户
                    $user = User::create([
                        'nickname' => $name,
                        'email' => $email,
                        'provider' => 'google',
                        'provider_id' => $googleId,
                        'password' => bcrypt(uniqid()), // 随机密码
                    ]);
                    Auth::login($user);
                }
            }

            $authToken = $user->createToken('google_login')->plainTextToken;
            return response()->json(['access_token' => $authToken, 'token_type' => 'Bearer']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Google login failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 关联已登录用户的 Google 账号.
     */
    public function linkGoogleAccount(Request $request): JsonResponse
    {
        $accessToken = $request->input('token');
        $user = $request->user(); // 需要 Sanctum 中间件

        if (!$accessToken) {
            return response()->json(['message' => 'Google access token missing'], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$response->ok()) {
                return response()->json(['message' => 'Failed to fetch Google user info'], 401);
            }

            $googleUser = $response->json();
            $googleId = $googleUser['sub'] ?? null;

            if (!$googleId) {
                return response()->json(['message' => 'Invalid Google user info'], 422);
            }

            // 检查该 Google ID 是否已被绑定
            $existingUserWithGoogle = User::where('provider', 'google')
                ->where('provider_id', $googleId)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUserWithGoogle) {
                return response()->json(['message' => 'This Google account is already linked to another user.'], 409);
            }

            // 绑定 Google 账号
            $user->update([
                'provider' => 'google',
                'provider_id' => $googleId,
            ]);

            return response()->json(['message' => 'Google account linked successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to link Google account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
