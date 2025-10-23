<?php
// src/routes.php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Controllers\PatientController;
use App\Controllers\FacilityController;
use App\Controllers\SpecialityController;
use App\Controllers\AuthController;
use App\Middleware\ApiKeyMiddleware;
use App\Middleware\JwtAuthMiddleware;

$routes = function (App $app) {

    $app->get('/', function (Request $request, Response $response, array $args) {
        $response->getBody()->write("Bienvenido a la API v4!");
        return $response;
    });

    $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
        $name = $args['name'];
        $response->getBody()->write("Hello, $name");
        return $response;
    });

    // --- Rutas con Autenticación ---  //

    // Login: Protegido por API Key Middleware
    $app->post('/login', [AuthController::class, 'login'])
        ->add(ApiKeyMiddleware::class); // Aplicamos el middleware de API Key

    // Datos Paciente: Protegido por JWT Auth Middleware
    $app->get('/patient/{rut}', [PatientController::class, 'getPatientByRut'])
        ->add(JwtAuthMiddleware::class); // Aplicamos el middleware de JWT

    // Búsqueda de Pacientes por diferentes parametros: Protegida por JWT Auth Middleware
    $app->get('/patients/search', [PatientController::class, 'searchPatients'])
        ->add(JwtAuthMiddleware::class);

    // Búsqueda de Sucursales: Protegida por JWT Auth Middleware
    $app->get('/facility', [FacilityController::class, 'getAllFacilities'])
        ->add(JwtAuthMiddleware::class);

    // Obtener Sucursal por ID: Protegida por JWT Auth Middleware
    $app->get('/facility/{facility_id}', [FacilityController::class, 'getFacilityById'])
        ->add(JwtAuthMiddleware::class);

    // Búsqueda de Especialidades: Protegida por JWT Auth Middleware
    $app->get('/speciality', [SpecialityController::class, 'getAllSpecialities'])
        ->add(JwtAuthMiddleware::class);

    // Obtener prestaciones por ID de especialidad: Protegida por JWT Auth Middleware
    $app->get('/speciality/{speciality_id}', [SpecialityController::class, 'getSpecialityById'])
        ->add(JwtAuthMiddleware::class);

    // --- Aquí añadirías tus otras rutas y sus middlewares ---
    // $app->get('/facility', [FacilityController::class, 'getAll'])
    //     ->add(JwtAuthMiddleware::class); // Ejemplo: también protegido por JWT

};

return $routes;