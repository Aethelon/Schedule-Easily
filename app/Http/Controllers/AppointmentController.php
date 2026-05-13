<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Professional;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

class AppointmentController extends Controller
{
    #[OA\Get(
        path: "/appointments",
        summary: "Listar consultas do usuário",
        security: [["bearerAuth" => []]],
        tags: ["Consultas"],
        parameters: [
            new OA\Parameter(name: "date", description: "Filtrar por data (AAAA-MM-DD)", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date", example: "2026-05-15")),
            new OA\Parameter(name: "professional_id", description: "Filtrar por ID do profissional", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Operação bem-sucedida"),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function index(Request $request)
    {
        $query = $request->user()->appointments()->with('professional');

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('professional_id')) {
            $query->where('professional_id', $request->professional_id);
        }

        $appointments = $query->get();
        return AppointmentResource::collection($appointments);
    }

    #[OA\Post(
        path: "/appointments",
        summary: "Agendar uma consulta",
        security: [["bearerAuth" => []]],
        tags: ["Consultas"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["professional_id", "date", "time"],
                properties: [
                    new OA\Property(property: "professional_id", type: "integer", example: 1),
                    new OA\Property(property: "date", type: "string", format: "date", example: "2026-05-15"),
                    new OA\Property(property: "time", type: "string", example: "10:00")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Consulta agendada com sucesso"),
            new OA\Response(response: 401, description: "Não autenticado"),
            new OA\Response(response: 422, description: "Erros de validação ou conflito de horário")
        ]
    )]
    public function store(StoreAppointmentRequest $request)
    {
        $dateTime = Carbon::parse($request->date . ' ' . $request->time);
        if ($dateTime->isPast()) {
            return response()->json([
                'message' => 'Não é possível agendar em uma data/hora no passado.',
            ], 422);
        }

        $conflict = Appointment::where('professional_id', $request->professional_id)
            ->where('date', $request->date)
            ->where('time', $request->time)
            ->where('status', 'scheduled')
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'O profissional já tem uma consulta agendada para este horário.',
            ], 422);
        }

        $appointment = $request->user()->appointments()->create([
            'professional_id' => $request->professional_id,
            'date' => $request->date,
            'time' => $request->time,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'data' => new AppointmentResource($appointment->load('professional')),
            'message' => 'Consulta agendada com sucesso',
        ], 201);
    }

    #[OA\Get(
        path: "/appointments/{appointment}",
        summary: "Obter detalhes da consulta",
        security: [["bearerAuth" => []]],
        tags: ["Consultas"],
        parameters: [
            new OA\Parameter(name: "appointment", description: "ID da Consulta", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Operação bem-sucedida"),
            new OA\Response(response: 401, description: "Não autenticado"),
            new OA\Response(response: 403, description: "Não autorizado"),
            new OA\Response(response: 404, description: "Consulta não encontrada")
        ]
    )]
    public function show(Appointment $appointment)
    {
        if ($appointment->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return new AppointmentResource($appointment->load('professional'));
    }

    #[OA\Delete(
        path: "/appointments/{appointment}",
        summary: "Cancelar uma consulta",
        security: [["bearerAuth" => []]],
        tags: ["Consultas"],
        parameters: [
            new OA\Parameter(name: "appointment", description: "ID da Consulta", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Consulta cancelada com sucesso"),
            new OA\Response(response: 401, description: "Não autenticado"),
            new OA\Response(response: 403, description: "Não autorizado"),
            new OA\Response(response: 404, description: "Consulta não encontrada")
        ]
    )]
    public function destroy(Appointment $appointment)
    {
        if ($appointment->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $appointment->update(['status' => 'canceled']);

        return response()->json([
            'message' => 'Consulta cancelada com sucesso',
        ], 200);
    }
}
