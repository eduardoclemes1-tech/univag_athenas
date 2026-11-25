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
 * Importa automaticamente o sqlAthenas/schema.sql caso o banco ainda não exista
 * na máquina local (ex.: computadores de laboratório com XAMPP/MySQL recém-ligado).
 */
function inicializarBancoAutomaticamente(string $dbHost, string $dbUser, string $dbPass, array $pdoOptions): void
{
    $schemaPath = __DIR__ . '/../sqlAthenas/schema.sql';
    if (!is_file($schemaPath) || !is_readable($schemaPath)) {
        return;
    }

    $pdoSetup = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    $sql = file_get_contents($schemaPath);
    if (!is_string($sql) || trim($sql) === '') {
        return;
    }

    // Remove comentários de linha simples e executa o script de inicialização
    $linhas = array_filter(
        explode("\n", $sql),
        static fn(string $l): bool => !str_starts_with(ltrim($l), '--')
    );
    $pdoSetup->exec(implode("\n", $linhas));
}

try {
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
    } catch (PDOException $e) {
        // Código 1049 = Unknown database 'athenas' -> Cria e popula o banco sozinho!
        if ((int) ($e->errorInfo[1] ?? 0) === 1049 || str_contains($e->getMessage(), '1049')) {
            inicializarBancoAutomaticamente($dbHost, $dbUser, $dbPass, $pdoOptions);
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, $pdoOptions);
        } else {
            throw $e;
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    die('Erro na conexão com o banco de dados. Confira as configurações e se o serviço MySQL está ativo.');
}
