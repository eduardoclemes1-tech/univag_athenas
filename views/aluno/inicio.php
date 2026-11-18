<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');
require_once __DIR__ . '/../../config/conexao.php';

$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Aluno';
$primeiroNome = explode(' ', trim($nomeUsuario))[0] ?: 'Aluno';
$alunoId = (int) $_SESSION['usuario_id'];

$dadosAluno = [
    'curso' => 'Sistemas de Informação',
    'periodo' => 'Graduação Presencial',
];

$resumo = [
    'proximas_count' => 0,
    'minutos_validados' => 0,
    'saldo_pontos' => 0,
    'pendencias_presenca' => 0,
    'pendencias_avaliacao' => 0,
];
$proximasMonitorias = [];
$monitoresDestaque = [];

$mesesCurto = [
    1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR',
    5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
    9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ',
];

try {
    $stmtAluno = $pdo->prepare('SELECT curso, periodo FROM usuarios WHERE id = ? LIMIT 1');
    $stmtAluno->execute([$alunoId]);
    if ($rowAluno = $stmtAluno->fetch()) {
        if (!empty($rowAluno['curso'])) {
            $dadosAluno['curso'] = $rowAluno['curso'];
        }
        if (!empty($rowAluno['periodo'])) {
            $dadosAluno['periodo'] = $rowAluno['periodo'];
        }
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos
         WHERE aluno_id = ? AND status IN ('confirmado', 'solicitado')
           AND agendado_para >= CURRENT_TIMESTAMP"
    );
    $stmt->execute([$alunoId]);
    $resumo['proximas_count'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(duracao_minutos), 0) FROM agendamentos
         WHERE aluno_id = ? AND status = 'confirmado'
           AND presenca_aluno = 1 AND presenca_monitor = 1"
    );
    $stmt->execute([$alunoId]);
    $resumo['minutos_validados'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(pontos), 0) FROM pontos_transacoes WHERE usuario_id = ?');
    $stmt->execute([$alunoId]);
    $resumo['saldo_pontos'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos
         WHERE aluno_id = ? AND status = 'confirmado'
           AND presenca_aluno IS NULL
           AND DATE_ADD(agendado_para, INTERVAL duracao_minutos MINUTE) < CURRENT_TIMESTAMP"
    );
    $stmt->execute([$alunoId]);
    $resumo['pendencias_presenca'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos a
         LEFT JOIN avaliacoes_monitorias av ON av.agendamento_id = a.id
         WHERE a.aluno_id = ? AND a.status = 'confirmado'
           AND a.presenca_aluno = 1 AND a.presenca_monitor = 1
           AND DATE_ADD(a.agendado_para, INTERVAL a.duracao_minutos MINUTE) < CURRENT_TIMESTAMP
           AND av.id IS NULL"
    );
    $stmt->execute([$alunoId]);
    $resumo['pendencias_avaliacao'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT a.id, a.monitor_id, a.assunto, a.agendado_para, a.duracao_minutos, a.status, a.meeting_url,
                m.nome AS monitor_nome, m.foto_mime AS monitor_foto,
                pm.disciplina AS monitor_disciplina, pm.area_atuacao AS monitor_area,
                (SELECT COUNT(*) FROM materiais_agendamentos mat WHERE mat.agendamento_id = a.id) AS total_materiais
         FROM agendamentos a
         JOIN usuarios m ON m.id = a.monitor_id
         LEFT JOIN perfis_monitores pm ON pm.monitor_id = m.id
         WHERE a.aluno_id = ? AND a.status IN ('confirmado', 'solicitado')
           AND a.agendado_para >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY)
         ORDER BY a.agendado_para ASC
         LIMIT 5"
    );
    $stmt->execute([$alunoId]);
    $proximasMonitorias = $stmt->fetchAll();

    $stmtMon = $pdo->query(
        "SELECT u.id, u.nome, u.foto_mime,
                pm.disciplina, pm.area_atuacao,
                (SELECT ROUND(AVG(av.nota), 1) FROM avaliacoes_monitorias av WHERE av.monitor_id = u.id) AS media_nota
         FROM usuarios u
         LEFT JOIN perfis_monitores pm ON pm.monitor_id = u.id
         WHERE u.tipo = 'monitor'
           AND EXISTS (SELECT 1 FROM disponibilidades_monitores dm WHERE dm.monitor_id = u.id AND dm.disponivel = 1)
         ORDER BY u.id ASC
         LIMIT 3"
    );
    $monitoresDestaque = $stmtMon->fetchAll();

    $stmtMaestria = $pdo->prepare(
        "SELECT a.assunto, a.duracao_minutos, pm.disciplina, pm.area_atuacao
         FROM agendamentos a
         LEFT JOIN perfis_monitores pm ON pm.monitor_id = a.monitor_id
         WHERE a.aluno_id = ? AND a.status = 'confirmado'
           AND a.presenca_aluno = 1 AND a.presenca_monitor = 1"
    );
    $stmtMaestria->execute([$alunoId]);
    $sessoesConcluidasMaestria = $stmtMaestria->fetchAll();

    $stmtComprasMaestria = $pdo->prepare(
        "SELECT i.visual
         FROM loja_compras c
         JOIN loja_itens i ON i.id = c.item_id
         WHERE c.usuario_id = ? AND i.categoria = 'fundo' AND i.preco = 0"
    );
    $stmtComprasMaestria->execute([$alunoId]);
    $visuaisMaestriaDesbloqueados = array_flip($stmtComprasMaestria->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {
    error_log($e->getMessage());
    $sessoesConcluidasMaestria = [];
    $visuaisMaestriaDesbloqueados = [];
}

require_once __DIR__ . '/../../includes/gamificacao.php';
$trilhasMaestria = athenasTrilhasMaestriaBase();

foreach ($sessoesConcluidasMaestria as $sessaoM) {
    $textoM = mb_strtolower(
        ($sessaoM['assunto'] ?? '') . ' ' . ($sessaoM['disciplina'] ?? '') . ' ' . ($sessaoM['area_atuacao'] ?? ''),
        'UTF-8'
    );
    $atribuidoM = false;
    foreach ($trilhasMaestria as &$trilhaRef) {
        foreach ($trilhaRef['palavras'] as $palavraM) {
            if (str_contains($textoM, $palavraM)) {
                $trilhaRef['minutos'] += (int) $sessaoM['duracao_minutos'];
                $trilhaRef['sessoes'] += 1;
                $atribuidoM = true;
                break;
            }
        }
        if ($atribuidoM) {
            break;
        }
    }
    unset($trilhaRef);
    if (!$atribuidoM) {
        $trilhasMaestria['logica']['minutos'] += (int) $sessaoM['duracao_minutos'];
        $trilhasMaestria['logica']['sessoes'] += 1;
    }
}

$metaMinutosMaestria10 = 540; // 9h de monitoria concluída (Nv. 1 -> Nv. 10)
$totalReliquiasLiberadas = 0;
$trilhaDestaque = null;

foreach ($trilhasMaestria as $k => &$t) {
    $desbloqueado = isset($visuaisMaestriaDesbloqueados[$t['premio_fundo']]);
    $minutosEfetivos = $desbloqueado ? $metaMinutosMaestria10 : (int) $t['minutos'];
    $nivel = min(10, max(1, (int) floor($minutosEfetivos / 60) + 1));
    if ($minutosEfetivos >= $metaMinutosMaestria10) {
        $nivel = 10;
    }
    if ($nivel >= 10) {
        $patente = 'Maestria 10 · Lenda';
        $totalReliquiasLiberadas++;
    } elseif ($nivel >= 7) {
        $patente = 'Mestre Ouro';
    } elseif ($nivel >= 4) {
        $patente = 'Especialista Prata';
    } elseif ($nivel >= 2) {
        $patente = 'Aprendiz Bronze';
    } else {
        $patente = 'Iniciante';
    }

    $minutosFaltantes = max(0, $metaMinutosMaestria10 - $minutosEfetivos);
    $niveisFaltantes = max(0, 10 - $nivel);
    $sessoesFaltantesEstimadas = (int) ceil($minutosFaltantes / 60);
    $hFalta = intdiv($minutosFaltantes, 60);
    $mFalta = $minutosFaltantes % 60;
    $tempoFaltanteFmt = ($hFalta > 0 ? $hFalta . 'h' : '') . ($mFalta > 0 ? ($hFalta > 0 ? ' ' : '') . $mFalta . 'min' : '');
    if ($tempoFaltanteFmt === '') {
        $tempoFaltanteFmt = '0h';
    }

    $t['nivel'] = $nivel;
    $t['patente'] = $patente;
    $t['pct'] = min(100, (int) round(($minutosEfetivos / $metaMinutosMaestria10) * 100));
    $t['minutos_faltantes'] = $minutosFaltantes;
    $t['niveis_faltantes'] = $niveisFaltantes;
    $t['sessoes_faltantes'] = $sessoesFaltantesEstimadas;
    $t['tempo_faltante_fmt'] = $tempoFaltanteFmt;
    $t['desbloqueado'] = ($nivel >= 10 || $desbloqueado);
    $t['minutos_efetivos'] = $minutosEfetivos;

    if ($trilhaDestaque === null || $minutosEfetivos > $trilhaDestaque['minutos_efetivos']) {
        $trilhaDestaque = $t;
    }
}
unset($t);

$horasInt = intdiv($resumo['minutos_validados'], 60);
$minutosResto = $resumo['minutos_validados'] % 60;
$horasFormatadas = $horasInt . 'h' . ($minutosResto > 0 ? ' ' . $minutosResto . 'min' : '');
$percentualHoras = min(100, (int) round(($resumo['minutos_validados'] / (60 * 60)) * 100));
$totalPendencias = $resumo['pendencias_presenca'] + $resumo['pendencias_avaliacao'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel | Portal do Aluno - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/aluno.css?v=9">
</head>
<body class="aluno-theme aluno-home-page">
    <?php include '../../includes/navbar.php'; ?>

    <main class="aluno-main">
        <div class="aluno-layout">

            <!-- Banner Superior Institucional com Saudação e Indicadores Rápidos -->
            <header class="ava-aluno-banner">
                <div class="ava-aluno-polygons" aria-hidden="true"></div>
                <div class="ava-aluno-banner-title">
                    <h1>Olá, <?php echo htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8'); ?>! <span aria-hidden="true">👋</span></h1>
                </div>
                <div class="ava-aluno-banner-chips">
                    <a href="#vitrineMaestriasInicio" class="banner-chip banner-chip-trophy">
                        <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                        <span>
                            Maestria: <strong><?php echo htmlspecialchars($trilhaDestaque['titulo'] ?? 'Banco de Dados', ENT_QUOTES, 'UTF-8'); ?> (Nv. <?php echo (int) ($trilhaDestaque['nivel'] ?? 1); ?>/10)</strong>
                        </span>
                    </a>
                    <a href="../monitor/horasComplementares.php" class="banner-chip">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                        <span><strong><?php echo htmlspecialchars($horasFormatadas, ENT_QUOTES, 'UTF-8'); ?></strong> validadas</span>
                    </a>
                    <a href="loja.php" class="banner-chip banner-chip-gold">
                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                        <span><strong><?php echo (int) $resumo['saldo_pontos']; ?></strong> pontos Athenas</span>
                    </a>
                </div>
            </header>

            <!-- Container Principal do Aluno -->
            <section class="ava-aluno-surface" aria-label="Painel acadêmico do aluno">

                <?php if ($totalPendencias > 0): ?>
                    <div class="aluno-alerta-pendencia" role="status">
                        <span>
                            <i class="fa-solid fa-bell" aria-hidden="true"></i>
                            Você possui <strong><?php echo $totalPendencias; ?></strong>
                            <?php echo $totalPendencias === 1 ? 'monitoria concluída aguardando' : 'monitorias concluídas aguardando'; ?>
                            confirmação de presença ou avaliação para liberar horas e ganhar até <strong>+35 pontos</strong>.
                        </span>
                        <a href="../agenda/index.php">Confirmar na agenda <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                <?php endif; ?>

                <!-- Card Principal Dividido do Curso do Aluno (Estilo Portal AVA UNIVAG) -->
                <article class="ava-aluno-split-card">
                    <div class="ava-aluno-cover" aria-hidden="true">
                        <div class="ava-aluno-cover-pattern"></div>
                        <span class="ava-aluno-watermark">UNIVAG</span>
                    </div>
                    <div class="ava-aluno-course-body">
                        <div class="ava-aluno-course-meta">
                            <span class="ava-aluno-eyebrow">Graduação · <?php echo htmlspecialchars($dadosAluno['periodo'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <h2><?php echo htmlspecialchars($dadosAluno['curso'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <div class="ava-aluno-pills">
                                <span class="aluno-pill pill-green">Presencial &amp; Digital</span>
                                <span class="aluno-pill pill-blue">
                                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                    <?php echo (int) $resumo['proximas_count']; ?> monitoria(s) na agenda
                                </span>
                                <span class="aluno-pill pill-slate">
                                    <i class="fa-solid fa-award" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($horasFormatadas, ENT_QUOTES, 'UTF-8'); ?> complementares
                                </span>
                            </div>
                        </div>

                        <div class="ava-aluno-course-footer">
                            <a href="monitores.php" class="ava-aluno-access-link">
                                Encontrar monitor da minha área <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            <div class="ava-aluno-quick-actions">
                                <a href="materias.php" class="ava-aluno-btn-secondary">
                                    <i class="fa-solid fa-book-open" aria-hidden="true"></i> Matérias
                                </a>
                                <a href="../agenda/index.php" class="ava-aluno-btn-primary">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i> Minha agenda
                                </a>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Seção Exclusiva do Aluno: Grid de 2 Colunas (Agenda de Estudos + Jornada & Monitores Disponíveis) -->
                <div class="aluno-hub-grid">

                    <!-- Coluna Esquerda: Cronograma de Estudos do Aluno -->
                    <div class="aluno-hub-col-main">
                        <div class="aluno-hub-header">
                            <div>
                                <span class="aluno-hub-kicker">ROTEIRO DE ESTUDOS</span>
                                <h3>Próximos Encontros de Monitoria</h3>
                            </div>
                            <a href="../agenda/index.php" class="aluno-hub-link">
                                Ver calendário completo <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <?php if ($proximasMonitorias): ?>
                            <div class="aluno-study-timeline">
                                <?php foreach ($proximasMonitorias as $item): ?>
                                    <?php
                                    $ts = strtotime($item['agendado_para']);
                                    $diaNum = date('d', $ts);
                                    $mesLabel = $mesesCurto[(int) date('n', $ts)] ?? 'MÊS';
                                    $horaInicio = date('H:i', $ts);
                                    $duracaoMin = (int) $item['duracao_minutos'];
                                    $ehConfirmado = $item['status'] === 'confirmado';
                                    $disciplinaMonitor = $item['monitor_disciplina'] ?: ($item['monitor_area'] ?: 'Monitoria Acadêmica');
                                    $inicialMon = mb_strtoupper(mb_substr(trim($item['monitor_nome']), 0, 1, 'UTF-8'), 'UTF-8');

                                    $urlSegura = '';
                                    if ($ehConfirmado && !empty($item['meeting_url'])) {
                                        if (strtolower((string) parse_url($item['meeting_url'], PHP_URL_SCHEME)) === 'https') {
                                            $urlSegura = $item['meeting_url'];
                                        }
                                    }
                                    ?>
                                    <article class="study-session-card">
                                        <!-- Bloco de Data Visual estilo Ticket/Calendário -->
                                        <div class="study-date-badge <?php echo $ehConfirmado ? 'date-confirmed' : 'date-pending'; ?>">
                                            <span class="study-month"><?php echo htmlspecialchars($mesLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <strong class="study-day"><?php echo htmlspecialchars($diaNum, ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span class="study-time"><?php echo htmlspecialchars($horaInicio, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>

                                        <!-- Conteúdo da Sessão do Aluno -->
                                        <div class="study-session-content">
                                            <div class="study-session-top">
                                                <span class="study-subject-tag">
                                                    <i class="fa-solid fa-book-bookmark" aria-hidden="true"></i>
                                                    <?php echo htmlspecialchars($disciplinaMonitor, ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <div class="study-session-rewards">
                                                    <span class="reward-chip" title="Ao confirmar presença e avaliar você ganha pontos proporcionais e horas complementares">
                                                        <i class="fa-solid fa-star" aria-hidden="true"></i> +<?php echo max(10, (int) round($duracaoMin / 3)); ?> pts · +<?php echo $duracaoMin; ?> min
                                                    </span>
                                                    <span class="study-status-badge <?php echo $ehConfirmado ? 'status-ok' : 'status-wait'; ?>">
                                                        <?php echo $ehConfirmado ? 'Confirmada' : 'Solicitada'; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <h4 class="study-session-title"><?php echo htmlspecialchars($item['assunto'], ENT_QUOTES, 'UTF-8'); ?></h4>

                                            <!-- Identificação do Monitor + Botões Rápidos do Aluno -->
                                            <div class="study-session-footer">
                                                <div class="study-mentor-info">
                                                    <span class="study-mentor-avatar">
                                                        <?php if (!empty($item['monitor_foto'])): ?>
                                                            <img src="../../actions/fotoPerfil.php?id=<?php echo (int) $item['monitor_id']; ?>" alt="">
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($inicialMon, ENT_QUOTES, 'UTF-8'); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <div>
                                                        <small>Monitor(a) orientador(a)</small>
                                                        <strong><?php echo htmlspecialchars($item['monitor_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    </div>
                                                </div>

                                                <div class="study-session-actions">
                                                    <button
                                                        type="button"
                                                        class="study-btn-chat"
                                                        data-iniciar-conversa-monitor="<?php echo (int) $item['monitor_id']; ?>"
                                                        title="Tirar dúvida pelo chat"
                                                    >
                                                        <i class="fa-regular fa-comments" aria-hidden="true"></i> Chat
                                                    </button>
                                                    <?php if ($urlSegura): ?>
                                                        <a
                                                            href="<?php echo htmlspecialchars($urlSegura, ENT_QUOTES, 'UTF-8'); ?>"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="study-btn-join"
                                                        >
                                                            <i class="fa-solid fa-video" aria-hidden="true"></i> Entrar na sala
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="../agenda/index.php" class="study-btn-agenda">
                                                            Detalhes
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="study-empty-box">
                                <span class="study-empty-icon"><i class="fa-solid fa-compass" aria-hidden="true"></i></span>
                                <h4>Nenhuma sessão agendada por enquanto</h4>
                                <p>Escolha um monitor disponível ao lado ou no catálogo para tirar dúvidas e ganhar pontos.</p>
                                <a href="monitores.php" class="ava-aluno-btn-primary">Explorar monitores</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Coluna Direita: Exclusiva do Aluno (Progresso Acadêmico, Maestria & Monitores Disponíveis) -->
                    <aside class="aluno-hub-col-side">

                        <!-- Card de Progresso de Horas, Pontos & Maestria Atual (Integrado ao contexto leve do portal) -->
                        <article class="aluno-side-card side-card-progress" id="vitrineMaestriasInicio">
                            <div class="side-card-head">
                                <div>
                                    <span class="aluno-hub-kicker">SUA JORNADA</span>
                                    <h3>Horas, Pontos &amp; Maestria</h3>
                                </div>
                                <span class="side-card-badge"><i class="fa-solid fa-trophy"></i></span>
                            </div>

                            <div class="progress-stat-row">
                                <span>Horas complementares</span>
                                <strong><?php echo htmlspecialchars($horasFormatadas, ENT_QUOTES, 'UTF-8'); ?> / 60h</strong>
                            </div>
                            <div class="aluno-progress-track" aria-hidden="true">
                                <div class="aluno-progress-fill" style="width: <?php echo $percentualHoras; ?>%;"></div>
                            </div>

                            <?php if ($trilhaDestaque): ?>
                                <!-- Bloco Compacto e Contextual de Maestria Atual -->
                                <div class="side-maestria-widget">
                                    <div class="side-maestria-header">
                                        <span class="side-maestria-title">
                                            <i class="fa-solid <?php echo htmlspecialchars($trilhaDestaque['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                            <?php echo htmlspecialchars($trilhaDestaque['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <span class="side-maestria-lvl">Nv. <?php echo (int) $trilhaDestaque['nivel']; ?>/10</span>
                                    </div>

                                    <div class="aluno-progress-track side-maestria-bar" aria-hidden="true">
                                        <div class="aluno-progress-fill side-maestria-fill" style="width: <?php echo (int) $trilhaDestaque['pct']; ?>%;"></div>
                                    </div>

                                    <p class="side-maestria-hint">
                                        <?php if ($trilhaDestaque['desbloqueado']): ?>
                                            <i class="fa-solid fa-circle-check"></i>
                                            Prêmio Nv. 10 liberado: <strong><?php echo htmlspecialchars($trilhaDestaque['premio_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>!
                                        <?php else: ?>
                                            Faltam <strong><?php echo (int) $trilhaDestaque['niveis_faltantes']; ?> nv. (<?php echo htmlspecialchars($trilhaDestaque['tempo_faltante_fmt'], ENT_QUOTES, 'UTF-8'); ?>)</strong> para liberar o prêmio Nv. 10: <strong>"<?php echo htmlspecialchars($trilhaDestaque['premio_nome'], ENT_QUOTES, 'UTF-8'); ?>"</strong>.
                                        <?php endif; ?>
                                    </p>

                                    <div class="side-maestria-mini-list">
                                        <?php foreach ($trilhasMaestria as $trItem): ?>
                                            <span class="side-maestria-pill <?php echo $trItem['desbloqueado'] ? 'pill-done' : ''; ?>" title="<?php echo htmlspecialchars($trItem['titulo'] . ' · Prêmio Nv. 10: ' . $trItem['premio_nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fa-solid <?php echo htmlspecialchars($trItem['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                                Nv. <?php echo (int) $trItem['nivel']; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="progress-points-box">
                                <div>
                                    <small>Saldo para personalização</small>
                                    <strong><i class="fa-solid fa-star"></i> <?php echo (int) $resumo['saldo_pontos']; ?> pontos</strong>
                                </div>
                                <a href="loja.php">Lojinha &amp; Maestrias <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </article>

                        <!-- Card de Monitores Disponíveis para Agendamento Rápido -->
                        <article class="aluno-side-card">
                            <div class="side-card-head">
                                <div>
                                    <span class="aluno-hub-kicker">PLANTÃO DE DÚVIDAS</span>
                                    <h3>Monitores com Horários</h3>
                                </div>
                                <a href="monitores.php" class="aluno-hub-link">Ver todos</a>
                            </div>

                            <div class="side-mentors-list">
                                <?php foreach ($monitoresDestaque as $mon): ?>
                                    <?php
                                    $iniM = mb_strtoupper(mb_substr(trim($mon['nome']), 0, 1, 'UTF-8'), 'UTF-8');
                                    $notaM = $mon['media_nota'] !== null
                                        ? number_format((float) $mon['media_nota'], 1, ',', '.')
                                        : '5,0';
                                    ?>
                                    <div class="side-mentor-row">
                                        <a href="../monitor/perfilMonitor.php?id=<?php echo (int) $mon['id']; ?>" class="side-mentor-main">
                                            <span class="side-mentor-avatar">
                                                <?php if (!empty($mon['foto_mime'])): ?>
                                                    <img src="../../actions/fotoPerfil.php?id=<?php echo (int) $mon['id']; ?>" alt="">
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($iniM, ENT_QUOTES, 'UTF-8'); ?>
                                                <?php endif; ?>
                                            </span>
                                            <div class="side-mentor-info">
                                                <strong><?php echo htmlspecialchars($mon['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <small><?php echo htmlspecialchars($mon['disciplina'] ?: 'Monitoria', ENT_QUOTES, 'UTF-8'); ?> · ★ <?php echo $notaM; ?></small>
                                            </div>
                                        </a>
                                        <button
                                            type="button"
                                            class="side-mentor-btn"
                                            data-solicitar-monitoria-monitor="<?php echo (int) $mon['id']; ?>"
                                        >
                                            Agendar
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>

                    </aside>

                </div>

                <!-- Grade de Módulos Digitais do Aluno (Estilo Tiles Azuis do AVA UNIVAG) -->
                <div class="ava-aluno-section">
                    <div class="ava-aluno-section-bar">
                        <div class="ava-aluno-section-title">
                            <i class="fa-solid fa-grip" aria-hidden="true"></i>
                            <h3>Acesso Rápido aos Módulos</h3>
                        </div>
                    </div>

                    <div class="ava-aluno-tiles-grid">
                        <a href="monitores.php" class="ava-aluno-tile">
                            <span class="tile-badge"><span class="tile-dot"></span> DIGITAL</span>
                            <strong class="tile-title">CATÁLOGO DE<br>MONITORES</strong>
                            <span class="tile-watermark" aria-hidden="true">1</span>
                        </a>

                        <a href="materias.php" class="ava-aluno-tile">
                            <span class="tile-badge"><span class="tile-dot"></span> DIGITAL</span>
                            <strong class="tile-title">EXPLORAR<br>MATÉRIAS</strong>
                            <span class="tile-watermark" aria-hidden="true">2</span>
                        </a>

                        <a href="../agenda/index.php" class="ava-aluno-tile">
                            <span class="tile-badge"><span class="tile-dot"></span> DIGITAL</span>
                            <strong class="tile-title">AGENDA &amp;<br>MATERIAIS</strong>
                            <span class="tile-watermark" aria-hidden="true">3</span>
                        </a>

                        <a href="../monitor/horasComplementares.php" class="ava-aluno-tile">
                            <span class="tile-badge"><span class="tile-dot"></span> DIGITAL</span>
                            <strong class="tile-title">HORAS<br>VALIDADAS</strong>
                            <span class="tile-watermark" aria-hidden="true">4</span>
                        </a>

                        <a href="../monitor/certificados.php" class="ava-aluno-tile">
                            <span class="tile-badge"><span class="tile-dot"></span> DIGITAL</span>
                            <strong class="tile-title">EMISSÃO DE<br>CERTIFICADOS</strong>
                            <span class="tile-watermark" aria-hidden="true">5</span>
                        </a>

                        <a href="loja.php" class="ava-aluno-tile">
                            <span class="tile-badge"><span class="tile-dot"></span> DIGITAL</span>
                            <strong class="tile-title">LOJINHA DE<br>PONTOS</strong>
                            <span class="tile-watermark" aria-hidden="true">6</span>
                        </a>
                    </div>
                </div>

            </section>
        </div>
    </main>

    <!-- Rodapé Institucional UNIVAG -->
    <footer class="ava-aluno-footer">
        <div class="ava-aluno-footer-links">
            <button type="button" class="ava-aluno-support-btn" data-abrir-chat>
                <i class="fa-regular fa-comments" aria-hidden="true"></i> Conversar com Monitores
            </button>
            <a href="../monitor/horasComplementares.php">Progresso de horas complementares</a>
            <a href="../monitor/certificados.php">Meus certificados acadêmicos</a>
        </div>
        <div class="ava-aluno-footer-brand">
            <small>Plataforma Acadêmica de Monitorias</small>
            <img src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">
        </div>
    </footer>
</body>
</html>
