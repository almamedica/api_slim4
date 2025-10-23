<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\SpecialityService; // <-- Usamos el Servicio
use Psr\Log\LoggerInterface;

class SpecialityController
{
    private SpecialityService $specialityService; // <-- Inyectamos el Servicio
    private LoggerInterface $logger;

    public function __construct(SpecialityService $specialityService, LoggerInterface $logger)
    {
        $this->specialityService = $specialityService;
        $this->logger = $logger;
    }

    // Método para obtener TODAS las sucursales
    public function getAllSpecialities(Request $request, Response $response): Response
    {
        $this->logger->info('Controlador: Petición GET /facility');
        try {
            $specialities = $this->specialityService->getAllSpecialities();

            // Formato de respuesta original
            $responseData = [
                "status" => "success",
                "data" => $specialities // El servicio ya devuelve un array
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\RuntimeException | \Exception $e) {
            $this->logger->error('Controlador: Error en getAllSpecialities', ['exception' => $e]);
            $responseData = ["status" => false, "message" => 'Error interno del servidor.'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Método para obtener prestaciones por ID de especialidad
    public function getSpecialityById(Request $request, Response $response, array $args): Response
    {
        $speciality_id = $args['speciality_id'] ?? null;
        $this->logger->info("Controlador: Petición GET /speciality/{$speciality_id}");

        try {
            // Convertimos a entero antes de pasar al servicio
            $speciality = $this->specialityService->getSpecialityById((int)$speciality_id);

            if ($speciality) {
                // Formato original
                $responseData = [
                    "status" => "success",
                    "data" => [$speciality] // Envuelto en array
                ];
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                // El servicio devolvió null (no encontrado o ID inválido manejado por excepción)
                $responseData = [
                    "status" => false,
                    "message" => "Prestaciones con ID {$speciality_id} de especialidad no encontrada."
                ];
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        // Capturamos excepciones específicas
        } catch (\InvalidArgumentException $e) {
             $this->logger->warning("Controlador: ID de especialidad inválido", ['id' => $speciality_id, 'message' => $e->getMessage()]);
             $responseData = ["status" => false, "message" => $e->getMessage()];
             $response->getBody()->write(json_encode($responseData));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException | \Exception $e) {
            $this->logger->error("Controlador: Error procesando getSpecialityById", ['id' => $speciality_id, 'exception' => $e]);
            $responseData = ["status" => false, "message" => 'Error interno del servidor.'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}