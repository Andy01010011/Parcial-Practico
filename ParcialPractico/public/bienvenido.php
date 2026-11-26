<?php
// Panel protegido: exige haber completado el login y la verificación 2FA.
session_start();

// Verifica que la sesión esté marcada como autenticada; si no, redirige al login
if (empty($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel Principal</title>
  <link rel="stylesheet" href="css/Estilos.css">
</head>
<body>
  <div class="contenedor-panel" style="max-width: 520px; margin: 40px auto; padding: 24px; background: #fff; border-radius: 10px;">
    <!-- Escapa el nombre de usuario y correo con htmlspecialchars para prevenir XSS -->
    <h2>Bienvenido, <?= htmlspecialchars((string) ($_SESSION['usuario'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Correo verificado: <strong><?= htmlspecialchars((string) ($_SESSION['correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Fase de solicitud QR confirmada y código 2FA validado correctamente.</p>
    <!-- Navegación hacia la consulta de privilegios de BD y cierre de sesión -->
    <p><a href="privilegios.php">Ver privilegios de base de datos</a> | <a href="logout.php">Cerrar sesión</a></p>
  </div>
</body>
</html>
