<?php
session_start();

$mensagem = $_SESSION['msn'] ?? '';
unset($_SESSION['msn']);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Monitor - Univag Athenas</title>

    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=10">
</head>
<body class="theme-monitor">
    <div class="login-container">

        <img
            class="logo"
            src="../../assets/img/logoAthenas.png"
            alt="UNIVAG Athenas"
        >

        <div class="forms-wrapper wrapper-cadastro">
            
            <!-- Volta dois níveis para encontrar a ação em actions/ -->
            <form class="form form-monitor active-form" action="../../actions/salvarCadastroMonitor.php" method="POST">
                <div class="titulo-login">Cadastro Monitor</div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                
                <input type="text" name="nome" placeholder="Nome completo" required minlength="3">
                <input type="email" name="email" placeholder="Email institucional" required>
                <input type="text" name="cpf" placeholder="CPF (somente números)" required inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" autocomplete="off">
                <input type="password" name="senha" placeholder="Crie uma senha" required minlength="6">
                
                <button type="submit" class="btn-submit btn-acessar-monitor">Cadastrar</button>
                
                <!-- O loginUsuario.php está na mesma pasta (views/auth/), então o caminho é direto -->
                <a href="loginUsuario.php?tipo=monitor">Já tenho cadastro</a>
            </form>

        </div>
    </div>
</body>
</html>
