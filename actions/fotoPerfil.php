<?php
require_once __DIR__ . '/../includes/auth.php';
exigirUsuarioAutenticado();

require_once __DIR__ . '/../config/conexao.php';

try {
    $stmt = $pdo->prepare('SELECT foto_perfil, foto_mime FROM usuarios WHERE id = ? AND tipo = ?');
    $stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_tipo']]);
    $foto = $stmt->fetch();

    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
    if (!$foto || !$foto['foto_perfil'] || !in_array($foto['foto_mime'], $tiposPermitidos, true)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $foto['foto_mime']);
    header('Content-Length: ' . strlen($foto['foto_perfil']));
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    echo $foto['foto_perfil'];
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Não foi possível carregar a foto de perfil.';
}
