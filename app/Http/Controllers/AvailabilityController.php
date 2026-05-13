<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use Illuminate\Http\Request;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class AvailabilityController extends Controller
{
    #[OA\Get(
        path: "/professionals/{professional}/availability",
        summary: "Obter horários disponíveis de um profissional",
        tags: ["Profissionais"],
        parameters: [
            new OA\Parameter(name: "professional", description: "ID do Profissional", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "date", description: "Data no formato AAAA-MM-DD", in: "query", required: true, schema: new OA\Schema(type: "string", format: "date", example: "2026-05-15"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Operação bem-sucedida"),
            new OA\Response(response: 422, description: "Erro de validação"),
            new OA\Response(response: 404, description: "Profissional não encontrado")
        ]
    )]
    public function index(Request $request, Professional $professional)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = Carbon::parse($request->date);
        
        // Horário de expediente: 09:00 às 17:00, slots de 1 hora
        $workingHoursStart = 9;
        $workingHoursEnd = 17;
        
        $slots = [];
        for ($i = $workingHoursStart; $i < $workingHoursEnd; $i++) {
            $slots[] = sprintf('%02d:00', $i);
        }

        // Buscar horários já agendados para este dia
        $bookedSlots = $professional->appointments()
            ->where('date', $request->date)
            ->where('status', 'scheduled')
            ->pluck('time')
            ->map(function ($t) {
                return substr($t, 0, 5); // garantir formato HH:MM
            })
            ->toArray();

        // Calcular slots disponíveis
        $availableSlots = array_values(array_diff($slots, $bookedSlots));

        return response()->json([
            'data' => [
                'professional_id' => $professional->id,
                'date' => $request->date,
                'available_slots' => $availableSlots,
            ]
        ], 200);
    }
}
