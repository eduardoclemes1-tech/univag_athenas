<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Monitor - Univag Athenas</title>
    <!-- Volta dois níveis para encontrar o CSS em assets/css/ -->
    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=10">
</head>
<body class="theme-monitor">
    <div class="login-container">
        
        <!-- Volta dois níveis para encontrar a imagem em assets/img/ -->
        <img class="logo" src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">
        
        <div class="forms-wrapper wrapper-cadastro">
            
            <!-- Volta dois níveis para encontrar a ação em actions/ -->
            <form class="form form-monitor active-form" action="../../actions/salvarCadastroMonitor.php" method="POST">
                <div class="titulo-login">Cadastro Monitor</div>
                
                <input type="text" name="nome" placeholder="Nome completo" required minlength="3">
                <input type="email" name="email" placeholder="Email institucional" required>
                <input type="password" name="senha" placeholder="Crie uma senha" required minlength="6">
                
                <button type="submit" class="btn-submit btn-acessar-monitor">Cadastrar</button>
                
                <!-- O loginUsuario.php está na mesma pasta (views/auth/), então o caminho é direto -->
                <a href="loginUsuario.php?tipo=monitor">Já tenho cadastro</a>
            </form>

        </div>
    </div>
</body>
</html>