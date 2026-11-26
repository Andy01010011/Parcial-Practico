<?php
/**
 * Utilidades estáticas para limpiar entradas de usuario y escapar salidas HTML.
 */
class Sanitizador {
    /**
     * Recorta espacios en los extremos y elimina etiquetas HTML o PHP.
     */
    public static function texto(?string $valor): string {
        $limpio = trim((string) ($valor ?? ''));
        return strip_tags($limpio);
    }

    /**
     * Limpia espacios y aplica el filtro de saneamiento de correo electrónico.
     */
    public static function email(?string $valor): string {
        $limpio = trim((string) ($valor ?? ''));
        return filter_var($limpio, FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Conserva solo letras, números y guiones bajos para nombres de usuario.
     */
    public static function usuario(?string $valor): string {
        $limpio = trim((string) ($valor ?? ''));
        return preg_replace('/[^a-zA-Z0-9_]/', '', $limpio) ?? '';
    }

    /**
     * Filtra caracteres no numéricos y convierte el resultado a entero.
     */
    public static function numero(?string $valor): int {
        $limpio = trim((string) ($valor ?? ''));
        return (int) filter_var($limpio, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Escapa caracteres especiales para imprimir texto de forma segura en HTML (previene XSS).
     */
    public static function salidaHtml(?string $valor): string {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
