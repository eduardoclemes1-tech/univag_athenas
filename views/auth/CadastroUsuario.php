<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Aluno - Univag Athenas</title>
    <!-- Volta dois níveis para encontrar o CSS em assets/css/ -->
    <link rel="stylesheet" href="../../assets/css/loginCadastro.css?v=10">
</head>
<body>
    <div class="login-container">
        
        <!-- Volta dois níveis para encontrar a imagem em assets/img/ -->
        <img class="logo" src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">
        
        <!-- Mantemos a classe wrapper-cadastro para a altura correta -->
        <div class="forms-wrapper wrapper-cadastro">
            
            <!-- Volta dois níveis para encontrar a ação em actions/ -->
            <form class="form form-aluno active-form" action="../../actions/salvarCadastroUsuario.php" method="POST">
                <div class="titulo-login">Cadastro Aluno</div>
                
                <input type="text" name="nome" placeholder="Nome completo" required minlength="3">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Crie uma senha" required minlength="6">
                
                <button type="submit" class="btn-submit">Cadastrar</button>
                
                <!-- Volta para o login normal do aluno (mesma pasta views/auth/) -->
                <a href="loginUsuario.php">Já tenho cadastro</a>
            </form>

        </div>
    </div>
</body>
</html>