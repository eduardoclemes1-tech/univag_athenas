<?php
require_once __DIR__ . '/../includes/auth.php';
exigirUsuarioAutenticado();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderChat(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function participanteConversa(PDO $pdo, int $conversaId, int $usuarioId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, aluno_id, monitor_id
         FROM conversas
         WHERE id = ? AND (aluno_id = ? OR monitor_id = ?)'
    );
    $stmt->execute([$conversaId, $usuarioId, $usuarioId]);
    $conversa = $stmt->fetch();
    return $conversa ?: null;
}

function registrarNotificacao(
    PDO $pdo,
    int $usuarioId,
    string $tipo,
    string $titulo,
    string $mensagem,
    ?int $agendamentoId = null,
    ?int $mensagemId = null
): void {
    $colunaPreferencia = $tipo === 'mensagem' ? 'notificar_mensagens' : 'notificar_agendamentos';
    $stmt = $pdo->prepare("SELECT {$colunaPreferencia} FROM usuarios WHERE id = ?");
    $stmt->execute([$usuarioId]);
    if (!(bool) $stmt->fetchColumn()) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, agendamento_id, mensagem_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$usuarioId, $tipo, $titulo, $mensagem, $agendamentoId, $mensagemId]);
}

$usuarioId = (int) $_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $tokenSessao = $_SESSION['csrf_token'] ?? '';
    if ($tokenSessao === '' || !hash_equals($tokenSessao, $token)) {
        responderChat(403, ['erro' => 'A sessão expirou. Atualize a página e tente novamente.']);
    }

    $entrada = json_decode(file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responderChat(400, ['erro' => 'Não foi possível interpretar a solicitação.']);
    }
}

require_once __DIR__ . '/../config/conexao.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $acao = $_GET['acao'] ?? 'inicio';

        if ($acao === 'inicio') {
            $stmt = $pdo->prepare(
                'SELECT c.id, c.atualizado_em, u.id AS contato_id, u.nome AS contato_nome,
                        u.email AS contato_email,
                        (SELECT m.conteudo FROM mensagens m WHERE m.conversa_id = c.id ORDER BY m.id DESC LIMIT 1) AS ultima_mensagem,
                        (SELECT m.criada_em FROM mensagens m WHERE m.conversa_id = c.id ORDER BY m.id DESC LIMIT 1) AS ultima_mensagem_em,
                        (SELECT COUNT(*) FROM mensagens m WHERE m.conversa_id = c.id AND m.remetente_id <> ? AND m.lida_em IS NULL) AS nao_lidas
                 FROM conversas c
                 JOIN usuarios u ON u.id = IF(c.aluno_id = ?, c.monitor_id, c.aluno_id)
                 WHERE c.aluno_id = ? OR c.monitor_id = ?
                 ORDER BY c.atualizado_em DESC'
            );
            $stmt->execute([$usuarioId, $usuarioId, $usuarioId, $usuarioId]);
            $conversas = $stmt->fetchAll();

            if ($tipoUsuario === 'aluno') {
                $stmt = $pdo->prepare(
                    "SELECT u.id, u.nome, u.email
                     FROM usuarios u
                     WHERE u.tipo = 'monitor' AND u.id <> ?
                     ORDER BY u.nome ASC
                     LIMIT 100"
                );
            } else {
                $stmt = $pdo->prepare(
                    "SELECT u.id, u.nome, u.email
                     FROM usuarios u
                     WHERE u.tipo = 'aluno' AND u.id <> ?
                       AND EXISTS (
                           SELECT 1 FROM conversas c
                           WHERE c.monitor_id = ? AND c.aluno_id = u.id
                       )
                     ORDER BY u.nome ASC
                     LIMIT 100"
                );
            }
            $stmt->execute($tipoUsuario === 'aluno' ? [$usuarioId] : [$usuarioId, $usuarioId]);
            $contatos = $stmt->fetchAll();

            responderChat(200, [
                'conversas' => $conversas,
                'contatos' => $contatos,
                'usuario' => ['id' => $usuarioId, 'tipo' => $tipoUsuario],
            ]);
        }

        if ($acao === 'alunos') {
            if ($tipoUsuario !== 'monitor') {
                responderChat(403, ['erro' => 'Somente monitores podem consultar alunos para agendamento.']);
            }

            $stmt = $pdo->query(
                "SELECT id, nome, email
                 FROM usuarios
                 WHERE tipo = 'aluno'
                 ORDER BY nome ASC"
            );
            responderChat(200, ['alunos' => $stmt->fetchAll()]);
        }

        if ($acao === 'agenda') {
            if ($tipoUsuario === 'monitor') {
                $stmt = $pdo->prepare(
                    'SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos,
                            a.status, a.link_zoom, u.nome AS participante_nome
                     FROM agendamentos a
                     JOIN usuarios u ON u.id = a.aluno_id
                     WHERE a.monitor_id = ? AND a.status = ?
                     ORDER BY a.agendado_para'
                );
            } else {
                $stmt = $pdo->prepare(
                    'SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos,
                            a.status, a.link_zoom, u.nome AS participante_nome
                     FROM agendamentos a
                     JOIN usuarios u ON u.id = a.monitor_id
                     WHERE a.aluno_id = ? AND a.status = ?
                     ORDER BY a.agendado_para'
                );
            }
            $stmt->execute([$usuarioId, 'confirmado']);
            responderChat(200, ['agendamentos' => $stmt->fetchAll()]);
        }

        if ($acao === 'mensagens') {
            $conversaId = filter_var($_GET['conversa_id'] ?? null, FILTER_VALIDATE_INT);
            $desdeId = filter_var($_GET['desde_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$conversaId || $desdeId === false || $desdeId < 0) {
                responderChat(400, ['erro' => 'Conversa inválida.']);
            }

            $conversa = participanteConversa($pdo, $conversaId, $usuarioId);
            if (!$conversa) {
                responderChat(404, ['erro' => 'Conversa não encontrada.']);
            }

            $stmt = $pdo->prepare(
                'SELECT m.id, m.remetente_id, u.nome AS remetente_nome, m.conteudo, m.criada_em
                 FROM mensagens m
                 JOIN usuarios u ON u.id = m.remetente_id
                 WHERE m.conversa_id = ? AND m.id > ?
                 ORDER BY m.id ASC LIMIT 100'
            );
            $stmt->execute([$conversaId, $desdeId]);
            $mensagens = $stmt->fetchAll();

            $stmt = $pdo->prepare(
                'UPDATE mensagens SET lida_em = CURRENT_TIMESTAMP
                 WHERE conversa_id = ? AND remetente_id <> ? AND lida_em IS NULL'
            );
            $stmt->execute([$conversaId, $usuarioId]);

            $stmt = $pdo->prepare(
                'UPDATE notificacoes SET lida_em = CURRENT_TIMESTAMP
                 WHERE usuario_id = ? AND tipo = ? AND lida_em IS NULL AND mensagem_id IN (
                     SELECT id FROM mensagens WHERE conversa_id = ? AND remetente_id <> ?
                 )'
            );
            $stmt->execute([$usuarioId, 'mensagem', $conversaId, $usuarioId]);

            $stmt = $pdo->prepare(
                'SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, a.status, a.link_zoom
                 FROM agendamentos a
                 WHERE a.conversa_id = ?
                 ORDER BY a.agendado_para DESC
                 LIMIT 30'
            );
            $stmt->execute([$conversaId]);
            $agendamentos = $stmt->fetchAll();

            responderChat(200, [
                'mensagens' => $mensagens,
                'agendamentos' => $agendamentos,
            ]);
        }

        responderChat(400, ['erro' => 'Ação de chat inválida.']);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        responderChat(405, ['erro' => 'Método não permitido.']);
    }

    $acao = $entrada['acao'] ?? '';
    $pdo->beginTransaction();

    if ($acao === 'iniciar') {
        if ($tipoUsuario !== 'aluno') {
            $pdo->rollBack();
            responderChat(403, ['erro' => 'Somente alunos podem iniciar novas conversas.']);
        }
        $monitorId = filter_var($entrada['monitor_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$monitorId || $monitorId === $usuarioId) {
            $pdo->rollBack();
            responderChat(400, ['erro' => 'Selecione um monitor válido.']);
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'monitor'");
        $stmt->execute([$monitorId]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            responderChat(404, ['erro' => 'Monitor não encontrado.']);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO conversas (aluno_id, monitor_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $stmt->execute([$usuarioId, $monitorId]);
        $conversaId = (int) $pdo->lastInsertId();
        $pdo->commit();
        responderChat(200, ['conversa_id' => $conversaId]);
    }

    if ($acao === 'agendar_monitor') {
        if ($tipoUsuario !== 'monitor') {
            $pdo->rollBack();
            responderChat(403, ['erro' => 'Somente o monitor pode criar um agendamento.']);
        }

        $alunoId = filter_var($entrada['aluno_id'] ?? null, FILTER_VALIDATE_INT);
        $assuntoEntrada = $entrada['assunto'] ?? null;
        $dataEntrada = $entrada['agendado_para'] ?? null;
        $duracao = filter_var($entrada['duracao_minutos'] ?? null, FILTER_VALIDATE_INT);
        $linkZoomEntrada = $entrada['link_zoom'] ?? null;
        if (
            !$alunoId || $alunoId === $usuarioId
            || !is_string($assuntoEntrada)
            || !is_string($dataEntrada)
            || strlen($dataEntrada) !== 16
            || !in_array($duracao, [30, 45, 60, 90], true)
            || !is_string($linkZoomEntrada)
        ) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Preencha todos os dados do agendamento corretamente.']);
        }

        $assunto = trim($assuntoEntrada);
        $agendadoPara = trim($dataEntrada);
        $linkZoom = trim($linkZoomEntrada);
        $hostZoom = strtolower((string) parse_url($linkZoom, PHP_URL_HOST));
        $data = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $agendadoPara);
        $errosData = DateTimeImmutable::getLastErrors();
        if (
            $assunto === ''
            || mb_strlen($assunto, 'UTF-8') > 180
            || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $agendadoPara)
            || !$data
            || ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
            || $data <= new DateTimeImmutable()
            || $data > new DateTimeImmutable('+90 days')
            || $data->modify('+' . $duracao . ' minutes')->format('Y-m-d') !== $data->format('Y-m-d')
        ) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Informe um assunto e uma data futura, dentro dos próximos 90 dias.']);
        }
        if (
            $linkZoom === ''
            || mb_strlen($linkZoom, 'UTF-8') > 500
            || !filter_var($linkZoom, FILTER_VALIDATE_URL)
            || strtolower((string) parse_url($linkZoom, PHP_URL_SCHEME)) !== 'https'
            || !preg_match('/(^|\.)zoom\.us$/', $hostZoom)
        ) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Informe um link válido de reunião do Zoom.']);
        }

        $stmt = $pdo->prepare(
            'SELECT dia_semana, disponivel, hora_inicio, hora_fim
             FROM disponibilidades_monitores
             WHERE monitor_id = ?'
        );
        $stmt->execute([$usuarioId]);
        $disponibilidades = $stmt->fetchAll();
        if ($disponibilidades) {
            $diaAgendado = (int) $data->format('N');
            $inicioAgendado = $data->format('H:i');
            $fimAgendado = $data->modify('+' . $duracao . ' minutes')->format('H:i');
            $dentroDaDisponibilidade = false;
            foreach ($disponibilidades as $disponibilidade) {
                if (
                    (int) $disponibilidade['dia_semana'] === $diaAgendado
                    && (bool) $disponibilidade['disponivel']
                    && $inicioAgendado >= substr($disponibilidade['hora_inicio'], 0, 5)
                    && $fimAgendado <= substr($disponibilidade['hora_fim'], 0, 5)
                ) {
                    $dentroDaDisponibilidade = true;
                    break;
                }
            }
            if (!$dentroDaDisponibilidade) {
                $pdo->rollBack();
                responderChat(422, ['erro' => 'Escolha um horário dentro da disponibilidade informada no seu perfil.']);
            }
        }

        $inicio = $data->format('Y-m-d H:i:s');
        $fim = $data->modify('+' . $duracao . ' minutes')->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'aluno'");
        $stmt->execute([$alunoId]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            responderChat(404, ['erro' => 'Aluno não encontrado.']);
        }

        $idsBloqueados = [$usuarioId, $alunoId];
        sort($idsBloqueados);
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id IN (?, ?) ORDER BY id FOR UPDATE');
        $stmt->execute($idsBloqueados);
        $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT id FROM bloqueios_disponibilidade
             WHERE monitor_id = ? AND inicio < ? AND fim > ?
             LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([$usuarioId, $fim, $inicio]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            responderChat(409, ['erro' => 'O horário escolhido foi bloqueado pelo monitor. Selecione outro período.']);
        }

        $stmt = $pdo->prepare(
            "SELECT id
             FROM agendamentos
             WHERE status IN ('solicitado', 'confirmado')
               AND agendado_para < ?
               AND DATE_ADD(agendado_para, INTERVAL duracao_minutos MINUTE) > ?
               AND (monitor_id = ? OR aluno_id = ?)
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$fim, $inicio, $usuarioId, $alunoId]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            responderChat(409, ['erro' => 'Este horário conflita com outro atendimento do monitor ou do aluno.']);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO conversas (aluno_id, monitor_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $stmt->execute([$alunoId, $usuarioId]);
        $conversaId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO agendamentos
                (conversa_id, aluno_id, monitor_id, assunto, agendado_para, duracao_minutos, status, link_zoom)
             VALUES (?, ?, ?, ?, ?, ?, 'confirmado', ?)"
        );
        $stmt->execute([
            $conversaId, $alunoId, $usuarioId, $assunto, $inicio, $duracao, $linkZoom,
        ]);
        $agendamentoId = (int) $pdo->lastInsertId();

        $dataFormatada = $data->format('d/m/Y \à\s H:i');
        $conteudo = 'O monitor agendou uma monitoria sobre ' . $assunto . ' para '
            . $dataFormatada . ' (' . $duracao . ' min). Link do Zoom: ' . $linkZoom;
        $stmt = $pdo->prepare(
            'INSERT INTO mensagens (conversa_id, remetente_id, conteudo) VALUES (?, ?, ?)'
        );
        $stmt->execute([$conversaId, $usuarioId, $conteudo]);
        $mensagemId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('UPDATE conversas SET atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$conversaId]);
        registrarNotificacao(
            $pdo,
            $alunoId,
            'agendamento',
            'Nova monitoria agendada',
            'O monitor agendou "' . $assunto . '" para ' . $dataFormatada . '.',
            $agendamentoId
        );
        registrarNotificacao(
            $pdo,
            $alunoId,
            'mensagem',
            'Nova mensagem',
            'Monitor: ' . mb_substr($conteudo, 0, 400, 'UTF-8'),
            null,
            $mensagemId
        );

        $pdo->commit();
        responderChat(201, [
            'agendamento_id' => $agendamentoId,
            'conversa_id' => $conversaId,
        ]);
    }

    $conversaId = filter_var($entrada['conversa_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$conversaId) {
        $pdo->rollBack();
        responderChat(400, ['erro' => 'Conversa inválida.']);
    }

    $conversa = participanteConversa($pdo, $conversaId, $usuarioId);
    if (!$conversa) {
        $pdo->rollBack();
        responderChat(404, ['erro' => 'Conversa não encontrada.']);
    }

    $destinatarioId = $usuarioId === (int) $conversa['aluno_id']
        ? (int) $conversa['monitor_id']
        : (int) $conversa['aluno_id'];

    if ($acao === 'enviar') {
        $conteudoEntrada = $entrada['conteudo'] ?? null;
        if (!is_string($conteudoEntrada)) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Escreva uma mensagem válida.']);
        }
        $conteudo = trim($conteudoEntrada);
        if ($conteudo === '' || mb_strlen($conteudo, 'UTF-8') > 4000) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'A mensagem deve ter entre 1 e 4.000 caracteres.']);
        }

        $stmt = $pdo->prepare('INSERT INTO mensagens (conversa_id, remetente_id, conteudo) VALUES (?, ?, ?)');
        $stmt->execute([$conversaId, $usuarioId, $conteudo]);
        $mensagemId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('UPDATE conversas SET atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$conversaId]);

        $nomeRemetente = $_SESSION['usuario_nome'] ?? 'Seu contato';
        registrarNotificacao(
            $pdo,
            $destinatarioId,
            'mensagem',
            'Nova mensagem',
            $nomeRemetente . ': ' . mb_substr($conteudo, 0, 400, 'UTF-8'),
            null,
            $mensagemId
        );
        $pdo->commit();
        responderChat(201, ['mensagem_id' => $mensagemId]);
    }

    if ($acao === 'agendar') {
        if ($tipoUsuario !== 'aluno') {
            $pdo->rollBack();
            responderChat(403, ['erro' => 'Somente o aluno pode solicitar um agendamento.']);
        }

        $assuntoEntrada = $entrada['assunto'] ?? null;
        $dataEntrada = $entrada['agendado_para'] ?? null;
        if (!is_string($assuntoEntrada) || !is_string($dataEntrada) || strlen($dataEntrada) !== 16) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Informe um assunto e uma data válidos.']);
        }
        $assunto = trim($assuntoEntrada);
        $agendadoPara = trim($dataEntrada);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $agendadoPara)) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Informe uma data e horário válidos.']);
        }
        $data = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $agendadoPara);
        $errosData = DateTimeImmutable::getLastErrors();
        if (
            $assunto === ''
            || mb_strlen($assunto, 'UTF-8') > 180
            || !$data
            || ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
            || $data <= new DateTimeImmutable()
            || $data > new DateTimeImmutable('+90 days')
        ) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Informe um assunto e uma data futura, dentro dos próximos 90 dias.']);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO agendamentos (conversa_id, aluno_id, monitor_id, assunto, agendado_para)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $conversaId,
            $usuarioId,
            $destinatarioId,
            $assunto,
            $data->format('Y-m-d H:i:s'),
        ]);
        $agendamentoId = (int) $pdo->lastInsertId();

        $nomeAluno = $_SESSION['usuario_nome'] ?? 'Um aluno';
        registrarNotificacao(
            $pdo,
            $destinatarioId,
            'agendamento',
            'Nova solicitação de monitoria',
            $nomeAluno . ' solicitou uma monitoria sobre ' . $assunto . '.',
            $agendamentoId
        );
        $pdo->commit();
        responderChat(201, ['agendamento_id' => $agendamentoId]);
    }

    if ($acao === 'responder_agendamento') {
        if ($tipoUsuario !== 'monitor') {
            $pdo->rollBack();
            responderChat(403, ['erro' => 'Somente o monitor pode responder ao agendamento.']);
        }

        $agendamentoId = filter_var($entrada['agendamento_id'] ?? null, FILTER_VALIDATE_INT);
        $status = $entrada['status'] ?? '';
        $linkZoomEntrada = $entrada['link_zoom'] ?? '';
        if (!is_string($linkZoomEntrada)) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Informe um link Zoom válido.']);
        }
        $linkZoom = trim($linkZoomEntrada);
        $hostZoom = strtolower((string) parse_url($linkZoom, PHP_URL_HOST));
        if (!$agendamentoId || !in_array($status, ['confirmado', 'recusado'], true)) {
            $pdo->rollBack();
            responderChat(400, ['erro' => 'Resposta de agendamento inválida.']);
        }
        if (
            $status === 'confirmado'
            && ($linkZoom === '' || mb_strlen($linkZoom, 'UTF-8') > 500
                || !filter_var($linkZoom, FILTER_VALIDATE_URL)
                || strtolower((string) parse_url($linkZoom, PHP_URL_SCHEME)) !== 'https'
                || !preg_match('/(^|\.)zoom\.us$/', $hostZoom))
        ) {
            $pdo->rollBack();
            responderChat(422, ['erro' => 'Informe um link válido do Zoom para confirmar o agendamento.']);
        }

        $stmt = $pdo->prepare(
            "UPDATE agendamentos
             SET status = ?, link_zoom = ?
             WHERE id = ? AND conversa_id = ? AND monitor_id = ? AND status = 'solicitado'"
        );
        $stmt->execute([$status, $status === 'confirmado' ? $linkZoom : null, $agendamentoId, $conversaId, $usuarioId]);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            responderChat(409, ['erro' => 'Esse agendamento não está mais aguardando resposta.']);
        }

        $titulo = $status === 'confirmado' ? 'Monitoria confirmada' : 'Solicitação de monitoria recusada';
        $mensagem = $status === 'confirmado'
            ? 'Sua monitoria foi confirmada. Acesse o link do Zoom: ' . $linkZoom
            : 'O monitor não pôde aceitar este horário. Converse para combinar outra data.';
        registrarNotificacao(
            $pdo,
            $destinatarioId,
            'agendamento',
            $titulo,
            $mensagem,
            $agendamentoId
        );

        $conteudo = $status === 'confirmado'
            ? 'Agendamento confirmado. Link do Zoom: ' . $linkZoom
            : 'O agendamento solicitado foi recusado. Vamos combinar outro horário pelo chat.';
        $stmt = $pdo->prepare('INSERT INTO mensagens (conversa_id, remetente_id, conteudo) VALUES (?, ?, ?)');
        $stmt->execute([$conversaId, $usuarioId, $conteudo]);
        $mensagemId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('UPDATE conversas SET atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$conversaId]);
        registrarNotificacao(
            $pdo,
            $destinatarioId,
            'mensagem',
            'Atualização da monitoria',
            $conteudo,
            null,
            $mensagemId
        );

        $pdo->commit();
        responderChat(200, ['status' => $status]);
    }

    $pdo->rollBack();
    responderChat(400, ['erro' => 'Ação de chat inválida.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    responderChat(500, ['erro' => 'Não foi possível concluir a operação. Tente novamente.']);
}
