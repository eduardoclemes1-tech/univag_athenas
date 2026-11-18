<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado();
require_once __DIR__ . '/../../config/conexao.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'aluno';
$ehMonitor = ($tipoUsuario === 'monitor');
$agendamentoId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$agendamentoId || $agendamentoId <= 0) {
    http_response_code(400);
    exit('Certificado inválido.');
}

try {
    $stmt = $pdo->prepare(
        "SELECT a.id, a.assunto, a.agendado_para, a.duracao_minutos, a.status,
                a.presenca_aluno, a.presenca_monitor,
                aluno.id AS aluno_id, aluno.nome AS aluno_nome, aluno.curso AS aluno_curso,
                monitor.id AS monitor_id, monitor.nome AS monitor_nome, monitor.curso AS monitor_curso,
                pm.disciplina AS monitor_disciplina,
                r.assunto_estudado
         FROM agendamentos a
         JOIN usuarios aluno ON aluno.id = a.aluno_id
         JOIN usuarios monitor ON monitor.id = a.monitor_id
         LEFT JOIN perfis_monitores pm ON pm.monitor_id = monitor.id
         LEFT JOIN registros_monitorias r ON r.agendamento_id = a.id
         WHERE a.id = ? AND (a.aluno_id = ? OR a.monitor_id = ?)"
    );
    $stmt->execute([$agendamentoId, $usuarioId, $usuarioId]);
    $dados = $stmt->fetch();

    if (
        !$dados
        || $dados['status'] !== 'confirmado'
        || (int) $dados['presenca_aluno'] !== 1
        || (int) $dados['presenca_monitor'] !== 1
    ) {
        http_response_code(404);
        exit('Certificado não encontrado ou monitoria ainda pendente de validação de presença.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Erro ao gerar o certificado.');
}

$nomeTitular = $ehMonitor ? $dados['monitor_nome'] : $dados['aluno_nome'];
$papelTexto = $ehMonitor ? 'atuou como MONITOR(A) ACADÊMICO(A)' : 'participou como ALUNO(A) ATENDIDO(A)';
$outroParticipante = $ehMonitor
    ? 'em atendimento individual ao discente ' . $dados['aluno_nome']
    : 'sob orientação do(a) monitor(a) ' . $dados['monitor_nome'];
$conteudoEstudado = $dados['assunto_estudado'] ?: $dados['assunto'];
$disciplina = $dados['monitor_disciplina'] ?: 'Apoio Acadêmico UNIVAG';
$durMin = (int) $dados['duracao_minutos'];
$durLabel = $durMin >= 60
    ? intdiv($durMin, 60) . ' hora(s)' . ($durMin % 60 ? ' e ' . ($durMin % 60) . ' minuto(s)' : '')
    : $durMin . ' minutos';
$dataSessao = date('d/m/Y', strtotime($dados['agendado_para']));
$codigoVerificacao = 'ATH-' . strtoupper(substr(hash('sha256', $dados['id'] . '|' . $dados['agendado_para'] . '|' . $usuarioId), 0, 12));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado #<?php echo (int) $dados['id']; ?> - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #e2e8f0;
            color: #1e293b;
            font-family: 'Georgia', 'Times New Roman', serif;
            padding: 32px 16px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .barra-acoes {
            width: min(960px, 100%);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .btn-acao {
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e293b;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-imprimir {
            background: #075eaa;
            border-color: #075eaa;
            color: #fff;
        }
        .certificado-folha {
            width: min(960px, 100%);
            background: #ffffff;
            border: 12px double #084b82;
            border-radius: 6px;
            padding: 56px 64px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
            text-align: center;
            position: relative;
        }
        .certificado-topo {
            margin-bottom: 28px;
        }
        .certificado-topo img {
            height: 60px;
            filter: brightness(0) saturate(100%) invert(18%) sepia(85%) saturate(1852%) hue-rotate(192deg) brightness(92%);
            margin-bottom: 12px;
        }
        .certificado-subtitulo {
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #075eaa;
            text-transform: uppercase;
        }
        .certificado-titulo {
            font-size: 38px;
            color: #0f172a;
            margin: 12px 0 28px;
            letter-spacing: 0.04em;
        }
        .certificado-texto {
            font-size: 18px;
            line-height: 1.85;
            color: #334155;
            max-width: 760px;
            margin: 0 auto 36px;
        }
        .certificado-nome {
            display: block;
            font-size: 28px;
            font-weight: bold;
            color: #084b82;
            margin: 12px 0;
        }
        .certificado-rodape {
            margin-top: 44px;
            padding-top: 28px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            font-family: 'Segoe UI', system-ui, sans-serif;
            text-align: left;
        }
        .assinatura-bloco {
            text-align: center;
            min-width: 240px;
        }
        .assinatura-linha {
            border-top: 1px solid #475569;
            padding-top: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .assinatura-bloco small {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .autenticidade {
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        .autenticidade strong {
            color: #0f172a;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .barra-acoes { display: none !important; }
            .certificado-folha { box-shadow: none; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="barra-acoes">
        <a href="certificados.php" class="btn-acao">
            <i class="fa-solid fa-arrow-left"></i> Voltar aos certificados
        </a>
        <button type="button" class="btn-acao btn-imprimir" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimir / Salvar como PDF
        </button>
    </div>

    <article class="certificado-folha">
        <header class="certificado-topo">
            <img src="../../assets/img/logoAthenas.png" alt="UNIVAG Athenas">
            <div class="certificado-subtitulo">Plataforma Integrada de Apoio Acadêmico e Monitorias</div>
            <h1 class="certificado-titulo">CERTIFICADO DE MONITORIA ACADÊMICA</h1>
        </header>

        <p class="certificado-texto">
            Certificamos, para os devidos fins de comprovação de atividades acadêmicas e horas complementares, que
            <span class="certificado-nome"><?php echo htmlspecialchars($nomeTitular, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php echo htmlspecialchars($papelTexto, ENT_QUOTES, 'UTF-8'); ?> na disciplina de
            <strong><?php echo htmlspecialchars($disciplina, ENT_QUOTES, 'UTF-8'); ?></strong>,
            abordando o tema <strong>"<?php echo htmlspecialchars($conteudoEstudado, ENT_QUOTES, 'UTF-8'); ?>"</strong>,
            <?php echo htmlspecialchars($outroParticipante, ENT_QUOTES, 'UTF-8'); ?>,
            realizada em <strong><?php echo htmlspecialchars($dataSessao, ENT_QUOTES, 'UTF-8'); ?></strong>,
            com carga horária validada de <strong><?php echo htmlspecialchars($durLabel, ENT_QUOTES, 'UTF-8'); ?></strong>.
        </p>

        <footer class="certificado-rodape">
            <div class="autenticidade">
                <div>Código de verificação: <strong><?php echo htmlspecialchars($codigoVerificacao, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div>Registro da sessão: <strong>#<?php echo (int) $dados['id']; ?></strong> · Emissão: <?php echo date('d/m/Y H:i'); ?></div>
            </div>
            <div class="assinatura-bloco">
                <div class="assinatura-linha">Coordenação de Monitorias — UNIVAG Athenas</div>
                <small>Validação eletrônica por confirmação mútua de presença</small>
            </div>
        </footer>
    </article>
</body>
</html>
