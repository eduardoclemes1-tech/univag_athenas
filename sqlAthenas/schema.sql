/* ===== 1. CRIAÇÃO DO BANCO DE DADOS E SELEÇÃO DE CONTEXTO ===== */
-- Garante a criação da base de dados com codificação UTF-8 para suporte a acentos
CREATE DATABASE IF NOT EXISTS athenas DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE athenas;

/* ===== 2. TABELA DE USUÁRIOS (ALUNOS E MONITORES) ===== */
-- Tabela unificada para armazenar acessos de alunos e monitores
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno', 'monitor') NOT NULL DEFAULT 'aluno',
    bio VARCHAR(500) NOT NULL DEFAULT '',
    curso VARCHAR(120) NOT NULL DEFAULT '',
    periodo VARCHAR(40) NOT NULL DEFAULT '',
    foto_perfil MEDIUMBLOB NULL,
    foto_mime VARCHAR(30) NULL,
    notificar_mensagens TINYINT(1) NOT NULL DEFAULT 1,
    notificar_agendamentos TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== 3. CONVERSAS E MENSAGENS ===== */
CREATE TABLE IF NOT EXISTS conversas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    monitor_id INT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversa_participantes (aluno_id, monitor_id),
    KEY idx_conversas_aluno_atualizada (aluno_id, atualizado_em),
    KEY idx_conversas_monitor_atualizada (monitor_id, atualizado_em),
    CONSTRAINT fk_conversa_aluno FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_conversa_monitor FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensagens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversa_id BIGINT UNSIGNED NOT NULL,
    remetente_id INT NOT NULL,
    conteudo TEXT NOT NULL,
    lida_em TIMESTAMP NULL DEFAULT NULL,
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mensagens_conversa_id (conversa_id, id),
    KEY idx_mensagens_nao_lidas (conversa_id, remetente_id, lida_em),
    CONSTRAINT fk_mensagem_conversa FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE,
    CONSTRAINT fk_mensagem_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== 4. AGENDAMENTOS E AVISOS ===== */
CREATE TABLE IF NOT EXISTS agendamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversa_id BIGINT UNSIGNED NOT NULL,
    aluno_id INT NOT NULL,
    monitor_id INT NOT NULL,
    assunto VARCHAR(180) NOT NULL,
    agendado_para DATETIME NOT NULL,
    status ENUM('solicitado', 'confirmado', 'recusado', 'cancelado') NOT NULL DEFAULT 'solicitado',
    link_zoom VARCHAR(500) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_agendamentos_aluno_data (aluno_id, agendado_para),
    KEY idx_agendamentos_monitor_data (monitor_id, agendado_para),
    CONSTRAINT fk_agendamento_conversa FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE,
    CONSTRAINT fk_agendamento_aluno FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_agendamento_monitor FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    mensagem VARCHAR(500) NOT NULL,
    agendamento_id BIGINT UNSIGNED NULL,
    mensagem_id BIGINT UNSIGNED NULL,
    lida_em TIMESTAMP NULL DEFAULT NULL,
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notificacoes_usuario_lidas (usuario_id, lida_em, criada_em),
    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_notificacao_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_notificacao_mensagem FOREIGN KEY (mensagem_id) REFERENCES mensagens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== 5. DADOS INICIAIS DE TESTE ===== */
-- Insere um usuário monitor e um aluno de teste (Senha padrão: 123456)
INSERT INTO usuarios (nome, email, cpf, senha, tipo) VALUES 
('Monitor Teste', 'admin@univag.br', '00000000000', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor'),
('Aluno Teste', 'aluno@univag.br', '11111111111', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'aluno')
ON DUPLICATE KEY UPDATE id=id;