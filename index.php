<?php
// Redireciona automaticamente qualquer pessoa que acessar http://localhost:8000/ para a tela de login
header('Location: views/auth/loginUsuario.php');
exit();