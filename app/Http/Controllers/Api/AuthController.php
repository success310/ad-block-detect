<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }
    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();
        $username = $this->userService->generateUniqueUsername($validated['name']);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $username,
            'password' => Hash::make($validated['password'])
        ]);
        return response()->json([
            'status' => true,
            'token' => $user->createToken(env('API_TOKEN'))->plainTextToken
        ], 200);
    }
    public function login(LoginUserRequest $request)
    {
        if (!Auth::attempt($request->only(['email', 'password']))) {
            return response()->json([
                'status' => false,
                'message' => 'Wrong credentials',
            ], 401);
        }
        $user = User::where('email', $request->email)->first();
        return response()->json([
            'status' => true,
            'user' => $user,
            'token' => $user->createToken(env('API_TOKEN'))->plainTextToken
        ], 200);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response(['success' => true]);
    }
}
