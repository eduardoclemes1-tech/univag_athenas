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
    <title>Cadastro Aluno - Univag Athenas</title>

    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=10">
</head>
<body>
    <div class="login-container">

        <img
            class="logo"
            src="../../assets/img/logoAthenas.png"
            alt="UNIVAG Athenas"
        >

        <div class="forms-wrapper wrapper-cadastro">

            <form
                class="form form-aluno active-form"
                action="../../actions/salvarCadastroAluno.php"
                method="POST"
            >
                <div class="titulo-login">Cadastro Aluno</div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <?php if ($mensagem !== ''): ?>
                    <p class="mensagem" role="status">
                        <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <input
                    type="text"
                    name="nome"
                    placeholder="Nome completo"
                    required
                    minlength="3"
                    autocomplete="name"
                >

                <input
                    type="email"
                    name="email"
                    placeholder="Email"
                    required
                    autocomplete="email"
                >

                <input
                    type="text"
                    name="cpf"
                    placeholder="CPF"
                    required
                    inputmode="numeric"
                    pattern="[0-9]{11}"
                    minlength="11"
                    maxlength="11"
                    autocomplete="off"
                >

                <input
                    type="password"
                    name="senha"
                    placeholder="Crie uma senha"
                    required
                    minlength="6"
                    autocomplete="new-password"
                >

                <button type="submit" class="btn-submit">
                    Cadastrar
                </button>

                <a href="loginUsuario.php">Já tenho cadastro</a>
            </form>

        </div>
    </div>
</body>
</html>
