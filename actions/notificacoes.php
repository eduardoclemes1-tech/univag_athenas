<?php
require_once __DIR__ . '/../includes/auth.php';
exigirUsuarioAutenticado();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderNotificacoes(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $tokenSessao = $_SESSION['csrf_token'] ?? '';
    if ($tokenSessao === '' || !hash_equals($tokenSessao, $token)) {
        responderNotificacoes(403, ['erro' => 'A sessão expirou. Atualize a página e tente novamente.']);
    }

    $entrada = json_decode(file_get_contents('php://input'), true);
    if (!is_array($entrada) || !in_array($entrada['acao'] ?? '', ['marcar_lida', 'marcar_lidas'], true)) {
        responderNotificacoes(400, ['erro' => 'Ação de notificação inválida.']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, POST');
    responderNotificacoes(405, ['erro' => 'Método não permitido.']);
}

require_once __DIR__ . '/../config/conexao.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT n.id, n.tipo, n.titulo, n.mensagem, n.agendamento_id, n.mensagem_id,
                    n.lida_em, n.criada_em, a.conversa_id, a.agendado_para, a.status, a.link_zoom
             FROM notificacoes n
             LEFT JOIN agendamentos a ON a.id = n.agendamento_id
             WHERE n.usuario_id = ?
             ORDER BY n.id DESC
             LIMIT 30'
        );
        $stmt->execute([$usuarioId]);
        $notificacoes = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida_em IS NULL'
        );
        $stmt->execute([$usuarioId]);
        $naoLidas = (int) $stmt->fetchColumn();

        responderNotificacoes(200, [
            'notificacoes' => $notificacoes,
            'nao_lidas' => $naoLidas,
        ]);
    }

    if ($entrada['acao'] === 'marcar_lida') {
        $notificacaoId = filter_var($entrada['notificacao_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$notificacaoId) {
            responderNotificacoes(400, ['erro' => 'Notificação inválida.']);
        }
        $stmt = $pdo->prepare(
            'UPDATE notificacoes SET lida_em = CURRENT_TIMESTAMP
             WHERE id = ? AND usuario_id = ? AND lida_em IS NULL'
        );
        $stmt->execute([$notificacaoId, $usuarioId]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE notificacoes SET lida_em = CURRENT_TIMESTAMP
             WHERE usuario_id = ? AND lida_em IS NULL'
        );
        $stmt->execute([$usuarioId]);
    }
    responderNotificacoes(200, ['marcadas' => $stmt->rowCount()]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    responderNotificacoes(500, ['erro' => 'Não foi possível carregar as notificações. Tente novamente.']);
}
