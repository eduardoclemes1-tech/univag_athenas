/* ===== 1. CRIAÇÃO DO BANCO DE DADOS E SELEÇÃO DE CONTEXTO ===== */
SET NAMES utf8mb4;
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
    meeting_url VARCHAR(500) NULL,
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

INSERT INTO loja_itens (slug, nome, descricao, categoria, preco, visual, ativo) VALUES
    ('fundo-matrix-cyber', 'Conjunto Ciberespaço Root', 'Malha hacker neon ciano/verde em movimento + Insígnia Terminal Root >_.', 'fundo', 20, 'matrix-cyber', 1),
    ('fundo-oceano-abissal', 'Conjunto Fossa de Poseidon', 'Correntes bioluminescentes oceânicas + Brasão Tridente Abissal.', 'fundo', 25, 'oceano-abissal', 1),
    ('fundo-templo-atena', 'Conjunto Guardião do Olimpo', 'Constelações safira em rotação celeste + Coruja Sábia de Atena.', 'fundo', 30, 'templo-atena', 1),
    ('fundo-floresta-runica', 'Conjunto Santuário Bio-Tech', 'Energia esmeralda e partículas douradas vivas + Insígnia Bio-Tech.', 'fundo', 40, 'floresta-runica', 1),
    ('fundo-horizonte-synth', 'Conjunto Outrun Synthwave', 'Sol neon retro-futurista e grade laser + Visor Cyber-Synth.', 'fundo', 50, 'horizonte-synth', 1),
    ('fundo-tempestade-plasma', 'Conjunto Sobrecarga Quântica', 'Descargas de plasma em alta voltagem + Reator de Núcleo Quântico.', 'fundo', 55, 'tempestade-plasma', 1),
    ('fundo-protocolo-fantasma', 'Conjunto Protocolo Fantasma', 'Feixes furtivos em carbono e ametista + Insígnia Ninja Cyber.', 'fundo', 65, 'protocolo-fantasma', 1),
    ('fundo-singularidade', 'Conjunto Horizonte de Eventos', 'Buraco negro com anel fotônico dourado + Viajante Interestelar.', 'fundo', 70, 'singularidade', 1),
    ('fundo-forja-dragao', 'Conjunto Fúria do Compilador', 'Magma vulcânico e brasas douradas + Brasão Dragão Compilador.', 'fundo', 80, 'forja-dragao', 1),
    ('fundo-eclipse-carmesim', 'Conjunto Lua de Sangue', 'Eclipse solar total com coroa carmesim + Soberano do Eclipse.', 'fundo', 85, 'eclipse-carmesim', 1),
    ('fundo-supernova-prisma', 'Conjunto Supernova Prismática', 'Explosão estelar holográfica diamantina + Joia Prismática.', 'fundo', 90, 'supernova-prisma', 1),
    ('fundo-chama-olimpica', 'Conjunto Honra Suprema UNIVAG', 'Luz imperial dourada de Atena + Coroa Suprema Lenda do Campus.', 'fundo', 100, 'chama-olimpica', 1),
    ('avatar-hacker-neon', 'Mestre do Terminal', 'Avatar do Conjunto Ciberespaço Root.', 'avatar', 20, 'hacker-neon', 1),
    ('avatar-leviata-dados', 'Senhor dos Mares', 'Avatar do Conjunto Fossa de Poseidon.', 'avatar', 25, 'leviata-dados', 1),
    ('avatar-coruja-atena', 'Coruja Sábia de Atena', 'Avatar do Conjunto Guardião do Olimpo.', 'avatar', 30, 'coruja-atena', 1),
    ('avatar-espirito-pantanal', 'Guardião Bio-Tech', 'Avatar do Conjunto Santuário Bio-Tech.', 'avatar', 40, 'espirito-pantanal', 1),
    ('avatar-piloto-synth', 'Visor Cyber-Synth', 'Avatar do Conjunto Outrun Synthwave.', 'avatar', 50, 'piloto-synth', 1),
    ('avatar-nucleo-quantico', 'Núcleo Quântico', 'Avatar do Conjunto Sobrecarga Quântica.', 'avatar', 55, 'nucleo-quantico', 1),
    ('avatar-ninja-cyber', 'Sombra Furtiva', 'Avatar do Conjunto Protocolo Fantasma.', 'avatar', 65, 'ninja-cyber', 1),
    ('avatar-viajante-astral', 'Viajante Interestelar', 'Avatar do Conjunto Horizonte de Eventos.', 'avatar', 70, 'viajante-astral', 1),
    ('avatar-dragao-codigo', 'Dragão Compilador', 'Avatar do Conjunto Fúria do Compilador.', 'avatar', 80, 'dragao-codigo', 1),
    ('avatar-soberano-eclipse', 'Soberano do Eclipse', 'Avatar do Conjunto Lua de Sangue.', 'avatar', 85, 'soberano-eclipse', 1),
    ('avatar-fenix-cosmica', 'Cristal de Supernova', 'Avatar do Conjunto Supernova Prismática.', 'avatar', 90, 'fenix-cosmica', 1),
    ('avatar-coroa-lendaria', 'Lenda do Campus UNIVAG', 'Avatar do Conjunto Honra Suprema UNIVAG.', 'avatar', 100, 'coroa-lendaria', 1),
    ('maestria-fundo-dados', 'O Literal Banco de Dados', 'Prêmio Exclusivo Maestria 10 (BD): Um banco de praça feito inteiramente de dados de 6 faces + O Analista de Dados.', 'fundo', 0, 'maestria-banco-dados', 1),
    ('maestria-fundo-logica', 'Na Minha Máquina Funciona™', 'Prêmio Exclusivo Maestria 10 (Lógica): Insetos (bugs) de cartola tomando café no terminal.', 'fundo', 0, 'maestria-cacador-bugs', 1),
    ('maestria-fundo-estruturas', 'Árvore Invertida & Pilha Alcalina', 'Prêmio Exclusivo Maestria 10 (Estruturas): Árvore com a raiz no céu e folhas no chão + Pilhas Duracell LIFO.', 'fundo', 0, 'maestria-arvore-pilha', 1),
    ('maestria-fundo-dev', 'O Escolhido que Centralizou a <div>', 'Prêmio Exclusivo Maestria 10 (Full-Stack): Uma <div> perfeitamente centralizada a laser + Pilha de Panquecas.', 'fundo', 0, 'maestria-centralizador-div', 1),
    ('maestria-fundo-redes', 'Rede de Varanda & Parede em Chamas', 'Prêmio Exclusivo Maestria 10 (Redes): Uma rede de praia balançando ao lado de um muro pegando fogo (Firewall).', 'fundo', 0, 'maestria-rede-dormir', 1),
    ('maestria-avatar-dados', 'O Analista de Dados (Literal)', 'Analisando dados de cassino com lupa · Maestria 10 em Banco de Dados.', 'avatar', 0, 'maestria-analista-dados', 1),
    ('maestria-avatar-logica', 'Criador Oficial de Bugs', 'Não é bug, é feature de gala · Maestria 10 em Lógica & Algoritmos.', 'avatar', 0, 'maestria-domador-bugs', 1),
    ('maestria-avatar-estruturas', 'Lenhador de Árvore Invertida', 'Raiz no topo, folhas embaixo · Maestria 10 em Estruturas de Dados.', 'avatar', 0, 'maestria-lenhador-binario', 1),
    ('maestria-avatar-dev', 'Senhor do margin: auto', 'Dominou a arte proibida de centralizar a <div> · Maestria 10 em Full-Stack.', 'avatar', 0, 'maestria-mestre-div', 1),
    ('maestria-avatar-redes', 'Admin de Rede (de Dormir)', 'Descansando na LAN protegido pelo muro em chamas · Maestria 10 em Redes.', 'avatar', 0, 'maestria-admin-rede', 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descricao = VALUES(descricao),
    preco = VALUES(preco),
    visual = VALUES(visual),
    ativo = VALUES(ativo);

UPDATE loja_itens SET ativo = 0
WHERE slug IN (
    'fundo-oceano', 'fundo-aurora', 'fundo-por-do-sol',
    'fundo-nebulosa', 'fundo-aurora-animada', 'fundo-chuva-estrelas', 'fundo-estrelas',
    'avatar-lilas', 'avatar-esmeralda', 'avatar-dourado',
    'avatar-asas-capelo', 'avatar-capivara'
);

CREATE TABLE IF NOT EXISTS pontos_transacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    agendamento_id BIGINT UNSIGNED NULL,
    item_id INT UNSIGNED NULL,
    evento ENUM('participacao', 'cancelamento_tardio', 'compra', 'avaliacao', 'material_apoio', 'bonus_monitor', 'perfil_completo') NOT NULL,
    pontos INT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pontos_agendamento_evento (agendamento_id, evento),
    UNIQUE KEY uq_pontos_compra_item (usuario_id, item_id, evento),
    KEY idx_pontos_usuario_criado (usuario_id, criado_em),
    CONSTRAINT fk_pontos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_pontos_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pontos_item FOREIGN KEY (item_id) REFERENCES loja_itens(id) ON DELETE SET NULL
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

/* ===== 6. DADOS INICIAIS DE TESTE (SOMENTE AMBIENTE DE DESENVOLVIMENTO) ===== */
-- ATENÇÃO: Nunca execute estas inserções em ambiente de produção com senhas padrão!
-- Insere monitores e alunos de teste para ambiente de desenvolvimento (Senha padrão: 123456)
INSERT INTO usuarios (nome, email, cpf, senha, tipo, bio, curso, periodo) VALUES
('Marcos Silva Pereira', 'admin@univag.br', '00000000000', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor', 'Gosto de explicar banco de dados com exemplos do dia a dia, começando pelos conceitos e avançando para exercícios práticos de SQL.', 'Sistemas de Informação', '5º semestre'),
('Aluno Teste', 'aluno@univag.br', '11111111111', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'aluno', 'Estudante dedicado em busca de reforço prático em programação e banco de dados.', 'Engenharia de Software', '3º semestre'),
('Mariana Souza Oliveira', 'mariana.oliveira@univag.br', '77777777777', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'aluno', 'Focada em modelagem de banco de dados e desenvolvimento back-end.', 'Sistemas de Informação', '2º semestre'),
('Gabriel Henrique Lima', 'gabriel.lima@univag.br', '88888888888', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'aluno', 'Interessado em algoritmos, estruturas de dados e projetos práticos.', 'Ciência da Computação', '1º semestre'),
('Beatriz Mendes Ferreira', 'beatriz.ferreira@univag.br', '99999999999', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'aluno', 'Buscando apoio em SQL, normalização e consultas avançadas.', 'Engenharia de Software', '4º semestre'),
('Ana Beatriz Costa', 'ana.costa@univag.br', '22222222222', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor', 'Ajudo quem está começando a transformar problemas em passos simples e escrever os primeiros programas com confiança.', 'Ciência da Computação', '4º semestre'),
('Lucas Mendes Rocha', 'lucas.rocha@univag.br', '33333333333', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor', 'Trabalho com projetos web e gosto de ensinar construindo páginas junto com o aluno, do primeiro layout à interação.', 'Sistemas de Informação', '6º semestre'),
('Juliana Ferreira Lima', 'juliana.lima@univag.br', '44444444444', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor', 'Ajudo a visualizar como os dados se organizam em listas, árvores e outras estruturas, relacionando teoria e implementação.', 'Ciência da Computação', '5º semestre'),
('Rafael Oliveira Santos', 'rafael.santos@univag.br', '55555555555', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor', 'Explico os fundamentos de redes e protocolos com exemplos práticos de comunicação entre dispositivos.', 'Redes de Computadores', '6º semestre'),
('Camila Nunes Almeida', 'camila.almeida@univag.br', '66666666666', '$2y$10$ea0oZHkIso5NZftQABwAt.dcRutq9gn01r4CiTgxOYME9bVuOBGse', 'monitor', 'Compartilho práticas para criar interfaces mobile simples, acessíveis e conectadas a serviços web.', 'Sistemas de Informação', '6º semestre')
ON DUPLICATE KEY UPDATE
    bio = IF(usuarios.bio = '', VALUES(bio), usuarios.bio),
    curso = IF(usuarios.curso = '', VALUES(curso), usuarios.curso),
    periodo = IF(usuarios.periodo = '', VALUES(periodo), usuarios.periodo);

INSERT INTO perfis_monitores (monitor_id, area_atuacao, disciplina, local_atendimento, duracao_monitoria, modalidade, metodologia, ideal_para, topicos)
SELECT id, 'Dados', 'Banco de Dados', 'Online e presencial', '1 hora', 'Online e presencial',
       'Explicações passo a passo de modelagem relacional e prática guiada com consultas SQL reais.',
       'Quem quer aprender a modelar informações e consultar bancos relacionais.',
       'SQL, Modelagem de dados, DER, Consultas e Junções'
FROM usuarios WHERE email = 'admin@univag.br'
ON DUPLICATE KEY UPDATE monitor_id = monitor_id;

INSERT INTO perfis_monitores (monitor_id, area_atuacao, disciplina, local_atendimento, duracao_monitoria, modalidade, metodologia, ideal_para, topicos)
SELECT id, 'Programação', 'Lógica de Programação', 'Sala virtual (Google Meet)', '1 hora', 'Online',
       'Resolução de exercícios práticos passo a passo com testes de mesa e Python.',
       'Iniciantes em programação que querem dominar estruturas condicionais e laços.',
       'Python, Algoritmos, Iniciantes, Fluxogramas'
FROM usuarios WHERE email = 'ana.costa@univag.br'
ON DUPLICATE KEY UPDATE monitor_id = monitor_id;

INSERT INTO perfis_monitores (monitor_id, area_atuacao, disciplina, local_atendimento, duracao_monitoria, modalidade, metodologia, ideal_para, topicos)
SELECT id, 'Desenvolvimento', 'Desenvolvimento Web', 'Laboratório 3 - Bloco C', '1 hora', 'Presencial',
       'Construção prática de interfaces semânticas, responsivas e interativas.',
       'Estudantes que querem criar páginas web modernas do zero.',
       'HTML e CSS, JavaScript, Projetos, Responsividade'
FROM usuarios WHERE email = 'lucas.rocha@univag.br'
ON DUPLICATE KEY UPDATE monitor_id = monitor_id;

INSERT INTO perfis_monitores (monitor_id, area_atuacao, disciplina, local_atendimento, duracao_monitoria, modalidade, metodologia, ideal_para, topicos)
SELECT id, 'Programação', 'Estruturas de Dados', 'Híbrido (Meet ou Lab 2)', '1 hora', 'Online e presencial',
       'Visualização gráfica de ponteiros, listas, pilhas, filas e árvores com implementação prática.',
       'Quem já conhece lógica básica e quer dominar estruturas de dados e complexidade.',
       'Algoritmos, Árvores, Ordenação, Pilhas e Filas'
FROM usuarios WHERE email = 'juliana.lima@univag.br'
ON DUPLICATE KEY UPDATE monitor_id = monitor_id;

INSERT INTO perfis_monitores (monitor_id, area_atuacao, disciplina, local_atendimento, duracao_monitoria, modalidade, metodologia, ideal_para, topicos)
SELECT id, 'Infraestrutura', 'Redes de Computadores', 'Sala virtual', '45 minutos', 'Online',
       'Simulações práticas de sub-redes, roteamento, DNS e análise de pacotes.',
       'Quem busca compreender protocolos TCP/IP e infraestrutura de redes.',
       'TCP/IP, Endereçamento, Conectividade, Roteamento'
FROM usuarios WHERE email = 'rafael.santos@univag.br'
ON DUPLICATE KEY UPDATE monitor_id = monitor_id;

INSERT INTO perfis_monitores (monitor_id, area_atuacao, disciplina, local_atendimento, duracao_monitoria, modalidade, metodologia, ideal_para, topicos)
SELECT id, 'Desenvolvimento', 'Desenvolvimento Mobile', 'Laboratório Mobile - Bloco B', '1 hora', 'Presencial',
       'Prototipação e integração de aplicativos mobile com consumo de APIs REST.',
       'Estudantes interessados em criação de apps e interfaces móveis.',
       'Interfaces, Aplicativos, APIs, UX Mobile'
FROM usuarios WHERE email = 'camila.almeida@univag.br'
ON DUPLICATE KEY UPDATE monitor_id = monitor_id;

INSERT IGNORE INTO disponibilidades_monitores (monitor_id, dia_semana, disponivel, hora_inicio, hora_fim)
SELECT u.id, d.dia_semana, d.disponivel, d.hora_inicio, d.hora_fim
FROM usuarios u
CROSS JOIN (
    SELECT 1 AS dia_semana, 1 AS disponivel, '14:00:00' AS hora_inicio, '18:00:00' AS hora_fim UNION ALL
    SELECT 2, 1, '14:00:00', '18:00:00' UNION ALL
    SELECT 3, 1, '14:00:00', '18:00:00' UNION ALL
    SELECT 4, 1, '14:00:00', '18:00:00' UNION ALL
    SELECT 5, 1, '14:00:00', '17:00:00' UNION ALL
    SELECT 6, 0, NULL, NULL UNION ALL
    SELECT 7, 0, NULL, NULL
) d
WHERE u.email IN ('admin@univag.br', 'ana.costa@univag.br', 'juliana.lima@univag.br', 'rafael.santos@univag.br', 'camila.almeida@univag.br');

-- Conversa e agendamentos iniciais para demonstração caso a tabela esteja vazia
INSERT IGNORE INTO conversas (aluno_id, monitor_id)
SELECT a.id, m.id
FROM usuarios a
JOIN usuarios m ON m.email = 'admin@univag.br'
WHERE a.email = 'aluno@univag.br';

INSERT INTO agendamentos (conversa_id, aluno_id, monitor_id, assunto, agendado_para, duracao_minutos, status, meeting_url, presenca_aluno, presenca_monitor)
SELECT c.id, c.aluno_id, c.monitor_id,
       'Modelagem Relacional e Consultas SQL (JOINs)',
       DATE_SUB(DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d 15:00:00'), INTERVAL 2 DAY),
       90, 'confirmado', 'https://meet.google.com/univag-athenas-sql', 1, 1
FROM conversas c
JOIN usuarios a ON a.id = c.aluno_id AND a.email = 'aluno@univag.br'
JOIN usuarios m ON m.id = c.monitor_id AND m.email = 'admin@univag.br'
WHERE NOT EXISTS (SELECT 1 FROM agendamentos ag WHERE ag.aluno_id = c.aluno_id);

INSERT INTO agendamentos (conversa_id, aluno_id, monitor_id, assunto, agendado_para, duracao_minutos, status, meeting_url)
SELECT c.id, c.aluno_id, c.monitor_id,
       'Revisão Prática de Normalização e Índices',
       DATE_ADD(DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d 16:00:00'), INTERVAL 2 DAY),
       60, 'confirmado', 'https://meet.google.com/univag-athenas-sql'
FROM conversas c
JOIN usuarios a ON a.id = c.aluno_id AND a.email = 'aluno@univag.br'
JOIN usuarios m ON m.id = c.monitor_id AND m.email = 'admin@univag.br'
WHERE (SELECT COUNT(*) FROM agendamentos ag WHERE ag.aluno_id = c.aluno_id) = 1;

INSERT IGNORE INTO pontos_transacoes (usuario_id, agendamento_id, evento, pontos)
SELECT ag.aluno_id, ag.id, 'participacao', GREATEST(10, ROUND(ag.duracao_minutos / 3))
FROM agendamentos ag
WHERE ag.presenca_aluno = 1 AND ag.presenca_monitor = 1;

