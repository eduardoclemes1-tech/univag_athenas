USE athenas;

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
