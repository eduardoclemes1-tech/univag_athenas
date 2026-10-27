USE athenas;

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
