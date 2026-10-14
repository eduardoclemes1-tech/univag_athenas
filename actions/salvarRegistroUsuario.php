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

    // ================= USUÁRIOS DE TESTE (SIMULAÇÃO DE BANCO) =================
    
    // 1. Acesso como Monitor / Admin
    if ($identificacao === 'admin@univag.br' && $senha === 'admin123') {
        $_SESSION['usuario_id']     = 1;
        $_SESSION['usuario_nome']   = 'Marcos Silva Pereira';
        $_SESSION['usuario_email']  = 'admin@univag.br';
        $_SESSION['usuario_tipo']   = 'monitor';
        $_SESSION['usuario_status'] = 'ativo';

        header('Location: ../views/monitor/perfilMonitor.php');
        exit();
    }

    // 2. Acesso como Aluno de Teste
    if ($identificacao === 'aluno@univag.br' && $senha === '123456') {
        $_SESSION['usuario_id']     = 2;
        $_SESSION['usuario_nome']   = 'Gabriel Souza';
        $_SESSION['usuario_email']  = 'aluno@univag.br';
        $_SESSION['usuario_tipo']   = 'aluno';
        $_SESSION['usuario_status'] = 'ativo';

        // Quando você criar a tela de busca ou perfil do aluno, redirecionará para lá
        header('Location: ../views/monitor/perfilMonitor.php');
        exit();
    }

    // 3. Fallback genérico: Aceita qualquer outro login digitado para você nunca ficar travado!
    $_SESSION['usuario_id']     = 1;
    $_SESSION['usuario_nome']   = 'Marcos Silva Pereira (Monitor)';
    $_SESSION['usuario_email']  = $identificacao;
    $_SESSION['usuario_tipo']   = 'monitor';
    $_SESSION['usuario_status'] = 'ativo';

    header('Location: ../views/monitor/perfilMonitor.php');
    exit();

} else {
    header('Location: ../views/auth/loginUsuario.php');
    exit();
}