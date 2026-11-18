<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('monitor');
require_once __DIR__ . '/../../config/conexao.php';

$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Monitor';
$primeiroNome = explode(' ', trim($nomeUsuario))[0] ?: 'Monitor';
$monitorId = (int) $_SESSION['usuario_id'];

$dadosMonitor = [
    'curso' => 'Graduação UNIVAG',
    'periodo' => '',
    'area_atuacao' => 'Apoio Acadêmico',
    'disciplina' => 'Monitoria Acadêmica',
    'modalidade' => 'Presencial e Online',
    'local_atendimento' => 'Ambiente Virtual / Campus UNIVAG',
];

$resumoSemana = [
    'atendimentos' => 0,
    'minutos_livres' => 0,
    'presencas_pendentes' => 0,
    'registros_pendentes' => 0,
    'avaliacao_media' => null,
];
$proximosAtendimentos = [];

try {
    $stmtPerfil = $pdo->prepare(
        'SELECT u.curso, u.periodo, pm.area_atuacao, pm.disciplina, pm.modalidade, pm.local_atendimento
         FROM usuarios u
         LEFT JOIN perfis_monitores pm ON pm.monitor_id = u.id
         WHERE u.id = ? LIMIT 1'
    );
    $stmtPerfil->execute([$monitorId]);
    if ($rowPerfil = $stmtPerfil->fetch()) {
        if (!empty($rowPerfil['curso'])) {
            $dadosMonitor['curso'] = $rowPerfil['curso'];
        }
        if (!empty($rowPerfil['periodo'])) {
            $dadosMonitor['periodo'] = $rowPerfil['periodo'];
        }
        if (!empty($rowPerfil['area_atuacao'])) {
            $dadosMonitor['area_atuacao'] = $rowPerfil['area_atuacao'];
        }
        if (!empty($rowPerfil['disciplina'])) {
            $dadosMonitor['disciplina'] = $rowPerfil['disciplina'];
        }
        if (!empty($rowPerfil['modalidade'])) {
            $dadosMonitor['modalidade'] = $rowPerfil['modalidade'];
        }
        if (!empty($rowPerfil['local_atendimento'])) {
            $dadosMonitor['local_atendimento'] = $rowPerfil['local_atendimento'];
        }
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos
         WHERE monitor_id = ? AND status = 'confirmado'
           AND agendado_para >= CURRENT_TIMESTAMP
           AND agendado_para < DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY)"
    );
    $stmt->execute([$monitorId]);
    $resumoSemana['atendimentos'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT dia_semana, disponivel, hora_inicio, hora_fim
         FROM disponibilidades_monitores WHERE monitor_id = ?'
    );
    $stmt->execute([$monitorId]);
    $disponibilidades = [];
    foreach ($stmt->fetchAll() as $disponibilidade) {
        $disponibilidades[(int) $disponibilidade['dia_semana']] = $disponibilidade;
    }
    $temDisponibilidade = (bool) $disponibilidades;

    $inicioSemana = new DateTimeImmutable('today');
    $fimSemana = $inicioSemana->modify('+7 days');
    $stmt = $pdo->prepare(
        'SELECT inicio, fim FROM bloqueios_disponibilidade
         WHERE monitor_id = ? AND inicio < ? AND fim > ?'
    );
    $stmt->execute([$monitorId, $fimSemana->format('Y-m-d H:i:s'), $inicioSemana->format('Y-m-d H:i:s')]);
    $bloqueios = $stmt->fetchAll();
    $stmt = $pdo->prepare(
        "SELECT agendado_para, duracao_minutos FROM agendamentos
         WHERE monitor_id = ? AND status = 'confirmado'
           AND agendado_para < ? AND DATE_ADD(agendado_para, INTERVAL duracao_minutos MINUTE) > ?"
    );
    $stmt->execute([$monitorId, $fimSemana->format('Y-m-d H:i:s'), $inicioSemana->format('Y-m-d H:i:s')]);
    $ocupados = $stmt->fetchAll();

    for ($diaOffset = 0; $diaOffset < 7; $diaOffset++) {
        $dia = $inicioSemana->modify('+' . $diaOffset . ' days');
        $horario = $disponibilidades[(int) $dia->format('N')] ?? null;
        if (!$horario || !(bool) $horario['disponivel']) {
            continue;
        }

        $minutoInicio = ((int) substr($horario['hora_inicio'], 0, 2) * 60) + (int) substr($horario['hora_inicio'], 3, 2);
        $minutoFim = ((int) substr($horario['hora_fim'], 0, 2) * 60) + (int) substr($horario['hora_fim'], 3, 2);
        if ($dia->format('Y-m-d') === date('Y-m-d')) {
            $agora = ((int) date('G') * 60) + (int) date('i');
            $minutoInicio = max($minutoInicio, $agora);
        }

        $intervalosOcupados = [];
        foreach (array_merge($bloqueios, $ocupados) as $periodo) {
            $inicioPeriodo = new DateTimeImmutable($periodo['inicio'] ?? $periodo['agendado_para']);
            if ($inicioPeriodo->format('Y-m-d') !== $dia->format('Y-m-d')) {
                continue;
            }
            $inicioMinuto = ((int) $inicioPeriodo->format('G') * 60) + (int) $inicioPeriodo->format('i');
            if (isset($periodo['fim'])) {
                $fimPeriodo = new DateTimeImmutable($periodo['fim']);
                $fimMinuto = ((int) $fimPeriodo->format('G') * 60) + (int) $fimPeriodo->format('i');
            } else {
                $fimMinuto = $inicioMinuto + (int) $periodo['duracao_minutos'];
            }
            $inicioMinuto = max($minutoInicio, $inicioMinuto);
            $fimMinuto = min($minutoFim, $fimMinuto);
            if ($fimMinuto > $inicioMinuto) {
                $intervalosOcupados[] = [$inicioMinuto, $fimMinuto];
            }
        }

        usort($intervalosOcupados, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $cursor = $minutoInicio;
        foreach ($intervalosOcupados as [$ocupadoInicio, $ocupadoFim]) {
            if ($ocupadoInicio > $cursor) {
                $resumoSemana['minutos_livres'] += $ocupadoInicio - $cursor;
            }
            $cursor = max($cursor, $ocupadoFim);
        }
        $resumoSemana['minutos_livres'] += max(0, $minutoFim - $cursor);
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos
         WHERE monitor_id = ? AND status = 'confirmado'
           AND presenca_monitor IS NULL
           AND DATE_ADD(agendado_para, INTERVAL duracao_minutos MINUTE) < CURRENT_TIMESTAMP"
    );
    $stmt->execute([$monitorId]);
    $resumoSemana['presencas_pendentes'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos a
         LEFT JOIN registros_monitorias r ON r.agendamento_id = a.id
         WHERE a.monitor_id = ? AND a.status = 'confirmado'
           AND a.presenca_monitor = 1
           AND DATE_ADD(a.agendado_para, INTERVAL a.duracao_minutos MINUTE) < CURRENT_TIMESTAMP
           AND r.id IS NULL"
    );
    $stmt->execute([$monitorId]);
    $resumoSemana['registros_pendentes'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT ROUND(AVG(nota), 1) FROM avaliacoes_monitorias WHERE monitor_id = ?');
    $stmt->execute([$monitorId]);
    $avaliacaoMedia = $stmt->fetchColumn();
    $resumoSemana['avaliacao_media'] = $avaliacaoMedia === null ? null : (float) $avaliacaoMedia;

    $stmt = $pdo->prepare(
        "SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, a.status, a.meeting_url,
                aluno.nome AS aluno_nome, aluno.curso AS aluno_curso
         FROM agendamentos a
         JOIN usuarios aluno ON aluno.id = a.aluno_id
         WHERE a.monitor_id = ? AND a.status IN ('confirmado', 'solicitado')
           AND a.agendado_para >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY)
         ORDER BY a.agendado_para ASC
         LIMIT 6"
    );
    $stmt->execute([$monitorId]);
    $proximosAtendimentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar o painel do monitor.');
}

$horasLivres = intdiv($resumoSemana['minutos_livres'], 60);
$minutosLivres = $resumoSemana['minutos_livres'] % 60;
$tempoLivreLabel = $temDisponibilidade
    ? $horasLivres . 'h' . ($minutosLivres ? ' ' . $minutosLivres . 'min' : '')
    : 'Definir horários';
$totalPendencias = $resumoSemana['presencas_pendentes'] + $resumoSemana['registros_pendentes'];
$subtituloCurso = implode(' · ', array_filter([$dadosMonitor['curso'], $dadosMonitor['periodo']])) ?: 'Monitoria Acadêmica UNIVAG';
$semestreAtual = date('Y') . '/' . ((int) date('n') <= 6 ? '1' : '2');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel | AVA Monitoria - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/monitorTheme.css?v=6">
    <link rel="stylesheet" href="../../assets/css/monitorInicio.css?v=7">
</head>
<body class="monitor-theme monitor-home-theme">
    <?php include '../../includes/navbar.php'; ?>

    <main class="ava-monitor-main">
        <div class="ava-monitor-container">

            <!-- Banner Superior no padrão AVA UNIVAG -->
            <header class="ava-welcome-banner">
                <div class="ava-banner-polygons" aria-hidden="true"></div>
                <h1>Olá, <?php echo htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8'); ?>! <span class="ava-wave" aria-hidden="true">👋</span></h1>
                <div class="ava-banner-chips">
                    <a href="../agenda/index.php" class="ava-banner-chip">
                        <i class="fa-regular fa-calendar-check"></i>
                        <span><strong><?php echo (int) $resumoSemana['atendimentos']; ?></strong> atendimento(s) na semana</span>
                    </a>
                    <a href="horasComplementares.php" class="ava-banner-chip">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Disponibilidade: <strong><?php echo htmlspecialchars($tempoLivreLabel, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    </a>
                </div>
            </header>

            <!-- Superfície Principal do Painel (Estilo AVA UNIVAG em Dark Mode Widescreen) -->
            <section class="ava-panel-surface" aria-label="Painel principal do monitor">

                <!-- Card Horizontal Principal da Disciplina de Monitoria (Estilo Painel AVA) -->
                <article class="ava-course-split-card">
                    <div class="ava-course-cover" aria-hidden="true">
                        <div class="ava-cover-triangles"></div>
                        <span class="ava-cover-watermark">UNIVAG</span>
                    </div>
                    <div class="ava-course-body">
                        <div class="ava-course-meta">
                            <span class="ava-course-category"><?php echo htmlspecialchars($subtituloCurso, ENT_QUOTES, 'UTF-8'); ?></span>
                            <h2><?php echo htmlspecialchars($dadosMonitor['disciplina'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <div class="ava-badges-row">
                                <span class="ava-pill ava-pill-green">
                                    <?php echo htmlspecialchars($dadosMonitor['modalidade'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span class="ava-pill ava-pill-blue">
                                    <?php echo (int) $resumoSemana['atendimentos']; ?> atendimento(s) na semana
                                </span>
                                <span class="ava-pill ava-pill-outline">
                                    Disponibilidade livre: <?php echo htmlspecialchars($tempoLivreLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if ($resumoSemana['avaliacao_media'] !== null): ?>
                                    <span class="ava-pill ava-pill-gold">
                                        ★ <?php echo number_format($resumoSemana['avaliacao_media'], 1, ',', '.'); ?> / 5
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="ava-course-footer">
                            <a href="perfilMonitor.php" class="ava-access-link">
                                Acessar disciplina e horários <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            <div class="ava-course-quick-Btns">
                                <button type="button" class="ava-btn-outline" data-abrir-chat>
                                    <i class="fa-regular fa-comments" aria-hidden="true"></i> Mensagens
                                </button>
                                <a href="../agenda/index.php" class="ava-btn-primary">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i> Abrir agenda
                                </a>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Grid Widescreen de 2 Colunas do Monitor: Atendimentos à Esquerda + Gestão & Módulos à Direita -->
                <div class="ava-monitor-hub-grid">

                    <!-- Coluna Principal: Atendimentos e Monitorias -->
                    <div class="ava-section-block">
                        <div class="ava-section-bar">
                            <div class="ava-section-title">
                                <i class="fa-regular fa-message" aria-hidden="true"></i>
                                <h3>Atendimentos e Monitorias (<?php echo htmlspecialchars($semestreAtual, ENT_QUOTES, 'UTF-8'); ?>)</h3>
                            </div>
                            <div class="ava-section-status">
                                <?php if ($totalPendencias === 0): ?>
                                    <span class="ava-status-done">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i> Tudo concluído
                                    </span>
                                <?php else: ?>
                                    <a href="../agenda/index.php" class="ava-status-pending">
                                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                        <?php echo $totalPendencias; ?> registro(s)/presença(s) pendente(s)
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($proximosAtendimentos): ?>
                            <div class="ava-discipline-stack">
                                <?php foreach ($proximosAtendimentos as $atendimento): ?>
                                    <?php
                                    $ts = strtotime($atendimento['agendado_para']);
                                    $confirmado = $atendimento['status'] === 'confirmado';
                                    $codigoSessao = 'MON' . str_pad((string) $atendimento['id'], 3, '0', STR_PAD_LEFT) . 'NA';
                                    $urlReuniaoSegura = '';
                                    if ($confirmado && !empty($atendimento['meeting_url'])) {
                                        if (strtolower((string) parse_url($atendimento['meeting_url'], PHP_URL_SCHEME)) === 'https') {
                                            $urlReuniaoSegura = $atendimento['meeting_url'];
                                        }
                                    }
                                    ?>
                                    <article class="ava-discipline-item">
                                        <div class="ava-discipline-top">
                                            <span class="ava-pill <?php echo $confirmado ? 'ava-pill-green' : 'ava-pill-amber'; ?>">
                                                <?php echo $confirmado ? 'Confirmada' : 'Solicitação recebida'; ?>
                                            </span>
                                            <span class="ava-discipline-date">
                                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                                <?php echo date('d/m/Y \à\s H:i', $ts); ?> · <?php echo (int) $atendimento['duracao_minutos']; ?> min
                                            </span>
                                        </div>
                                        <h4>
                                            <?php echo htmlspecialchars($atendimento['assunto'], ENT_QUOTES, 'UTF-8'); ?>
                                            - <?php echo htmlspecialchars($semestreAtual, ENT_QUOTES, 'UTF-8'); ?>
                                            - <?php echo htmlspecialchars($codigoSessao, ENT_QUOTES, 'UTF-8'); ?>
                                        </h4>
                                        <p class="ava-discipline-student">
                                            Discente: <strong><?php echo htmlspecialchars($atendimento['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if (!empty($atendimento['aluno_curso'])): ?>
                                                <span>(<?php echo htmlspecialchars($atendimento['aluno_curso'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                            <?php endif; ?>
                                        </p>
                                        <div class="ava-discipline-actions">
                                            <a href="../agenda/index.php" class="ava-access-link">
                                                Acessar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                            </a>
                                            <?php if ($urlReuniaoSegura): ?>
                                                <a href="<?php echo htmlspecialchars($urlReuniaoSegura, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="ava-access-link ava-meeting-direct">
                                                    Link da aula <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <article class="ava-discipline-item ava-discipline-empty">
                                <span class="ava-pill ava-pill-blue"><?php echo htmlspecialchars($semestreAtual, ENT_QUOTES, 'UTF-8'); ?></span>
                                <h4>Nenhum atendimento agendado para os próximos dias</h4>
                                <p class="ava-discipline-student">
                                    Mantenha seus horários de disponibilidade atualizados ou inicie um agendamento pela barra superior.
                                </p>
                                <div class="ava-discipline-actions">
                                    <a href="../agenda/index.php" class="ava-access-link">
                                        Consultar calendário <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endif; ?>
                    </div>

                    <!-- Coluna Lateral: Resumo Operacional & Módulos da Monitoria -->
                    <aside class="ava-monitor-side-col">
                        <div class="ava-section-block">
                            <div class="ava-section-bar">
                                <div class="ava-section-title">
                                    <i class="fa-solid fa-grip" aria-hidden="true"></i>
                                    <h3>Módulos &amp; Ações Rápidas</h3>
                                </div>
                            </div>

                            <div class="ava-tiles-grid">
                                <a href="../agenda/index.php" class="ava-module-tile">
                                    <span class="ava-tile-badge"><span class="ava-dot"></span> DIGITAL</span>
                                    <strong class="ava-tile-title">AGENDA &amp;<br>PRESENÇAS</strong>
                                    <span class="ava-tile-watermark" aria-hidden="true">1</span>
                                </a>

                                <button type="button" class="ava-module-tile" data-abrir-chat>
                                    <span class="ava-tile-badge"><span class="ava-dot"></span> DIGITAL</span>
                                    <strong class="ava-tile-title">SALA DE<br>MENSAGENS</strong>
                                    <span class="ava-tile-watermark" aria-hidden="true">2</span>
                                </button>

                                <a href="perfilMonitor.php" class="ava-module-tile">
                                    <span class="ava-tile-badge"><span class="ava-dot"></span> DIGITAL</span>
                                    <strong class="ava-tile-title">HORÁRIOS &amp;<br>DISCIPLINA</strong>
                                    <span class="ava-tile-watermark" aria-hidden="true">3</span>
                                </a>

                                <a href="horasComplementares.php" class="ava-module-tile">
                                    <span class="ava-tile-badge"><span class="ava-dot"></span> DIGITAL</span>
                                    <strong class="ava-tile-title">RELATÓRIO<br>DE HORAS</strong>
                                    <span class="ava-tile-watermark" aria-hidden="true">4</span>
                                </a>

                                <a href="certificados.php" class="ava-module-tile">
                                    <span class="ava-tile-badge"><span class="ava-dot"></span> DIGITAL</span>
                                    <strong class="ava-tile-title">EMISSÃO DE<br>CERTIFICADOS</strong>
                                    <span class="ava-tile-watermark" aria-hidden="true">5</span>
                                </a>

                                <a href="../configuracoes.php" class="ava-module-tile">
                                    <span class="ava-tile-badge"><span class="ava-dot"></span> DIGITAL</span>
                                    <strong class="ava-tile-title">PREFERÊNCIAS<br>DA CONTA</strong>
                                    <span class="ava-tile-watermark" aria-hidden="true">6</span>
                                </a>
                            </div>
                        </div>
                    </aside>

                </div>

            </section>
        </div>
    </main>

    <!-- Rodapé Institucional no padrão do AVA UNIVAG -->
    <footer class="ava-institutional-footer">
        <div class="ava-footer-links">
            <button type="button" class="ava-footer-support-btn" data-abrir-chat>
                <i class="fa-regular fa-life-ring" aria-hidden="true"></i> Central de Atendimento Athenas
            </button>
            <a href="horasComplementares.php">Resumo de carga horária</a>
            <a href="certificados.php">Validação de certificados</a>
        </div>
        <div class="ava-footer-brand">
            <small>Plataforma Acadêmica de Monitorias</small>
            <img src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">
        </div>
    </footer>
</body>
</html>
