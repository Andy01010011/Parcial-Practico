<?php
/**
 * Gestiona la conexión a la base de datos MySQL mediante PDO.
 */
class Database {
    /**
     * Crea y devuelve una instancia de PDO configurada para el proyecto.
     */
    public static function conectar(): PDO {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=proyecto_web;charset=utf8mb4',
            'app_web',
            'ClaveSegura#2026',
            [
                // Lanza excepciones PDO ante errores de consulta o conexión
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Devuelve los resultados como arreglos asociativos por defecto
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;
    }
}
