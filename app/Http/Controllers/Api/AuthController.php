<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('admin')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'username' => ['Invalid credentials.'],
            ]);
        }

        $request->session()->regenerate();

        /** @var AdminUser $user */
        $user = Auth::guard('admin')->user();

        return response()->json([
            'message' => 'Logged in',
            'user' => ['id' => $user->id, 'username' => $user->username],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var AdminUser|null $user */
        $user = Auth::guard('admin')->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => ['id' => $user->id, 'username' => $user->username],
        ]);
    }
}
