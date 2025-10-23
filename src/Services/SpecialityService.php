<?php

namespace App\Services;

use App\Repositories\SpecialityRepository;
use Psr\Log\LoggerInterface;

class SpecialityService
{
    private SpecialityRepository $specialityRepository;
    private LoggerInterface $logger;

    public function __construct(SpecialityRepository $specialityRepository, LoggerInterface $logger)
    {
        $this->specialityRepository = $specialityRepository;
        $this->logger = $logger;
    }

    /**
     * Obtiene todas las especialidades activas.
     * @return array
     * @throws \Exception
     */
    public function getAllSpecialities(): array
    {
        $this->logger->info('Servicio: Obteniendo todas las especialidades');
        try {
            return $this->specialityRepository->findAllActive();
        } catch (\PDOException $e) {
            $this->logger->error('Servicio: Error DB en getAllFacilities', ['exception' => $e]);
            throw new \RuntimeException("Error interno al obtener especialidades.");
        }
    }

    /**
     * Obtiene prestaciones por ID de especialidad.
     * @param int $id
     * @return array|null
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function getSpecialityById(int $id): ?array
    {
        $this->logger->info('Servicio: Buscando prestaciones por ID de especialidad', ['id' => $id]);

        // Validación básica del ID
        if ($id <= 0) {
            $this->logger->warning('Servicio: Intento de búsqueda con ID de especialidad inválido', ['id' => $id]);
            throw new \InvalidArgumentException("El ID de especialidad '{$id}' no es válido.");
        }

        try {
            $speciality = $this->specialityRepository->findActiveById($id);
            if (!$speciality) {
                $this->logger->info('Servicio: Especialidad no encontrada', ['id' => $id]);
            }
            return $speciality; // Devuelve el array de datos o null
        } catch (\PDOException $e) {
            $this->logger->error('Servicio: Error DB en getFacilityById', ['id' => $id, 'exception' => $e]);
            throw new \RuntimeException("Error interno al obtener la sucursal.");
        }
    }
}