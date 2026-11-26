<?php
// Inicia la sesión para poder guardar datos del usuario entre páginas (CSRF, 2FA, etc.)
session_start();

// Carga de dependencias: conexión a BD y clases de apoyo
require_once __DIR__ . '/../config/Database.php';          // Conexión a la base de datos
require_once __DIR__ . '/../clases/Sanitizador.php';       // Limpieza de datos de entrada
require_once __DIR__ . '/../clases/Csrf.php';              // Protección contra ataques CSRF
require_once __DIR__ . '/../clases/HashInterface.php';     // Interfaz para algoritmos de hash
require_once __DIR__ . '/../clases/PasswordHasher.php';    // Implementación del hash de contraseñas
require_once __DIR__ . '/../clases/RegistroUsuario.php';   // Lógica de registro de usuarios

// Variables de estado de la vista
$errores = [];     // Lista de mensajes de error a mostrar
$exito = false;    // Bandera para mostrar mensaje de éxito
$usuario = '';     // Valor para repoblar el campo "usuario" del formulario
$email = '';       // Valor para repoblar el campo "email" del formulario

// Solo procesa el formulario cuando la petición es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtiene el token CSRF enviado en el formulario (vacío si no existe)
    $token = $_POST['csrf_token'] ?? '';

    // Valida el token CSRF para asegurar que la petición proviene de este formulario
    if (!Csrf::validar($token)) {
        $errores[] = 'Token CSRF inválido. Recarga la página.';
    } else {
        // Obtiene los datos crudos del formulario, forzando tipo string
        $usuarioRaw = (string) ($_POST['usuario'] ?? '');
        $emailRaw = (string) ($_POST['email'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password_confirm'] ?? '');

        // Sanitiza usuario y correo (las contraseñas no se sanitizan para no alterarlas)
        $usuario = Sanitizador::usuario($usuarioRaw);
        $email = Sanitizador::email($emailRaw);

        // Crea la conexión a la BD y las dependencias del registro
        $pdo = Database::conectar();
        $hasher = new PasswordHasher();                    // Se encargará de hashear la contraseña
        $registro = new RegistroUsuario($pdo, $hasher);    // Inyección de dependencias

        // Intenta registrar; devuelve un arreglo de errores (vacío si todo salió bien)
        $errores = $registro->registrar($usuario, $email, $pass, $pass2);

        // Si no hubo errores, el usuario fue creado
        if (!$errores) {
            // Busca el ID del usuario recién creado usando su correo (consulta preparada)
            $stmtId = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $stmtId->execute([$email]);
            $nuevoId = $stmtId->fetchColumn();

            if ($nuevoId) {
                // Regenera el ID de sesión para evitar ataques de fijación de sesión
                session_regenerate_id(true);

                // Guarda los datos temporales necesarios para activar el 2FA
                $_SESSION['2fa_usuario_id'] = (int) $nuevoId;
                $_SESSION['2fa_usuario'] = $usuario;
                $_SESSION['2fa_correo'] = $email;
                unset($_SESSION['2fa_qr_solicitado']);     // Reinicia el estado del QR

                // Redirige a la página de activación de autenticación en dos pasos
                header('Location: activar_2fa.php');
                exit;
            }

            // Respaldo: si no se pudo obtener el ID, muestra solo el mensaje de éxito
            $exito = true;
            $usuario = $email = '';   // Limpia los campos del formulario
        }
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
  <title>Registro de Usuario</title>
  <!-- Hoja de estilos del formulario -->
  <link rel="stylesheet" href="css/Estilos.css">
</head>
<body>
  <!-- Formulario de registro, envía los datos por POST a esta misma página -->
  <form method="POST" action="registro.php">
    <h2>Crear Cuenta</h2>

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

    <!-- Muestra mensaje de éxito si el registro fue correcto -->
    <?php if ($exito): ?>
      <div class="alert exito">
        <p>Usuario registrado correctamente.</p>
      </div>
    <?php endif; ?>

    <!-- Token CSRF oculto, se validará al enviar el formulario -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Campo de usuario: conserva el valor ingresado si hubo errores -->
    <label>Usuario</label>
    <input type="text" name="usuario" required minlength="4" value="<?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. usuario_01">

    <!-- Campo de correo electrónico con validación nativa del navegador -->
    <label>Correo electrónico</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="correo@ejemplo.com">

    <!-- Contraseña (nunca se repuebla por seguridad) -->
    <label>Contraseña</label>
    <input type="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres">

    <!-- Confirmación de contraseña, se compara con la anterior en el servidor -->
    <label>Confirmar contraseña</label>
    <input type="password" name="password_confirm" required minlength="8" placeholder="Repite la contraseña">

    <button type="submit">Registrarse</button>

    <!-- Enlace para usuarios que ya tienen cuenta -->
    <p class="enlace">¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
  </form>
</body>
</html>