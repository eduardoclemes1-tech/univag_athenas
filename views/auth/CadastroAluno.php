<?php
require_once __DIR__ . '/../../includes/auth.php';
iniciarSessaoSegura();

$tipoInicialCadastro = $tipoInicialCadastro ?? (($_GET['tipo'] ?? 'aluno') === 'monitor' ? 'monitor' : 'aluno');
$ehMonitorInicial = ($tipoInicialCadastro === 'monitor');
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
    <title><?php echo $ehMonitorInicial ? 'Cadastro Monitor' : 'Cadastro Acadêmico'; ?> - UNIVAG Athenas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/monitorTheme.css?v=6">
    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=15">
</head>
<body<?php echo $ehMonitorInicial ? ' class="theme-monitor"' : ''; ?>>
    <div class="login-container">

        <img class="logo" src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">

        <?php if ($mensagem !== ''): ?>
            <p class="auth-feedback auth-feedback-error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <div class="forms-wrapper wrapper-cadastro">

            <!-- ================= CADASTRO DO ALUNO ================= -->
            <form class="form form-aluno <?php echo $ehMonitorInicial ? 'hidden-form' : 'active-form'; ?>" id="formCadastroAluno" action="../../actions/salvarCadastroAluno.php" method="POST">
                <span class="auth-badge">
                    <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Novo Discente
                </span>
                <div class="titulo-login">Cadastro Aluno</div>
                <p class="subtitulo-login">Crie sua conta para agendar monitorias e ganhar certificados</p>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="input-group">
                    <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
                    <input type="text" name="nome" placeholder="Nome completo" required minlength="3" autocomplete="name">
                </div>

                <div class="input-group">
                    <i class="fa-regular fa-envelope input-icon" aria-hidden="true"></i>
                    <input type="email" name="email" placeholder="E-mail acadêmico ou pessoal" required autocomplete="email">
                </div>

                <div class="input-group">
                    <i class="fa-regular fa-id-card input-icon" aria-hidden="true"></i>
                    <input type="text" name="cpf" placeholder="CPF (11 números)" required inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" autocomplete="off">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <input type="password" name="senha" id="cad-senha-aluno" data-strength-bar="barAluno" placeholder="Crie uma senha (mín. 6 caracteres)" required minlength="6" autocomplete="new-password">
                    <button type="button" class="btn-eye" data-toggle-password="cad-senha-aluno" aria-label="Mostrar ou ocultar senha">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" aria-hidden="true">
                    <div class="password-strength-bar" id="barAluno"></div>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Concluir cadastro</span>
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                </button>

                <a href="loginUsuario.php?tipo=aluno">Já tenho cadastro · Fazer login</a>

                <div class="auth-divider">ou cadastre-se como monitor</div>

                <button type="button" class="btn-toggle" onclick="alternarCadastro()">
                    <i class="fa-solid fa-repeat" aria-hidden="true"></i>
                    <span>Quero ser Monitor</span>
                </button>
            </form>

            <!-- ================= CADASTRO DO MONITOR ================= -->
            <form class="form form-monitor <?php echo $ehMonitorInicial ? 'active-form' : 'hidden-form'; ?>" id="formCadastroMonitor" action="../../actions/salvarCadastroMonitor.php" method="POST">
                <span class="auth-badge">
                    <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i> Novo Monitor
                </span>
                <div class="titulo-login">Cadastro Monitor</div>
                <p class="subtitulo-login">Credencie-se para orientar alunos e registrar horas acadêmicas</p>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="input-group">
                    <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
                    <input type="text" name="nome" placeholder="Nome completo" required minlength="3" autocomplete="name">
                </div>

                <div class="input-group">
                    <i class="fa-regular fa-envelope input-icon" aria-hidden="true"></i>
                    <input type="email" name="email" placeholder="E-mail institucional" required autocomplete="email">
                </div>

                <div class="input-group">
                    <i class="fa-regular fa-id-card input-icon" aria-hidden="true"></i>
                    <input type="text" name="cpf" placeholder="CPF (11 números)" required inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" autocomplete="off">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <input type="password" name="senha" id="cad-senha-monitor" data-strength-bar="barMonitor" placeholder="Crie uma senha (mín. 6 caracteres)" required minlength="6" autocomplete="new-password">
                    <button type="button" class="btn-eye" data-toggle-password="cad-senha-monitor" aria-label="Mostrar ou ocultar senha">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" aria-hidden="true">
                    <div class="password-strength-bar" id="barMonitor"></div>
                </div>

                <button type="submit" class="btn-submit btn-acessar-monitor">
                    <span>Credenciar como Monitor</span>
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                </button>

                <a href="loginUsuario.php?tipo=monitor">Já tenho cadastro · Fazer login</a>

                <div class="auth-divider">ou cadastre-se como aluno</div>

                <button type="button" class="btn-toggle btn-voltar" onclick="alternarCadastro()">
                    <i class="fa-solid fa-repeat" aria-hidden="true"></i>
                    <span>Sou Aluno</span>
                </button>
            </form>

        </div>
    </div>

    <script>
        function alternarCadastro() {
            const formAluno = document.getElementById('formCadastroAluno');
            const formMonitor = document.getElementById('formCadastroMonitor');

            if (formAluno.classList.contains('active-form')) {
                formAluno.classList.remove('active-form');
                formAluno.classList.add('hidden-form');
                formMonitor.classList.remove('hidden-form');
                formMonitor.classList.add('active-form');
                document.body.classList.add('theme-monitor');
            } else {
                formMonitor.classList.remove('active-form');
                formMonitor.classList.add('hidden-form');
                formAluno.classList.remove('hidden-form');
                formAluno.classList.add('active-form');
                document.body.classList.remove('theme-monitor');
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

        document.querySelectorAll('[data-strength-bar]').forEach(function (input) {
            input.addEventListener('input', function () {
                const bar = document.getElementById(this.dataset.strengthBar);
                if (!bar) return;
                const len = this.value.length;
                if (len === 0) {
                    bar.style.width = '0%';
                } else if (len < 6) {
                    bar.style.width = '35%';
                    bar.style.backgroundColor = '#ef4444';
                } else if (len < 9) {
                    bar.style.width = '70%';
                    bar.style.backgroundColor = '#f59e0b';
                } else {
                    bar.style.width = '100%';
                    bar.style.backgroundColor = '#22c55e';
                }
            });
        });
    </script>
</body>
</html>
