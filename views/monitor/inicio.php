<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('monitor');
require_once __DIR__ . '/../../config/conexao.php';

$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Monitor';
$primeiroNome = explode(' ', trim($nomeUsuario))[0] ?: 'Monitor';
$monitorId = (int) $_SESSION['usuario_id'];
$resumoSemana = [
    'atendimentos' => 0,
    'minutos_livres' => 0,
    'presencas_pendentes' => 0,
    'registros_pendentes' => 0,
    'avaliacao_media' => null,
];
$proximosAtendimentos = [];

try {
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

    $colunaPresenca = 'presenca_monitor';
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamentos
         WHERE monitor_id = ? AND status = 'confirmado'
           AND {$colunaPresenca} IS NULL
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
        "SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, aluno.nome AS aluno_nome
         FROM agendamentos a
         JOIN usuarios aluno ON aluno.id = a.aluno_id
         WHERE a.monitor_id = ? AND a.status = 'confirmado'
           AND a.agendado_para >= CURRENT_TIMESTAMP
           AND a.agendado_para < DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY)
         ORDER BY a.agendado_para LIMIT 5"
    );
    $stmt->execute([$monitorId]);
    $proximosAtendimentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar o resumo do monitor.');
}

$horasLivres = intdiv($resumoSemana['minutos_livres'], 60);
$minutosLivres = $resumoSemana['minutos_livres'] % 60;
$tempoLivreLabel = $temDisponibilidade
    ? $horasLivres . 'h' . ($minutosLivres ? ' ' . $minutosLivres . 'min' : '')
    : '—';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início do monitor - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/monitorTheme.css?v=5">
    <link rel="stylesheet" href="../../assets/css/monitorInicio.css?v=4">
</head>
<body class="monitor-theme monitor-home-theme">
    <?php include '../../includes/navbar.php'; ?>

    <main class="monitor-main">
        <div class="monitor-layout">
            <div class="monitor-banner" aria-hidden="true"></div>
            <div class="monitor-content">
                <section class="monitor-boas-vindas">
                    <div>
                        <span class="monitor-eyebrow">ÁREA DO MONITOR</span>
                        <h1>Olá, <?php echo htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8'); ?>!</h1>
                        <p>Seu espaço para organizar atendimentos e acompanhar sua atuação acadêmica.</p>
                        <div class="monitor-acoes">
                            <a class="monitor-botao monitor-botao-principal" href="../agenda/index.php">
                                <i class="fa-regular fa-calendar"></i> Ver minha agenda
                            </a>
                            <button class="monitor-botao monitor-botao-secundario" type="button" data-abrir-chat>
                                <i class="fa-solid fa-comments"></i> Abrir mensagens
                            </button>
                        </div>
                    </div>
                    <div class="monitor-hero-icone" aria-hidden="true">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                </section>

                <section class="monitor-operational-summary" aria-label="Resumo da sua semana">
                    <div class="summary-header">
                        <div>
                            <span class="monitor-eyebrow">ACOMPANHAMENTO</span>
                            <h2>Resumo da semana</h2>
                        </div>
                        <a href="../agenda/index.php">Ver agenda <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="summary-metrics">
                        <article class="summary-metric">
                            <span>Atendimentos próximos</span>
                            <strong><?php echo $resumoSemana['atendimentos']; ?></strong>
                            <small>nos próximos 7 dias</small>
                        </article>
                        <article class="summary-metric">
                            <span>Horários livres</span>
                            <strong><?php echo htmlspecialchars($tempoLivreLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small><?php echo $temDisponibilidade ? 'na próxima semana' : 'cadastre horários no perfil'; ?></small>
                        </article>
                        <article class="summary-metric">
                            <span>Tarefas pendentes</span>
                            <strong><?php echo $resumoSemana['presencas_pendentes'] + $resumoSemana['registros_pendentes']; ?></strong>
                            <small><?php echo $resumoSemana['presencas_pendentes']; ?> presenças · <?php echo $resumoSemana['registros_pendentes']; ?> registros</small>
                        </article>
                        <article class="summary-metric">
                            <span>Avaliação média</span>
                            <strong><?php echo $resumoSemana['avaliacao_media'] === null
                                ? '—'
                                : number_format($resumoSemana['avaliacao_media'], 1, ',', '.') . ' / 5'; ?></strong>
                            <small>avaliações dos alunos</small>
                        </article>
                    </div>
                </section>

                <section class="monitor-upcoming monitor-card" aria-labelledby="upcomingTitle">
                    <div class="upcoming-heading">
                        <div>
                            <span class="monitor-eyebrow">AGENDA</span>
                            <h2 id="upcomingTitle">Próximas monitorias</h2>
                        </div>
                        <a href="../agenda/index.php">Abrir calendário <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <?php if ($proximosAtendimentos): ?>
                        <ul class="upcoming-list">
                            <?php foreach ($proximosAtendimentos as $atendimento): ?>
                                <li>
                                    <time datetime="<?php echo htmlspecialchars(date('c', strtotime($atendimento['agendado_para'])), ENT_QUOTES, 'UTF-8'); ?>">
                                        <strong><?php echo date('d/m', strtotime($atendimento['agendado_para'])); ?></strong>
                                        <span><?php echo date('H:i', strtotime($atendimento['agendado_para'])); ?></span>
                                    </time>
                                    <div>
                                        <strong><?php echo htmlspecialchars($atendimento['assunto'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small><?php echo htmlspecialchars($atendimento['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo (int) $atendimento['duracao_minutos']; ?> min</small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="upcoming-empty">Você não tem monitorias agendadas para os próximos 7 dias.</p>
                    <?php endif; ?>
                </section>

                <section class="monitor-resumo" aria-label="Atalhos do monitor">
                    <a class="monitor-card resumo-card" href="../agenda/index.php">
                        <span class="resumo-icone icone-azul"><i class="fa-solid fa-calendar-days"></i></span>
                        <span class="resumo-conteudo">
                            <span class="resumo-label">Organização</span>
                            <strong>Agenda</strong>
                            <span class="resumo-link">Consultar calendário <i class="fa-solid fa-arrow-right"></i></span>
                        </span>
                    </a>
                    <a class="monitor-card resumo-card" href="perfilMonitor.php">
                        <span class="resumo-icone icone-cinza"><i class="fa-solid fa-id-card"></i></span>
                        <span class="resumo-conteudo">
                            <span class="resumo-label">Apresentação</span>
                            <strong>Perfil profissional</strong>
                            <span class="resumo-link">Ver meu perfil <i class="fa-solid fa-arrow-right"></i></span>
                        </span>
                    </a>
                    <button class="monitor-card resumo-card" type="button" data-abrir-chat>
                        <span class="resumo-icone icone-cinza"><i class="fa-solid fa-comments"></i></span>
                        <span class="resumo-conteudo">
                            <span class="resumo-label">Atendimentos</span>
                            <strong>Mensagens</strong>
                            <span class="resumo-link">Abrir conversas <i class="fa-solid fa-arrow-right"></i></span>
                        </span>
                    </button>
                </section>

                <section class="monitor-paineis">
                    <article class="monitor-card painel-atalhos">
                        <div class="painel-titulo">
                            <div>
                                <span class="monitor-eyebrow">ACESSO RÁPIDO</span>
                                <h2>O que você precisa fazer?</h2>
                            </div>
                        </div>
                        <div class="atalhos-lista">
                            <button class="atalho-item" type="button" data-abrir-chat>
                                <span class="atalho-icone"><i class="fa-solid fa-comments"></i></span>
                                <span><strong>Conversar com alunos</strong><small>Abra suas mensagens e continue uma conversa.</small></span>
                                <i class="fa-solid fa-arrow-right atalho-seta" aria-hidden="true"></i>
                            </button>
                            <a class="atalho-item" href="perfilMonitor.php">
                                <span class="atalho-icone"><i class="fa-solid fa-user-pen"></i></span>
                                <span><strong>Atualizar meu perfil</strong><small>Revise as informações que os alunos consultam.</small></span>
                                <i class="fa-solid fa-arrow-right atalho-seta" aria-hidden="true"></i>
                            </a>
                            <a class="atalho-item" href="../configuracoes.php">
                                <span class="atalho-icone"><i class="fa-solid fa-sliders"></i></span>
                                <span><strong>Configurar minha conta</strong><small>Gerencie sua senha e preferências de notificações.</small></span>
                                <i class="fa-solid fa-arrow-right atalho-seta" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>

                    <aside class="monitor-card painel-dica">
                        <span class="dica-icone"><i class="fa-solid fa-lightbulb"></i></span>
                        <span class="monitor-eyebrow">SEU PERFIL</span>
                        <h2>Uma boa apresentação ajuda os alunos a encontrar você.</h2>
                        <p>Mantenha suas disciplinas, áreas de atuação e informações de atendimento atualizadas.</p>
                        <a href="perfilMonitor.php">Revisar meu perfil <i class="fa-solid fa-arrow-right"></i></a>
                    </aside>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
