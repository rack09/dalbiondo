<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $password = $credentials['password'];

        if (
            strlen($password) > 72 ||
            str_contains($password, "\0") ||
            ! Auth::guard('web')->attempt($credentials)
        ) {
            return response()->json([
                'message' => 'Email o password non validi.',
            ], 422);
        }

        $request->session()->regenerate();

        return $this->show($request);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user('web')->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}