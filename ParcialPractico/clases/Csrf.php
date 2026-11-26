<?php
/**
 * Genera y valida tokens anti-CSRF almacenados en la sesión del usuario.
 */
class Csrf {
    /**
     * Obtiene el token CSRF de la sesión o crea uno nuevo si aún no existe.
     */
    public static function generar(): string {
        if (empty($_SESSION['csrf_token'])) {
            // Genera 32 bytes aleatorios criptográficamente seguros y los convierte a hexadecimal
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Compara el token recibido contra el de la sesión de forma segura.
     */
    public static function validar(string $token): bool {
        // hash_equals evita ataques de temporización (timing attacks)
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
