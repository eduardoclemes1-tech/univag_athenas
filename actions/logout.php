<?php
require_once __DIR__ . '/../includes/auth.php';
iniciarSessaoSegura();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametrosCookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parametrosCookie['path'],
        'domain' => $parametrosCookie['domain'],
        'secure' => $parametrosCookie['secure'],
        'httponly' => $parametrosCookie['httponly'],
        'samesite' => $parametrosCookie['samesite'] ?? 'Lax',
    ]);
}

session_destroy();
header('Location: ../views/auth/loginUsuario.php');
exit;
