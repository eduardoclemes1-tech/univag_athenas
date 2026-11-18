<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado();
require_once __DIR__ . '/../../config/conexao.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'aluno';
$ehMonitor = ($tipoUsuario === 'monitor');
$tituloPagina = "Horas Complementares - UNIVAG ATHENAS";

$metaHoras = 60;
$metaMinutos = $metaHoras * 60;
$minutosValidados = 0;
$atividades = [];

try {
    $colunaUsuario = $ehMonitor ? 'a.monitor_id' : 'a.aluno_id';
    $stmt = $pdo->prepare(
        "SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, a.status,
                a.presenca_aluno, a.presenca_monitor,
                aluno.nome AS aluno_nome, monitor.nome AS monitor_nome,
                r.assunto_estudado
         FROM agendamentos a
         JOIN usuarios aluno ON aluno.id = a.aluno_id
         JOIN usuarios monitor ON monitor.id = a.monitor_id
         LEFT JOIN registros_monitorias r ON r.agendamento_id = a.id
         WHERE {$colunaUsuario} = ? AND a.status = 'confirmado'
         ORDER BY a.agendado_para DESC
         LIMIT 100"
    );
    $stmt->execute([$usuarioId]);
    $atividades = $stmt->fetchAll();

    foreach ($atividades as $ativ) {
        if ((int) $ativ['presenca_aluno'] === 1 && (int) $ativ['presenca_monitor'] === 1) {
            $minutosValidados += (int) $ativ['duracao_minutos'];
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$horasInt = intdiv($minutosValidados, 60);
$minutosResto = $minutosValidados % 60;
$labelTotalHoras = $horasInt . 'h' . ($minutosResto > 0 ? ' ' . $minutosResto . 'min' : '');
$percentualMeta = min(100, (int) round(($minutosValidados / $metaMinutos) * 100));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($ehMonitor): ?>
        <link rel="stylesheet" href="../../assets/css/monitorTheme.css?v=5">
    <?php else: ?>
        <link rel="stylesheet" href="../../assets/css/aluno.css?v=3">
    <?php endif; ?>
    <link rel="stylesheet" href="../../assets/css/horasComplementares.css?v=3">
</head>
<body class="<?php echo $ehMonitor ? 'monitor-theme' : 'aluno-theme'; ?>">

    <?php include '../../includes/navbar.php'; ?>

    <main class="horas-main-container">
        <div class="horas-content-wrapper">

            <header class="horas-header-bloco">
                <h1>Horas complementares</h1>
                <p>
                    <?php echo $ehMonitor
                        ? 'Acompanhe as horas de monitorias ministradas e validadas no semestre.'
                        : 'Acompanhe suas horas de participação em monitorias validadas no semestre.'; ?>
                </p>
            </header>

            <section class="card-hero-progresso">
                <div class="hero-total-bloco">
                    <small>Total validado</small>
                    <div class="hero-valor-horas">
                        <strong><?php echo htmlspecialchars($labelTotalHoras, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span>de <?php echo $metaHoras; ?>h da meta semestral</span>
                    </div>
                </div>

                <div class="divisor-hero-vertical"></div>

                <div class="hero-barra-bloco">
                    <div class="barra-progresso-container">
                        <div class="barra-progresso-preenchimento" style="width: <?php echo $percentualMeta; ?>%;"></div>
                    </div>
                    <div class="meta-rotulo">
                        <span>Progresso da meta do semestre</span>
                        <strong><?php echo $percentualMeta; ?>%</strong>
                    </div>
                </div>
            </section>

            <section class="card-tabela-atividades">
                <div class="tabela-header">
                    <h2>Histórico de atividades</h2>
                    <a href="certificados.php" class="link-ver-todas">
                        <i class="fa-solid fa-award"></i> Ver meus certificados
                    </a>
                </div>

                <div class="tabela-responsive-wrapper">
                    <table class="tabela-atividades">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Monitoria / Conteúdo</th>
                                <th><?php echo $ehMonitor ? 'Aluno(a)' : 'Monitor(a)'; ?></th>
                                <th>Carga Horária</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($atividades): ?>
                                <?php foreach ($atividades as $ativ): ?>
                                    <?php
                                    $validado = ((int) $ativ['presenca_aluno'] === 1 && (int) $ativ['presenca_monitor'] === 1);
                                    $jaTerminou = (strtotime($ativ['agendado_para']) + ((int) $ativ['duracao_minutos'] * 60)) <= time();
                                    if ($validado) {
                                        $statusClasse = 'status-validado';
                                        $statusTexto = 'Validado';
                                    } elseif ($jaTerminou) {
                                        $statusClasse = 'status-validacao';
                                        $statusTexto = 'Aguardando presença';
                                    } else {
                                        $statusClasse = 'status-agendado';
                                        $statusTexto = 'Agendada';
                                    }
                                    $durMin = (int) $ativ['duracao_minutos'];
                                    $durLabel = $durMin >= 60
                                        ? intdiv($durMin, 60) . 'h' . ($durMin % 60 ? ' ' . ($durMin % 60) . 'min' : '')
                                        : $durMin . ' min';
                                    $tituloExibicao = $ativ['assunto_estudado'] ?: $ativ['assunto'];
                                    $participante = $ehMonitor ? $ativ['aluno_nome'] : $ativ['monitor_nome'];
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ativ['agendado_para'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($tituloExibicao, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($participante, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($durLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="badge-status <?php echo $statusClasse; ?>"><?php echo $statusTexto; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 32px;">
                                        Nenhuma monitoria confirmada encontrada até o momento. Quando você e o outro participante confirmarem presença na Agenda, as horas aparecerão aqui automaticamente!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

</body>
</html>
