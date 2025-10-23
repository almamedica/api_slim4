<?php

namespace App\Repositories;

use PDO;

class SpecialityRepository
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Busca todas las especialidades activas.
     * @return array Array de especialidades (arrays asociativos)
     */
    public function findAllActive(): array
    {
        $query = "SELECT  li.list_id, li.option_id, li.title, nom.id grupo, nom.nombre nombre_grupo 
                    FROM list_options li
                    LEFT JOIN especialidades_web espe ON li.option_id = espe.id_especialidad
                    LEFT JOIN nombre_especialidades nom ON espe.id_nombre_especialidad = nom.id
                    WHERE list_id = 'especialidades' AND activity = 1"; // Asumiendo que esta es la condición para activas

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve array de arrays
    }

    /**
     * Busca prestaciones activa por su ID de especialidad.
     * @param int $id
     * @return array|null Array asociativo con los datos o null si no se encuentra
     */
    public function findActiveById(int $id): ?array
    {
        $query = "SELECT *
                    FROM openemr_postcalendar_categories opc  
                    WHERE opc.pc_cattype='0' AND opc.pc_active = 1 AND opc.pc_especialidad = :especialidad";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':especialidad', $id, PDO::PARAM_INT);
        $stmt->execute();
        $facility = $stmt->fetch(PDO::FETCH_ASSOC); // Devuelve un solo array o false

        return $facility ?: null; // Devuelve el array o null
    }

    // Puedes añadir más métodos si necesitas (ej. findByName, save, update, etc.)
}