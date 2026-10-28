USE athenas;

SET @adicionar_presenca_aluno = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = 'presenca_aluno') = 0,
    'ALTER TABLE agendamentos ADD COLUMN presenca_aluno TINYINT(1) NULL DEFAULT NULL AFTER link_zoom',
    'SELECT 1'
);
PREPARE migracao FROM @adicionar_presenca_aluno;
EXECUTE migracao;
DEALLOCATE PREPARE migracao;

SET @adicionar_presenca_monitor = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = 'presenca_monitor') = 0,
    'ALTER TABLE agendamentos ADD COLUMN presenca_monitor TINYINT(1) NULL DEFAULT NULL AFTER presenca_aluno',
    'SELECT 1'
);
PREPARE migracao FROM @adicionar_presenca_monitor;
EXECUTE migracao;
DEALLOCATE PREPARE migracao;

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
