USE athenas;

ALTER TABLE agendamentos
    ADD COLUMN duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 60 AFTER agendado_para;
