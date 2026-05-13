# Schedule Easily

[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![Sanctum](https://img.shields.io/badge/Auth-Sanctum-0EA5E9?style=for-the-badge)](https://laravel.com/docs/12.x/sanctum)
[![OpenAPI](https://img.shields.io/badge/Docs-OpenAPI-85EA2D?style=for-the-badge&logo=swagger&logoColor=black)](https://swagger.io/specification/)
[![Repository](https://img.shields.io/badge/GitHub-Aethelon%2FSchedule--Easily-181717?style=for-the-badge&logo=github)](https://github.com/Aethelon/Schedule-Easily)

API REST para gerenciamento de agendamentos entre usuários e profissionais, com autenticação por token, consulta de disponibilidade e documentação interativa da API.

## Sumário

- [Visão geral](#visão-geral)
- [Funcionalidades](#funcionalidades)
- [Regras de negócio](#regras-de-negócio)
- [Stack e arquitetura](#stack-e-arquitetura)
- [Modelo de dados](#modelo-de-dados)
- [Endpoints](#endpoints)
- [Como executar localmente](#como-executar-localmente)
- [Autenticação](#autenticação)
- [Exemplos de uso (cURL)](#exemplos-de-uso-curl)
- [Testes](#testes)
- [Documentação da API](#documentação-da-api)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Integrantes](#integrantes)
- [Licença](#licença)

## Visão geral

O **Schedule Easily** centraliza o fluxo de agendamento:

1. Usuário cria conta e autentica.
2. Usuário consulta profissionais e disponibilidade por data.
3. Usuário agenda uma consulta com validações de conflito e data/hora.
4. Usuário consulta e cancela seus próprios agendamentos.

## Funcionalidades

- Registro e login de usuário com emissão de token (`Laravel Sanctum`)
- Logout e consulta de usuário autenticado
- Cadastro/listagem/detalhe de profissionais
- Consulta de disponibilidade por profissional em uma data específica
- Criação de agendamento com validações de data e conflito
- Listagem de consultas com filtros por data e profissional
- Cancelamento de consulta com controle de autorização por usuário
- Documentação OpenAPI via Swagger UI

## Regras de negócio

- Agendamentos usam slots fixos de 1 hora entre **09:00 e 17:00**.
- Não é permitido agendar em data/hora passada.
- Não é permitido agendar dois atendimentos no mesmo horário para o mesmo profissional quando o status está `scheduled`.
- Um usuário só pode visualizar/cancelar as próprias consultas.

## Stack e arquitetura

### Tecnologias

- **Backend:** Laravel 12
- **Linguagem:** PHP 8.2+
- **Autenticação:** Laravel Sanctum (Bearer Token)
- **Documentação:** L5-Swagger (OpenAPI)
- **Banco de dados:** SQLite (padrão local; pode ser alterado via `.env`)

### Componentes principais

- `FormRequest` para validação de entrada
- Controllers REST para autenticação, profissionais, disponibilidade e consultas
- Eloquent Models para `User`, `Professional` e `Appointment`
- Rotas versionadas em `/api/*`

## Modelo de dados

| Entidade | Campos principais | Observações |
| --- | --- | --- |
| `users` | `id`, `name`, `email`, `password` | usuário autenticável |
| `professionals` | `id`, `name`, `specialty` | profissionais disponíveis para agenda |
| `appointments` | `id`, `user_id`, `professional_id`, `date`, `time`, `status` | status padrão: `scheduled` |

## Endpoints

Base URL local: `http://localhost:8000/api`

### Públicos

| Método | Rota | Descrição |
| --- | --- | --- |
| POST | `/register` | cria usuário e retorna token |
| POST | `/login` | autentica usuário e retorna token |
| GET | `/professionals` | lista profissionais |
| GET | `/professionals/{professional}` | detalhe de profissional |
| GET | `/professionals/{professional}/availability?date=YYYY-MM-DD` | horários disponíveis |

### Protegidos (`auth:sanctum`)

| Método | Rota | Descrição |
| --- | --- | --- |
| POST | `/logout` | invalida token atual |
| GET | `/user` | dados do usuário autenticado |
| POST | `/professionals` | cadastra profissional |
| GET | `/appointments` | lista consultas do usuário autenticado |
| POST | `/appointments` | cria agendamento |
| GET | `/appointments/{appointment}` | detalha consulta do próprio usuário |
| DELETE | `/appointments/{appointment}` | cancela consulta (status `canceled`) |

### Filtros de consultas

- `GET /appointments?date=YYYY-MM-DD`
- `GET /appointments?professional_id={id}`
- Filtros podem ser combinados.

## Como executar localmente

### Pré-requisitos

- PHP 8.2+
- Composer 2+
- Node.js 18+ e npm
- Extensões PHP compatíveis com Laravel (incluindo SQLite, se usar banco padrão)

### Setup rápido

```bash
git clone https://github.com/Aethelon/Schedule-Easily.git
cd Schedule-Easily
composer run setup
composer run dev
```

O script `setup` executa:

- `composer install`
- criação do `.env` (se não existir)
- `php artisan key:generate`
- `php artisan migrate --force`
- `npm install`
- `npm run build`

### Setup manual (alternativo)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

## Autenticação

As rotas protegidas exigem header:

```http
Authorization: Bearer {seu_token}
Accept: application/json
```

## Exemplos de uso (cURL)

### Registrar usuário

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "João da Silva",
    "email": "joao@example.com",
    "password": "senha1234",
    "password_confirmation": "senha1234"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "senha1234"
  }'
```

### Criar agendamento (rota autenticada)

```bash
curl -X POST http://localhost:8000/api/appointments \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{
    "professional_id": 1,
    "date": "2026-05-20",
    "time": "10:00"
  }'
```

## Testes

```bash
composer test
```

## Documentação da API

Com a aplicação rodando:

- Swagger UI: `http://localhost:8000/api/documentation`

Se necessário, regenere a documentação:

```bash
php artisan l5-swagger:generate
```

## Estrutura do projeto

```text
app/
  Http/
    Controllers/
    Requests/
    Resources/
  Models/
database/
  migrations/
  seeders/
routes/
  api.php
```

## Integrantes

- Bruno Magno
- Leandro Oliveira
- Marcelo Guilherme
- Paulo de Araujo

## Repositório

https://github.com/Aethelon/Schedule-Easily

## Licença

Projeto sob licença MIT.
