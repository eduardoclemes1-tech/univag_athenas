
<?php

class Usuario {
    public $nome;
    public $email;
    public $cpf;
    public $senha;
    public $nivel;

    public function __construct($nome, $email, $cpf, $senha, $nivel) {
        $this->nome = $nome;
        $this->email = $email;
        $this->cpf = $cpf;
        $this->senha = $senha;
        $this->nivel = $nivel;
    }

    // Conexão com o banco MySQL
    public function conectarBD() {
        $conexao = new mysqli("localhost", "root", "", "athenas");

        if ($conexao->connect_error) {
            error_log($conexao->connect_error);
            return false;
        }

        $conexao->set_charset("utf8mb4");

        return $conexao;
    }

    // Cadastra o usuário
    public function salvarUsuBD() {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "INSERT INTO usuario (nome, email, cpf, senha, nivel)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conexaoBD->prepare($sql);

        if (!$stmt) {
            $conexaoBD->close();
            return false;
        }

        $stmt->bind_param(
            "sssss",
            $this->nome,
            $this->email,
            $this->cpf,
            $this->senha,
            $this->nivel
        );

        $resultado = $stmt->execute();

        $stmt->close();
        $conexaoBD->close();

        return $resultado;
    }

    // Verifica se o e-mail já está cadastrado
    public function emailExiste($email) {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "SELECT id_usuario FROM usuario WHERE email = ?";
        $stmt = $conexaoBD->prepare($sql);

        if (!$stmt) {
            $conexaoBD->close();
            return false;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        $existe = $stmt->num_rows > 0;

        $stmt->close();
        $conexaoBD->close();

        return $existe;
    }

    // Verifica se o CPF já está cadastrado
    public function cpfExiste($cpf) {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "SELECT id_usuario FROM usuario WHERE cpf = ?";
        $stmt = $conexaoBD->prepare($sql);

        if (!$stmt) {
            $conexaoBD->close();
            return false;
        }

        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $stmt->store_result();

        $existe = $stmt->num_rows > 0;

        $stmt->close();
        $conexaoBD->close();

        return $existe;
    }

    // Busca todos os usuários
    public function buscarUsuario() {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "SELECT * FROM usuario";
        $resultado = $conexaoBD->query($sql);

        $conexaoBD->close();

        return $resultado;
    }

    // Busca um usuário pelo ID
    public function buscarUnicoUsuario($id) {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "SELECT * FROM usuario WHERE id_usuario = ?";
        $stmt = $conexaoBD->prepare($sql);

        if (!$stmt) {
            $conexaoBD->close();
            return false;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        $stmt->close();
        $conexaoBD->close();

        return $usuario;
    }

    // Exclui um usuário pelo ID
    public function deleteUmUsuario($id) {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "DELETE FROM usuario WHERE id_usuario = ?";
        $stmt = $conexaoBD->prepare($sql);

        if (!$stmt) {
            $conexaoBD->close();
            return false;
        }

        $stmt->bind_param("i", $id);
        $resultado = $stmt->execute();

        $stmt->close();
        $conexaoBD->close();

        return $resultado;
    }

    // Exclui todos os usuários
    public function deleteTodos() {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $resultado = $conexaoBD->query("DELETE FROM usuario");

        $conexaoBD->close();

        return $resultado;
    }

    // Atualiza nome e e-mail
    public function editarUsuario($id, $nome, $email) {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "UPDATE usuario
                SET nome = ?, email = ?
                WHERE id_usuario = ?";

        $stmt = $conexaoBD->prepare($sql);

        if (!$stmt) {
            $conexaoBD->close();
            return false;
        }

        $stmt->bind_param("ssi", $nome, $email, $id);
        $resultado = $stmt->execute();

        $stmt->close();
        $conexaoBD->close();

        return $resultado;
    }

    // Login por CPF ou e-mail, verificando o nível de acesso
    public function loginUsuario($identificacao, $senha, $nivel) {
        $conexaoBD = $this->conectarBD();

        if (!$conexaoBD) {
            return false;
        }

        $sql = "SELECT id_usuario, nome, email, cpf, senha, nivel
                FROM usuario
                WHERE email = ? OR cpf = ?";

        $stmt = $conexaoBD->prepare($sql);

        if (!$stmt) {
            $conexaoBD->close();
            return false;
        }

        $stmt->bind_param(
            "ss",
            $identificacao,
            $identificacao
        );

        $stmt->execute();

        $resultado = $stmt->get_result();
        $dados = $resultado->fetch_assoc();

        $stmt->close();
        $conexaoBD->close();

        if (
            !$dados ||
            $dados['nivel'] !== $nivel ||
            !password_verify($senha, $dados['senha'])
        ) {
            return false;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['user'] = true;
        $_SESSION['id_usuario'] = $dados['id_usuario'];
        $_SESSION['nome'] = $dados['nome'];
        $_SESSION['email'] = $dados['email'];
        $_SESSION['cpf'] = $dados['cpf'];
        $_SESSION['nivel'] = $dados['nivel'];

        // Não armazenamos a senha na sessão.
        return true;
    }
}
?>
