<?php
// src/Middleware/JwtAuthMiddleware.php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use PDO; // Necesitamos PDO para verificar el token en la BD

class JwtAuthMiddleware implements MiddlewareInterface
{
    private PDO $db; // Recibirá la conexión PDO

    // El contenedor DI inyectará PDO aquí
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $response = new \Slim\Psr7\Response(); // Preparamos una respuesta por si hay error

        if (empty($authHeader)) {
            $response->getBody()->write(json_encode(["error" => true, "message" => "Falta cabecera de autorización"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (stripos($authHeader, 'Bearer ') !== 0) {
            $response->getBody()->write(json_encode(["error" => true, "message" => "Se requiere autorización Bearer"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = trim(substr($authHeader, 7));

        if (empty($token)) {
            $response->getBody()->write(json_encode(["error" => true, "message" => "Token vacío"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // --- Validación del token contra la BD ---
        // (Adaptado de tus funciones postTokenLogin y postTokenTiempo)

        try {
            // 1. Validar si el token existe
            $stmtLogin = $this->db->prepare("SELECT token FROM users_token WHERE token = :token");
            $stmtLogin->bindParam(':token', $token);
            $stmtLogin->execute();
            if ($stmtLogin->rowCount() === 0) {
                 $response->getBody()->write(json_encode(["error" => true, "message" => "Token inválido"]));
                 return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            // 2. Validar tiempo de expiración
            // Necesitamos la zona horaria correcta
            // Asumimos que tienes un método obtenerZonaHoraria() o define la zona directamente
            // date_default_timezone_set($this->obtenerZonaHoraria()); // O usa 'America/Santiago'
            $time = time();
            $stmtTime = $this->db->prepare("SELECT token FROM users_token WHERE token = :token AND token_exp > :time");
            $stmtTime->bindParam(':token', $token);
            $stmtTime->bindParam(':time', $time, PDO::PARAM_INT);
            $stmtTime->execute();
            if ($stmtTime->rowCount() === 0) {
                $response->getBody()->write(json_encode(["error" => true, "message" => "Token expirado"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

        } catch (\PDOException $e) {
            // Loguear el error real en producción
            error_log("Error validando token: " . $e->getMessage());
            $response->getBody()->write(json_encode(["error" => true, "message" => "Error interno del servidor al validar token"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        // --- Fin Validación ---

        // Si el token es válido, continuar
        return $handler->handle($request);
    }

     // Podrías mover la lógica de obtenerZonaHoraria aquí si la necesitas
     /*
     private function obtenerZonaHoraria(): string {
        // ... tu lógica para consultar la BD o devolver un valor fijo ...
        return 'America/Santiago'; // Ejemplo
     }
     */
}