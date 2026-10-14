<?php
session_start();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: CadastroUsuario.php");
    exit;
}

$nome  = $_POST['nome'];
$email = $_POST['email'];
$cpf   = $_POST['cpf'];
$senha = $_POST['senha'];

try {
    $conexao = new PDO(
        "sqlsrv:Server=.\\SQLEXPRESS;Database=Athenas;TrustServerCertificate=true",
        null,
        null
    );
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO usuario (nome, email, cpf, senha, nivel)
            VALUES (?, ?, ?, ?, 'usuario')";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([$nome, $email, $cpf, $senha]);

    $_SESSION['msn'] = 'Cadastrado com sucesso!';
header("Location: login.html");
exit;

} catch (PDOException $e) {
    echo "Erro ao inserir registro: " . $e->getMessage();
}
?>