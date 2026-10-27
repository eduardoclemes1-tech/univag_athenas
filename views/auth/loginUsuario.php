<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro = $_SESSION['erro'] ?? '';
$sucesso = $_SESSION['sucesso'] ?? '';
$tipoSelecionado = $_GET['tipo'] ?? 'aluno';
if (!in_array($tipoSelecionado, ['aluno', 'monitor'], true)) {
    $tipoSelecionado = 'aluno';
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
unset($_SESSION['erro'], $_SESSION['sucesso']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Univag Athenas</title>
    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=11">
</head>
<body<?php echo $tipoSelecionado === 'monitor' ? ' class="theme-monitor"' : ''; ?>>
    <div class="login-container">
        
        <img class="logo" src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">
        
        <div class="forms-wrapper">
            <?php if ($erro !== ''): ?>
                <p class="auth-feedback auth-feedback-error" role="alert"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php elseif ($sucesso !== ''): ?>
                <p class="auth-feedback auth-feedback-success" role="status"><?php echo htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            
            <!-- ================= CARTÃO DO ALUNO ================= -->
            <form class="form form-aluno <?php echo $tipoSelecionado === 'aluno' ? 'active-form' : 'hidden-form'; ?>" id="formAluno" action="../../actions/salvarLoginUsuario.php" method="POST">
                <div class="titulo-login">Login Aluno</div>
                <input type="hidden" name="tipo" value="aluno">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <input type="text" name="identificacao" placeholder="Identificação de usuário (CPF ou Email)" required>
                <input type="password" name="senha" id="senha-aluno" placeholder="Senha" required>
                
                <button type="submit" class="btn-submit">Acessar</button>
                
                <a href="CadastroAluno.php">Criar conta</a>
                
                <button type="button" class="btn-toggle" onclick="alternarLogin()">Já é um monitor ?</button>
            </form>

            <!-- ================= CARTÃO DO MONITOR ================= -->
            <form class="form form-monitor <?php echo $tipoSelecionado === 'monitor' ? 'active-form' : 'hidden-form'; ?>" id="formMonitor" action="../../actions/salvarLoginUsuario.php" method="POST">
                <div class="titulo-login">Login Monitor</div>
                <input type="hidden" name="tipo" value="monitor">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <input type="text" name="identificacao" placeholder="Identificação de usuário (CPF ou Email)" required>
                <input type="password" name="senha" id="senha-monitor" placeholder="Senha" required>
                
                <button type="submit" class="btn-submit btn-acessar-monitor">Acessar</button>
                
                <a href="cadastroMonitor.php">Criar conta</a>
                
                <button type="button" class="btn-toggle btn-voltar" onclick="alternarLogin()">Sou Aluno</button>
            </form>

        </div>
    </div>

    <script>
        function alternarLogin() {
            const formAluno = document.getElementById('formAluno');
            const formMonitor = document.getElementById('formMonitor');

            if (formAluno.classList.contains('active-form')) {
                formAluno.classList.remove('active-form');
                formAluno.classList.add('hidden-form');
                formMonitor.classList.remove('hidden-form');
                formMonitor.classList.add('active-form');
                
                // Ativa o fundo escuro noturno para o Monitor
                document.body.classList.add('theme-monitor');
            } else {
                formMonitor.classList.remove('active-form');
                formMonitor.classList.add('hidden-form');
                formAluno.classList.remove('hidden-form');
                formAluno.classList.add('active-form');
                
                // Volta para o fundo mais claro para o Aluno
                document.body.classList.remove('theme-monitor');
            }
        }

    </script>
</body>
</html>