<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificacao = trim($_POST['identificacao'] ?? '');
    $senha         = $_POST['senha'] ?? '';

    // Validação básica de campos vazios
    if (empty($identificacao) || empty($senha)) {
        header('Location: ../views/auth/loginUsuario.php?erro=campos_vazios');
        exit();
    }

    // ================= USUÁRIOS DE TESTE =================
    
    // 1. Acesso como Admin / Monitor
    if ($identificacao === 'admin@univag.br' && $senha === 'admin123') {
        $_SESSION['usuario_id']     = 1;
        $_SESSION['usuario_nome']   = 'Marcos Silva Pereira';
        $_SESSION['usuario_email']  = 'admin@univag.br';
        $_SESSION['usuario_tipo']   = 'monitor';

        header('Location: ../views/monitor/perfilMonitor.php');
        exit();
    }

    // 2. Fallback: Aceita qualquer login para não travar os seus testes
    $_SESSION['usuario_id']     = 1;
    $_SESSION['usuario_nome']   = 'Marcos Silva Pereira (Monitor)';
    $_SESSION['usuario_email']  = $identificacao;
    $_SESSION['usuario_tipo']   = 'monitor';

    header('Location: ../views/monitor/perfilMonitor.php');
    exit();

} else {
    header('Location: ../views/auth/loginUsuario.php');
    exit();
}