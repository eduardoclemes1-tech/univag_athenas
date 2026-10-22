
<?php
session_start();

require_once '../models/Usuario.php';

// Verifica se o formulário foi enviado.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/auth/cadastroMonitor.php');
    exit;
}

// Recebe os dados do formulário.
$nome  = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

// Valida os dados recebidos.
if (
    $nome === '' ||
    strlen($nome) < 3 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($senha) < 6
) {
    $_SESSION['msn'] = 'Confira os dados preenchidos. A senha deve ter pelo menos 6 caracteres.';
    header('Location: ../views/auth/cadastroMonitor.php');
    exit;
}

// Cria um objeto para verificar se o e-mail já existe.
$verificacao = new Usuario('', $email, '', '', 'monitor');

// Impede o cadastro com e-mail duplicado.
if ($verificacao->emailExiste($email)) {
    $_SESSION['msn'] = 'Este e-mail já está cadastrado!';
    header('Location: ../views/auth/cadastroMonitor.php');
    exit;
}

// Criptografa a senha antes de armazená-la.
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Cria o usuário com nível de monitor.
$usuario = new Usuario(
    $nome,
    $email,
    '',
    $senhaHash,
    'monitor'
);

// Salva no banco de dados.
if ($usuario->salvarUsuBD()) {
    $_SESSION['msn'] = 'Cadastro de monitor realizado com sucesso!';
    header('Location: ../views/auth/loginUsuario.php?tipo=monitor');
    exit;
}

// Se o cadastro falhar.
$_SESSION['msn'] = 'Não foi possível realizar o cadastro. Verifique a conexão e a tabela usuario.';
header('Location: ../views/auth/cadastroMonitor.php');
exit;
