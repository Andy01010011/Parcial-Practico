<?php
// Inicia la sesión para acceder a los datos temporales del flujo 2FA
session_start();

// Carga de dependencias: conexión a BD, protección CSRF y lógica de autenticación 2FA
require_once __DIR__ . '/../config/Database.php';          // Conexión a la base de datos
require_once __DIR__ . '/../clases/Csrf.php';              // Protección contra ataques CSRF
require_once __DIR__ . '/../clases/Autenticacion2FA.php';  // Verificación de códigos TOTP

// Recupera de la sesión el ID del usuario que está en proceso de verificación
$usuarioId = $_SESSION['2fa_usuario_id'] ?? null;
// Indica si el usuario ya visualizó/solicitó su código QR en el paso anterior
$qrSolicitado = $_SESSION['2fa_qr_solicitado'] ?? false;

// Control de acceso: si no hay usuario en proceso o no pasó por la pantalla del QR,
// se le devuelve a activar_2fa.php (evita saltarse pasos del flujo)
if (!$usuarioId || $qrSolicitado !== true) {
    header('Location: activar_2fa.php');
    exit;
}

// Lista de mensajes de error a mostrar en la vista
$errores = [];

// Crea la conexión a la BD y el objeto que maneja el 2FA
// 'ParcialPractico' es el nombre del emisor (issuer) que aparece en Google Authenticator
$pdo = Database::conectar();
$auth2fa = new Autenticacion2FA($pdo, 'ParcialPractico');

// Solo procesa el formulario cuando la petición es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtiene el token CSRF enviado en el formulario
    $token = $_POST['csrf_token'] ?? '';
    // Obtiene el código 2FA y elimina cualquier espacio en blanco (ej. "123 456" -> "123456")
    $codigo = preg_replace('/\s+/', '', (string) ($_POST['codigo_2fa'] ?? ''));

    // Validación 1: el token CSRF debe ser válido
    if (!Csrf::validar($token)) {
        $errores[] = 'Token CSRF inválido. Recarga la página.';
    // Validación 2: el código debe tener exactamente 6 dígitos numéricos
    } elseif (!preg_match('/^[0-9]{6}$/', $codigo)) {
        $errores[] = 'Ingresa un código numérico de 6 dígitos.';
    // Validación 3: el código debe coincidir con el generado por la app autenticadora
    } elseif (!$auth2fa->verificarCodigo((int) $usuarioId, $codigo)) {
        $errores[] = 'El código 2FA es incorrecto o ha expirado.';
    } else {
        // Código correcto: marca como usado el último registro de QR del usuario
        // para que no pueda reutilizarse
        $stmtUsado = $pdo->prepare(
            "UPDATE codigos_qr SET usado = 1 WHERE usuario_id = ? ORDER BY id DESC LIMIT 1"
        );
        $stmtUsado->execute([(int) $usuarioId]);

        // Regenera el ID de sesión al elevar privilegios (previene fijación de sesión)
        session_regenerate_id(true);

        // Establece la sesión autenticada definitiva
        $_SESSION['autenticado'] = true;
        $_SESSION['usuario_id'] = (int) $usuarioId;
        $_SESSION['usuario'] = $_SESSION['2fa_usuario'] ?? '';
        $_SESSION['correo'] = $_SESSION['2fa_correo'] ?? '';

        // Elimina todos los datos temporales del flujo 2FA, que ya no son necesarios
        unset(
            $_SESSION['2fa_usuario_id'],
            $_SESSION['2fa_usuario'],
            $_SESSION['2fa_correo'],
            $_SESSION['2fa_qr_solicitado'],
            $_SESSION['2fa_secreto_pendiente'],
            $_SESSION['2fa_qr_url'],
            $_SESSION['2fa_qr_usuario_id']
        );

        // Redirige a la página principal del sistema
        header('Location: bienvenido.php');
        exit;
    }
}

// Genera un nuevo token CSRF para incluirlo en el formulario
$token = Csrf::generar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verificar Código 2FA</title>
  <!-- Hoja de estilos del formulario -->
  <link rel="stylesheet" href="css/Estilos.css">
</head>
<body>
  <!-- Formulario de verificación, envía el código por POST a esta misma página -->
  <form method="POST" action="verificar_2fa.php">
    <h2>Verificación en Dos Pasos</h2>

    <!-- Muestra la lista de errores si existen -->
    <?php if ($errores): ?>
      <div class="alert error">
        <ul>
          <?php foreach ($errores as $err): ?>
            <!-- htmlspecialchars evita inyección XSS al imprimir mensajes -->
            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Instrucción para el usuario -->
    <p class="enlace">Ingresa el código de 6 dígitos generado en Google Authenticator:</p>

    <!-- Token CSRF oculto, se validará al enviar el formulario -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Campo del código: solo 6 dígitos; autocomplete="one-time-code" permite autocompletar desde SMS/apps -->
    <label>Código de verificación</label>
    <input type="text" name="codigo_2fa" required pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456">

    <button type="submit">Entrar al sistema</button>

    <!-- Permite volver a ver el QR si el usuario no lo escaneó correctamente -->
    <p class="enlace"><a href="activar_2fa.php">Volver a mostrar el código QR</a></p>
  </form>
</body>
</html>