<?php
// Carga la interfaz HashInterface que esta clase implementa
require_once __DIR__ . '/HashInterface.php';

/**
 * Implementación de HashInterface usando las funciones nativas de hashing de PHP.
 */
class PasswordHasher implements HashInterface {
    /**
     * Genera un hash seguro con salt automático usando el algoritmo por defecto de PHP.
     */
    public function generar(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifica una contraseña en texto plano contra su hash de forma segura.
     */
    public function validar(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
