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
    duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    status ENUM('solicitado', 'confirmado', 'recusado', 'cancelado') NOT NULL DEFAULT 'solicitado',
    link_zoom VARCHAR(500) NULL,
    presenca_aluno TINYINT(1) NULL DEFAULT NULL,
    presenca_monitor TINYINT(1) NULL DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_agendamentos_aluno_data (aluno_id, agendado_para),
    KEY idx_agendamentos_monitor_data (monitor_id, agendado_para),
    CONSTRAINT fk_agendamento_conversa FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE,
    CONSTRAINT fk_agendamento_aluno FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_agendamento_monitor FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS avaliacoes_monitorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id BIGINT UNSIGNED NOT NULL UNIQUE,
    aluno_id INT NOT NULL,
    monitor_id INT NOT NULL,
    nota TINYINT UNSIGNED NOT NULL,
    comentario VARCHAR(1000) NOT NULL DEFAULT '',
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avaliacao_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacao_aluno FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacao_monitor FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT chk_avaliacao_nota CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS registros_monitorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id BIGINT UNSIGNED NOT NULL UNIQUE,
    monitor_id INT NOT NULL,
    assunto_estudado VARCHAR(180) NOT NULL,
    observacoes VARCHAR(2000) NOT NULL DEFAULT '',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_registro_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_registro_monitor FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS materiais_agendamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id BIGINT UNSIGNED NOT NULL,
    remetente_id INT NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    storage_key CHAR(64) NULL,
    mime_type VARCHAR(100) NULL,
    tamanho_bytes INT UNSIGNED NOT NULL DEFAULT 0,
    url VARCHAR(2048) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_materiais_agendamento (agendamento_id, criado_em),
    CONSTRAINT fk_material_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_material_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bloqueios_disponibilidade (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    monitor_id INT NOT NULL,
    inicio DATETIME NOT NULL,
    fim DATETIME NOT NULL,
    motivo VARCHAR(180) NOT NULL DEFAULT '',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bloqueios_monitor_data (monitor_id, inicio, fim),
    CONSTRAINT fk_bloqueio_monitor FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT chk_bloqueio_periodo CHECK (inicio < fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lembretes_agendamentos (
    agendamento_id BIGINT UNSIGNED NOT NULL,
    usuario_id INT NOT NULL,
    enviado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (agendamento_id, usuario_id),
    CONSTRAINT fk_lembrete_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_lembrete_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
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

/* ===== 5. PERFIL E DISPONIBILIDADE DOS MONITORES ===== */
CREATE TABLE IF NOT EXISTS perfis_monitores (
    monitor_id INT PRIMARY KEY,
    area_atuacao VARCHAR(120) NOT NULL DEFAULT '',
    disciplina VARCHAR(120) NOT NULL DEFAULT '',
    local_atendimento VARCHAR(120) NOT NULL DEFAULT '',
    duracao_monitoria VARCHAR(40) NOT NULL DEFAULT '',
    modalidade VARCHAR(80) NOT NULL DEFAULT '',
    metodologia VARCHAR(500) NOT NULL DEFAULT '',
    ideal_para VARCHAR(300) NOT NULL DEFAULT '',
    topicos TEXT NOT NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_perfil_monitor_usuario
        FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disponibilidades_monitores (
    monitor_id INT NOT NULL,
    dia_semana TINYINT UNSIGNED NOT NULL,
    disponivel TINYINT(1) NOT NULL DEFAULT 0,
    hora_inicio TIME NULL,
    hora_fim TIME NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (monitor_id, dia_semana),
    CONSTRAINT fk_disponibilidade_monitor_usuario
        FOREIGN KEY (monitor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT chk_disponibilidade_dia_semana
        CHECK (dia_semana BETWEEN 1 AND 7),
    CONSTRAINT chk_disponibilidade_horarios
        CHECK (
            (disponivel = 0 AND hora_inicio IS NULL AND hora_fim IS NULL)
            OR (disponivel = 1 AND hora_inicio IS NOT NULL AND hora_fim IS NOT NULL AND hora_inicio < hora_fim)
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ===== 6. DADOS INICIAIS DE TESTE ===== */
-- Insere um usuário monitor e um aluno de teste (Senha padrão: 123456)
INSERT INTO usuarios (nome, email, cpf, senha, tipo) VALUES 
('Monitor Teste', 'admin@univag.br', '00000000000', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor'),
('Aluno Teste', 'aluno@univag.br', '11111111111', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'aluno')
ON DUPLICATE KEY UPDATE id=id;