<?php

namespace App\Services;

use App\Repositories\FacilityRepository;
use Psr\Log\LoggerInterface;

class FacilityService
{
    private FacilityRepository $facilityRepository;
    private LoggerInterface $logger;

    public function __construct(FacilityRepository $facilityRepository, LoggerInterface $logger)
    {
        $this->facilityRepository = $facilityRepository;
        $this->logger = $logger;
    }

    /**
     * Obtiene todas las sucursales activas.
     * @return array
     * @throws \Exception
     */
    public function getAllFacilities(): array
    {
        $this->logger->info('Servicio: Obteniendo todas las sucursales');
        try {
            return $this->facilityRepository->findAllActive();
        } catch (\PDOException $e) {
            $this->logger->error('Servicio: Error DB en getAllFacilities', ['exception' => $e]);
            throw new \RuntimeException("Error interno al obtener sucursales.");
        }
    }

    /**
     * Obtiene una sucursal por ID.
     * @param int $id
     * @return array|null
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function getFacilityById(int $id): ?array
    {
        $this->logger->info('Servicio: Buscando sucursal por ID', ['id' => $id]);

        // Validación básica del ID
        if ($id <= 0) {
            $this->logger->warning('Servicio: Intento de búsqueda con ID de sucursal inválido', ['id' => $id]);
            throw new \InvalidArgumentException("El ID de sucursal '{$id}' no es válido.");
        }

        try {
            $facility = $this->facilityRepository->findActiveById($id);
            if (!$facility) {
                $this->logger->info('Servicio: Sucursal no encontrada', ['id' => $id]);
            }
            return $facility; // Devuelve el array de datos o null
        } catch (\PDOException $e) {
            $this->logger->error('Servicio: Error DB en getFacilityById', ['id' => $id, 'exception' => $e]);
            throw new \RuntimeException("Error interno al obtener la sucursal.");
        }
    }
}