CREATE TABLE usuario (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    dataNascimento DATE NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    nivel VARCHAR(20) NOT NULL DEFAULT 'usuario'
);


select * from usuario;

ALTER TABLE usuario DROP COLUMN dataNascimento;