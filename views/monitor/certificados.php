<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado();
require_once __DIR__ . '/../../config/conexao.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'aluno';
$ehMonitor = ($tipoUsuario === 'monitor');
$tituloPagina = "Meus Certificados - UNIVAG ATHENAS";

$disponiveis = [];
$emBreve = [];

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
         WHERE {$colunaUsuario} = ? AND a.status IN ('confirmado', 'solicitado')
         ORDER BY a.agendado_para DESC
         LIMIT 100"
    );
    $stmt->execute([$usuarioId]);
    foreach ($stmt->fetchAll() as $sessao) {
        if (
            $sessao['status'] === 'confirmado'
            && (int) $sessao['presenca_aluno'] === 1
            && (int) $sessao['presenca_monitor'] === 1
        ) {
            $disponiveis[] = $sessao;
        } else {
            $emBreve[] = $sessao;
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}
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
    <link rel="stylesheet" href="../../assets/css/certificados.css?v=3">
</head>
<body class="<?php echo $ehMonitor ? 'monitor-theme' : 'aluno-theme'; ?>">

    <?php include '../../includes/navbar.php'; ?>

    <main class="certificados-main-container">
        <div class="certificados-content-wrapper">

            <header class="certificados-header">
                <h1>Certificados</h1>
                <p>Emita e salve em PDF os comprovantes oficiais das suas monitorias concluídas e validadas.</p>
            </header>

            <section class="card-certificados-container">
                
                <div class="tabs-certificados">
                    <button type="button" class="btn-tab ativo" id="tabDisponiveis">
                        Disponíveis (<?php echo count($disponiveis); ?>)
                    </button>
                    <button type="button" class="btn-tab" id="tabEmBreve">
                        Em breve / Pendentes (<?php echo count($emBreve); ?>)
                    </button>
                </div>

                <!-- Painel de Certificados Disponíveis -->
                <div class="painel-certificados ativo" id="painelDisponiveis">
                    <?php if ($disponiveis): ?>
                        <div class="lista-certificados">
                            <?php foreach ($disponiveis as $cert): ?>
                                <?php
                                $tituloCert = $cert['assunto_estudado'] ?: $cert['assunto'];
                                $durMin = (int) $cert['duracao_minutos'];
                                $durLabel = $durMin >= 60
                                    ? intdiv($durMin, 60) . 'h' . ($durMin % 60 ? ' ' . ($durMin % 60) . 'min' : '')
                                    : $durMin . ' min';
                                ?>
                                <article class="item-certificado">
                                    <div class="certificado-icone-bloco">
                                        <i class="fa-solid fa-award"></i>
                                    </div>
                                    <div class="certificado-info">
                                        <h2><?php echo htmlspecialchars($tituloCert, ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <p>
                                            <?php echo $ehMonitor
                                                ? 'Aluno(a) atendido(a): ' . htmlspecialchars($cert['aluno_nome'], ENT_QUOTES, 'UTF-8')
                                                : 'Monitor(a): ' . htmlspecialchars($cert['monitor_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                        <small>Data: <?php echo date('d/m/Y', strtotime($cert['agendado_para'])); ?> • Carga horária: <?php echo htmlspecialchars($durLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                                    </div>
                                    <a
                                        href="emissaoCertificado.php?id=<?php echo (int) $cert['id']; ?>"
                                        target="_blank"
                                        class="btn-baixar-certificado"
                                    >
                                        <i class="fa-solid fa-file-pdf"></i> Emitir Certificado
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="estado-vazio-certificados">
                            <i class="fa-solid fa-award"></i>
                            <p>Nenhum certificado disponível ainda. Assim que você e o outro participante confirmarem presença após uma monitoria na Agenda, seu certificado ficará disponível aqui para emissão imediata!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Painel de Certificados Em Breve -->
                <div class="painel-certificados" id="painelEmBreve" style="display: none;">
                    <?php if ($emBreve): ?>
                        <div class="lista-certificados">
                            <?php foreach ($emBreve as $pend): ?>
                                <?php
                                $durMin = (int) $pend['duracao_minutos'];
                                $durLabel = $durMin >= 60
                                    ? intdiv($durMin, 60) . 'h' . ($durMin % 60 ? ' ' . ($durMin % 60) . 'min' : '')
                                    : $durMin . ' min';
                                ?>
                                <article class="item-certificado">
                                    <div class="certificado-icone-bloco" style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="fa-solid fa-clock"></i>
                                    </div>
                                    <div class="certificado-info">
                                        <h2><?php echo htmlspecialchars($pend['assunto'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <p>
                                            <?php echo $ehMonitor
                                                ? 'Aluno(a): ' . htmlspecialchars($pend['aluno_nome'], ENT_QUOTES, 'UTF-8')
                                                : 'Monitor(a): ' . htmlspecialchars($pend['monitor_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                        <small>
                                            Data: <?php echo date('d/m/Y \à\s H:i', strtotime($pend['agendado_para'])); ?>
                                            • <?php echo htmlspecialchars($durLabel, ENT_QUOTES, 'UTF-8'); ?>
                                            • <?php echo $pend['status'] === 'solicitado' ? 'Aguardando confirmação do monitor' : 'Aguardando realização e registro de presença'; ?>
                                        </small>
                                    </div>
                                    <a href="../agenda/index.php" class="btn-baixar-certificado">
                                        <i class="fa-regular fa-calendar"></i> Ver na Agenda
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="estado-vazio-certificados">
                            <i class="fa-solid fa-clock"></i>
                            <p>Nenhum certificado pendente no momento.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </section>

        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabDisponiveis    = document.getElementById('tabDisponiveis');
        const tabEmBreve        = document.getElementById('tabEmBreve');
        const painelDisponiveis = document.getElementById('painelDisponiveis');
        const painelEmBreve     = document.getElementById('painelEmBreve');

        tabDisponiveis.addEventListener('click', function () {
            tabEmBreve.classList.remove('ativo');
            this.classList.add('ativo');
            painelEmBreve.style.display = 'none';
            painelDisponiveis.style.display = 'block';
        });

        tabEmBreve.addEventListener('click', function () {
            tabDisponiveis.classList.remove('ativo');
            this.classList.add('ativo');
            painelDisponiveis.style.display = 'none';
            painelEmBreve.style.display = 'block';
        });
    });
    </script>

</body>
</html>
