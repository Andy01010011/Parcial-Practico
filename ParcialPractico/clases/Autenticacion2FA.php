<?php
// Carga el autoloader de Composer para usar las librerías externas instaladas.
require_once __DIR__ . '/../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

/**
 * Gestiona la generación de secretos TOTP, URLs de código QR y verificación 2FA.
 */
class Autenticacion2FA
{
    private PDO $pdo;
    private GoogleAuthenticator $ga;
    private string $nombreApp;

    /**
     * Inicializa la conexión a base de datos, el autenticador TOTP y el nombre de la app.
     */
    public function __construct(PDO $pdo, string $nombreApp = 'ParcialPractico')
    {
        $this->pdo = $pdo;
        $this->ga = new GoogleAuthenticator();
        $this->nombreApp = $nombreApp;
    }

    /**
     * Genera un nuevo secreto 2FA, lo guarda en el usuario y construye la URL del QR.
     */
    public function generarYGuardarSecreto(int $usuarioId, string $correo): array
    {
        // Genera una clave secreta aleatoria compatible con Google Authenticator (Base32)
        $secreto = $this->ga->generateSecret();

        // Guarda el secreto 2FA asociado al usuario
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios SET secret_2fa = ? WHERE id = ?"
        );
        $stmt->execute([$secreto, $usuarioId]);

        // Registra el código QR como no usado (usado = 0)
        $stmtQr = $this->pdo->prepare(
            "INSERT INTO codigos_qr (usuario_id, codigo, usado) VALUES (?, ?, 0)"
        );
        $stmtQr->execute([$usuarioId, substr($secreto, 0, 64)]);

        // Limpia espacios en el correo y caracteres especiales en el nombre de la aplicación
        $correoLimpio = str_replace(' ', '', trim($correo));
        $appLimpia = preg_replace('/[^a-zA-Z0-9_-]/', '', $this->nombreApp) ?: 'ParcialPractico';

        // Construye el URI estándar otpauth para aplicaciones TOTP
        $otpauthUri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($appLimpia),
            rawurlencode($correoLimpio),
            rawurlencode($secreto),
            rawurlencode($appLimpia)
        );

        // Genera la URL de imagen del QR mediante QuickChart
        $qrUrl = 'https://quickchart.io/qr?size=220x220&text=' . urlencode($otpauthUri);

        return [
            'secreto' => $secreto,
            'qr_url'  => $qrUrl,
        ];
    }

    /**
     * Recupera el secreto 2FA almacenado para un usuario; devuelve null si no existe.
     */
    public function obtenerSecretoPorUsuarioId(int $usuarioId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT secret_2fa FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        $secreto = $stmt->fetchColumn();

        return $secreto ? (string) $secreto : null;
    }

    /**
     * Verifica el código 2FA ingresado considerando una tolerancia de ventanas de tiempo.
     */
    public function verificarCodigo(int $usuarioId, string $codigoIngresado, int $discrepancia = 2): bool
    {
        $secreto = $this->obtenerSecretoPorUsuarioId($usuarioId);
        if (!$secreto) {
            return false;
        }

        // Valida el código de 6 dígitos con margen de discrepancia de intervalos de 30s
        return $this->ga->checkCode($secreto, trim($codigoIngresado), $discrepancia);
    }
}
