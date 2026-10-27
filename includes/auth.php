<?php
function exigirUsuarioAutenticado(?string $tipoRequerido = null): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

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
            ? 'monitor/perfilMonitor.php'
            : 'aluno/inicio.php';
        header('Location: ' . $prefixoAplicacao . '/views/' . $paginaInicial);
        exit;
    }
}
