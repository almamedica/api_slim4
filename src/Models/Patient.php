<?php

namespace App\Models;

class Patient
{
    // Propiedades públicas para fácil acceso (puedes hacerlas privadas con getters/setters si prefieres)
    public ?int $id = null;
    public ?string $rut = null;
    public ?string $nombre = null;
    public ?string $paterno = null;
    public ?string $materno = null;
    public ?string $direccion = null;
    public ?string $sexo = null;
    public ?string $fecha_nacimiento = null;
    public ?string $prevision = null;
    public ?string $celular = null;
    public ?string $telefono_casa = null;
    public ?string $email = null;
    public ?string $comuna_id = null;
    public ?string $region_id = null;
    public ?string $country_code = null;
    public ?string $occupation = null;
    public ?string $passport = null;

    // Puedes añadir un constructor o métodos aquí si necesitas
}