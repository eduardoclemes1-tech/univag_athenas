<?php
function processarCadastroUsuario(string $tipo): never
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $formularios = [
        'aluno' => 'CadastroAluno.php',
        'monitor' => 'cadastroMonitor.php',
    ];
    if (!isset($formularios[$tipo])) {
        http_response_code(400);
        exit('Tipo de cadastro inválido.');
    }

    $urlCadastro = '../views/auth/' . $formularios[$tipo];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . $urlCadastro);
        exit;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['msn'] = 'Sua sessão expirou. Atualize a página e tente novamente.';
        header('Location: ' . $urlCadastro);
        exit;
    }

    $nomeEntrada = $_POST['nome'] ?? '';
    $emailEntrada = $_POST['email'] ?? '';
    $cpfEntrada = $_POST['cpf'] ?? '';
    $senha = $_POST['senha'] ?? '';
    if (
        !is_string($nomeEntrada)
        || !is_string($emailEntrada)
        || !is_string($cpfEntrada)
        || !is_string($senha)
    ) {
        $_SESSION['msn'] = 'Confira os dados preenchidos e tente novamente.';
        header('Location: ' . $urlCadastro);
        exit;
    }

    $nome = trim($nomeEntrada);
    $email = strtolower(trim($emailEntrada));
    $cpf = preg_replace('/\D+/', '', $cpfEntrada);
    if (
        mb_strlen($nome, 'UTF-8') < 3
        || mb_strlen($nome, 'UTF-8') > 100
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 100
        || strlen($cpf) !== 11
        || mb_strlen($senha, 'UTF-8') < 6
        || strlen($senha) > 72
    ) {
        $_SESSION['msn'] = 'Informe nome, e-mail, CPF com 11 números e senha com pelo menos 6 caracteres.';
        header('Location: ' . $urlCadastro);
        exit;
    }

    require_once __DIR__ . '/../config/conexao.php';

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nome, email, cpf, senha, tipo)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nome, $email, $cpf, password_hash($senha, PASSWORD_DEFAULT), $tipo]);
        $_SESSION['sucesso'] = 'Cadastro realizado. Faça login para continuar.';
        header('Location: ../views/auth/loginUsuario.php?tipo=' . urlencode($tipo));
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $_SESSION['msn'] = 'Este e-mail ou CPF já está cadastrado.';
        } else {
            error_log($e->getMessage());
            $_SESSION['msn'] = 'Não foi possível concluir o cadastro. Tente novamente.';
        }
        header('Location: ' . $urlCadastro);
        exit;
    }
}
