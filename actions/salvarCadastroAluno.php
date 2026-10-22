
<?php
session_start();

require_once '../models/Usuario.php';

// Só permite acessar este arquivo pelo envio do formulário.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/auth/cadastroAluno.php');
    exit;
}

// Recebe os dados do formulário.
$nome  = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$cpf   = trim($_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

// Valida os campos.
if (
    $nome === '' ||
    strlen($nome) < 3 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $cpf === '' ||
    strlen($senha) < 6
) {
    $_SESSION['msn'] = 'Confira os dados. A senha deve ter pelo menos 6 caracteres.';
    header('Location: ../views/auth/cadastroAluno.php');
    exit;
}

// Cria o hash da senha para não armazená-la em texto puro.
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// O aluno não escolhe o próprio nível de acesso.
$usuario = new Usuario(
    $nome,
    $email,
    $cpf,
    $senhaHash,
    'aluno'
);

// Verifica se o e-mail já está cadastrado.
if ($usuario->emailExiste($email)) {
    $_SESSION['msn'] = ' e-mail já está cadastrado!';
    header('Location: ../views/auth/cadastroAluno.php');
    exit;
}

// Verifica se o CPF já está cadastrado.
if ($usuario->cpfExiste($cpf)) {
    $_SESSION['msn'] = ' CPF já está cadastrado!';
    header('Location: ../views/auth/cadastroAluno.php');
    exit;
}

// Tenta salvar no banco de dados.
if ($usuario->salvarUsuBD()) {
    $_SESSION['msn'] = 'Cadastro realizado com sucesso! Você já pode fazer login.';
    header('Location: ../views/auth/loginUsuario.php');
    exit;
}

// Se o banco rejeitar o cadastro.
$_SESSION['msn'] = 'Não foi possível realizar o cadastro. Verifique a conexão e a tabela usuario.';
header('Location: ../views/auth/cadastroAluno.php');
exit;
