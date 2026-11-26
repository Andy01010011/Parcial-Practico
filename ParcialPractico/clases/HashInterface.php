<?php
/**
 * Contrato para clases encargadas de generar y verificar hashes de contraseñas.
 */
interface HashInterface {
    /**
     * Genera un hash seguro a partir de una contraseña en texto plano.
     */
    public function generar(string $password): string;

    /**
     * Verifica si una contraseña coincide con un hash almacenado.
     */
    public function validar(string $password, string $hash): bool;
}
