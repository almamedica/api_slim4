<?php
// src/Controllers/AuthController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AuthService; // <-- Usamos el Servicio
use Psr\Log\LoggerInterface; // <-- Añadimos Logger

class AuthController
{
    private AuthService $authService; // <-- Inyectamos el Servicio
    private LoggerInterface $logger;

    // Inyectamos el Servicio y el Logger
    public function __construct(AuthService $authService, LoggerInterface $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?: [];
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        $apiKeySecret = trim($data['API_SECRET'] ?? $data['api_secret'] ?? '');

        // --- Validación inicial ---
        if ((!$username || !$password) && !$apiKeySecret) {
            $responseData = ["status" => false, "message" => 'Se requiere "username"/"password" o "API_SECRET".'];
            // ... (resto del código 400) ...
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // --- Lógica de Autenticación delegada al Servicio ---
            $authResult = null;
            if ($username && $password) {
                $this->logger->info("Intento de login con credenciales", ["username" => $username]);
                $authResult = $this->authService->loginWithCredentials($username, $password);
            } elseif ($apiKeySecret) {
                $this->logger->info("Intento de login con API Secret"); // No loguear el secret
                $authResult = $this->authService->loginWithApiSecret($apiKeySecret);
            }

            // --- Construcción de respuesta ---
            if (!empty($authResult) && ($authResult['respuesta'] ?? '') === 'ok') {
                unset($authResult['respuesta']);
                $responseData = ["status" => "valido", "data" => $authResult];
                // ... (resto del código 200 OK) ...
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $errorMessage = $authResult['respuesta'] ?? 'Error de autenticación.';
                 $this->logger->warning("Login fallido", ["username" => $username, "reason" => $errorMessage]);
                $responseData = ["status" => "invalido", "message" => $errorMessage];
                // ... (resto del código 401) ...
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

        } catch (\Exception $e) { // Captura cualquier excepción del Servicio
            $this->logger->error("Error crítico en login", ['exception' => $e, "username" => $username]);
            $responseData = ["status" => "error", "message" => "Error interno del servidor."];
            // ... (resto del código 500) ...
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}