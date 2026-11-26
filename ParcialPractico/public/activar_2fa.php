<?php
// Genera y muestra el código QR / secreto 2FA para vincular la cuenta antes de verificar el código.
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../clases/Csrf.php';
require_once __DIR__ . '/../clases/Autenticacion2FA.php';

// Verifica que exista un usuario en etapa previa de 2FA; si no, regresa al login
$usuarioId = $_SESSION['2fa_usuario_id'] ?? null;
$correo = $_SESSION['2fa_correo'] ?? null;

if (!$usuarioId || !$correo) {
    header('Location: login.php');
    exit;
}

// Conecta a la base de datos y genera el secreto + URL del QR para el usuario actual
$pdo = Database::conectar();
$auth2fa = new Autenticacion2FA($pdo, 'ParcialPractico');

$datosQr = $auth2fa->generarYGuardarSecreto((int) $usuarioId, (string) $correo);

// Marca en sesión que ya se solicitó el QR para habilitar el paso de verificación
$_SESSION['2fa_qr_solicitado'] = true;

// Genera el token CSRF que viajará oculto hacia verificar_2fa.php
$token = Csrf::generar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configurar Código QR (2FA)</title>
  <link rel="stylesheet" href="css/Estilos.css">
</head>
<body>
  <form method="POST" action="verificar_2fa.php">
    <h2>Vincular Google Authenticator</h2>
    <p class="enlace">Escanea este código QR con Google Authenticator antes de continuar:</p>

    <!-- Muestra la imagen del QR escapada para evitar inyección en atributos HTML -->
    <div style="text-align:center; margin: 18px 0;">
      <img src="<?= htmlspecialchars($datosQr['qr_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Escanea este QR con Google Authenticator">
    </div>

    <!-- Clave secreta en texto por si el usuario prefiere ingresarla manualmente -->
    <p class="enlace">Clave manual: <strong><?= htmlspecialchars($datosQr['secreto'], ENT_QUOTES, 'UTF-8') ?></strong></p>

    <!-- Token anti-CSRF para proteger el envío hacia verificar_2fa.php -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <label>Código de 6 dígitos</label>
    <input type="text" name="codigo_2fa" required pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456">

    <button type="submit">Verificar y continuar</button>
  </form>
</body>
</html>
