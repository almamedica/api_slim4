<?php
// src/Repositories/PatientRepository.php

namespace App\Repositories;

use PDO;
use App\Models\Patient; // Opcional: si usas el Modelo

class PatientRepository
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Busca un paciente por su RUT.
     * Devuelve un objeto Patient o null si no se encuentra.
     */
    public function findByRut(string $rut): ?Patient // O puedes devolver ?array si no usas el Modelo
    {
        $query = "SELECT pd.id, pd.ss AS rut, pd.fname as nombre, pd.lname paterno, pd.mname materno,
                        pd.street direccion,
                        substr(pd.sex, 1, 1) as sexo, pd.DOB as fecha_nacimiento, pd.prevision as prevision,
                        pd.phone_cell celular, pd.phone_home telefono_casa, pd.email email,
                        pd.state comuna_id, pd.city region_id,
                        pd.country_code, pd.occupation, pd.passport
                  FROM patient_data pd
                  WHERE pd.ss = :rut";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':rut', $rut, PDO::PARAM_STR);
        $stmt->execute();

        // Usamos FETCH_CLASS para mapear directamente a nuestro Modelo Patient
        // Si no usas Modelo, usa FETCH_ASSOC
        $stmt->setFetchMode(PDO::FETCH_CLASS, Patient::class);
        $patient = $stmt->fetch();

        return $patient ?: null; // Devuelve el objeto Patient o null
    }

    /**
     * Busca pacientes por varios criterios.
     * Devuelve un array de objetos Patient (o arrays asociativos).
     */
    public function search(array $params): array
    {
        $query = "SELECT pd.id, pd.ss AS rut, pd.fname as nombre, pd.lname paterno, pd.mname materno,
                        pd.street direccion,
                        substr(pd.sex, 1, 1) as sexo, pd.DOB as fecha_nacimiento, pd.prevision as prevision,
                        pd.phone_cell celular, pd.phone_home telefono_casa, pd.email email,
                        pd.state comuna_id, pd.city region_id,
                        pd.country_code, pd.occupation, pd.passport
                  FROM patient_data pd
                  WHERE 1=1";
        $binds = [];

        // Construcción dinámica (igual que antes)
        if (!empty($params['rut'])) { $query .= " AND pd.ss = :rut"; $binds[':rut'] = $params['rut']; }
        if (!empty($params['nombre'])) { $query .= " AND pd.fname LIKE :nombre_fname"; $binds[':nombre_fname'] = '%' . $params['nombre'] . '%'; }
        if (!empty($params['apellido'])) { $query .= " AND (pd.lname LIKE :apellido_lname OR pd.mname LIKE :apellido_mname)"; $binds[':apellido_lname'] = '%' . $params['apellido'] . '%'; $binds[':apellido_mname'] = '%' . $params['apellido'] . '%';}
        if (!empty($params['fecha_nacimiento'])) { $query .= " AND pd.DOB = :fecha"; $binds[':fecha'] = $params['fecha_nacimiento']; }
        if (!empty($params['email'])) { $query .= " AND pd.email LIKE :email"; $binds[':email'] = '%' . $params['email'] . '%'; }
        if (!empty($params['telefono'])) { $query .= " AND (pd.phone_cell LIKE :telefono_cell OR pd.phone_home LIKE :telefono_home)"; $binds[':telefono_cell'] = '%' . $params['telefono'] . '%'; $binds[':telefono_home'] = '%' . $params['telefono'] . '%';}

        $stmt = $this->db->prepare($query);
        foreach ($binds as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        // Devolvemos todos los resultados como objetos Patient
        // Si no usas Modelo, usa FETCH_ASSOC
        return $stmt->fetchAll(PDO::FETCH_CLASS, Patient::class);
    }

    // Aquí añadirías métodos para insertar (save), actualizar (update), etc.
}