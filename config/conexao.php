<?php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'athenas';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

/**
 * Executa as declarações do sqlAthenas/schema.sql comando por comando.
 */
function executarScriptSchemaAthenas(PDO $pdoAlvo, bool $incluirCreateDatabase = false): void
{
    $schemaPath = __DIR__ . '/../sqlAthenas/schema.sql';
    if (!is_file($schemaPath) || !is_readable($schemaPath)) {
        return;
    }

    $sql = file_get_contents($schemaPath);
    if (!is_string($sql) || trim($sql) === '') {
        return;
    }

    // Remove comentários de linha (-- ...) e comentários em bloco (/* ... */)
    $sqlLimpo = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;
    $linhas = array_filter(
        explode("\n", $sqlLimpo),
        static fn(string $l): bool => !str_starts_with(ltrim($l), '--')
    );
    $conteudo = implode("\n", $linhas);

    foreach (explode(';', $conteudo) as $comando) {
        $cmd = trim($comando);
        if ($cmd === '') {
            continue;
        }
        if (!$incluirCreateDatabase && preg_match('/^(CREATE\s+DATABASE|USE\s+)/i', $cmd)) {
            continue;
        }
        try {
            $pdoAlvo->exec($cmd);
        } catch (Throwable $e) {
            error_log('[Athenas Auto-Sync] ' . $e->getMessage());
        }
    }
}

/**
 * Garante que bancos já existentes (ex.: volumes antigos do Docker no Codespaces)
 * recebam todas as colunas, tabelas, conjuntos da lojinha e maestrias novas.
 */
function sincronizarSchemaAthenas(PDO $pdo): void
{
    static $jaVerificadoNoProcesso = false;
    if ($jaVerificadoNoProcesso) {
        return;
    }
    $jaVerificadoNoProcesso = true;

    try {
        // Verifica rapidamente se o banco já possui a tabela loja_itens com os novos prêmios de Maestria 10
        // e a tabela perfis_monitores
        $precisaSincronizar = false;
        try {
            $stmtChk = $pdo->query(
                "SELECT
                    (SELECT COUNT(*) FROM loja_itens WHERE slug = 'maestria-fundo-dados' AND ativo = 1) AS tem_maestria,
                    (SELECT COUNT(*) FROM loja_itens WHERE slug = 'fundo-oceano' AND ativo = 1) AS tem_legado_ativo,
                    (SELECT COUNT(*) FROM perfis_monitores) AS tem_monitores"
            );
            $rowChk = $stmtChk ? $stmtChk->fetch() : false;
            if (
                !$rowChk
                || (int) ($rowChk['tem_maestria'] ?? 0) === 0
                || (int) ($rowChk['tem_legado_ativo'] ?? 0) > 0
                || (int) ($rowChk['tem_monitores'] ?? 0) === 0
            ) {
                $precisaSincronizar = true;
            }
        } catch (Throwable $e) {
            // Se alguma tabela ainda não existir no volume antigo do Docker, força a sincronização completa
            $precisaSincronizar = true;
        }

        if (!$precisaSincronizar) {
            return;
        }

        // 1. Garante colunas adicionadas em migrações incrementais na tabela usuarios
        $colunasUsuarios = [
            'bio' => "ALTER TABLE usuarios ADD COLUMN bio VARCHAR(500) NOT NULL DEFAULT ''",
            'curso' => "ALTER TABLE usuarios ADD COLUMN curso VARCHAR(120) NOT NULL DEFAULT ''",
            'periodo' => "ALTER TABLE usuarios ADD COLUMN periodo VARCHAR(40) NOT NULL DEFAULT ''",
            'foto_perfil' => "ALTER TABLE usuarios ADD COLUMN foto_perfil MEDIUMBLOB NULL",
            'foto_mime' => "ALTER TABLE usuarios ADD COLUMN foto_mime VARCHAR(30) NULL",
            'notificar_mensagens' => "ALTER TABLE usuarios ADD COLUMN notificar_mensagens TINYINT(1) NOT NULL DEFAULT 1",
            'notificar_agendamentos' => "ALTER TABLE usuarios ADD COLUMN notificar_agendamentos TINYINT(1) NOT NULL DEFAULT 1",
        ];
        foreach ($colunasUsuarios as $col => $ddl) {
            $stmtCol = $pdo->prepare(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = ?"
            );
            $stmtCol->execute([$col]);
            if (!$stmtCol->fetchColumn()) {
                try {
                    $pdo->exec($ddl);
                } catch (Throwable $e) {
                }
            }
        }

        // 2. Garante colunas adicionadas em migrações incrementais na tabela agendamentos
        $stmtZoom = $pdo->prepare(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = 'link_zoom'"
        );
        $stmtZoom->execute();
        $temLinkZoom = (bool) $stmtZoom->fetchColumn();

        $stmtMeet = $pdo->prepare(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = 'meeting_url'"
        );
        $stmtMeet->execute();
        $temMeetingUrl = (bool) $stmtMeet->fetchColumn();

        if ($temLinkZoom && !$temMeetingUrl) {
            try {
                $pdo->exec("ALTER TABLE agendamentos CHANGE COLUMN link_zoom meeting_url VARCHAR(500) NULL");
            } catch (Throwable $e) {
            }
        }

        $colunasAgendamentos = [
            'duracao_minutos' => "ALTER TABLE agendamentos ADD COLUMN duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 60",
            'meeting_url' => "ALTER TABLE agendamentos ADD COLUMN meeting_url VARCHAR(500) NULL",
            'presenca_aluno' => "ALTER TABLE agendamentos ADD COLUMN presenca_aluno TINYINT(1) NULL DEFAULT NULL",
            'presenca_monitor' => "ALTER TABLE agendamentos ADD COLUMN presenca_monitor TINYINT(1) NULL DEFAULT NULL",
        ];
        foreach ($colunasAgendamentos as $col => $ddl) {
            $stmtCol = $pdo->prepare(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = ?"
            );
            $stmtCol->execute([$col]);
            if (!$stmtCol->fetchColumn()) {
                try {
                    $pdo->exec($ddl);
                } catch (Throwable $e) {
                }
            }
        }

        // 3. Executa todas as criações de tabelas e seeds do schema.sql
        executarScriptSchemaAthenas($pdo, false);

        // 4. Desativa itens antigos da primeira versão da loja caso existam no volume do Docker
        try {
            $pdo->exec(
                "UPDATE loja_itens SET ativo = 0
                 WHERE slug IN (
                    'fundo-oceano', 'fundo-aurora', 'fundo-por-do-sol',
                    'fundo-nebulosa', 'fundo-aurora-animada', 'fundo-chuva-estrelas', 'fundo-estrelas',
                    'avatar-lilas', 'avatar-esmeralda', 'avatar-dourado',
                    'avatar-asas-capelo', 'avatar-capivara'
                 )"
            );
        } catch (Throwable $e) {
        }

        // 5. Atualiza o ENUM de eventos da tabela pontos_transacoes para suportar todas as missões
        try {
            $pdo->exec(
                "ALTER TABLE pontos_transacoes
                 MODIFY COLUMN evento ENUM(
                    'participacao', 'cancelamento_tardio', 'compra',
                    'avaliacao', 'material_apoio', 'bonus_monitor', 'perfil_completo'
                 ) NOT NULL"
            );
        } catch (Throwable $e) {
        }
    } catch (Throwable $e) {
        error_log('[Athenas Sync] ' . $e->getMessage());
    }
}

try {
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
    } catch (PDOException $e) {
        // Código 1049 = Unknown database 'athenas' -> Cria e popula o banco sozinho!
        if ((int) ($e->errorInfo[1] ?? 0) === 1049 || str_contains($e->getMessage(), '1049')) {
            $pdoSetup = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);
            executarScriptSchemaAthenas($pdoSetup, true);
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
        } else {
            throw $e;
        }
    }

    sincronizarSchemaAthenas($pdo);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    die('Erro na conexão com o banco de dados. Confira as configurações e se o serviço MySQL está ativo.');
}
