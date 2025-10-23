<?php

namespace App\Repositories;

use PDO;

class FacilityRepository
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Busca todas las sucursales activas.
     * @return array Array de sucursales (arrays asociativos)
     */
    public function findAllActive(): array
    {
        $query = "SELECT id, name, phone, fax, street, city, state, website, email
                  FROM facility
                  WHERE accepts_assignment = 1"; // Asumiendo que esta es la condición para activas

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve array de arrays
    }

    /**
     * Busca una sucursal activa por su ID.
     * @param int $id
     * @return array|null Array asociativo con los datos o null si no se encuentra
     */
    public function findActiveById(int $id): ?array
    {
        $query = "SELECT id, name, phone, fax, street, city, state, website, email
                  FROM facility
                  WHERE accepts_assignment = 1 AND id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $facility = $stmt->fetch(PDO::FETCH_ASSOC); // Devuelve un solo array o false

        return $facility ?: null; // Devuelve el array o null
    }

    // Puedes añadir más métodos si necesitas (ej. findByName, save, update, etc.)
}