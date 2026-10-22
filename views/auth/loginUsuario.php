<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Univag Athenas</title>
    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=10">
</head>
<body>
    <div class="login-container">
        
        <img class="logo" src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">
        
        <div class="forms-wrapper">
            
            <!-- ================= CARTÃO DO ALUNO ================= -->
            <form class="form form-aluno active-form" id="formAluno" action="../../actions/salvarLoginUsuario.php" method="POST">
                <div class="titulo-login">Login Aluno</div>
                
                <input type="text" name="identificacao" placeholder="Identificação de usuário (CPF ou Email)" required>
                <input type="password" name="senha" id="senha-aluno" placeholder="Senha" required>
                
                <button type="submit" class="btn-submit">Acessar</button>
                
                <a href="#">Esqueceu a senha?</a>
                <a href="cadastroAluno.php">Criar conta</a>
                
                <button type="button" class="btn-toggle" onclick="alternarLogin()">Já é um monitor ?</button>
            </form>

            <!-- ================= CARTÃO DO MONITOR ================= -->
            <form class="form form-monitor hidden-form" id="formMonitor" action="../../actions/salvarLoginUsuario.php" method="POST">
                <div class="titulo-login">Login Monitor</div>
                
                <input type="text" name="identificacao" placeholder="Identificação de usuário (CPF ou Email)" required>
                <input type="password" name="senha" id="senha-monitor" placeholder="Senha" required>
                
                <button type="submit" class="btn-submit btn-acessar-monitor">Acessar</button>
                
                <a href="#">Esqueceu a senha?</a>
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

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tipo') === 'monitor') {
                alternarLogin();
            }
        }
    </script>
</body>
</html>