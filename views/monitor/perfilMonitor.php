<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado();
require_once __DIR__ . '/../../config/conexao.php';

$tituloPagina = "Perfil do Monitor - UNIVAG ATHENAS";
$tipoUsuario = $_SESSION['usuario_tipo'];
$ehDono = ($tipoUsuario === 'monitor');
$diasSemana = [
    1 => ['chave' => 'Seg', 'nome' => 'Segunda-feira'],
    2 => ['chave' => 'Ter', 'nome' => 'Terça-feira'],
    3 => ['chave' => 'Qua', 'nome' => 'Quarta-feira'],
    4 => ['chave' => 'Qui', 'nome' => 'Quinta-feira'],
    5 => ['chave' => 'Sex', 'nome' => 'Sexta-feira'],
    6 => ['chave' => 'Sab', 'nome' => 'Sábado'],
    7 => ['chave' => 'Dom', 'nome' => 'Domingo'],
];
$perfil = [
    'nome' => $_SESSION['usuario_nome'] ?? 'Monitor',
    'bio' => '',
    'curso' => '',
    'periodo' => '',
    'foto_mime' => null,
    'area_atuacao' => '',
    'disciplina' => '',
    'local_atendimento' => '',
    'duracao_monitoria' => '',
    'modalidade' => '',
    'metodologia' => '',
    'ideal_para' => '',
    'topicos' => '',
];
$horariosSemana = array_fill_keys(array_keys($diasSemana), [
    'disponivel' => false,
    'inicio' => '',
    'fim' => '',
]);
$bloqueiosAgenda = [];
$avaliacoesMonitor = [];
$mediaAvaliacaoMonitor = null;
$totalAvaliacoesMonitor = 0;
$erros = [];
$modalComErro = '';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!in_array($metodo, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Método não permitido.');
}

if ($ehDono) {
    try {
        $stmt = $pdo->prepare(
            'SELECT u.nome, u.bio, u.curso, u.periodo, u.foto_mime,
                    pm.area_atuacao, pm.disciplina, pm.local_atendimento,
                    pm.duracao_monitoria, pm.modalidade, pm.metodologia,
                    pm.ideal_para, pm.topicos
             FROM usuarios u
             LEFT JOIN perfis_monitores pm ON pm.monitor_id = u.id
             WHERE u.id = ? AND u.tipo = ?'
        );
        $stmt->execute([$_SESSION['usuario_id'], 'monitor']);
        $dadosBanco = $stmt->fetch();
        if (!$dadosBanco) {
            http_response_code(404);
            exit('Perfil de monitor não encontrado.');
        }
        foreach (array_keys($perfil) as $campo) {
            if ($dadosBanco[$campo] !== null) {
                $perfil[$campo] = $dadosBanco[$campo];
            }
        }

        $stmt = $pdo->prepare(
            'SELECT dia_semana, disponivel, hora_inicio, hora_fim
             FROM disponibilidades_monitores
             WHERE monitor_id = ?'
        );
        $stmt->execute([$_SESSION['usuario_id']]);
        foreach ($stmt->fetchAll() as $horario) {
            $dia = (int) $horario['dia_semana'];
            if (isset($horariosSemana[$dia])) {
                $horariosSemana[$dia] = [
                    'disponivel' => (bool) $horario['disponivel'],
                    'inicio' => $horario['hora_inicio'] ? substr($horario['hora_inicio'], 0, 5) : '',
                    'fim' => $horario['hora_fim'] ? substr($horario['hora_fim'], 0, 5) : '',
                ];
            }
        }

        $stmt = $pdo->prepare(
            'SELECT id, inicio, fim, motivo
             FROM bloqueios_disponibilidade
             WHERE monitor_id = ? AND fim > CURRENT_TIMESTAMP
             ORDER BY inicio
             LIMIT 30'
        );
        $stmt->execute([$_SESSION['usuario_id']]);
        $bloqueiosAgenda = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*), ROUND(AVG(nota), 1)
             FROM avaliacoes_monitorias WHERE monitor_id = ?'
        );
        $stmt->execute([$_SESSION['usuario_id']]);
        [$totalAvaliacoesMonitor, $mediaAvaliacaoMonitor] = $stmt->fetch(PDO::FETCH_NUM);
        $stmt = $pdo->prepare(
            'SELECT av.nota, av.comentario, av.criada_em, aluno.nome AS aluno_nome
             FROM avaliacoes_monitorias av
             JOIN usuarios aluno ON aluno.id = av.aluno_id
             WHERE av.monitor_id = ?
             ORDER BY av.criada_em DESC
             LIMIT 5'
        );
        $stmt->execute([$_SESSION['usuario_id']]);
        $avaliacoesMonitor = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit('Não foi possível carregar o perfil do monitor.');
    }
} else {
    $perfil = array_merge($perfil, [
        'nome' => 'Perfil de exemplo',
        'bio' => 'Sou estudante e monitor. Gosto de explicar os conteúdos de forma simples e prática.',
        'curso' => 'Técnico em Informática',
        'periodo' => '',
        'area_atuacao' => 'Técnico em Informática',
        'disciplina' => 'Banco de Dados',
        'local_atendimento' => 'Online e presencial',
        'duracao_monitoria' => '1 hora',
        'modalidade' => 'Individual ou em grupo',
        'metodologia' => 'Explicações passo a passo, exemplos práticos e exercícios.',
        'ideal_para' => 'Estudantes que querem tirar dúvidas ou reforçar o conteúdo.',
        'topicos' => 'SQL, Modelagem de Banco de Dados, DER, Normalização',
    ]);
    foreach ([1 => ['19:00', '21:00'], 2 => ['19:00', '21:00'], 3 => ['14:00', '16:00'], 4 => ['15:00', '18:00']] as $dia => [$inicio, $fim]) {
        $horariosSemana[$dia] = ['disponivel' => true, 'inicio' => $inicio, 'fim' => $fim];
    }
}

if ($metodo === 'POST' && $ehDono) {
    $acao = $_POST['acao'] ?? '';
    $modalComErro = match ($acao) {
        'perfil' => 'modalEditarPerfil',
        'apresentacao' => 'modalEditarApresentacao',
        'disponibilidade' => 'modalEditarHorarios',
        default => '',
    };
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $erros[] = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($acao === 'perfil') {
        foreach ([
            'nome' => ['inputNome', 100],
            'bio' => ['inputSobre', 500],
            'curso' => ['inputCurso', 120],
            'periodo' => ['inputPeriodo', 40],
            'area_atuacao' => ['inputArea', 120],
            'disciplina' => ['inputDisciplina', 120],
            'local_atendimento' => ['inputLocal', 120],
            'duracao_monitoria' => ['inputDuracao', 40],
            'modalidade' => ['inputModalidade', 80],
        ] as $campo => [$nomePost, $limite]) {
            $valor = $_POST[$nomePost] ?? '';
            $perfil[$campo] = is_string($valor) ? trim($valor) : '';
            if (mb_strlen($perfil[$campo], 'UTF-8') > $limite) {
                $erros[] = "O campo {$nomePost} ultrapassa o limite permitido.";
            }
        }
        if (mb_strlen($perfil['nome'], 'UTF-8') < 3) {
            $erros[] = 'O nome deve ter pelo menos 3 caracteres.';
        }

        $arquivoFoto = $_FILES['foto_perfil'] ?? null;
        $enviouFoto = is_array($arquivoFoto) && ($arquivoFoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $removerFoto = ($_POST['remover_foto'] ?? '') === '1';
        $fotoBinaria = null;
        $mimeFoto = null;
        if ($enviouFoto && $removerFoto) {
            $erros[] = 'Escolha entre enviar uma nova foto ou remover a atual.';
        } elseif ($enviouFoto) {
            $codigoUpload = $arquivoFoto['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($codigoUpload !== UPLOAD_ERR_OK) {
                $erros[] = $codigoUpload === UPLOAD_ERR_INI_SIZE || $codigoUpload === UPLOAD_ERR_FORM_SIZE
                    ? 'A foto deve ter no máximo 2 MB.'
                    : 'Não foi possível receber a foto. Tente novamente.';
            } elseif (
                !isset($arquivoFoto['tmp_name'], $arquivoFoto['size'])
                || !is_string($arquivoFoto['tmp_name'])
                || !is_int($arquivoFoto['size'])
                || !is_uploaded_file($arquivoFoto['tmp_name'])
                || $arquivoFoto['size'] < 1
                || $arquivoFoto['size'] > 2 * 1024 * 1024
            ) {
                $erros[] = 'A foto deve ter no máximo 2 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeDetectado = $finfo->file($arquivoFoto['tmp_name']);
                $imagem = getimagesize($arquivoFoto['tmp_name']);
                $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
                if (
                    !in_array($mimeDetectado, $tiposPermitidos, true)
                    || !$imagem
                    || ($imagem['mime'] ?? '') !== $mimeDetectado
                    || $imagem[0] > 5000
                    || $imagem[1] > 5000
                    || $imagem[0] * $imagem[1] > 20000000
                ) {
                    $erros[] = 'Use uma imagem JPG, PNG ou WEBP válida, com dimensões de até 5.000 × 5.000 pixels.';
                } else {
                    $fotoBinaria = file_get_contents($arquivoFoto['tmp_name']);
                    $mimeFoto = $mimeDetectado;
                    if ($fotoBinaria === false) {
                        $fotoBinaria = null;
                        $mimeFoto = null;
                        $erros[] = 'Não foi possível ler a foto enviada.';
                    }
                }
            }
        }

        if (!$erros) {
            try {
                $pdo->beginTransaction();
                if ($fotoBinaria !== null) {
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios
                         SET nome = ?, bio = ?, curso = ?, periodo = ?, foto_perfil = ?, foto_mime = ?
                         WHERE id = ? AND tipo = ?'
                    );
                    $stmt->execute([
                        $perfil['nome'], $perfil['bio'], $perfil['curso'], $perfil['periodo'],
                        $fotoBinaria, $mimeFoto, $_SESSION['usuario_id'], 'monitor',
                    ]);
                } elseif ($removerFoto) {
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios
                         SET nome = ?, bio = ?, curso = ?, periodo = ?, foto_perfil = NULL, foto_mime = NULL
                         WHERE id = ? AND tipo = ?'
                    );
                    $stmt->execute([
                        $perfil['nome'], $perfil['bio'], $perfil['curso'], $perfil['periodo'],
                        $_SESSION['usuario_id'], 'monitor',
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE usuarios SET nome = ?, bio = ?, curso = ?, periodo = ?
                         WHERE id = ? AND tipo = ?'
                    );
                    $stmt->execute([
                        $perfil['nome'], $perfil['bio'], $perfil['curso'], $perfil['periodo'],
                        $_SESSION['usuario_id'], 'monitor',
                    ]);
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO perfis_monitores
                        (monitor_id, area_atuacao, disciplina, local_atendimento, duracao_monitoria,
                         modalidade, metodologia, ideal_para, topicos)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        area_atuacao = VALUES(area_atuacao),
                        disciplina = VALUES(disciplina),
                        local_atendimento = VALUES(local_atendimento),
                        duracao_monitoria = VALUES(duracao_monitoria),
                        modalidade = VALUES(modalidade),
                        metodologia = VALUES(metodologia),
                        ideal_para = VALUES(ideal_para),
                        topicos = VALUES(topicos)'
                );
                $stmt->execute([
                    $_SESSION['usuario_id'], $perfil['area_atuacao'], $perfil['disciplina'],
                    $perfil['local_atendimento'], $perfil['duracao_monitoria'], $perfil['modalidade'],
                    $perfil['metodologia'], $perfil['ideal_para'], $perfil['topicos'],
                ]);
                $pdo->commit();
                $_SESSION['usuario_nome'] = $perfil['nome'];
                header('Location: perfilMonitor.php?salvo=perfil');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());
                $erros[] = 'Não foi possível salvar seu perfil. Tente novamente.';
            }
        }
    } elseif ($acao === 'apresentacao') {
        foreach ([
            'bio' => ['inputSobre', 500],
            'metodologia' => ['inputMetodologia', 500],
            'ideal_para' => ['inputIdealPara', 300],
            'topicos' => ['inputTopicos', 1000],
        ] as $campo => [$nomePost, $limite]) {
            $valor = $_POST[$nomePost] ?? '';
            $perfil[$campo] = is_string($valor) ? trim($valor) : '';
            if (mb_strlen($perfil[$campo], 'UTF-8') > $limite) {
                $erros[] = "O campo {$nomePost} ultrapassa o limite permitido.";
            }
        }

        if (!$erros) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    'UPDATE usuarios SET bio = ? WHERE id = ? AND tipo = ?'
                );
                $stmt->execute([$perfil['bio'], $_SESSION['usuario_id'], 'monitor']);

                $stmt = $pdo->prepare(
                    'INSERT INTO perfis_monitores (monitor_id, metodologia, ideal_para, topicos)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        metodologia = VALUES(metodologia),
                        ideal_para = VALUES(ideal_para),
                        topicos = VALUES(topicos)'
                );
                $stmt->execute([
                    $_SESSION['usuario_id'], $perfil['metodologia'],
                    $perfil['ideal_para'], $perfil['topicos'],
                ]);
                $pdo->commit();
                header('Location: perfilMonitor.php?salvo=apresentacao');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());
                $erros[] = 'Não foi possível salvar sua apresentação. Tente novamente.';
            }
        }
    } elseif ($acao === 'disponibilidade') {
        foreach ($diasSemana as $dia => $dadosDia) {
            $disponivel = ($_POST['disponivel_' . $dia] ?? '') === '1';
            $inicio = $_POST['inicio_' . $dia] ?? '';
            $fim = $_POST['fim_' . $dia] ?? '';
            $inicio = is_string($inicio) ? $inicio : '';
            $fim = is_string($fim) ? $fim : '';
            $horariosSemana[$dia] = [
                'disponivel' => $disponivel,
                'inicio' => $inicio,
                'fim' => $fim,
            ];
            if (
                $disponivel
                && (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $inicio)
                    || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fim)
                    || $fim <= $inicio)
            ) {
                $erros[] = 'Informe horários válidos e com término posterior ao início para cada dia disponível.';
                break;
            }
        }

        if (!$erros) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('DELETE FROM disponibilidades_monitores WHERE monitor_id = ?');
                $stmt->execute([$_SESSION['usuario_id']]);
                $stmt = $pdo->prepare(
                    'INSERT INTO disponibilidades_monitores
                        (monitor_id, dia_semana, disponivel, hora_inicio, hora_fim)
                     VALUES (?, ?, ?, ?, ?)'
                );
                foreach ($horariosSemana as $dia => $horario) {
                    $stmt->execute([
                        $_SESSION['usuario_id'],
                        $dia,
                        $horario['disponivel'] ? 1 : 0,
                        $horario['disponivel'] ? $horario['inicio'] : null,
                        $horario['disponivel'] ? $horario['fim'] : null,
                    ]);
                }
                $pdo->commit();
                header('Location: perfilMonitor.php?salvo=disponibilidade');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());
                $erros[] = 'Não foi possível salvar sua disponibilidade. Tente novamente.';
            }
        }
    } elseif ($acao === 'adicionar_bloqueio') {
        $dataEntrada = $_POST['data_bloqueio'] ?? '';
        $inicioEntrada = $_POST['inicio_bloqueio'] ?? '';
        $fimEntrada = $_POST['fim_bloqueio'] ?? '';
        $motivoEntrada = $_POST['motivo_bloqueio'] ?? '';
        $dataEntrada = is_string($dataEntrada) ? $dataEntrada : '';
        $inicioEntrada = is_string($inicioEntrada) ? $inicioEntrada : '';
        $fimEntrada = is_string($fimEntrada) ? $fimEntrada : '';
        $motivoEntrada = is_string($motivoEntrada) ? trim($motivoEntrada) : '';
        $dataBloqueio = DateTimeImmutable::createFromFormat('!Y-m-d', $dataEntrada);
        $errosData = DateTimeImmutable::getLastErrors();
        if (
            !$dataBloqueio
            || ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
            || $dataBloqueio->format('Y-m-d') !== $dataEntrada
            || $dataBloqueio->format('Y-m-d') < date('Y-m-d')
            || $dataBloqueio > (new DateTimeImmutable('today'))->modify('+90 days')
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $inicioEntrada)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fimEntrada)
            || ($dataEntrada === date('Y-m-d') && $inicioEntrada <= date('H:i'))
            || $fimEntrada <= $inicioEntrada
            || mb_strlen($motivoEntrada, 'UTF-8') > 180
        ) {
            $erros[] = 'Informe uma data, período e motivo válidos para o bloqueio.';
        } else {
            $inicioBloqueio = $dataEntrada . ' ' . $inicioEntrada . ':00';
            $fimBloqueio = $dataEntrada . ' ' . $fimEntrada . ':00';
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ? FOR UPDATE');
                $stmt->execute([$_SESSION['usuario_id']]);
                $stmt = $pdo->prepare(
                    "SELECT id FROM agendamentos
                     WHERE monitor_id = ? AND status = 'confirmado'
                       AND agendado_para < ?
                       AND DATE_ADD(agendado_para, INTERVAL duracao_minutos MINUTE) > ?
                     LIMIT 1 FOR UPDATE"
                );
                $stmt->execute([$_SESSION['usuario_id'], $fimBloqueio, $inicioBloqueio]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    $erros[] = 'Já existe uma monitoria confirmada nesse período. O bloqueio não foi salvo.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO bloqueios_disponibilidade (monitor_id, inicio, fim, motivo)
                         VALUES (?, ?, ?, ?)'
                    );
                    $stmt->execute([$_SESSION['usuario_id'], $inicioBloqueio, $fimBloqueio, $motivoEntrada]);
                    $pdo->commit();
                    header('Location: perfilMonitor.php?salvo=bloqueio');
                    exit;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());
                $erros[] = 'Não foi possível salvar o bloqueio de agenda.';
            }
        }
    } elseif ($acao === 'remover_bloqueio') {
        $bloqueioId = filter_var($_POST['bloqueio_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$bloqueioId) {
            $erros[] = 'Bloqueio de agenda inválido.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'DELETE FROM bloqueios_disponibilidade
                     WHERE id = ? AND monitor_id = ? AND fim > CURRENT_TIMESTAMP'
                );
                $stmt->execute([$bloqueioId, $_SESSION['usuario_id']]);
                if ($stmt->rowCount() !== 1) {
                    $erros[] = 'Não foi possível remover esse bloqueio.';
                } else {
                    header('Location: perfilMonitor.php?salvo=bloqueio_removido');
                    exit;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $erros[] = 'Não foi possível remover o bloqueio de agenda.';
            }
        }
    } else {
        $erros[] = 'Ação de perfil inválida.';
    }
}

$mensagensSucesso = [
    'perfil' => 'Seu perfil foi atualizado.',
    'apresentacao' => 'Sua apresentação foi atualizada.',
    'disponibilidade' => 'Sua disponibilidade foi atualizada.',
    'bloqueio' => 'O período foi bloqueado na sua agenda.',
    'bloqueio_removido' => 'O bloqueio foi removido.',
];
$mensagemSucesso = $mensagensSucesso[$_GET['salvo'] ?? ''] ?? '';
$nomeExibicao = $perfil['nome'];
$cursoPeriodo = implode(' · ', array_filter([$perfil['curso'], $perfil['periodo']]));
$inicialNome = mb_substr(trim($nomeExibicao), 0, 1, 'UTF-8') ?: 'M';
$topicos = array_filter(array_map('trim', explode(',', $perfil['topicos'])));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ===== 2. METADADOS E BIBLIOTECAS ===== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?></title>
    
    <!-- Ícones do FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Unificado do Perfil do Monitor -->
    <link rel="stylesheet" href="../../assets/css/monitorTheme.css?v=5">
    <link rel="stylesheet" href="../../assets/css/perfilMonitor.css?v=5">
</head>
<body class="monitor-theme perfil-monitor-page">

    <!-- ===== 3. NAVBAR UNIVERSAL ===== -->
    <?php include '../../includes/navbar.php'; ?>

    <!-- ===== 4. CONTAINER PRINCIPAL ===== -->
    <main class="perfil-main-container">
        <div class="perfil-content-wrapper">

            <div class="perfil-aviso" role="note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <p>
                    <?php if ($ehDono): ?>
                        Mantenha sua apresentação e disponibilidade atualizadas para que os alunos encontrem as informações corretas.
                    <?php else: ?>
                        Este é um perfil demonstrativo. As informações exibidas ainda não correspondem a um cadastro real de monitor.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($mensagemSucesso !== ''): ?>
                <div class="perfil-feedback perfil-feedback-sucesso" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($erros): ?>
                <div class="perfil-feedback perfil-feedback-erro" role="alert">
                    <strong>Não foi possível concluir:</strong>
                    <ul>
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="perfil-grid">

                <div class="coluna-esquerda">

                    <article class="card-perfil-base">
                        <div class="perfil-capa" aria-hidden="true"></div>
                        <div class="perfil-apresentacao">
                            <div class="perfil-identidade-linha">
                                <div class="monitor-avatar" id="displayAvatar">
                                    <?php if ($ehDono && $perfil['foto_mime']): ?>
                                        <img src="../../actions/fotoPerfil.php?v=<?php echo (int) $_SESSION['usuario_id']; ?>" alt="Foto de perfil">
                                    <?php else: ?>
                                        <span aria-hidden="true"><?php echo htmlspecialchars($inicialNome, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="monitor-identificacao">
                                    <div class="monitor-dados-pessoais">
                                        <h1 id="displayNomeMonitor"><?php echo htmlspecialchars($nomeExibicao, ENT_QUOTES, 'UTF-8'); ?></h1>
                                        <p class="perfil-titulo-profissional">Monitor acadêmico <span aria-hidden="true">·</span> <span id="displayAreaAtuacao1"><?php echo htmlspecialchars($perfil['area_atuacao'] ?: 'Área não informada', ENT_QUOTES, 'UTF-8'); ?></span></p>
                                        <div class="perfil-metadados">
                                            <span><i class="fa-solid fa-book-open" aria-hidden="true"></i> <span id="displayDisciplina"><?php echo htmlspecialchars($perfil['disciplina'] ?: 'Disciplina não informada', ENT_QUOTES, 'UTF-8'); ?></span></span>
                                            <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <span id="displayLocalAtendimento"><?php echo htmlspecialchars($perfil['local_atendimento'] ?: 'Atendimento não informado', ENT_QUOTES, 'UTF-8'); ?></span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="perfil-acoes">
                                    <?php if ($ehDono): ?>
                                        <button type="button" class="btn-acao-secundario" data-modal-target="modalEditarPerfil">
                                            <i class="fa-solid fa-pen-to-square"></i> Editar perfil
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn-acao-primario" id="btnAgendarMonitoriaPerfil">
                                            <i class="fa-solid fa-calendar-plus"></i> Solicitar monitoria
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="monitor-detalhes-academicos">
                                <div class="item-academico">
                                    <span class="item-academico-icone"><i class="fa-solid fa-clock" aria-hidden="true"></i></span>
                                    <div>
                                        <small>Duração sugerida</small>
                                        <strong id="displayDuracao"><?php echo htmlspecialchars($perfil['duracao_monitoria'] ?: 'Não informada', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                                <div class="item-academico">
                                    <span class="item-academico-icone"><i class="fa-solid fa-tag" aria-hidden="true"></i></span>
                                    <div>
                                        <small>Curso</small>
                                        <strong><?php echo htmlspecialchars($cursoPeriodo ?: 'Não informado', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                                <div class="item-academico">
                                    <span class="item-academico-icone"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                                    <div>
                                        <small>Formato</small>
                                        <strong id="displayModalidade"><?php echo htmlspecialchars($perfil['modalidade'] ?: 'Não informada', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="card-conteudo-metodologia">
                        <div class="secao-cabecalho">
                            <div>
                                <span class="perfil-eyebrow">TRAJETÓRIA E ENSINO</span>
                                <h2>Sobre o monitor</h2>
                            </div>
                            <?php if ($ehDono): ?>
                                <button type="button" class="btn-acao-secundario" data-modal-target="modalEditarApresentacao">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar apresentação
                                </button>
                            <?php endif; ?>
                        </div>

                        <section class="secao-detalhe">
                            <div class="secao-titulo">
                                <i class="fa-solid fa-user"></i>
                                <h3>Apresentação</h3>
                            </div>
                            <p class="secao-texto" id="displaySobreTexto"><?php echo nl2br(htmlspecialchars($perfil['bio'] ?: 'Adicione uma apresentação para que os alunos conheçam sua experiência e forma de ensinar.', ENT_QUOTES, 'UTF-8')); ?></p>
                        </section>

                        <div class="grid-subsecoes secao-dividida">
                            <section class="secao-detalhe">
                                <div class="secao-titulo">
                                    <i class="fa-solid fa-lightbulb"></i>
                                    <h3>Metodologia</h3>
                                </div>
                                <div class="caixa-destaque" id="displayMetodologia"><?php echo nl2br(htmlspecialchars($perfil['metodologia'] ?: 'Descreva como costuma conduzir suas monitorias.', ENT_QUOTES, 'UTF-8')); ?></div>
                            </section>

                            <section class="secao-detalhe">
                                <div class="secao-titulo">
                                    <i class="fa-solid fa-bullseye"></i>
                                    <h3>Ideal para</h3>
                                </div>
                                <div class="caixa-destaque" id="displayIdealPara"><?php echo nl2br(htmlspecialchars($perfil['ideal_para'] ?: 'Indique para quais estudantes ou objetivos sua monitoria é mais indicada.', ENT_QUOTES, 'UTF-8')); ?></div>
                            </section>
                        </div>

                        <section class="secao-detalhe secao-dividida" id="secaoTopicos">
                            <div class="secao-titulo">
                                <i class="fa-solid fa-layer-group"></i>
                                <h3>Conteúdos de auxílio</h3>
                            </div>
                            <ul class="lista-tags" id="displayListaTopicos">
                                <?php if ($topicos): ?>
                                    <?php foreach ($topicos as $topico): ?>
                                        <li><?php echo htmlspecialchars($topico, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="topicos-vazio">Adicione os conteúdos que você pode auxiliar.</li>
                                <?php endif; ?>
                            </ul>
                        </section>
                    </article>

                </div>

                <div class="coluna-direita">

                <article class="card-disponibilidade">
                    <div class="disponibilidade-header">
                        <div class="disponibilidade-titulo">
                            <span class="disponibilidade-icone"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span>
                            <div>
                                <span class="perfil-eyebrow">AGENDA SEMANAL</span>
                                <h2>Disponibilidade</h2>
                            </div>
                        </div>
                        <?php if ($ehDono): ?>
                            <button type="button" class="btn-acao-secundario btn-editar-disponibilidade" data-modal-target="modalEditarHorarios">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="perfil-nota-secao">
                        <?php if ($ehDono): ?>
                            Esses horários ficam vinculados à sua conta e podem ser alterados quando precisar.
                        <?php else: ?>
                            Estes horários são ilustrativos e ainda não representam a disponibilidade real do monitor.
                        <?php endif; ?>
                    </p>

                    <ul class="lista-horarios" id="listaHorarios">
                        <?php foreach ($diasSemana as $dia => $dadosDia): ?>
                            <?php $horario = $horariosSemana[$dia]; ?>
                            <li class="item-horario" data-dia="<?php echo (int) $dia; ?>">
                                <span class="dia-horario"><?php echo htmlspecialchars($dadosDia['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="status-badge <?php echo $horario['disponivel'] ? 'disponivel' : 'indisponivel'; ?>">
                                    <i class="fa-solid fa-circle" aria-hidden="true"></i>
                                    <?php echo $horario['disponivel'] ? 'Disponível' : 'Indisponível'; ?>
                                </span>
                                <span class="horario-texto">
                                    <?php echo $horario['disponivel']
                                        ? htmlspecialchars($horario['inicio'] . ' às ' . $horario['fim'], ENT_QUOTES, 'UTF-8')
                                        : '—'; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($ehDono): ?>
                        <div class="agenda-bloqueios">
                            <h3><i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i> Bloquear um período</h3>
                            <p>Use esta opção para compromissos pontuais. Os horários recorrentes acima continuam valendo nos demais dias.</p>
                            <form method="post" class="bloqueio-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="acao" value="adicionar_bloqueio">
                                <label>
                                    <span>Data</span>
                                    <input type="date" name="data_bloqueio" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+90 days')); ?>" required>
                                </label>
                                <label>
                                    <span>Das</span>
                                    <input type="time" name="inicio_bloqueio" required>
                                </label>
                                <label>
                                    <span>Às</span>
                                    <input type="time" name="fim_bloqueio" required>
                                </label>
                                <label class="bloqueio-motivo">
                                    <span>Motivo (opcional)</span>
                                    <input type="text" name="motivo_bloqueio" maxlength="180" placeholder="Ex.: compromisso acadêmico">
                                </label>
                                <button type="submit" class="btn-acao-secundario">Bloquear horário</button>
                            </form>
                            <?php if ($bloqueiosAgenda): ?>
                                <ul class="lista-bloqueios">
                                    <?php foreach ($bloqueiosAgenda as $bloqueio): ?>
                                        <li>
                                            <span>
                                                <strong><?php echo date('d/m/Y', strtotime($bloqueio['inicio'])); ?></strong>
                                                <?php echo date('H:i', strtotime($bloqueio['inicio'])); ?> às <?php echo date('H:i', strtotime($bloqueio['fim'])); ?>
                                                <?php if ($bloqueio['motivo'] !== ''): ?>
                                                    <small><?php echo htmlspecialchars($bloqueio['motivo'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </span>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="acao" value="remover_bloqueio">
                                                <input type="hidden" name="bloqueio_id" value="<?php echo (int) $bloqueio['id']; ?>">
                                                <button type="submit" aria-label="Remover bloqueio">Remover</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="lista-bloqueios-vazia">Nenhum bloqueio futuro cadastrado.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                        <?php if (!$ehDono): ?>
                            <div class="container-fale-comigo">
                                <button type="button" class="btn-acao-primario btn-bloco" id="btnIniciarConversaPerfil">
                                    <i class="fa-solid fa-comments"></i> Iniciar conversa
                                </button>
                                <button type="button" class="btn-acao-outline btn-bloco" id="btnSolicitarMonitoriaPerfil">
                                    <i class="fa-solid fa-calendar-check"></i> Solicitar monitoria
                                </button>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="card-feedbacks">
                        <div class="secao-cabecalho">
                            <div>
                                <span class="perfil-eyebrow">REPUTAÇÃO</span>
                                <h2>Avaliações</h2>
                            </div>
                        </div>
                        <?php if ($ehDono && $totalAvaliacoesMonitor > 0): ?>
                            <div class="avaliacao-resumo">
                                <strong><?php echo number_format((float) $mediaAvaliacaoMonitor, 1, ',', '.'); ?> / 5</strong>
                                <span><?php echo (int) $totalAvaliacoesMonitor; ?> <?php echo (int) $totalAvaliacoesMonitor === 1 ? 'avaliação recebida' : 'avaliações recebidas'; ?></span>
                            </div>
                            <ul class="lista-avaliacoes">
                                <?php foreach ($avaliacoesMonitor as $avaliacao): ?>
                                    <li>
                                        <div>
                                            <strong><?php echo htmlspecialchars($avaliacao['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span><?php echo (int) $avaliacao['nota']; ?>/5 · <?php echo date('d/m/Y', strtotime($avaliacao['criada_em'])); ?></span>
                                        </div>
                                        <?php if ($avaliacao['comentario'] !== ''): ?>
                                            <p><?php echo htmlspecialchars($avaliacao['comentario'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="perfil-vazio">
                                <span class="perfil-vazio-icone"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i></span>
                                <p>As avaliações aparecem depois que aluno e monitor confirmam presença em uma monitoria concluída.</p>
                            </div>
                        <?php endif; ?>
                    </article>

                </div>

            </div>

        </div>
    </main>

    <?php if ($ehDono): ?>
    <div class="modal-overlay <?php echo $modalComErro === 'modalEditarPerfil' ? 'ativo' : ''; ?>" id="modalEditarPerfil">
        <div class="modal-container">
            <div class="modal-header">
                <div>
                    <span class="perfil-eyebrow">SEU PERFIL PROFISSIONAL</span>
                    <h3><i class="fa-solid fa-user-pen" aria-hidden="true"></i> Editar perfil</h3>
                </div>
                <button type="button" class="btn-modal-fechar" data-modal-close aria-label="Fechar">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form id="formEditarPerfil" class="modal-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="acao" value="perfil">
                <label class="form-group">
                    <span class="form-label">Foto de perfil</span>
                    <span class="campo-ajuda">JPG, PNG ou WEBP, até 2 MB.</span>
                    <input type="file" id="inputFoto" name="foto_perfil" class="form-input" accept="image/jpeg,image/png,image/webp">
                </label>
                <?php if ($perfil['foto_mime']): ?>
                    <label class="remover-foto">
                        <input type="checkbox" name="remover_foto" value="1">
                        <span>Remover minha foto atual</span>
                    </label>
                <?php endif; ?>
                <div class="form-group">
                    <label for="inputNome">Nome</label>
                    <input type="text" id="inputNome" name="inputNome" class="form-input" value="<?php echo htmlspecialchars($perfil['nome'], ENT_QUOTES, 'UTF-8'); ?>" minlength="3" maxlength="100" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="inputDisciplina">Disciplina</label>
                        <input type="text" id="inputDisciplina" name="inputDisciplina" class="form-input" value="<?php echo htmlspecialchars($perfil['disciplina'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120">
                    </div>
                    <div class="form-group">
                        <label for="inputArea">Área de Atuação</label>
                        <input type="text" id="inputArea" name="inputArea" class="form-input" value="<?php echo htmlspecialchars($perfil['area_atuacao'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="inputCurso">Curso</label>
                        <input type="text" id="inputCurso" name="inputCurso" class="form-input" value="<?php echo htmlspecialchars($perfil['curso'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120">
                    </div>
                    <div class="form-group">
                        <label for="inputPeriodo">Período ou semestre</label>
                        <input type="text" id="inputPeriodo" name="inputPeriodo" class="form-input" value="<?php echo htmlspecialchars($perfil['periodo'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="40">
                    </div>
                </div>
                <div class="form-grid-2">
                    <label class="form-group">
                        <span class="form-label">Duração sugerida</span>
                        <input type="text" id="inputDuracao" name="inputDuracao" class="form-input" value="<?php echo htmlspecialchars($perfil['duracao_monitoria'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="40" placeholder="Ex.: 1 hora">
                    </label>
                    <label class="form-group">
                        <span class="form-label">Formato</span>
                        <input type="text" id="inputModalidade" name="inputModalidade" class="form-input" value="<?php echo htmlspecialchars($perfil['modalidade'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="80" placeholder="Ex.: individual ou em grupo">
                    </label>
                </div>
                <div class="form-group">
                    <label for="inputLocal">Local ou modalidade de atendimento</label>
                    <input type="text" id="inputLocal" name="inputLocal" class="form-input" value="<?php echo htmlspecialchars($perfil['local_atendimento'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120" placeholder="Ex.: online ou presencial">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar perfil</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay <?php echo $modalComErro === 'modalEditarApresentacao' ? 'ativo' : ''; ?>" id="modalEditarApresentacao">
        <div class="modal-container">
            <div class="modal-header">
                <div>
                    <span class="perfil-eyebrow">TRAJETÓRIA E ENSINO</span>
                    <h3><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar apresentação</h3>
                </div>
                <button type="button" class="btn-modal-fechar" data-modal-close aria-label="Fechar">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form id="formEditarApresentacao" class="modal-form" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="acao" value="apresentacao">
                <div class="form-group">
                    <label for="inputSobre">Apresentação</label>
                    <textarea id="inputSobre" name="inputSobre" class="form-textarea" rows="4" maxlength="500" placeholder="Conte sobre sua trajetória, experiência e interesses."><?php echo htmlspecialchars($perfil['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <small class="campo-ajuda">Até 500 caracteres.</small>
                </div>
                <div class="form-group">
                    <label for="inputMetodologia">Como você ensina?</label>
                    <textarea id="inputMetodologia" name="inputMetodologia" class="form-textarea" rows="3" maxlength="500" placeholder="Descreva sua metodologia e como conduz os atendimentos."><?php echo htmlspecialchars($perfil['metodologia'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="inputIdealPara">Para quem sua monitoria é indicada?</label>
                    <textarea id="inputIdealPara" name="inputIdealPara" class="form-textarea" rows="2" maxlength="300" placeholder="Ex.: iniciantes, revisão para provas, dúvidas específicas."><?php echo htmlspecialchars($perfil['ideal_para'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="inputTopicos">Conteúdos que pode auxiliar</label>
                    <textarea id="inputTopicos" name="inputTopicos" class="form-textarea" rows="2" maxlength="1000" placeholder="Separe os conteúdos por vírgula."><?php echo htmlspecialchars($perfil['topicos'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <small class="campo-ajuda">Separe cada conteúdo por vírgula.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar apresentação</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay <?php echo $modalComErro === 'modalEditarHorarios' ? 'ativo' : ''; ?>" id="modalEditarHorarios">
        <div class="modal-container">
            <div class="modal-header">
                <div>
                    <span class="perfil-eyebrow">DISPONIBILIDADE</span>
                    <h3><i class="fa-regular fa-calendar-days" aria-hidden="true"></i> Editar dias e horários</h3>
                </div>
                <button type="button" class="btn-modal-fechar" data-modal-close aria-label="Fechar">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <p class="modal-ajuda">Marque os dias em que costuma atender e informe um período para cada dia. As alterações serão salvas na sua conta.</p>
            <form id="formEditarHorarios" class="modal-form" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="acao" value="disponibilidade">
                <div class="horarios-edicao-lista">
                    <?php foreach ($diasSemana as $dia => $dadosDia): ?>
                        <?php $horario = $horariosSemana[$dia]; ?>
                        <div class="horario-edicao-linha <?php echo $horario['disponivel'] ? '' : 'dia-desativado'; ?>">
                            <label class="dia-disponivel">
                                <input type="checkbox" name="disponivel_<?php echo (int) $dia; ?>" value="1" <?php echo $horario['disponivel'] ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($dadosDia['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                            <label class="campo-horario">
                                <span>Das</span>
                                <input type="time" name="inicio_<?php echo (int) $dia; ?>" value="<?php echo htmlspecialchars($horario['inicio'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $horario['disponivel'] ? 'required' : 'disabled'; ?>>
                            </label>
                            <label class="campo-horario">
                                <span>Às</span>
                                <input type="time" name="fim_<?php echo (int) $dia; ?>" value="<?php echo htmlspecialchars($horario['fim'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $horario['disponivel'] ? 'required' : 'disabled'; ?>>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar disponibilidade</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>

    <!-- ===== 9. INTERATIVIDADE JAVASCRIPT ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Gerenciador unificado de modais
        const botoesAbrir  = document.querySelectorAll('[data-modal-target]');
        const botoesFechar = document.querySelectorAll('[data-modal-close]');
        const overlays     = document.querySelectorAll('.modal-overlay');

        function fecharTodosModais() {
            overlays.forEach(modal => modal.classList.remove('ativo'));
        }

        botoesAbrir.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-modal-target');
                const modalTarget = document.getElementById(targetId);
                if (modalTarget) {
                    fecharTodosModais();
                    modalTarget.classList.add('ativo');
                }
            });
        });

        botoesFechar.forEach(btn => {
            btn.addEventListener('click', fecharTodosModais);
        });

        overlays.forEach(overlay => {
            overlay.addEventListener('click', function (e) {
                if (e.target === this) fecharTodosModais();
            });
        });

        document.querySelectorAll('.horario-edicao-linha').forEach(linha => {
            const disponivel = linha.querySelector('input[type="checkbox"]');
            const camposHorario = linha.querySelectorAll('input[type="time"]');
            disponivel.addEventListener('change', function () {
                camposHorario.forEach(campo => {
                    campo.disabled = !this.checked;
                    campo.required = this.checked;
                });
                linha.classList.toggle('dia-desativado', !this.checked);
            });
        });

        const inputFoto = document.getElementById('inputFoto');
        const avatarPerfil = document.getElementById('displayAvatar');
        if (inputFoto && avatarPerfil) {
            inputFoto.addEventListener('change', function () {
                const arquivo = this.files && this.files[0];
                if (!arquivo || !arquivo.type.startsWith('image/')) return;
                const imagem = document.createElement('img');
                imagem.src = URL.createObjectURL(arquivo);
                imagem.alt = 'Prévia da foto de perfil';
                avatarPerfil.replaceChildren(imagem);
            });
        }

        const botaoIniciarConversaPerfil = document.getElementById('btnIniciarConversaPerfil');
        if (botaoIniciarConversaPerfil) {
            botaoIniciarConversaPerfil.addEventListener('click', function () {
                const chatToggle = document.getElementById('btnMessages');
                if (chatToggle) {
                    chatToggle.click();
                    const selectContato = document.getElementById('chatContactSelect');
                    if (selectContato && !selectContato.value) {
                        selectContato.focus();
                    }
                }
            });
        }

        const botaoSolicitarMonitoriaPerfil = document.getElementById('btnSolicitarMonitoriaPerfil');
        const botaoAgendarMonitoriaPerfil = document.getElementById('btnAgendarMonitoriaPerfil');
        const acaoMonitoriaPerfil = function () {
            const chatToggle = document.getElementById('btnMessages');
            if (chatToggle) {
                chatToggle.click();
                const abertura = document.getElementById('requestAppointment');
                if (abertura) {
                    abertura.click();
                }
            }
        };

        if (botaoSolicitarMonitoriaPerfil) {
            botaoSolicitarMonitoriaPerfil.addEventListener('click', acaoMonitoriaPerfil);
        }
        if (botaoAgendarMonitoriaPerfil) {
            botaoAgendarMonitoriaPerfil.addEventListener('click', acaoMonitoriaPerfil);
        }
    });
    </script>

</body>
</html>