<?php
require_once __DIR__ . '/../../includes/auth.php';
iniciarSessaoSegura();

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
    <title>Acesso Acadêmico - UNIVAG Athenas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/monitorTheme.css?v=6">
    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=15">
</head>
<body<?php echo $tipoSelecionado === 'monitor' ? ' class="theme-monitor"' : ''; ?>>
    <div class="login-container">
        
        <img class="logo" src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">

        <?php if ($erro !== ''): ?>
            <p class="auth-feedback auth-feedback-error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php elseif ($sucesso !== ''): ?>
            <p class="auth-feedback auth-feedback-success" role="status">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>
        
        <div class="forms-wrapper">
            
            <!-- ================= CARTÃO DO ALUNO ================= -->
            <form class="form form-aluno <?php echo $tipoSelecionado === 'aluno' ? 'active-form' : 'hidden-form'; ?>" id="formAluno" action="../../actions/salvarLoginUsuario.php" method="POST">
                <span class="auth-badge">
                    <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Portal do Aluno
                </span>
                <div class="titulo-login">Login Aluno</div>
                <p class="subtitulo-login">Acesse suas monitorias, agenda e materiais de estudo</p>

                <input type="hidden" name="tipo" value="aluno">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="input-group">
                    <i class="fa-regular fa-id-card input-icon" aria-hidden="true"></i>
                    <input type="text" name="identificacao" placeholder="E-mail institucional ou CPF" required autocomplete="username">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <input type="password" name="senha" id="senha-aluno" placeholder="Sua senha de acesso" required autocomplete="current-password">
                    <button type="button" class="btn-eye" data-toggle-password="senha-aluno" aria-label="Mostrar ou ocultar senha">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" class="btn-submit">
                    <span>Acessar plataforma</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
                
                <a href="CadastroAluno.php">Ainda não tem conta? Criar cadastro de Aluno</a>
                
                <div class="auth-divider">ou alterne seu perfil</div>

                <button type="button" class="btn-toggle" onclick="alternarLogin()">
                    <i class="fa-solid fa-repeat" aria-hidden="true"></i>
                    <span>Já é um monitor ?</span>
                </button>
            </form>

            <!-- ================= CARTÃO DO MONITOR ================= -->
            <form class="form form-monitor <?php echo $tipoSelecionado === 'monitor' ? 'active-form' : 'hidden-form'; ?>" id="formMonitor" action="../../actions/salvarLoginUsuario.php" method="POST">
                <span class="auth-badge">
                    <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i> Ambiente do Monitor
                </span>
                <div class="titulo-login">Login Monitor</div>
                <p class="subtitulo-login">Gerencie atendimentos, presenças, horários e certificados</p>

                <input type="hidden" name="tipo" value="monitor">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="input-group">
                    <i class="fa-regular fa-id-card input-icon" aria-hidden="true"></i>
                    <input type="text" name="identificacao" placeholder="E-mail institucional ou CPF" required autocomplete="username">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <input type="password" name="senha" id="senha-monitor" placeholder="Sua senha de monitor" required autocomplete="current-password">
                    <button type="button" class="btn-eye" data-toggle-password="senha-monitor" aria-label="Mostrar ou ocultar senha">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" class="btn-submit btn-acessar-monitor">
                    <span>Acessar painel do monitor</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
                
                <a href="cadastroMonitor.php">Credenciar nova conta de Monitor</a>

                <div class="auth-divider">ou alterne seu perfil</div>
                
                <button type="button" class="btn-toggle btn-voltar" onclick="alternarLogin()">
                    <i class="fa-solid fa-repeat" aria-hidden="true"></i>
                    <span>Sou Aluno</span>
                </button>
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
                document.body.classList.add('theme-monitor');
                window.history.replaceState(null, '', '?tipo=monitor');
            } else {
                formMonitor.classList.remove('active-form');
                formMonitor.classList.add('hidden-form');
                formAluno.classList.remove('hidden-form');
                formAluno.classList.add('active-form');
                document.body.classList.remove('theme-monitor');
                window.history.replaceState(null, '', '?tipo=aluno');
            }
        }

        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.togglePassword);
                if (!input) return;
                const mostrar = input.type === 'password';
                input.type = mostrar ? 'text' : 'password';
                this.querySelector('i').className = mostrar ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            });
        });
    </script>
</body>
</html>
