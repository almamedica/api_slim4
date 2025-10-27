<?php
// src/dependencies.php

use DI\Container;
use Psr\Container\ContainerInterface;
use Monolog\Logger;              // Logging library
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
            'path' => 'php://stderr', // Ruta al archivo de log
            'level' => Logger::DEBUG,               // Nivel mínimo para loguear (DEBUG muestra todo)
        ];
        $logger = new Logger($settings['name']);

        // Añadir ID único a cada entrada de log
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        // Definir dónde escribir los logs
        // NOTA: En Cloud Run, los logs a STDOUT/STDERR son capturados automáticamente.
        // Escribir a un archivo puede no ser la mejor estrategia en Cloud Run
        // a menos que tengas un volumen montado, pero para debugging inicial está bien.
        $handler = new StreamHandler($settings['path'], $settings['level']);
        $logger->pushHandler($handler);

        return $logger;
    });

    // --- Configuración de PDO (Base de Datos) ---
    $container->set(PDO::class, function (ContainerInterface $c) {
        // Obtener credenciales desde variables de entorno
        $db_user = $_ENV['DB_USER'] ?? 'root';
        $db_pass = $_ENV['DB_PASSWORD'] ?? '';
        $db_name = $_ENV['DB_NAME'] ?? 'test';
        
        // Usamos DB_HOST para todo.
        // En local, será '127.0.0.1' (o lo que tengas en .env)
        // En Cloud Run, será la ruta '/cloudsql/...'
        $db_host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $db_port = $_ENV['DB_PORT'] ?? '3306';
        
        $dsn = '';

        // --- Lógica Corregida ---
        // Comprueba si DB_HOST es una ruta de socket de Cloud SQL
        if (str_starts_with($db_host, '/cloudsql/')) {
            // DSN para Cloud SQL Socket
            // $db_host ya contiene la ruta completa
            $dsn = sprintf('mysql:dbname=%s;unix_socket=%s;charset=utf8',
                            $db_name, $db_host);
        } else {
            // DSN para conexión local estándar (usa el $db_host '127.0.0.1')
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8',
                            $db_host, $db_port, $db_name);
        }
        // --- Fin de la Lógica ---

        try {
            // Crear la instancia de PDO
            $pdo = new PDO($dsn, $db_user, $db_pass);
            // Configurar PDO para mayor seguridad y consistencia
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 
            return $pdo;
        } catch (\PDOException $e) {
            // Obtener el logger para registrar el error crítico
            $logger = $c->get(LoggerInterface::class);
            // Registramos el error real de PDO
            $logger->critical('Error de conexión a la base de datos', [
                'dsn_attempted' => $dsn, // Logueamos el DSN que se intentó usar
                'error_message' => $e->getMessage()
            ]); 
            // Lanzar una excepción genérica para no exponer detalles
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

}; // Fin de la función anónima

return $dependencies; // Devuelve la función para que index.php la pueda ejecutar