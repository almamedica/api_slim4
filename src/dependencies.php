<?php
// src/dependencies.php

use DI\Container;
use Psr\Container\ContainerInterface;
use Monolog\Logger;                 // Logging library
use Monolog\Handler\StreamHandler;  // Handler to write logs to file
use Monolog\Processor\UidProcessor; // Processor to add unique ID to logs
use Psr\Log\LoggerInterface;        // Standard interface for logging

// Importar las clases de Repositorios y Servicios
use App\Repositories\PatientRepository;
use App\Services\AuthService;
use App\Services\PatientService;
use App\Repositories\FacilityRepository;
use App\Services\FacilityService;
use App\Repositories\SpecialityRepository;
use App\Services\SpecialityService;

$dependencies = function (Container $container) {

    // --- Configuración de Logging (Monolog) ---
    $container->set(LoggerInterface::class, function (ContainerInterface $c) {
        $settings = [
            'name' => 'api-slim4',
            'path' => __DIR__ . '/../var/log/app.log', // Ruta al archivo de log
            'level' => Logger::DEBUG,                  // Nivel mínimo para loguear (DEBUG muestra todo)
        ];
        $logger = new Logger($settings['name']);

        // Añadir ID único a cada entrada de log
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        // Definir dónde escribir los logs
        $handler = new StreamHandler($settings['path'], $settings['level']);
        $logger->pushHandler($handler);

        return $logger;
    });

    // --- Configuración de PDO (Base de Datos) ---
    $container->set(PDO::class, function (ContainerInterface $c) {
        // Obtener credenciales desde variables de entorno (.env) con valores por defecto
        $db_user = $_ENV['DB_USER'] ?? 'root';
        $db_pass = $_ENV['DB_PASSWORD'] ?? '';
        $db_name = $_ENV['DB_NAME'] ?? 'test';
        $instance_connection_name = $_ENV['DB_INSTANCE_CONNECTION_NAME'] ?? null; // Para Cloud Run
        $socket_dir = '/cloudsql/'; // Directorio del socket en Cloud Run

        // Determinar si usar conexión por socket (Cloud Run) o host/puerto (Local/XAMPP)
        if ($instance_connection_name) {
             // DSN para Cloud SQL Socket
             $dsn = sprintf('mysql:dbname=%s;unix_socket=%s%s;charset=utf8',
                            $db_name, $socket_dir, $instance_connection_name);
        } else {
             // DSN para conexión local estándar
             $db_host = $_ENV['DB_HOST'] ?? '127.0.0.1'; // Usar 127.0.0.1 es a menudo más fiable que localhost
             $db_port = $_ENV['DB_PORT'] ?? '3306';
             $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8',
                            $db_host, $db_port, $db_name);
        }

        try {
            // Crear la instancia de PDO
            $pdo = new PDO($dsn, $db_user, $db_pass);
            // Configurar PDO para mayor seguridad y consistencia
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Lanzar excepciones en errores SQL
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Devolver arrays asociativos por defecto
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Usar preparaciones nativas (más seguro)
            return $pdo;
        } catch (\PDOException $e) {
            // Obtener el logger para registrar el error crítico
            $logger = $c->get(LoggerInterface::class);
            $logger->critical('Error de conexión a la base de datos', ['exception' => $e]); // Registrar el error detallado
            // Lanzar una excepción genérica para no exponer detalles al usuario
            throw new \RuntimeException('Error de conexión a la base de datos.');
        }
    });

    // --- Registrar Repositorios y Servicios ---
    // Usamos autowire() para que PHP-DI resuelva e inyecte automáticamente las dependencias (PDO, Logger)
    // en los constructores de estas clases.
    $container->set(PatientRepository::class, \DI\autowire());
    $container->set(AuthService::class, \DI\autowire());
    $container->set(PatientService::class, \DI\autowire());
    $container->set(FacilityRepository::class, \DI\autowire());
    $container->set(FacilityService::class, \DI\autowire());
    $container->set(SpecialityRepository::class, \DI\autowire());
    $container->set(SpecialityService::class, \DI\autowire());

    // Puedes añadir más dependencias aquí a medida que tu aplicación crezca
    // Ejemplo: Registrar un cliente HTTP si necesitas llamar a otras APIs
    // $container->set(HttpClientInterface::class, function() {
    //     return new GuzzleHttp\Client();
    // });

}; // Fin de la función anónima

return $dependencies; // Devuelve la función para que index.php la pueda ejecutar