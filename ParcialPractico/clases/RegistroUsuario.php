<?php
/**
 * Coordina la validación, verificación de duplicados y registro de nuevos usuarios.
 */
class RegistroUsuario {
    private PDO $pdo;
    private HashInterface $hasher;

    /**
     * Recibe la conexión PDO y el hasheador mediante inyección de dependencias.
     */
    public function __construct(PDO $pdo, HashInterface $hasher) {
        $this->pdo = $pdo;
        $this->hasher = $hasher;
    }

    /**
     * Valida los datos básicos del formulario de registro y devuelve los errores encontrados.
     */
    public function validarCampos(string $usuario, string $email, string $pass, string $pass2): array {
        $errores = [];

        if ($usuario === '' || strlen($usuario) < 4) {
            $errores[] = 'El usuario debe tener al menos 4 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no es válido.';
        }
        if (strlen($pass) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($pass !== $pass2) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        return $errores;
    }

    /**
     * Comprueba si ya existe un registro con el valor indicado en 'usuario' o 'email'.
     */
    public function existe(string $campo, string $valor): bool {
        // Lista blanca para evitar interpolar nombres de columna no permitidos en SQL
        if (!in_array($campo, ['usuario', 'email'], true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE $campo = ?");
        $stmt->execute([$valor]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Inserta el nuevo usuario guardando su contraseña ya hasheada.
     */
    public function guardar(string $usuario, string $email, string $pass): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (usuario, email, password_hash) VALUES (?, ?, ?)"
        );
        // Genera el hash de la contraseña antes de almacenarla en la base de datos
        $stmt->execute([$usuario, $email, $this->hasher->generar($pass)]);
    }

    /**
     * Ejecuta el flujo completo de registro; retorna un arreglo vacío si todo sale bien.
     */
    public function registrar(string $usuario, string $email, string $pass, string $pass2): array {
        $errores = $this->validarCampos($usuario, $email, $pass, $pass2);
        if ($errores) return $errores;

        // Verifica que ni el nombre de usuario ni el correo estén ya en uso
        if ($this->existe('usuario', $usuario)) $errores[] = 'Ese usuario ya existe.';
        if ($this->existe('email', $email))     $errores[] = 'Ese correo ya está registrado.';
        if ($errores) return $errores;

        $this->guardar($usuario, $email, $pass);
        return [];
    }
}
