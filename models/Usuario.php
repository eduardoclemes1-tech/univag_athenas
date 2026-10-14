<?php 

class Usuario{
    public $nome;
    public $email;
    public $senha;

    public $cpf;
    public $nivel;

    public function __construct($nome, $email, $senha, $cpf, $nivel){
        $this->nome = $nome;
        $this->email = $email;
        $this->senha = $senha;
        $this->cpf = $cpf;
        $this->nivel = $nivel;
    

}



public function conectarBD(){

    $servidor = ".\\SQLEXPRESS";
    $banco = "Athenas";

    $conexao = new PDO(
        "sqlsrv:Server=$servidor;Database=$banco;TrustServerCertificate=true",
        null,
        null
    );

    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $conexao;
}

public function salvarUsuBD(){
    $conexaoBD = $this->conectarBD();
    $sql = "INSERT INTO usuario (nome, email, senha, cpf, nivel)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexaoBD->prepare($sql);
    $stmt->execute([
        $this->nome, $this->email, $this->senha,
      $this->cpf, $this->nivel
    ]);
    return true;
}

public function buscarUnicoUsuario($id) {
    $conexaoBD = $this->conectarBD();
    $stmt = $conexaoBD->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}





public function deleteUmUsuario($id){
   $conexaoBD=$this->conectarBD();
   $sql="DELETE FROM usuario WHERE id_usuario='$id';";
   $conexaoBD->query($sql);
}

public function deleteTodos(){
   $conexaoBD= $this->conectarBD();
   $sql="DELETE FROM usuario;";
   $conexaoBD->query($sql);
}

public function editarUsuario($id,$nome,$email){
   $conexaoBD= $this->conectarBD();
   $sql="UPDATE usuario SET nome='$nome', email='$email' WHERE id_usuario='$id';";
   
   $conexaoBD->query($sql);
}

public function loginUsuario($identificacao, $senha){
    $conexaoBD = $this->conectarBD();
    $stmt = $conexaoBD->prepare("SELECT * FROM usuario WHERE cpf = ? OR email = ?");
    $stmt->execute([$identificacao, $identificacao]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        session_start();
        $_SESSION['user']       = true;
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['cpf']        = $usuario['cpf'];
        $_SESSION['email']      = $usuario['email'];
        $_SESSION['nivel']      = $usuario['nivel'];
        return true;
    }
    return false;
}

}
?>