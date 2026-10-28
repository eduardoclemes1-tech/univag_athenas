<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tipo = $_POST['tipo'] ?? 'aluno';
if (!is_string($tipo) || !in_array($tipo, ['aluno', 'monitor'], true)) {
    $tipo = 'aluno';
}
$urlLogin = '../views/auth/loginUsuario.php?tipo=' . urlencode($tipo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/auth/loginUsuario.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    $_SESSION['erro'] = 'Sua sessão expirou. Atualize a página e tente novamente.';
    header('Location: ' . $urlLogin);
    exit;
}

$identificacaoEntrada = $_POST['identificacao'] ?? '';
$senha = $_POST['senha'] ?? '';
if (!is_string($identificacaoEntrada) || !is_string($senha)) {
    $_SESSION['erro'] = 'E-mail/CPF, senha ou tipo de acesso incorretos.';
    header('Location: ' . $urlLogin);
    exit;
}

$identificacao = trim($identificacaoEntrada);
if ($identificacao === '' || $senha === '') {
    $_SESSION['erro'] = 'Preencha e-mail/CPF e senha para continuar.';
    header('Location: ' . $urlLogin);
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

try {
    $cpf = preg_replace('/\D+/', '', $identificacao);
    $email = strtolower($identificacao);
    $stmt = $pdo->prepare(
        'SELECT id, nome, email, senha, tipo
         FROM usuarios
         WHERE (email = ? OR cpf = ?) AND tipo = ?
         LIMIT 1'
    );
    $stmt->execute([$email, $cpf, $tipo]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        unset(
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'],
            $_SESSION['usuario_email'],
            $_SESSION['usuario_tipo']
        );
        $_SESSION['erro'] = 'E-mail/CPF, senha ou tipo de acesso incorretos.';
        header('Location: ' . $urlLogin);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $destino = $tipo === 'monitor'
        ? '../views/monitor/inicio.php'
        : '../views/aluno/inicio.php';
    header('Location: ' . $destino);
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['erro'] = 'Não foi possível validar o login. Verifique a conexão com o banco.';
    header('Location: ' . $urlLogin);
    exit;
}
