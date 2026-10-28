<?php
require_once __DIR__ . '/../includes/auth.php';
exigirUsuarioAutenticado();

header('Cache-Control: no-store');

function responderMonitorias(int $status, array $dados): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function participanteAgendamento(PDO $pdo, int $agendamentoId, int $usuarioId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT a.*, aluno.nome AS aluno_nome, monitor.nome AS monitor_nome
         FROM agendamentos a
         JOIN usuarios aluno ON aluno.id = a.aluno_id
         JOIN usuarios monitor ON monitor.id = a.monitor_id
         WHERE a.id = ? AND (a.aluno_id = ? OR a.monitor_id = ?)'
    );
    $stmt->execute([$agendamentoId, $usuarioId, $usuarioId]);
    return $stmt->fetch() ?: null;
}

function notificarParticipante(PDO $pdo, int $usuarioId, int $agendamentoId, string $titulo, string $mensagem): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, agendamento_id)
         SELECT id, ?, ?, ?, ?
         FROM usuarios
         WHERE id = ? AND notificar_agendamentos = 1'
    );
    $stmt->execute(['agendamento', $titulo, $mensagem, $agendamentoId, $usuarioId]);
}

$usuarioId = (int) $_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'];
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodo === 'POST') {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (
        !is_string($csrf)
        || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $csrf)
    ) {
        responderMonitorias(403, ['erro' => 'A sessão expirou. Atualize a página e tente novamente.']);
    }

    if (str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            responderMonitorias(400, ['erro' => 'Não foi possível interpretar a solicitação.']);
        }
    } else {
        $entrada = $_POST;
    }
} elseif ($metodo !== 'GET') {
    header('Allow: GET, POST');
    responderMonitorias(405, ['erro' => 'Método não permitido.']);
}

require_once __DIR__ . '/../config/conexao.php';

try {
    if ($metodo === 'GET' && isset($_GET['arquivo'])) {
        $materialId = filter_var($_GET['arquivo'], FILTER_VALIDATE_INT);
        if (!$materialId) {
            responderMonitorias(400, ['erro' => 'Material inválido.']);
        }

        $stmt = $pdo->prepare(
            'SELECT m.nome_original, m.storage_key, m.mime_type, a.aluno_id, a.monitor_id
             FROM materiais_agendamentos m
             JOIN agendamentos a ON a.id = m.agendamento_id
             WHERE m.id = ? AND (a.aluno_id = ? OR a.monitor_id = ?)'
        );
        $stmt->execute([$materialId, $usuarioId, $usuarioId]);
        $material = $stmt->fetch();
        if (!$material || !$material['storage_key'] || !preg_match('/^[a-f0-9]{64}$/', $material['storage_key'])) {
            responderMonitorias(404, ['erro' => 'Arquivo não encontrado.']);
        }

        $diretorio = rtrim(getenv('MATERIAL_STORAGE_DIR') ?: dirname(__DIR__, 2) . '/athenas_uploads', DIRECTORY_SEPARATOR);
        $caminho = realpath($diretorio . DIRECTORY_SEPARATOR . $material['storage_key']);
        $raiz = realpath($diretorio);
        if (!$caminho || !$raiz || !str_starts_with($caminho, $raiz . DIRECTORY_SEPARATOR) || !is_file($caminho) || !is_readable($caminho)) {
            responderMonitorias(404, ['erro' => 'Arquivo não encontrado.']);
        }

        $nomeSeguro = preg_replace('/[^A-Za-z0-9._-]/', '_', $material['nome_original']);
        header('Content-Type: ' . $material['mime_type']);
        header('Content-Length: ' . filesize($caminho));
        header('Content-Disposition: attachment; filename="' . $nomeSeguro . '"; filename*=UTF-8\'\'' . rawurlencode($material['nome_original']));
        header('X-Content-Type-Options: nosniff');
        readfile($caminho);
        exit;
    }

    if ($metodo === 'GET') {
        $stmt = $pdo->prepare(
            "SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, a.status, a.link_zoom,
                    a.aluno_id, a.monitor_id, a.presenca_aluno, a.presenca_monitor,
                    aluno.nome AS aluno_nome, monitor.nome AS monitor_nome,
                    av.nota AS avaliacao_nota, av.comentario AS avaliacao_comentario,
                    r.assunto_estudado, r.observacoes AS registro_observacoes,
                    r.atualizado_em AS registro_atualizado_em
             FROM agendamentos a
             JOIN usuarios aluno ON aluno.id = a.aluno_id
             JOIN usuarios monitor ON monitor.id = a.monitor_id
             LEFT JOIN avaliacoes_monitorias av ON av.agendamento_id = a.id
             LEFT JOIN registros_monitorias r ON r.agendamento_id = a.id
             WHERE a.status = 'confirmado'
               AND (a.aluno_id = ? OR a.monitor_id = ?)
               AND a.agendado_para >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 60 DAY)
               AND a.agendado_para < DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 120 DAY)
             ORDER BY a.agendado_para
             LIMIT 500"
        );
        $stmt->execute([$usuarioId, $usuarioId]);
        $agendamentos = $stmt->fetchAll();
        $ids = array_map(static fn(array $item): int => (int) $item['id'], $agendamentos);
        $materiaisPorAgendamento = [];
        if ($ids) {
            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT m.id, m.agendamento_id, m.remetente_id, u.nome AS remetente_nome,
                        m.nome_original, m.url, m.tamanho_bytes, m.criado_em
                 FROM materiais_agendamentos m
                 JOIN usuarios u ON u.id = m.remetente_id
                 WHERE m.agendamento_id IN ({$marcadores})
                 ORDER BY m.criado_em"
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $material) {
                $materiaisPorAgendamento[(int) $material['agendamento_id']][] = $material;
            }
        }

        foreach ($agendamentos as &$agendamento) {
            $agendamento['materiais'] = $materiaisPorAgendamento[(int) $agendamento['id']] ?? [];
        }
        unset($agendamento);
        responderMonitorias(200, ['agendamentos' => $agendamentos]);
    }

    $acao = $entrada['acao'] ?? '';
    $agendamentoId = filter_var($entrada['agendamento_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$agendamentoId) {
        responderMonitorias(400, ['erro' => 'Monitoria inválida.']);
    }

    $agendamento = participanteAgendamento($pdo, $agendamentoId, $usuarioId);
    if (!$agendamento || $agendamento['status'] !== 'confirmado') {
        responderMonitorias(404, ['erro' => 'Monitoria não encontrada.']);
    }

    if ($acao === 'presenca') {
        if (strtotime($agendamento['agendado_para']) + (int) $agendamento['duracao_minutos'] * 60 > time()) {
            responderMonitorias(422, ['erro' => 'A presença só pode ser registrada após o término da monitoria.']);
        }
        $presenteEntrada = $entrada['presente'] ?? null;
        if (!in_array($presenteEntrada, [true, false, 1, 0, '1', '0'], true)) {
            responderMonitorias(422, ['erro' => 'Informe se você compareceu à monitoria.']);
        }
        $valorPresenca = in_array($presenteEntrada, [true, 1, '1'], true) ? 1 : 0;
        $coluna = $tipoUsuario === 'aluno' ? 'presenca_aluno' : 'presenca_monitor';
        $outroUsuarioId = $tipoUsuario === 'aluno'
            ? (int) $agendamento['monitor_id']
            : (int) $agendamento['aluno_id'];

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT {$coluna} FROM agendamentos WHERE id = ? FOR UPDATE");
        $stmt->execute([$agendamentoId]);
        if ($stmt->fetchColumn() !== null) {
            $pdo->rollBack();
            responderMonitorias(409, ['erro' => 'Sua presença já foi registrada.']);
        }
        $stmt = $pdo->prepare("UPDATE agendamentos SET {$coluna} = ? WHERE id = ?");
        $stmt->execute([$valorPresenca, $agendamentoId]);
        notificarParticipante(
            $pdo,
            $outroUsuarioId,
            $agendamentoId,
            'Presença registrada',
            ($tipoUsuario === 'aluno' ? $agendamento['aluno_nome'] : $agendamento['monitor_nome'])
                . ' registrou presença na monitoria "' . $agendamento['assunto'] . '".'
        );
        $pdo->commit();
        responderMonitorias(200, ['presenca' => $valorPresenca]);
    }

    if ($acao === 'avaliar') {
        if ($tipoUsuario !== 'aluno') {
            responderMonitorias(403, ['erro' => 'Somente o aluno pode avaliar a monitoria.']);
        }
        if (
            (int) $agendamento['presenca_aluno'] !== 1
            || (int) $agendamento['presenca_monitor'] !== 1
            || strtotime($agendamento['agendado_para']) + (int) $agendamento['duracao_minutos'] * 60 > time()
        ) {
            responderMonitorias(422, ['erro' => 'A avaliação fica disponível após ambos confirmarem presença.']);
        }
        $nota = filter_var($entrada['nota'] ?? null, FILTER_VALIDATE_INT);
        $comentario = $entrada['comentario'] ?? '';
        if (!in_array($nota, [1, 2, 3, 4, 5], true) || !is_string($comentario) || mb_strlen(trim($comentario), 'UTF-8') > 1000) {
            responderMonitorias(422, ['erro' => 'Informe uma nota de 1 a 5 e um comentário de até 1.000 caracteres.']);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO avaliacoes_monitorias (agendamento_id, aluno_id, monitor_id, nota, comentario)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $agendamentoId,
                $usuarioId,
                (int) $agendamento['monitor_id'],
                $nota,
                trim($comentario),
            ]);
            notificarParticipante(
                $pdo,
                (int) $agendamento['monitor_id'],
                $agendamentoId,
                'Nova avaliação recebida',
                'Você recebeu uma avaliação da monitoria "' . $agendamento['assunto'] . '".'
            );
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                responderMonitorias(409, ['erro' => 'Esta monitoria já foi avaliada.']);
            }
            throw $e;
        }
        responderMonitorias(201, ['avaliacao' => ['nota' => $nota, 'comentario' => trim($comentario)]]);
    }

    if ($acao === 'registrar_atendimento') {
        if ($tipoUsuario !== 'monitor' || (int) $agendamento['monitor_id'] !== $usuarioId) {
            responderMonitorias(403, ['erro' => 'Somente o monitor responsável pode registrar o atendimento.']);
        }
        if (
            strtotime($agendamento['agendado_para']) + (int) $agendamento['duracao_minutos'] * 60 > time()
            || (int) $agendamento['presenca_monitor'] !== 1
        ) {
            responderMonitorias(422, ['erro' => 'Registre o atendimento após confirmar sua presença.']);
        }

        $assuntoEstudado = $entrada['assunto_estudado'] ?? null;
        $observacoes = $entrada['observacoes'] ?? '';
        if (
            !is_string($assuntoEstudado)
            || trim($assuntoEstudado) === ''
            || mb_strlen(trim($assuntoEstudado), 'UTF-8') > 180
            || !is_string($observacoes)
            || mb_strlen(trim($observacoes), 'UTF-8') > 2000
        ) {
            responderMonitorias(422, ['erro' => 'Informe o conteúdo estudado e observações de até 2.000 caracteres.']);
        }

        $assuntoEstudado = trim($assuntoEstudado);
        $observacoes = trim($observacoes);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO registros_monitorias
                (agendamento_id, monitor_id, assunto_estudado, observacoes)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                assunto_estudado = VALUES(assunto_estudado),
                observacoes = VALUES(observacoes)'
        );
        $stmt->execute([$agendamentoId, $usuarioId, $assuntoEstudado, $observacoes]);
        notificarParticipante(
            $pdo,
            (int) $agendamento['aluno_id'],
            $agendamentoId,
            'Registro da monitoria atualizado',
            'O monitor registrou o conteúdo estudado em "' . $agendamento['assunto'] . '".'
        );
        $pdo->commit();
        responderMonitorias(200, [
            'registro' => [
                'assunto_estudado' => $assuntoEstudado,
                'observacoes' => $observacoes,
            ],
        ]);
    }

    if ($acao === 'material') {
        $arquivoArmazenado = null;
        $url = $entrada['url'] ?? '';
        if (!is_string($url)) {
            responderMonitorias(422, ['erro' => 'Informe um link HTTPS válido.']);
        }
        $url = trim($url);
        if ($url !== '') {
            if (
                mb_strlen($url, 'UTF-8') > 2048
                || !filter_var($url, FILTER_VALIDATE_URL)
                || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https'
                || !parse_url($url, PHP_URL_HOST)
            ) {
                responderMonitorias(422, ['erro' => 'Informe um link HTTPS válido.']);
            }
            $nome = $entrada['nome'] ?? '';
            if (!is_string($nome) || trim($nome) === '' || mb_strlen(trim($nome), 'UTF-8') > 255) {
                responderMonitorias(422, ['erro' => 'Informe um nome de até 255 caracteres para o link.']);
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO materiais_agendamentos (agendamento_id, remetente_id, nome_original, url)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$agendamentoId, $usuarioId, trim($nome), $url]);
            $materialId = (int) $pdo->lastInsertId();
        } else {
            $arquivo = $_FILES['arquivo'] ?? null;
            if (!is_array($arquivo) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                responderMonitorias(422, ['erro' => 'Selecione um arquivo válido de até 10 MB.']);
            }
            $tamanho = $arquivo['size'] ?? 0;
            $tmp = $arquivo['tmp_name'] ?? '';
            $nome = $arquivo['name'] ?? '';
            if (!is_int($tamanho) || $tamanho < 1 || $tamanho > 10 * 1024 * 1024 || !is_uploaded_file($tmp) || !is_string($nome)) {
                responderMonitorias(422, ['erro' => 'O arquivo deve ter até 10 MB.']);
            }

            $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
            $mimeDetectado = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            $tiposPermitidos = [
                'pdf' => ['application/pdf'],
                'png' => ['image/png'],
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'webp' => ['image/webp'],
                'txt' => ['text/plain'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
                'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
                'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
            ];
            if (!isset($tiposPermitidos[$extensao]) || !in_array($mimeDetectado, $tiposPermitidos[$extensao], true)) {
                responderMonitorias(422, ['erro' => 'Formato não permitido. Use PDF, imagens, TXT, DOCX, XLSX ou PPTX.']);
            }

            $diretorio = rtrim(getenv('MATERIAL_STORAGE_DIR') ?: dirname(__DIR__, 2) . '/athenas_uploads', DIRECTORY_SEPARATOR);
            if (!is_dir($diretorio)) {
                if (!is_dir(dirname($diretorio)) || !is_writable(dirname($diretorio))) {
                    throw new RuntimeException('Não foi possível preparar o armazenamento de materiais.');
                }
                set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
                    throw new ErrorException($message, 0, $severity, $file, $line);
                });
                try {
                    $diretorioCriado = mkdir($diretorio, 0700, true);
                } finally {
                    restore_error_handler();
                }
                if (!$diretorioCriado && !is_dir($diretorio)) {
                    throw new RuntimeException('Não foi possível preparar o armazenamento de materiais.');
                }
            }
            if (!is_writable($diretorio)) {
                throw new RuntimeException('O armazenamento de materiais não está disponível para escrita.');
            }
            $storageKey = bin2hex(random_bytes(32));
            $caminho = $diretorio . DIRECTORY_SEPARATOR . $storageKey;
            set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
                throw new ErrorException($message, 0, $severity, $file, $line);
            });
            try {
                $movido = move_uploaded_file($tmp, $caminho);
            } finally {
                restore_error_handler();
            }
            if (!$movido) {
                throw new RuntimeException('Não foi possível armazenar o material enviado.');
            }
            $arquivoArmazenado = $caminho;
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO materiais_agendamentos
                        (agendamento_id, remetente_id, nome_original, storage_key, mime_type, tamanho_bytes)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $agendamentoId,
                    $usuarioId,
                    mb_substr(basename($nome), 0, 255, 'UTF-8'),
                    $storageKey,
                    $mimeDetectado,
                    $tamanho,
                ]);
                $materialId = (int) $pdo->lastInsertId();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if (is_file($caminho)) {
                    unlink($caminho);
                }
                throw $e;
            }
        }

        $outroUsuarioId = $usuarioId === (int) $agendamento['aluno_id']
            ? (int) $agendamento['monitor_id']
            : (int) $agendamento['aluno_id'];
        notificarParticipante(
            $pdo,
            $outroUsuarioId,
            $agendamentoId,
            'Novo material de monitoria',
            'Um participante compartilhou "' . mb_substr($nome, 0, 200, 'UTF-8') . '".'
        );
        $pdo->commit();
        responderMonitorias(201, ['material_id' => $materialId]);
    }

    responderMonitorias(400, ['erro' => 'Ação inválida.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($arquivoArmazenado) && $arquivoArmazenado !== null && is_file($arquivoArmazenado)) {
        unlink($arquivoArmazenado);
    }
    error_log($e->getMessage());
    responderMonitorias(500, ['erro' => 'Não foi possível concluir a solicitação. Tente novamente.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($arquivoArmazenado) && $arquivoArmazenado !== null && is_file($arquivoArmazenado)) {
        unlink($arquivoArmazenado);
    }
    error_log($e->getMessage());
    responderMonitorias(500, ['erro' => 'Não foi possível concluir a solicitação. Tente novamente.']);
}
