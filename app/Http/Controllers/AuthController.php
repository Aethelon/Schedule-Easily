<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/register",
        summary: "Registrar um novo usuário",
        tags: ["Autenticação"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "João da Silva"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "joao@exemplo.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "senha123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "senha123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Usuário registrado com sucesso"),
            new OA\Response(response: 422, description: "Erros de validação")
        ]
    )]
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    #[OA\Post(
        path: "/login",
        summary: "Fazer login e obter o token",
        tags: ["Autenticação"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "joao@exemplo.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "senha123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Login realizado com sucesso"),
            new OA\Response(response: 401, description: "Credenciais inválidas"),
            new OA\Response(response: 422, description: "Erros de validação")
        ]
    )]
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    #[OA\Post(
        path: "/logout",
        summary: "Fazer logout do usuário",
        security: [["bearerAuth" => []]],
        tags: ["Autenticação"],
        responses: [
            new OA\Response(response: 200, description: "Logout realizado com sucesso"),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ], 200);
    }

    #[OA\Get(
        path: "/user",
        summary: "Obter detalhes do usuário autenticado",
        security: [["bearerAuth" => []]],
        tags: ["Autenticação"],
        responses: [
            new OA\Response(response: 200, description: "Operação bem-sucedida"),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function user(Request $request)
    {
        return response()->json([
            'data' => new UserResource($request->user())
        ], 200);
    }
}
