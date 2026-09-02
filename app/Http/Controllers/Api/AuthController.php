<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['customer', 'driver'])], 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data verification error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

      
        if ($user->role === 'driver') {
            $user->driverProfile()->create([
                'status' => 'available',
                'joined_at' => now(),
            ]);
        }

        $token = $user->createToken($request->device_name ?? 'default-device')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

   
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data verification error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid phone number or password',
            ], 401);
        }

        $token = $user->createToken($request->device_name ?? 'default-device')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

   
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }


    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}