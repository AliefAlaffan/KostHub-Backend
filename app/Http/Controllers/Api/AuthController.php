<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('email', $credentials['email'])
            ->orWhere('phone', $credentials['email'])
            ->first();

        if (!$user || !Auth::attempt(['email' => $user->email, 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => ['Email/No. HP atau password salah.'],
            ]);
        }

        if ($user->status !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}