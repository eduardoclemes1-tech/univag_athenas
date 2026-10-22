
<?php
session_start();

include_once '../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/login/loginUsuario.php");
    exit;
}

$identificacao = trim($_POST['identificacao'] ?? '');
$senha = $_POST['senha'] ?? '';
$nivel = $_POST['nivel'] ?? '';

if (
    empty($identificacao) ||
    empty($senha) ||
    !in_array($nivel, ['aluno', 'monitor'], true)
) {
    header("Location: ../views/login/loginUsuario.php?erro=1");
    exit;
}

$usuario = new Usuario('', '', '', '', '', '', '');

$respo = $usuario->loginUsuario($identificacao, $senha, $nivel);

if ($respo == true) {

    session_regenerate_id(true);

    if ($nivel == 'aluno') {
        header("Location: ../views/aluno/telaAluno.php");
        exit;
    } else {
        header("Location: ../views/monitor/telaMonitor.php");
        exit;
    }

} else {
    header("Location: ../views/login/loginUsuario.php?erro=login");
    exit;
}
?>
