<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Http\Resources\ProfessionalResource;
use App\Http\Requests\StoreProfessionalRequest;
use OpenApi\Attributes as OA;

class ProfessionalController extends Controller
{
    #[OA\Get(
        path: "/professionals",
        summary: "Obter lista de profissionais",
        tags: ["Profissionais"],
        responses: [
            new OA\Response(response: 200, description: "Operação bem-sucedida")
        ]
    )]
    public function index()
    {
        $professionals = Professional::all();
        return ProfessionalResource::collection($professionals);
    }

    #[OA\Post(
        path: "/professionals",
        summary: "Cadastrar um novo profissional",
        security: [["bearerAuth" => []]],
        tags: ["Profissionais"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "specialty"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Dr. Carlos Silva"),
                    new OA\Property(property: "specialty", type: "string", example: "Ortopedista")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Profissional cadastrado com sucesso"),
            new OA\Response(response: 401, description: "Não autenticado"),
            new OA\Response(response: 422, description: "Erros de validação")
        ]
    )]
    public function store(StoreProfessionalRequest $request)
    {
        $professional = Professional::create([
            'name' => $request->name,
            'specialty' => $request->specialty,
        ]);

        return response()->json([
            'data' => new ProfessionalResource($professional),
            'message' => 'Profissional cadastrado com sucesso',
        ], 201);
    }

    #[OA\Get(
        path: "/professionals/{professional}",
        summary: "Obter detalhes de um profissional",
        tags: ["Profissionais"],
        parameters: [
            new OA\Parameter(name: "professional", description: "ID do Profissional", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Operação bem-sucedida"),
            new OA\Response(response: 404, description: "Profissional não encontrado")
        ]
    )]
    public function show(Professional $professional)
    {
        return new ProfessionalResource($professional);
    }
}
