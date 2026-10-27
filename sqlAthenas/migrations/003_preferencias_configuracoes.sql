USE athenas;

ALTER TABLE usuarios
    ADD COLUMN notificar_mensagens TINYINT(1) NOT NULL DEFAULT 1 AFTER foto_mime,
    ADD COLUMN notificar_agendamentos TINYINT(1) NOT NULL DEFAULT 1 AFTER notificar_mensagens;
