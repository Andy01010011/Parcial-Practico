<?php
// Cierra la sesión actual en el servidor y elimina la cookie de sesión en el navegador.
session_start();

// Vacía todas las variables almacenadas en la sesión
$_SESSION = [];

// Si las sesiones usan cookies, caduca la cookie de sesión en el navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruye los datos de sesión almacenados en el servidor y redirige al login
session_destroy();
header('Location: login.php');
exit;
