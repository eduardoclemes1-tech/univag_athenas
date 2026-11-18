<?php
require_once __DIR__ . '/../includes/auth.php';
exigirUsuarioAutenticado();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/conexao.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'aluno';
$ehMonitor = ($tipoUsuario === 'monitor');
$termo = trim((string) ($_GET['q'] ?? ''));

function extrairIniciaisBusca(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome)) ?: ['U'];
    $ini = mb_substr($partes[0] ?? 'U', 0, 1, 'UTF-8')
        . (isset($partes[1]) ? mb_substr($partes[1], 0, 1, 'UTF-8') : '');
    return mb_strtoupper($ini ?: 'U', 'UTF-8');
}

$catalogoMaterias = [
    [
        'titulo' => 'Lógica de Programação',
        'categoria' => 'Programação',
        'disciplina' => 'Fundamentos de programação e Python',
        'icone' => 'fa-code',
    ],
    [
        'titulo' => 'Banco de Dados',
        'categoria' => 'Dados',
        'disciplina' => 'Modelagem relacional, DER e SQL',
        'icone' => 'fa-database',
    ],
    [
        'titulo' => 'Estruturas de Dados',
        'categoria' => 'Programação',
        'disciplina' => 'Listas, pilhas, filas, árvores e grafos',
        'icone' => 'fa-diagram-project',
    ],
    [
        'titulo' => 'Desenvolvimento Mobile',
        'categoria' => 'Desenvolvimento',
        'disciplina' => 'Interfaces móveis e consumo de APIs',
        'icone' => 'fa-mobile-screen-button',
    ],
    [
        'titulo' => 'Redes de Computadores',
        'categoria' => 'Infraestrutura',
        'disciplina' => 'Protocolos TCP/IP, roteamento e DNS',
        'icone' => 'fa-network-wired',
    ],
    [
        'titulo' => 'Desenvolvimento Web',
        'categoria' => 'Desenvolvimento',
        'disciplina' => 'HTML5, CSS3, JavaScript e interfaces',
        'icone' => 'fa-window-maximize',
    ],
];

try {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $termo) . '%';

    // 1. Busca de Alunos
    if ($termo === '') {
        $stmtAlunos = $pdo->prepare(
            "SELECT u.id, u.nome, u.email, u.curso, u.periodo, u.foto_mime,
                    (SELECT COUNT(*) FROM agendamentos a
                     WHERE a.aluno_id = u.id AND a.monitor_id = ? AND a.status = 'confirmado') AS total_sessoes
             FROM usuarios u
             WHERE u.tipo = 'aluno'
             ORDER BY total_sessoes DESC, u.nome ASC
             LIMIT 6"
        );
        $stmtAlunos->execute([$usuarioId]);
    } else {
        $stmtAlunos = $pdo->prepare(
            "SELECT u.id, u.nome, u.email, u.curso, u.periodo, u.foto_mime,
                    (SELECT COUNT(*) FROM agendamentos a
                     WHERE a.aluno_id = u.id AND a.monitor_id = ? AND a.status = 'confirmado') AS total_sessoes
             FROM usuarios u
             WHERE u.tipo = 'aluno'
               AND (u.nome LIKE ? OR u.email LIKE ? OR u.curso LIKE ? OR u.periodo LIKE ?)
             ORDER BY total_sessoes DESC, u.nome ASC
             LIMIT 8"
        );
        $stmtAlunos->execute([$usuarioId, $like, $like, $like, $like]);
    }

    $alunos = [];
    foreach ($stmtAlunos->fetchAll() as $row) {
        $alunos[] = [
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'email' => $row['email'],
            'curso' => implode(' · ', array_filter([$row['curso'], $row['periodo']])) ?: 'Estudante UNIVAG',
            'iniciais' => extrairIniciaisBusca($row['nome']),
            'tem_foto' => !empty($row['foto_mime']),
            'total_sessoes' => (int) $row['total_sessoes'],
        ];
    }

    // 2. Busca de Monitores / Professores Orientadores
    if ($termo === '') {
        $stmtMonitores = $pdo->query(
            "SELECT u.id, u.nome, u.email, u.curso, u.periodo, u.foto_mime,
                    pm.area_atuacao, pm.disciplina, pm.modalidade, pm.topicos,
                    (SELECT ROUND(AVG(av.nota), 1) FROM avaliacoes_monitorias av WHERE av.monitor_id = u.id) AS media_nota,
                    EXISTS(SELECT 1 FROM disponibilidades_monitores dm WHERE dm.monitor_id = u.id AND dm.disponivel = 1) AS disponivel
             FROM usuarios u
             LEFT JOIN perfis_monitores pm ON pm.monitor_id = u.id
             WHERE u.tipo = 'monitor'
             ORDER BY disponivel DESC, u.id ASC
             LIMIT 6"
        );
    } else {
        $stmtMonitores = $pdo->prepare(
            "SELECT u.id, u.nome, u.email, u.curso, u.periodo, u.foto_mime,
                    pm.area_atuacao, pm.disciplina, pm.modalidade, pm.topicos,
                    (SELECT ROUND(AVG(av.nota), 1) FROM avaliacoes_monitorias av WHERE av.monitor_id = u.id) AS media_nota,
                    EXISTS(SELECT 1 FROM disponibilidades_monitores dm WHERE dm.monitor_id = u.id AND dm.disponivel = 1) AS disponivel
             FROM usuarios u
             LEFT JOIN perfis_monitores pm ON pm.monitor_id = u.id
             WHERE u.tipo = 'monitor'
               AND (u.nome LIKE ? OR u.email LIKE ? OR u.curso LIKE ?
                    OR pm.area_atuacao LIKE ? OR pm.disciplina LIKE ? OR pm.topicos LIKE ?)
             ORDER BY disponivel DESC, u.nome ASC
             LIMIT 8"
        );
        $stmtMonitores->execute([$like, $like, $like, $like, $like, $like]);
    }

    $monitores = [];
    foreach ($stmtMonitores->fetchAll() as $row) {
        $monitores[] = [
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'disciplina' => $row['disciplina'] ?: 'Monitoria Acadêmica',
            'area' => $row['area_atuacao'] ?: 'Tecnologia',
            'modalidade' => $row['modalidade'] ?: 'Online e presencial',
            'curso' => implode(' · ', array_filter([$row['curso'], $row['periodo']])) ?: 'Monitor Acadêmico',
            'iniciais' => extrairIniciaisBusca($row['nome']),
            'tem_foto' => !empty($row['foto_mime']),
            'disponivel' => (bool) $row['disponivel'],
            'nota' => $row['media_nota'] !== null ? number_format((float) $row['media_nota'], 1, ',', '.') : '5,0',
        ];
    }

    // 3. Busca de Atendimentos / Sessões Agendadas do Usuário Logado
    $colunaParticipante = $ehMonitor ? 'a.monitor_id' : 'a.aluno_id';
    if ($termo === '') {
        $stmtAtend = $pdo->prepare(
            "SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, a.status,
                    a.presenca_aluno, a.presenca_monitor,
                    aluno.nome AS aluno_nome, monitor.nome AS monitor_nome
             FROM agendamentos a
             JOIN usuarios aluno ON aluno.id = a.aluno_id
             JOIN usuarios monitor ON monitor.id = a.monitor_id
             WHERE {$colunaParticipante} = ? AND a.status IN ('confirmado', 'solicitado')
             ORDER BY a.agendado_para DESC
             LIMIT 4"
        );
        $stmtAtend->execute([$usuarioId]);
    } else {
        $stmtAtend = $pdo->prepare(
            "SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, a.status,
                    a.presenca_aluno, a.presenca_monitor,
                    aluno.nome AS aluno_nome, monitor.nome AS monitor_nome
             FROM agendamentos a
             JOIN usuarios aluno ON aluno.id = a.aluno_id
             JOIN usuarios monitor ON monitor.id = a.monitor_id
             LEFT JOIN registros_monitorias r ON r.agendamento_id = a.id
             WHERE {$colunaParticipante} = ?
               AND (a.assunto LIKE ? OR r.assunto_estudado LIKE ? OR aluno.nome LIKE ? OR monitor.nome LIKE ?)
             ORDER BY a.agendado_para DESC
             LIMIT 6"
        );
        $stmtAtend->execute([$usuarioId, $like, $like, $like, $like]);
    }

    $atendimentos = [];
    foreach ($stmtAtend->fetchAll() as $ag) {
        $concluido = ((int) $ag['presenca_aluno'] === 1 && (int) $ag['presenca_monitor'] === 1);
        $atendimentos[] = [
            'id' => (int) $ag['id'],
            'assunto' => $ag['assunto'],
            'data' => date('d/m/Y H:i', strtotime($ag['agendado_para'])),
            'participante' => $ehMonitor ? $ag['aluno_nome'] : $ag['monitor_nome'],
            'status' => $concluido ? 'concluido' : $ag['status'],
            'concluido' => $concluido,
        ];
    }

    // 4. Filtro de Matérias
    $materiasFiltradas = [];
    $termoLower = mb_strtolower($termo, 'UTF-8');
    foreach ($catalogoMaterias as $mat) {
        $alvo = mb_strtolower($mat['titulo'] . ' ' . $mat['categoria'] . ' ' . $mat['disciplina'], 'UTF-8');
        if ($termo === '' || str_contains($alvo, $termoLower)) {
            $materiasFiltradas[] = $mat;
        }
    }

    // 5. Resumo Operacional Rápido para o Monitor
    $resumoMonitor = null;
    if ($ehMonitor) {
        $stmtPend = $pdo->prepare(
            "SELECT COUNT(*) FROM agendamentos
             WHERE monitor_id = ? AND status = 'confirmado'
               AND presenca_monitor IS NULL
               AND DATE_ADD(agendado_para, INTERVAL duracao_minutos MINUTE) < CURRENT_TIMESTAMP"
        );
        $stmtPend->execute([$usuarioId]);
        $pendencias = (int) $stmtPend->fetchColumn();

        $stmtSolic = $pdo->prepare(
            "SELECT COUNT(*) FROM agendamentos
             WHERE monitor_id = ? AND status = 'solicitado' AND agendado_para >= CURRENT_TIMESTAMP"
        );
        $stmtSolic->execute([$usuarioId]);
        $solicitacoes = (int) $stmtSolic->fetchColumn();

        $resumoMonitor = [
            'pendencias_presenca' => $pendencias,
            'solicitacoes_abertas' => $solicitacoes,
        ];
    }

    echo json_encode([
        'termo' => $termo,
        'tipo_usuario' => $tipoUsuario,
        'alunos' => $alunos,
        'monitores' => $monitores,
        'atendimentos' => $atendimentos,
        'materias' => $materiasFiltradas,
        'resumo_monitor' => $resumoMonitor,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível realizar a busca no momento.'], JSON_UNESCAPED_UNICODE);
}
