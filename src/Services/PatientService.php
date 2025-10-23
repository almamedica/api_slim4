<?php
// src/Services/PatientService.php

namespace App\Services;

use App\Repositories\PatientRepository; // Necesita el repositorio
use App\Models\Patient;                // Opcional, si devuelves modelos
use Psr\Log\LoggerInterface;           // Para logging

class PatientService
{
    private PatientRepository $patientRepository;
    private LoggerInterface $logger;

    // Inyectamos las dependencias que necesita el servicio
    public function __construct(PatientRepository $patientRepository, LoggerInterface $logger)
    {
        $this->patientRepository = $patientRepository;
        $this->logger = $logger;
    }

    /**
     * Obtiene un paciente por RUT.
     * Puede lanzar excepciones si el RUT es inválido o si hay error de BD.
     *
     * @param string $rut
     * @return Patient|null // O array|null si no usas modelos
     * @throws \InvalidArgumentException Si el RUT es inválido
     * @throws \Exception Si ocurre un error inesperado
     */
    public function getPatientByRut(string $rut): ?Patient // O ?array
    {
        $this->logger->info("Servicio: Buscando paciente por RUT", ['rut' => $rut]);

        // Validación básica (podría ir en una clase Validator también)
        if (!$rut || !preg_match('/^\d{7,8}-[\dkK]$/', $rut) /* || !validarRut($rut) */ ) {
             $this->logger->warning("Servicio: Intento de búsqueda con RUT inválido", ['rut' => $rut]);
             // Lanzamos una excepción específica para que el controlador la capture
             throw new \InvalidArgumentException("RUT {$rut} no es válido o falta.");
        }

        try {
            // Llama al repositorio para obtener los datos
            $patient = $this->patientRepository->findByRut($rut);

            if (!$patient) {
                $this->logger->info("Servicio: Paciente no encontrado", ['rut' => $rut]);
                return null; // El servicio indica que no se encontró
            }

            $this->logger->info("Servicio: Paciente encontrado", ['rut' => $rut]);
            return $patient; // Devuelve el objeto Patient (o array)

        } catch (\PDOException $e) {
            // Loguea el error de BD y relanza una excepción genérica
            $this->logger->error("Servicio: Error DB en getPatientByRut", ['exception' => $e]);
            throw new \RuntimeException("Error interno al consultar la base de datos.");
        } catch (\Exception $e) {
             // Captura otras posibles excepciones
             $this->logger->error("Servicio: Error inesperado en getPatientByRut", ['exception' => $e]);
             throw $e; // Relanza para que el controlador maneje
        }
    }

     /**
      * Busca pacientes según criterios.
      *
      * @param array $params Criterios de búsqueda
      * @return array Array de objetos Patient (o arrays asociativos)
      * @throws \InvalidArgumentException Si no se proveen criterios
      * @throws \Exception Si ocurre un error inesperado
      */
    public function searchPatients(array $params): array
    {
        $this->logger->info('Servicio: Búsqueda de pacientes iniciada', ['params' => $params]);

        // Validación (movida desde el controlador)
        if (empty(array_filter($params, fn($val, $key) => !empty($val) && in_array($key, ['rut', 'nombre', 'apellido', 'fecha_nacimiento', 'email', 'telefono']), ARRAY_FILTER_USE_BOTH))) {
             $this->logger->warning('Servicio: Búsqueda sin criterios válidos');
            throw new \InvalidArgumentException("Debe especificar al menos un criterio de búsqueda.");
        }

        try {
            // Llama al repositorio
            $patients = $this->patientRepository->search($params);
            $this->logger->info('Servicio: Búsqueda completada', ['count' => count($patients)]);
            return $patients; // Devuelve el array de resultados

        } catch (\PDOException $e) {
            $this->logger->error("Servicio: Error DB en searchPatients", ['exception' => $e, 'params' => $params]);
            throw new \RuntimeException("Error interno al consultar la base de datos.");
        } catch (\Exception $e) {
             $this->logger->error("Servicio: Error inesperado en searchPatients", ['exception' => $e, 'params' => $params]);
             throw $e;
        }
    }

    // Aquí añadirías más métodos de lógica de negocio:
    // public function createPatient(array $data): Patient { ... }
    // public function updatePatient(string $rut, array $data): bool { ... }
}