<?php
// Valida las credenciales del usuario e inicia el paso previo de autenticación 2FA.
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../clases/Sanitizador.php';
require_once __DIR__ . '/../clases/Csrf.php';
require_once __DIR__ . '/../clases/HashInterface.php';
require_once __DIR__ . '/../clases/PasswordHasher.php';

$errores = [];
$identificador = '';

// Procesa el envío del formulario solo mediante método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    // Verifica que el token CSRF coincida con el almacenado en sesión
    if (!Csrf::validar($token)) {
        $errores[] = 'Token CSRF inválido. Recarga la página.';
    } else {
        $identificador = trim((string) ($_POST['identificador'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($identificador === '' || $password === '') {
            $errores[] = 'Debes ingresar tu usuario/correo y contraseña.';
        } else {
            $pdo = Database::conectar();
            $hasher = new PasswordHasher();

            // Determina si el identificador es un correo o un nombre de usuario y lo limpia
            $esCorreo = filter_var($identificador, FILTER_VALIDATE_EMAIL) !== false;
            $campoLimpio = $esCorreo
                ? Sanitizador::email($identificador)
                : Sanitizador::usuario($identificador);

            // Busca el usuario con consulta preparada para evitar inyección SQL
            $sql = $esCorreo
                ? "SELECT id, usuario, email, password_hash FROM usuarios WHERE email = ? LIMIT 1"
                : "SELECT id, usuario, email, password_hash FROM usuarios WHERE usuario = ? LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$campoLimpio]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Comprueba que el usuario exista y que la contraseña coincida con su hash
            if (!$user || !$hasher->validar($password, (string) $user['password_hash'])) {
                $errores[] = 'Credenciales incorrectas.';
            } else {
                // Regenera el ID de sesión para mitigar fijación de sesión
                session_regenerate_id(true);

                // Guarda datos temporales de 2FA y limpia cualquier solicitud QR previa
                $_SESSION['2fa_usuario_id'] = (int) $user['id'];
                $_SESSION['2fa_usuario'] = (string) $user['usuario'];
                $_SESSION['2fa_correo'] = (string) $user['email'];
                unset($_SESSION['2fa_qr_solicitado']);

                header('Location: activar_2fa.php');
                exit;
            }
        }
    }
}

// Genera u obtiene el token CSRF para incluirlo en el formulario
$token = Csrf::generar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar Sesión</title>
  <link rel="stylesheet" href="css/Estilos.css">
</head>
<body>
  <form method="POST" action="login.php">
    <h2>Iniciar Sesión</h2>

    <!-- Muestra mensajes de error escapados contra XSS -->
    <?php if ($errores): ?>
      <div class="alert error">
        <ul>
          <?php foreach ($errores as $err): ?>
            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Campo oculto con el token anti-CSRF -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <label>Usuario o correo electrónico</label>
    <input type="text" name="identificador" required value="<?= htmlspecialchars($identificador, ENT_QUOTES, 'UTF-8') ?>" placeholder="usuario o correo@ejemplo.com">

    <label>Contraseña</label>
    <input type="password" name="password" required placeholder="Tu contraseña">

    <button type="submit">Continuar al Código QR</button>

    <p class="enlace">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
  </form>
</body>
</html>
