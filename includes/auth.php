<?php
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        return preg_match_all('/./us', $string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return substr($string, $start, $length ?? strlen($string));
        }
        return implode('', array_slice($chars, $start, $length));
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        $mapa = [
            'Á' => 'á', 'À' => 'à', 'Â' => 'â', 'Ã' => 'ã', 'Ä' => 'ä',
            'É' => 'é', 'È' => 'è', 'Ê' => 'ê', 'Ë' => 'ë',
            'Í' => 'í', 'Ì' => 'ì', 'Î' => 'î', 'Ï' => 'ï',
            'Ó' => 'ó', 'Ò' => 'ò', 'Ô' => 'ô', 'Õ' => 'õ', 'Ö' => 'ö',
            'Ú' => 'ú', 'Ù' => 'ù', 'Û' => 'û', 'Ü' => 'ü',
            'Ç' => 'ç', 'Ñ' => 'ñ',
        ];
        return strtolower(strtr($string, $mapa));
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string
    {
        $mapa = [
            'á' => 'Á', 'à' => 'À', 'â' => 'Â', 'ã' => 'Ã', 'ä' => 'Ä',
            'é' => 'É', 'è' => 'È', 'ê' => 'Ê', 'ë' => 'Ë',
            'í' => 'Í', 'ì' => 'Ì', 'î' => 'Î', 'ï' => 'Ï',
            'ó' => 'Ó', 'ò' => 'Ò', 'ô' => 'Ô', 'õ' => 'Õ', 'ö' => 'Ö',
            'ú' => 'Ú', 'ù' => 'Ù', 'û' => 'Û', 'ü' => 'Ü',
            'ç' => 'Ç', 'ñ' => 'Ñ',
        ];
        return strtoupper(strtr($string, $mapa));
    }
}

function iniciarSessaoSegura(): void
{
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
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
