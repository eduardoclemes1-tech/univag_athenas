//*CREATE TABLE usuario (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    dataNascimento DATE NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    nivel VARCHAR(20) NOT NULL DEFAULT 'usuario'
);


select * from usuario;

ALTER TABLE usuario DROP COLUMN dataNascimento;*//




CREATE DATABASE IF NOT EXISTS Athenas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE Athenas;

-- 1. USUARIOS
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    cpf VARCHAR(14) UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('aluno', 'monitor', 'admin')
        NOT NULL DEFAULT 'aluno',
    status ENUM('ativo', 'inativo')
        NOT NULL DEFAULT 'ativo',
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. DADOS COMPLEMENTARES DOS MONITORES
CREATE TABLE IF NOT EXISTS monitor (
    id_monitor INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    curso VARCHAR(100),
    semestre VARCHAR(30),
    descricao TEXT,
    aprovado BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT fk_monitor_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- 3. DISCIPLINAS
CREATE TABLE IF NOT EXISTS disciplina (
    id_disciplina INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    status ENUM('ativa', 'inativa')
        NOT NULL DEFAULT 'ativa'
);

-- 4. DISCIPLINAS ATENDIDAS POR CADA MONITOR
CREATE TABLE IF NOT EXISTS monitor_disciplina (
    id_monitor INT NOT NULL,
    id_disciplina INT NOT NULL,

    PRIMARY KEY (id_monitor, id_disciplina),

    CONSTRAINT fk_md_monitor
        FOREIGN KEY (id_monitor)
        REFERENCES monitor(id_monitor)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_md_disciplina
        FOREIGN KEY (id_disciplina)
        REFERENCES disciplina(id_disciplina)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- 5. DISPONIBILIDADES DOS MONITORES
CREATE TABLE IF NOT EXISTS disponibilidade (
    id_disponibilidade INT AUTO_INCREMENT PRIMARY KEY,
    id_monitor INT NOT NULL,
    dia_semana TINYINT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    modalidade ENUM('presencial', 'online', 'ambas')
        NOT NULL DEFAULT 'ambas',
    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT fk_disponibilidade_monitor
        FOREIGN KEY (id_monitor)
        REFERENCES monitor(id_monitor)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT chk_dia_semana
        CHECK (dia_semana BETWEEN 1 AND 7),

    CONSTRAINT chk_horario_disponibilidade
        CHECK (hora_inicio < hora_fim)
);

-- 6. SOLICITACOES DE MONITORIA
CREATE TABLE IF NOT EXISTS solicitacao (
    id_solicitacao INT AUTO_INCREMENT PRIMARY KEY,
    id_aluno INT NOT NULL,
    id_monitor INT NOT NULL,
    id_disciplina INT NOT NULL,
    descricao TEXT,
    data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM(
        'pendente',
        'aceita',
        'recusada',
        'cancelada',
        'concluida'
    ) NOT NULL DEFAULT 'pendente',

    CONSTRAINT fk_solicitacao_aluno
        FOREIGN KEY (id_aluno)
        REFERENCES usuario(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacao_monitor
        FOREIGN KEY (id_monitor)
        REFERENCES monitor(id_monitor)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_solicitacao_disciplina
        FOREIGN KEY (id_disciplina)
        REFERENCES disciplina(id_disciplina)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- 7. SESSOES DE ATENDIMENTO
CREATE TABLE IF NOT EXISTS sessao (
    id_sessao INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitacao INT NOT NULL UNIQUE,
    data_sessao DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    modalidade ENUM('presencial', 'online') NOT NULL,
    local_atendimento VARCHAR(255),
    link_online VARCHAR(500),
    observacoes TEXT,
    status ENUM(
        'agendada',
        'em_andamento',
        'realizada',
        'cancelada'
    ) NOT NULL DEFAULT 'agendada',

    CONSTRAINT fk_sessao_solicitacao
        FOREIGN KEY (id_solicitacao)
        REFERENCES solicitacao(id_solicitacao)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT chk_horario_sessao
        CHECK (hora_inicio < hora_fim)
);

-- 8. AVALIACOES DAS SESSOES
CREATE TABLE IF NOT EXISTS avaliacao (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,
    id_sessao INT NOT NULL UNIQUE,
    nota TINYINT NOT NULL,
    comentario TEXT,
    data_avaliacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_avaliacao_sessao
        FOREIGN KEY (id_sessao)
        REFERENCES sessao(id_sessao)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT chk_nota_avaliacao
        CHECK (nota BETWEEN 1 AND 5)
);

-- 9. NOTIFICACOES
CREATE TABLE IF NOT EXISTS notificacao (
    id_notificacao INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notificacao_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
