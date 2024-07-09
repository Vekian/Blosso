<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(path: '/api/register', summary: "Inscription d'utilisateur", tags: ["user"], parameters: [
        new OA\Parameter(
            name: 'Accept',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'application/json'
        )
    ],)]
    #[OA\Response(response: '200', description: 'Le token')]
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
         ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    #[OA\Post(path: '/api/login', summary: "Connexion d'utilisateur", tags: ["user"], parameters: [
        new OA\Parameter(
            name: 'Accept',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'application/json'
        ),
        new OA\Parameter(
            name: 'email',
            in: 'query',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'exemple@gmail.com'
        ),
        new OA\Parameter(
            name: 'password',
            in: 'query',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'password'
        )
    ],)]
    #[OA\Response(response: '200', description: 'Le token')]
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
            'message' => 'Invalid login details'
                    ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    #[OA\Post(path: '/api/me', summary: "Récupération d'utilisateur", tags: ["user"], security: [['bearerAuth' => []]], parameters: [
        new OA\Parameter(
            name: 'Accept',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'application/json'
        )
    ],)]
    #[OA\Response(response: '200', description: 'L\'utilisateur')]
    public function me(Request $request)
    {
        return $request->user();
    }
}
