<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class ApiKeyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $apiKey = $_ENV['API_KEY'] ?? null; // Obtener la API Key de las variables de entorno
        $headerApiKey = $request->getHeaderLine('API_KEY'); // Obtener la API Key de la cabecera

        if (!$apiKey || $headerApiKey !== $apiKey) {
            // Si la API Key falta o no coincide, devolver error 401 Unauthorized
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                "error" => true,
                "message" => "Acceso denegado. API_KEY inválida o faltante."
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Si la API Key es válida, continuar con la siguiente capa (el controlador de la ruta)
        return $handler->handle($request);
    }
}