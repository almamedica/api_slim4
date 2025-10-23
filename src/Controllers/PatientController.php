<?php
// src/Controllers/PatientController.php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\PatientService; // <-- CAMBIO: Usamos el Servicio
use Psr\Log\LoggerInterface;

class PatientController
{
    private PatientService $patientService; // <-- CAMBIO: Inyectamos el Servicio
    private LoggerInterface $logger;

    // Inyectamos el Servicio y el Logger
    public function __construct(PatientService $patientService, LoggerInterface $logger)
    {
        $this->patientService = $patientService;
        $this->logger = $logger;
    }

    public function getPatientByRut(Request $request, Response $response, array $args): Response
    {
        $rut = $args['rut'] ?? null;
        $this->logger->info("Controlador: Petición GET /patient/{$rut}"); // Loguear inicio

        try {
            // --- Llamada al Servicio ---
            $patient = $this->patientService->getPatientByRut((string)$rut); // Llama al servicio

            // --- Construcción de Respuesta HTTP ---
            if ($patient) {
                // Convertir modelo a array si es necesario
                $patientData = ($patient instanceof \App\Models\Patient) ? get_object_vars($patient) : $patient;
                $responseData = ["status" => "success", "data" => [$patientData]];
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                // El servicio devolvió null (no encontrado)
                $responseData = ["status" => false, "message" => "Paciente con RUT {$rut} no encontrado."];
                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        // Capturamos excepciones específicas lanzadas por el servicio
        } catch (\InvalidArgumentException $e) {
             $this->logger->warning("Controlador: Argumento inválido", ['rut' => $rut, 'message' => $e->getMessage()]);
             $responseData = ["status" => false, "message" => $e->getMessage()];
             $response->getBody()->write(json_encode($responseData));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException | \Exception $e) { // Captura errores de BD u otros
            $this->logger->error("Controlador: Error procesando getPatientByRut", ['rut' => $rut, 'exception' => $e]);
            $responseData = ["status" => false, "message" => 'Error interno del servidor.'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function searchPatients(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $this->logger->info('Controlador: Petición GET /patients/search', ['params' => $params]);

        try {
            // --- Llamada al Servicio ---
            $patients = $this->patientService->searchPatients($params);

            // --- Construcción de Respuesta HTTP ---
            if ($patients) {
                 // Convertir modelos a arrays si es necesario
                 $patientsData = array_map(function($p) {
                     return ($p instanceof \App\Models\Patient) ? get_object_vars($p) : $p;
                 }, $patients);
                 $response->getBody()->write(json_encode($patientsData)); // Devuelve el array directamente
                 return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                 // El servicio devolvió array vacío (no encontrado)
                 $responseData = ["status" => false, "message" => "No se encontraron pacientes"];
                 $response->getBody()->write(json_encode($responseData));
                 return $response->withHeader('Content-Type', 'application/json')->withStatus(404); // O 200 con array vacío
            }
         } catch (\InvalidArgumentException $e) {
             $this->logger->warning("Controlador: Argumento inválido en search", ['params' => $params, 'message' => $e->getMessage()]);
             $responseData = ["status" => false, "message" => $e->getMessage()];
             $response->getBody()->write(json_encode($responseData));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException | \Exception $e) {
            $this->logger->error("Controlador: Error procesando searchPatients", ['params' => $params, 'exception' => $e]);
            $responseData = ["status" => false, "message" => 'Error interno del servidor.'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}