<?php
function iniciarSessaoSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        session_start();
    }
}

function exigirUsuarioAutenticado(?string $tipoRequerido = null): void
{
    iniciarSessaoSegura();

    $tipoUsuario = $_SESSION['usuario_tipo'] ?? null;
    $autenticado = isset($_SESSION['usuario_id'])
        && in_array($tipoUsuario, ['aluno', 'monitor'], true);

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $posicaoViews = strpos($scriptName, '/views/');
    $prefixoAplicacao = $posicaoViews === false ? '' : substr($scriptName, 0, $posicaoViews);

    if (!$autenticado) {
        header('Location: ' . $prefixoAplicacao . '/views/auth/loginUsuario.php');
        exit;
    }

    if ($tipoRequerido !== null && $tipoUsuario !== $tipoRequerido) {
        $paginaInicial = $tipoUsuario === 'monitor'
            ? 'monitor/inicio.php'
            : 'aluno/inicio.php';
        header('Location: ' . $prefixoAplicacao . '/views/' . $paginaInicial);
        exit;
    }
}
