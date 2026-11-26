<?php
// Consulta y muestra los privilegios asignados al usuario actual de la base de datos.
require_once __DIR__ . '/../config/Database.php';

$filas = [];
$error = null;

try {
    // Conecta mediante PDO y consulta los permisos efectivos del usuario conectado
    $pdo = Database::conectar();
    $filas = $pdo->query("SHOW GRANTS FOR CURRENT_USER()")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Evita exponer detalles internos de la excepción al usuario final
    $error = "No fue posible consultar los privilegios de la base de datos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Privilegios de Base de Datos</title>
</head>
<body>
    <!-- Indica si la conexión y consulta a la base de datos fueron exitosas -->
    <?php if ($error === null): ?>
        <p style="color:green">✔ Conexión exitosa a la base de datos</p>
    <?php else: ?>
        <p style="color:red">✖ <?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <h2>Comando usado:</h2>
    <code>SHOW GRANTS FOR CURRENT_USER();</code>

    <!-- Lista cada GRANT devuelto escapando el texto para prevenir XSS -->
    <h2>Privilegios concedidos:</h2>
    <?php if (!empty($filas)): ?>
        <ul>
            <?php foreach ($filas as $g): ?>
                <li><?= htmlspecialchars($g) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No se encontraron privilegios para mostrar.</p>
    <?php endif; ?>
</body>
</html>
