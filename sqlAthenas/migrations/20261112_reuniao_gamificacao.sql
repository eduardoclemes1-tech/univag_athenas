USE athenas;

SET @renomear_url_reuniao = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = 'link_zoom'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = 'meeting_url'
    ),
    'ALTER TABLE agendamentos CHANGE COLUMN link_zoom meeting_url VARCHAR(500) NULL',
    'SELECT 1'
);
PREPARE migracao_url_reuniao FROM @renomear_url_reuniao;
EXECUTE migracao_url_reuniao;
DEALLOCATE PREPARE migracao_url_reuniao;

CREATE TABLE IF NOT EXISTS loja_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NOT NULL DEFAULT '',
    categoria ENUM('fundo', 'avatar') NOT NULL,
    preco INT UNSIGNED NOT NULL,
    visual VARCHAR(50) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loja_compras (
    usuario_id INT NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    comprado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, item_id),
    CONSTRAINT fk_loja_compra_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_loja_compra_item FOREIGN KEY (item_id) REFERENCES loja_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loja_equipamentos (
    usuario_id INT NOT NULL,
    categoria ENUM('fundo', 'avatar') NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    equipado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, categoria),
    CONSTRAINT fk_loja_equipamento_compra
        FOREIGN KEY (usuario_id, item_id) REFERENCES loja_compras(usuario_id, item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO loja_itens (slug, nome, descricao, categoria, preco, visual) VALUES
    ('fundo-oceano', 'Fundo Oceano', 'Um degradê azul para o seu perfil.', 'fundo', 20, 'oceano'),
    ('fundo-aurora', 'Fundo Aurora', 'Tons verdes e azulados inspirados na aurora.', 'fundo', 30, 'aurora'),
    ('fundo-por-do-sol', 'Fundo Pôr do Sol', 'Cores quentes para personalizar seu perfil.', 'fundo', 30, 'por-do-sol'),
    ('avatar-lilas', 'Avatar Lilás', 'Um avatar acadêmico em tons de lilás.', 'avatar', 15, 'lilas'),
    ('avatar-esmeralda', 'Avatar Esmeralda', 'Um avatar acadêmico em tons de verde.', 'avatar', 15, 'esmeralda'),
    ('avatar-dourado', 'Avatar Dourado', 'Um avatar acadêmico em tons dourados.', 'avatar', 25, 'dourado');

CREATE TABLE IF NOT EXISTS pontos_transacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    agendamento_id BIGINT UNSIGNED NULL,
    item_id INT UNSIGNED NULL,
    evento ENUM('participacao', 'cancelamento_tardio', 'compra') NOT NULL,
    pontos INT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pontos_agendamento_evento (agendamento_id, evento),
    UNIQUE KEY uq_pontos_compra_item (usuario_id, item_id, evento),
    KEY idx_pontos_usuario_criado (usuario_id, criado_em),
    CONSTRAINT fk_pontos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_pontos_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pontos_item FOREIGN KEY (item_id) REFERENCES loja_itens(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
