<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\FacilityService; // <-- Usamos el Servicio
use Psr\Log\LoggerInterface;

class FacilityController
{
    private FacilityService $facilityService; // <-- Inyectamos el Servicio
    private LoggerInterface $logger;

    public function __construct(FacilityService $facilityService, LoggerInterface $logger)
    {
        $this->facilityService = $facilityService;
        $this->logger = $logger;
    }

    // Método para obtener TODAS las sucursales
    public function getAllFacilities(Request $request, Response $response): Response
    {
        $this->logger->info('Controlador: Petición GET /facility');
        try {
            $facilities = $this->facilityService->getAllFacilities();

            // Formato de respuesta original
            $responseData = [
                "status" => "success",
                "data" => $facilities // El servicio ya devuelve un array
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\RuntimeException | \Exception $e) {
            $this->logger->error('Controlador: Error en getAllFacilities', ['exception' => $e]);
            $responseData = ["status" => false, "message" => 'Error interno del servidor.'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Método para obtener UNA sucursal por ID
    public function getFacilityById(Request $request, Response $response, array $args): Response
    {
        $facility_id = $args['facility_id'] ?? null;
        $this->logger->info("Controlador: Petición GET /facility/{$facility_id}");

        try {
            // Convertimos a entero antes de pasar al servicio
            $facility = $this->facilityService->getFacilityById((int)$facility_id);

            if ($facility) {
                // Formato original
                $responseData = [
                    "status" => "success",
                    "data" => [$facility] // Envuelto en array
                ];
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                // El servicio devolvió null (no encontrado o ID inválido manejado por excepción)
                $responseData = [
                    "status" => false,
                    "message" => "Sucursal con ID {$facility_id} no encontrada."
                ];
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        // Capturamos excepciones específicas
        } catch (\InvalidArgumentException $e) {
             $this->logger->warning("Controlador: ID de sucursal inválido", ['id' => $facility_id, 'message' => $e->getMessage()]);
             $responseData = ["status" => false, "message" => $e->getMessage()];
             $response->getBody()->write(json_encode($responseData));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException | \Exception $e) {
            $this->logger->error("Controlador: Error procesando getFacilityById", ['id' => $facility_id, 'exception' => $e]);
            $responseData = ["status" => false, "message" => 'Error interno del servidor.'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}