<?php
// src/Services/AuthService.php

namespace App\Services;

use PDO;
use Firebase\JWT\JWT;
use App\Repositories\UserRepository; // Asumiremos un UserRepository más adelante

class AuthService
{
    private PDO $db; // Podríamos inyectar repositorios en lugar de PDO directamente
    private string $jwtSecret;

    // Inyectamos PDO (o idealmente, repositorios)
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
        $this->jwtSecret = $_ENV['JWT_SECRET_KEY'] ?? 'tu_clave_secreta_por_defecto';
    }

    public function loginWithCredentials(string $username, string $password): ?array
    {
        // Esta lógica es idéntica a la que teníamos en AuthController::authenticateWithCredentials
        $query = "SELECT uc.id, uc.username, uc.password, u.user_group FROM users_secure uc INNER JOIN users u ON uc.id = u.id WHERE uc.username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':username' => $username]);
        if ($stmt->rowCount() === 0) return ["respuesta" => "Nombre de usuario inválido"];
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        if (!password_verify($password, $user->password)) return ["respuesta" => "Contraseña inválida"];

        return $this->generateTokenAndUserData($user->id, $user->user_group, $password);
    }

    public function loginWithApiSecret(string $apiSecret): ?array
    {
        // Esta lógica es idéntica a la que teníamos en AuthController::authenticateWithApiSecret
        $query = "SELECT user_id, api_secret_hash FROM users_secret";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $apiSecrets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $userId = null;
        $hashedPasswordForToken = null;
        foreach ($apiSecrets as $secret) {
            if (password_verify($apiSecret, $secret['api_secret_hash'])) {
                $userId = $secret['user_id'];
                $hashedPasswordForToken = $secret['api_secret_hash'];
                break;
            }
        }
        if ($userId === null) return ["respuesta" => "API Secret inválida"];

        $stmtGroup = $this->db->prepare("SELECT user_group FROM users WHERE id = :id");
        $stmtGroup->execute([':id' => $userId]);
        $user = $stmtGroup->fetch(PDO::FETCH_OBJ);
        if (!$user) return ["respuesta" => "Usuario asociado a API Secret no encontrado"];

        return $this->generateTokenAndUserData($userId, $user->user_group, null, $hashedPasswordForToken);
    }

    // generateTokenAndUserData se mantiene casi igual, solo que ahora es parte del servicio
    private function generateTokenAndUserData(int $id, int $user_group, ?string $password = null, ?string $hashedPassword = null): ?array
    {
        // ... (La lógica para obtener datos del usuario, generar JWT y guardar/actualizar token sigue aquí, idéntica a como estaba en AuthController) ...
         // Obtener datos del usuario
        $query = "SELECT u.federaltaxid AS rut, fac.id AS id_caja, uf.fk_facility AS facility, u.email, u.urlfoto
                  FROM users u
                  LEFT JOIN facility_cajas fac ON u.id = fac.id_user
                  LEFT JOIN users_facility uf ON u.id = uf.fk_user
                  WHERE u.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        $datosUser = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$datosUser) return ["respuesta" => "Datos de usuario no encontrados"];
        $email_user = trim($datosUser->email);
        if (!filter_var($email_user, FILTER_VALIDATE_EMAIL)) return ["respuesta" => "Email inválido"];

        // Generar token JWT
        $time = time();
        $tokenData = ['iat' => $time, 'exp' => $time + 3600, 'id' => $id, 'email_user' => $email_user];
        $jwt = JWT::encode($tokenData, $this->jwtSecret, 'HS256');
        $exp = $tokenData['exp'];

        // Actualizar/Insertar token en BD
        $stmtToken = $this->db->prepare("SELECT id_user FROM users_token WHERE id_user = :id");
        $stmtToken->execute([':id' => $id]);
        if ($stmtToken->rowCount() > 0) {
            $update = "UPDATE users_token SET token = :token, token_exp = :exp WHERE id_user = :id";
            $stmtUpdate = $this->db->prepare($update);
            $stmtUpdate->execute([':token' => $jwt, ':exp' => $exp, ':id' => $id]);
        } else {
             $passwordToStore = $hashedPassword ?? ($password ? password_hash($password, PASSWORD_BCRYPT) : null);
             if (!$passwordToStore) throw new \Exception("No se pudo determinar la contraseña para el nuevo registro de token.");
             $insert = "INSERT INTO users_token (id_user, email_user, token, token_exp, password_user) VALUES (:id, :email, :token, :exp, :password)";
             $stmtInsert = $this->db->prepare($insert);
             $stmtInsert->execute([':id' => $id, ':email' => $email_user, ':token' => $jwt, ':exp' => $exp, ':password' => $passwordToStore]);
        }

        // Construir datos de respuesta
        $host = $_SERVER["HTTP_HOST"] ?? 'localhost';
        $baseUrl = ($host === 'localhost') ? "http://$host/api_slim4/" : "/";
        $foto = (!empty($datosUser->urlfoto)) ? $baseUrl . $datosUser->urlfoto : null;

        return [
            "respuesta" => "ok",
            "user_id" => $id, "user_rut" => $datosUser->rut, "user_email" => $email_user,
            "user_group" => $user_group, "cash_register_id" => $datosUser->id_caja,
            "facility_id" => $datosUser->facility, "user_photo" => $foto,
            "token" => $jwt, "token_exp" => $exp
        ];
    }
}