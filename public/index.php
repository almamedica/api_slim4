<?php

// public/index.php

// Declarar el uso estricto de tipos mejora la calidad del código (Opcional)
declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use Dotenv\Dotenv;

// Incluir el autoload de Composer (esencial)
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno desde el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad no falla si .env no existe, útil para producción

// Crear el contenedor de Inyección de Dependencias (PHP-DI)
$container = new Container();

// Configurar las dependencias definidas en src/dependencies.php
// Esta línea incluye el archivo y ejecuta la función $dependencies que definimos allí.
$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($container);

// Crear la instancia de la aplicación Slim usando el contenedor DI
AppFactory::setContainer($container);
$app = AppFactory::create();

if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
    $app->setBasePath('/api_slim4');
}

// --- Middleware ---

// Middleware para parsear el cuerpo de las peticiones (JSON, form-data, etc.)
// Debe ir antes del middleware de ruteo.
$app->addBodyParsingMiddleware();

// Middleware de Ruteo de Slim. Esencial para que las rutas funcionen.
// Debe ir justo antes del middleware de errores.
$app->addRoutingMiddleware();

// Middleware para manejo de errores. Captura excepciones y muestra errores.
// Es importante configurarlo según el entorno (desarrollo vs. producción).
$displayErrorDetails = ($_ENV['APP_ENV'] ?? 'development') === 'development'; // Lee 'APP_ENV' desde .env
$logErrors = true; // Siempre es bueno loguear errores
$logErrorDetails = ($_ENV['APP_ENV'] ?? 'development') === 'development'; // Loguear detalles solo en desarrollo

// Añadir el middleware de errores
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);

// --- Rutas ---

// Definir las rutas de la aplicación desde src/routes.php
// Esta línea incluye el archivo y ejecuta la función $routes que definimos allí.
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

// --- Ejecutar la aplicación ---
// Esta línea inicia el procesamiento de la petición y envía la respuesta.
$app->run();

