<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(version: "1.0.0", description: "Documentação da API de Agendamento de Consultas", title: "API de Agendamentos")]
#[OA\Server(url: "http://localhost:8000/api", description: "Servidor Principal")]
#[OA\SecurityScheme(securityScheme: "bearerAuth", type: "http", scheme: "bearer")]
abstract class Controller
{
    //
}
