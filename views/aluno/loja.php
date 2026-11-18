<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');
require_once __DIR__ . '/../../config/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$usuarioId = (int) $_SESSION['usuario_id'];
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Aluno';
$inicialNome = mb_strtoupper(mb_substr(trim($nomeUsuario), 0, 1, 'UTF-8'), 'UTF-8') ?: 'A';
$erros = [];
$sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] === '1';

require_once __DIR__ . '/../../includes/gamificacao.php';

/* Catálogo Oficial dos 12 Conjuntos Completos da Loja + 5 Prêmios de Maestria 10 */
$catalogoConjuntos = athenasCatalogoConjuntos();
$premiosMaestria10 = athenasPremiosMaestria10();
$todosPares = $catalogoConjuntos + $premiosMaestria10;

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Método não permitido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $acao = $_POST['acao'] ?? '';
    $itemId = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $erros[] = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif (!in_array($acao, ['comprar', 'equipar', 'remover', 'resgatar_maestria'], true)) {
        $erros[] = 'Ação da loja inválida.';
    } elseif ($acao !== 'remover' && !$itemId) {
        $erros[] = 'Selecione um conjunto válido.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'aluno' FOR UPDATE");
            $stmt->execute([$usuarioId]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                http_response_code(404);
                exit('Aluno não encontrado.');
            }

            if ($acao === 'remover') {
                $stmt = $pdo->prepare('DELETE FROM loja_equipamentos WHERE usuario_id = ?');
                $stmt->execute([$usuarioId]);
                $pdo->commit();
                header('Location: loja.php?sucesso=1');
                exit;
            } else {
                $stmt = $pdo->prepare(
                    "SELECT id, nome, categoria, preco, visual
                     FROM loja_itens WHERE id = ? AND categoria = 'fundo' AND ativo = 1"
                );
                $stmt->execute([$itemId]);
                $itemFundo = $stmt->fetch();

                if (!$itemFundo || !isset($todosPares[$itemFundo['visual']])) {
                    $pdo->rollBack();
                    $erros[] = 'Este conjunto não está disponível na loja.';
                } else {
                    $avatarVisualPar = $todosPares[$itemFundo['visual']]['avatar_visual'];
                    $stmtAv = $pdo->prepare(
                        "SELECT id FROM loja_itens WHERE categoria = 'avatar' AND visual = ? AND ativo = 1 LIMIT 1"
                    );
                    $stmtAv->execute([$avatarVisualPar]);
                    $itemAvatarId = (int) $stmtAv->fetchColumn();

                    $stmtPossui = $pdo->prepare(
                        'SELECT item_id FROM loja_compras WHERE usuario_id = ? AND item_id = ?'
                    );
                    $stmtPossui->execute([$usuarioId, $itemId]);
                    $jaPossuiConjunto = (bool) $stmtPossui->fetch();

                    if ($acao === 'resgatar_maestria') {
                        // Desbloqueia e equipa o prêmio exclusivo de Maestria 10 (custo 0 pts)
                        $stmtCompra = $pdo->prepare(
                            'INSERT IGNORE INTO loja_compras (usuario_id, item_id) VALUES (?, ?)'
                        );
                        $stmtCompra->execute([$usuarioId, $itemId]);
                        if ($itemAvatarId > 0) {
                            $stmtCompra->execute([$usuarioId, $itemAvatarId]);
                        }

                        $stmtEquip = $pdo->prepare(
                            'INSERT INTO loja_equipamentos (usuario_id, categoria, item_id)
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)'
                        );
                        $stmtEquip->execute([$usuarioId, 'fundo', $itemId]);
                        if ($itemAvatarId > 0) {
                            $stmtEquip->execute([$usuarioId, 'avatar', $itemAvatarId]);
                        }

                        $pdo->commit();
                        header('Location: loja.php?sucesso=1');
                        exit;
                    } elseif ($acao === 'comprar') {
                        if ($jaPossuiConjunto) {
                            $pdo->rollBack();
                            $erros[] = 'Você já desbloqueou este conjunto completo.';
                        } else {
                            $stmtSaldo = $pdo->prepare(
                                'SELECT COALESCE(SUM(pontos), 0) FROM pontos_transacoes WHERE usuario_id = ?'
                            );
                            $stmtSaldo->execute([$usuarioId]);
                            $saldoAtual = (int) $stmtSaldo->fetchColumn();

                            if ($saldoAtual < (int) $itemFundo['preco']) {
                                $pdo->rollBack();
                                $erros[] = 'Você ainda não tem pontos suficientes para este conjunto. Conclua monitorias para ganhar +10 pontos por sessão!';
                            } else {
                                $stmtCompra = $pdo->prepare(
                                    'INSERT IGNORE INTO loja_compras (usuario_id, item_id) VALUES (?, ?)'
                                );
                                $stmtCompra->execute([$usuarioId, $itemId]);
                                if ($itemAvatarId > 0) {
                                    $stmtCompra->execute([$usuarioId, $itemAvatarId]);
                                }

                                $stmtDebito = $pdo->prepare(
                                    "INSERT INTO pontos_transacoes (usuario_id, item_id, evento, pontos)
                                     VALUES (?, ?, 'compra', ?)"
                                );
                                $stmtDebito->execute([$usuarioId, $itemId, -(int) $itemFundo['preco']]);

                                $stmtEquip = $pdo->prepare(
                                    'INSERT INTO loja_equipamentos (usuario_id, categoria, item_id)
                                     VALUES (?, ?, ?)
                                     ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)'
                                );
                                $stmtEquip->execute([$usuarioId, 'fundo', $itemId]);
                                if ($itemAvatarId > 0) {
                                    $stmtEquip->execute([$usuarioId, 'avatar', $itemAvatarId]);
                                }

                                $pdo->commit();
                                header('Location: loja.php?sucesso=1');
                                exit;
                            }
                        }
                    } else {
                        if (!$jaPossuiConjunto) {
                            $pdo->rollBack();
                            $erros[] = 'Desbloqueie o conjunto antes de equipá-lo.';
                        } else {
                            if ($itemAvatarId > 0) {
                                $stmtGarantePar = $pdo->prepare(
                                    'INSERT IGNORE INTO loja_compras (usuario_id, item_id) VALUES (?, ?)'
                                );
                                $stmtGarantePar->execute([$usuarioId, $itemAvatarId]);
                            }

                            $stmtEquip = $pdo->prepare(
                                'INSERT INTO loja_equipamentos (usuario_id, categoria, item_id)
                                 VALUES (?, ?, ?)
                                 ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)'
                            );
                            $stmtEquip->execute([$usuarioId, 'fundo', $itemId]);
                            if ($itemAvatarId > 0) {
                                $stmtEquip->execute([$usuarioId, 'avatar', $itemAvatarId]);
                            }

                            $pdo->commit();
                            header('Location: loja.php?sucesso=1');
                            exit;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($e->getMessage());
            $erros[] = 'Não foi possível concluir a operação na loja. Tente novamente.';
        }
    }
}

$cursoAluno = 'Graduação UNIVAG';
$temFotoPerfil = false;
$fundoEquipado = '';
$avatarEquipado = '';
$sessoesConcluidas = [];

try {
    $stmtUser = $pdo->prepare('SELECT curso, periodo, foto_mime FROM usuarios WHERE id = ? LIMIT 1');
    $stmtUser->execute([$usuarioId]);
    if ($uRow = $stmtUser->fetch()) {
        $cursoAluno = implode(' · ', array_filter([$uRow['curso'], $uRow['periodo']])) ?: 'Graduação UNIVAG';
        $temFotoPerfil = !empty($uRow['foto_mime']);
    }

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(pontos), 0) FROM pontos_transacoes WHERE usuario_id = ?');
    $stmt->execute([$usuarioId]);
    $saldo = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT i.id, i.slug, i.nome, i.descricao, i.categoria, i.preco, i.visual,
                (c.item_id IS NOT NULL) AS possui,
                (e.item_id = i.id) AS equipado
         FROM loja_itens i
         LEFT JOIN loja_compras c ON c.item_id = i.id AND c.usuario_id = ?
         LEFT JOIN loja_equipamentos e
            ON e.usuario_id = ? AND e.categoria = i.categoria AND e.item_id = i.id
         WHERE i.ativo = 1
         ORDER BY i.preco ASC, i.id ASC"
    );
    $stmt->execute([$usuarioId, $usuarioId]);
    $itens = $stmt->fetchAll();

    $stmtMaestria = $pdo->prepare(
        "SELECT a.assunto, a.duracao_minutos, pm.disciplina, pm.area_atuacao
         FROM agendamentos a
         LEFT JOIN perfis_monitores pm ON pm.monitor_id = a.monitor_id
         WHERE a.aluno_id = ? AND a.status = 'confirmado'
           AND a.presenca_aluno = 1 AND a.presenca_monitor = 1"
    );
    $stmtMaestria->execute([$usuarioId]);
    $sessoesConcluidas = $stmtMaestria->fetchAll();

    $stmtExtrato = $pdo->prepare(
        "SELECT pt.evento, pt.pontos, pt.criado_em, a.assunto, i.nome AS item_nome
         FROM pontos_transacoes pt
         LEFT JOIN agendamentos a ON a.id = pt.agendamento_id
         LEFT JOIN loja_itens i ON i.id = pt.item_id
         WHERE pt.usuario_id = ?
         ORDER BY pt.criado_em DESC, pt.id DESC
         LIMIT 6"
    );
    $stmtExtrato->execute([$usuarioId]);
    $extratoRecente = $stmtExtrato->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar a loja.');
}

$conjuntosLoja = [];
$itensMaestriaPorTrilha = [];

foreach ($itens as $item) {
    if ((bool) $item['equipado']) {
        if ($item['categoria'] === 'fundo') {
            $fundoEquipado = $item['visual'];
        } elseif ($item['categoria'] === 'avatar') {
            $avatarEquipado = $item['visual'];
        }
    }
    if ($item['categoria'] === 'fundo' && isset($catalogoConjuntos[$item['visual']])) {
        $meta = $catalogoConjuntos[$item['visual']];
        $item['avatar_visual'] = $meta['avatar_visual'];
        $item['avatar_nome'] = $meta['avatar_nome'];
        $item['icone'] = $meta['icone'];
        $item['peculiaridade'] = $meta['peculiaridade'];
        $conjuntosLoja[] = $item;
    } elseif ($item['categoria'] === 'fundo' && isset($premiosMaestria10[$item['visual']])) {
        $metaM = $premiosMaestria10[$item['visual']];
        $item['avatar_visual'] = $metaM['avatar_visual'];
        $item['avatar_nome'] = $metaM['avatar_nome'];
        $item['icone'] = $metaM['icone'];
        $item['piada'] = $metaM['piada'];
        $itensMaestriaPorTrilha[$metaM['trilha']] = $item;
    }
}

$iconesAvatares = athenasIconesAvatares();

function obterRaridadeConjunto(int $preco): array
{
    if ($preco >= 85) {
        return ['classe' => 'raridade-mitico', 'nome' => 'Mítico'];
    }
    if ($preco >= 55) {
        return ['classe' => 'raridade-lendario', 'nome' => 'Lendário'];
    }
    return ['classe' => 'raridade-epico', 'nome' => 'Épico'];
}

$trilhasMaestria = athenasTrilhasMaestriaBase();

foreach ($sessoesConcluidas as $sessao) {
    $texto = mb_strtolower(
        ($sessao['assunto'] ?? '') . ' ' . ($sessao['disciplina'] ?? '') . ' ' . ($sessao['area_atuacao'] ?? ''),
        'UTF-8'
    );
    $atribuido = false;
    foreach ($trilhasMaestria as $chave => &$trilha) {
        foreach ($trilha['palavras'] as $palavra) {
            if (str_contains($texto, $palavra)) {
                $trilha['minutos'] += (int) $sessao['duracao_minutos'];
                $trilha['sessoes'] += 1;
                $atribuido = true;
                break;
            }
        }
        if ($atribuido) {
            break;
        }
    }
    unset($trilha);
    if (!$atribuido) {
        $trilhasMaestria['logica']['minutos'] += (int) $sessao['duracao_minutos'];
        $trilhasMaestria['logica']['sessoes'] += 1;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lojinha &amp; Maestrias - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/aluno.css?v=6">
    <link rel="stylesheet" href="../../assets/css/loja.css?v=9">
    <link rel="stylesheet" href="../../assets/css/personalizacao.css?v=5">
</head>
<body class="aluno-theme">
    <?php include '../../includes/navbar.php'; ?>

    <main class="aluno-main loja-main">
        <div class="aluno-layout">

            <!-- Cabeçalho + Provador Ao Vivo do Perfil -->
            <section class="loja-hero-showcase">
                <div class="loja-hero-info">
                    <span class="aluno-eyebrow">GAMIFICAÇÃO, CONJUNTOS &amp; RELÍQUIAS DE MAESTRIA 10</span>
                    <h1>Arsenal &amp; Lojinha Athenas</h1>
                    <p>Desbloqueie <strong>Conjuntos de Perfil Completo</strong> por um preço único ou alcance a <strong>Maestria 10</strong> nas disciplinas para ganhar <strong>Conjuntos Exclusivos de Piadas Acadêmicas</strong>!</p>

                    <div class="loja-hero-stats">
                        <div class="loja-saldo" aria-label="Seu saldo de pontos">
                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                            <strong><?php echo $saldo; ?></strong>
                            <span>pontos disponíveis</span>
                        </div>
                        <a href="perfil.php" class="loja-ver-perfil-btn">
                            <i class="fa-regular fa-id-card" aria-hidden="true"></i> Ver meu perfil público
                        </a>
                    </div>
                </div>

                <!-- Provador Interativo do Perfil em Tempo Real -->
                <div class="loja-live-preview-wrapper">
                    <div class="preview-header-bar">
                        <span><i class="fa-solid fa-wand-magic-sparkles"></i> Simulador de Conjunto Completo</span>
                        <small id="previewStatusLabel">Visual equipado atualmente</small>
                    </div>
                    <div class="preview-profile-card">
                        <div
                            id="livePreviewBanner"
                            class="loja-preview-fundo preview-banner-box <?php echo $fundoEquipado ? 'visual-' . htmlspecialchars($fundoEquipado, ENT_QUOTES, 'UTF-8') : 'visual-templo-atena'; ?>"
                        ></div>
                        <div class="preview-profile-body">
                            <div
                                id="livePreviewAvatar"
                                class="loja-preview-avatar preview-avatar-box <?php echo $avatarEquipado ? 'visual-' . htmlspecialchars($avatarEquipado, ENT_QUOTES, 'UTF-8') : 'visual-coruja-atena'; ?>"
                            >
                                <?php if ($avatarEquipado): ?>
                                    <i class="fa-solid <?php echo htmlspecialchars($iconesAvatares[$avatarEquipado] ?? 'fa-user-graduate', ENT_QUOTES, 'UTF-8'); ?>"></i>
                                <?php elseif ($temFotoPerfil): ?>
                                    <img src="../../actions/fotoPerfil.php?id=<?php echo $usuarioId; ?>" alt="">
                                <?php else: ?>
                                    <span><?php echo htmlspecialchars($inicialNome, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="preview-profile-text">
                                <strong><?php echo htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars($cursoAluno, ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <?php if ($sucesso): ?>
                <div class="loja-aviso loja-sucesso" role="status">
                    <i class="fa-solid fa-circle-check"></i> Seu Conjunto Completo (Banner + Avatar com Aura) foi atualizado no Perfil e na Navbar!
                </div>
            <?php endif; ?>
            <?php if ($erros): ?>
                <div class="loja-aviso loja-erro" role="alert">
                    <?php foreach ($erros as $erro): ?>
                        <p><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ================= VITRINE UNIFICADA: 12 CONJUNTOS DE PERFIL COMPLETO (3 FILEIRAS DE 4) ================= -->
            <section class="loja-secao" aria-labelledby="tituloConjuntosCompletos">
                <div class="loja-secao-titulo">
                    <div>
                        <span class="aluno-eyebrow">12 COLEÇÕES EXCLUSIVAS · PREÇO ÚNICO (BANNER + AVATAR INCLUSOS)</span>
                        <h2 id="tituloConjuntosCompletos"><i class="fa-solid fa-layer-group"></i> Conjuntos de Perfil Completo</h2>
                        <p class="secao-subtitulo">Ao desbloquear qualquer conjunto abaixo, você recebe e equipa <strong>o Banner Animado + a Insígnia com Aura na Navbar</strong> juntos, sem pagar duas vezes!</p>
                    </div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="acao" value="remover">
                        <button class="loja-link-button" type="submit">
                            <i class="fa-solid fa-rotate-left"></i> Restaurar perfil padrão
                        </button>
                    </form>
                </div>

                <div class="conjuntos-completos-grid">
                    <?php foreach ($conjuntosLoja as $conjunto): ?>
                        <?php
                        $visualFundo = $conjunto['visual'];
                        $visualAvatar = $conjunto['avatar_visual'];
                        $iconeAv = $conjunto['icone'];
                        $raridade = obterRaridadeConjunto((int) $conjunto['preco']);
                        $estaEquipado = (bool) $conjunto['equipado'];
                        ?>
                        <article class="loja-item conjunto-completo-card <?php echo $raridade['classe']; ?> <?php echo $estaEquipado ? 'item-equipado-card' : ''; ?>">
                            <!-- Topo: Banner Animado + Avatar com Aura Combinado -->
                            <div class="conjunto-stage">
                                <div class="loja-preview-fundo conjunto-banner-preview visual-<?php echo htmlspecialchars($visualFundo, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
                                    <span class="raridade-pill <?php echo $raridade['classe']; ?>"><?php echo $raridade['nome']; ?></span>
                                    <small class="loja-selo-animado"><i class="fa-solid fa-wand-magic-sparkles"></i> Banner + Avatar</small>
                                </div>
                                <div class="conjunto-identidade-bar">
                                    <div class="loja-preview-avatar conjunto-avatar-preview visual-<?php echo htmlspecialchars($visualAvatar, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
                                        <i class="fa-solid <?php echo htmlspecialchars($iconeAv, ENT_QUOTES, 'UTF-8'); ?>"></i>
                                    </div>
                                    <div class="conjunto-avatar-tag">
                                        <small>INSÍGNIA INCLUSA</small>
                                        <strong><?php echo htmlspecialchars($conjunto['avatar_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Corpo do Conjunto -->
                            <div class="loja-item-info">
                                <h3><?php echo htmlspecialchars($conjunto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($conjunto['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="item-perk-tag">
                                    <i class="fa-solid fa-gem" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($conjunto['peculiaridade'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>

                            <!-- Ações: Provador + Comprar/Equipar Conjunto -->
                            <div class="loja-item-acao">
                                <button
                                    type="button"
                                    class="loja-btn-testar"
                                    data-testar-combo-fundo="<?php echo htmlspecialchars($visualFundo, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-testar-combo-avatar="<?php echo htmlspecialchars($visualAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-testar-combo-icone="<?php echo htmlspecialchars($iconeAv, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-testar-combo-nome="<?php echo htmlspecialchars($conjunto['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                                    title="Simular conjunto completo no topo"
                                >
                                    <i class="fa-regular fa-eye"></i> Provador
                                </button>

                                <?php if ($estaEquipado): ?>
                                    <span class="loja-equipado"><i class="fa-solid fa-check"></i> Equipado</span>
                                <?php elseif ((bool) $conjunto['possui']): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="acao" value="equipar">
                                        <input type="hidden" name="item_id" value="<?php echo (int) $conjunto['id']; ?>">
                                        <button class="loja-botao btn-equipar-item" type="submit">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i> Equipar Conjunto
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="acao" value="comprar">
                                        <input type="hidden" name="item_id" value="<?php echo (int) $conjunto['id']; ?>">
                                        <button class="loja-botao" type="submit">
                                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                                            <?php echo (int) $conjunto['preco']; ?> pts
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ================= SISTEMA DE MAESTRIA DE MATÉRIA + PRÊMIOS EXCLUSIVOS NV. 10 ================= -->
            <section class="loja-secao maestria-secao" aria-labelledby="tituloMaestria">
                <div class="loja-secao-titulo">
                    <div>
                        <span class="aluno-eyebrow">PROGRESSÃO POR DISCIPLINA · BÔNUS DE NÍVEL 10</span>
                        <h2 id="tituloMaestria"><i class="fa-solid fa-trophy"></i> Maestria de Matérias &amp; Prêmios Nível 10</h2>
                        <p class="secao-subtitulo">Conclua monitorias em cada área para evoluir até a <strong>Maestria 10</strong> e desbloquear um conjunto temático divertido de cada disciplina.</p>
                    </div>
                </div>

                <div class="maestria-grid">
                    <?php foreach ($trilhasMaestria as $chaveTrilha => $trilha): ?>
                        <?php
                        $premio = $itensMaestriaPorTrilha[$chaveTrilha] ?? null;
                        $jaDesbloqueouPremio = $premio ? (bool) $premio['possui'] : false;
                        $premioEquipado = $premio ? (bool) $premio['equipado'] : false;
                        $infoPatente = calcularPatenteMaestria($jaDesbloqueouPremio ? 540 : $trilha['minutos']);
                        $pct = min(100, (int) round((($jaDesbloqueouPremio ? 540 : $trilha['minutos']) / $infoPatente['meta']) * 100));
                        ?>
                        <article class="maestria-card maestria-<?php echo htmlspecialchars($trilha['cor'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($infoPatente['classe'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="maestria-topo">
                                <div class="maestria-crest">
                                    <i class="fa-solid <?php echo htmlspecialchars($trilha['icone'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                    <span class="maestria-lvl">Nv. <?php echo (int) $infoPatente['nivel']; ?>/10</span>
                                </div>
                                <div class="maestria-head-info">
                                    <h3><?php echo htmlspecialchars($trilha['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="maestria-tier-badge">
                                        <?php echo htmlspecialchars($infoPatente['patente'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="maestria-corpo">
                                <div class="maestria-xp-row">
                                    <span>Progresso até Maestria 10</span>
                                    <strong>Nv. <?php echo (int) $infoPatente['nivel']; ?> / 10 (<?php echo $pct; ?>%)</strong>
                                </div>
                                <div class="maestria-bar-track" aria-hidden="true">
                                    <div class="maestria-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                                </div>
                            </div>

                            <?php if ($premio): ?>
                                <!-- Vitrine do Prêmio Exclusivo de Maestria 10 -->
                                <div class="maestria-premio-box">
                                    <div class="maestria-premio-header">
                                        <span><i class="fa-solid fa-gift"></i> PRÊMIO EXCLUSIVO · MAESTRIA 10</span>
                                    </div>
                                    <div class="loja-preview-fundo maestria-banner-preview visual-<?php echo htmlspecialchars($premio['visual'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                                    <div class="maestria-premio-identidade">
                                        <div class="loja-preview-avatar maestria-avatar-preview visual-<?php echo htmlspecialchars($premio['avatar_visual'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fa-solid <?php echo htmlspecialchars($premio['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                        </div>
                                        <div class="maestria-premio-texto">
                                            <strong><?php echo htmlspecialchars($premio['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small>Insígnia: <?php echo htmlspecialchars($premio['avatar_nome'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </div>
                                    </div>
                                    <p class="maestria-piada-desc"><?php echo htmlspecialchars($premio['piada'], ENT_QUOTES, 'UTF-8'); ?></p>

                                    <div class="maestria-premio-acoes">
                                        <button
                                            type="button"
                                            class="maestria-btn-provador"
                                            data-testar-combo-fundo="<?php echo htmlspecialchars($premio['visual'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-testar-combo-avatar="<?php echo htmlspecialchars($premio['avatar_visual'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-testar-combo-icone="<?php echo htmlspecialchars($premio['icone'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-testar-combo-nome="<?php echo htmlspecialchars($premio['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <i class="fa-regular fa-eye"></i> Provador
                                        </button>

                                        <?php if ($premioEquipado): ?>
                                            <span class="maestria-btn-equipado"><i class="fa-solid fa-check"></i> Equipado</span>
                                        <?php elseif ($jaDesbloqueouPremio || $infoPatente['nivel'] >= 10): ?>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="acao" value="resgatar_maestria">
                                                <input type="hidden" name="item_id" value="<?php echo (int) $premio['id']; ?>">
                                                <button type="submit" class="maestria-btn-resgatar">
                                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Equipar Prêmio Nv. 10
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="acao" value="resgatar_maestria">
                                                <input type="hidden" name="item_id" value="<?php echo (int) $premio['id']; ?>">
                                                <button type="submit" class="maestria-btn-resgatar" title="Desbloquear e equipar este conjunto de Maestria 10">
                                                    <i class="fa-solid fa-bolt"></i> Liberar &amp; Equipar Nv. 10
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ================= GUIA DE MISSÕES DE PONTOS & EXTRATO RECENTE ================= -->
            <section class="loja-secao" aria-labelledby="tituloGuiaPontos">
                <div class="loja-secao-titulo">
                    <div>
                        <span class="aluno-eyebrow">SISTEMA DE RECOMPENSAS ACADÊMICAS</span>
                        <h2 id="tituloGuiaPontos"><i class="fa-solid fa-coins"></i> Como Ganhar Mais Pontos &amp; Seu Extrato</h2>
                        <p class="secao-subtitulo">Participe ativamente das monitorias para acumular pontos mais rápido e subir suas maestrias.</p>
                    </div>
                </div>

                <div class="guia-pontos-layout">
                    <div class="missoes-pontos-grid">
                        <div class="missao-ponto-card">
                            <span class="missao-badge">+10 a +30 pts</span>
                            <strong><i class="fa-solid fa-user-check"></i> Presença Confirmada</strong>
                            <p>Proporcional à duração da sessão: 30min (+10), 45min (+15), 1h (+20) ou 1h30 (+30 pts).</p>
                        </div>
                        <div class="missao-ponto-card">
                            <span class="missao-badge">+5 pts</span>
                            <strong><i class="fa-solid fa-star"></i> Avaliar Monitoria</strong>
                            <p>Avalie o atendimento do monitor de 1 a 5 estrelas na Agenda após a sessão.</p>
                        </div>
                        <div class="missao-ponto-card">
                            <span class="missao-badge">+10 pts</span>
                            <strong><i class="fa-solid fa-award"></i> Bônus de Dedicação</strong>
                            <p>Concedido pelo monitor ao registrar sua participação exemplar na ata da aula.</p>
                        </div>
                        <div class="missao-ponto-card">
                            <span class="missao-badge">+5 pts</span>
                            <strong><i class="fa-solid fa-paperclip"></i> Aluno Preparado</strong>
                            <p>Anexe a lista de exercícios, código ou link da dúvida na monitoria agendada.</p>
                        </div>
                        <div class="missao-ponto-card">
                            <span class="missao-badge">+15 pts</span>
                            <strong><i class="fa-solid fa-id-card"></i> Perfil Completo</strong>
                            <p>Bônus único de boas-vindas ao preencher seu curso, semestre e apresentação no Perfil.</p>
                        </div>
                    </div>

                    <div class="extrato-pontos-box">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Últimas Movimentações</h3>
                        <?php if (!empty($extratoRecente)): ?>
                            <ul class="extrato-lista">
                                <?php
                                $rotulosEvento = [
                                    'participacao' => 'Presença em monitoria',
                                    'avaliacao' => 'Avaliação de monitoria',
                                    'material_apoio' => 'Material / dúvida enviada',
                                    'bonus_monitor' => 'Bônus de dedicação do monitor',
                                    'perfil_completo' => 'Bônus de perfil completo',
                                    'compra' => 'Desbloqueio na Lojinha',
                                    'cancelamento_tardio' => 'Cancelamento < 24h',
                                ];
                                ?>
                                <?php foreach ($extratoRecente as $mov): ?>
                                    <?php
                                    $ptsMov = (int) $mov['pontos'];
                                    $positivo = $ptsMov >= 0;
                                    $tituloMov = $rotulosEvento[$mov['evento']] ?? $mov['evento'];
                                    $detalheMov = $mov['item_nome'] ?: ($mov['assunto'] ?: 'Conta acadêmica');
                                    ?>
                                    <li>
                                        <div>
                                            <strong><?php echo htmlspecialchars($tituloMov, ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small><?php echo htmlspecialchars($detalheMov, ENT_QUOTES, 'UTF-8'); ?></small>
                                        </div>
                                        <span class="<?php echo $positivo ? 'pts-pos' : 'pts-neg'; ?>">
                                            <?php echo ($positivo ? '+' : '') . $ptsMov; ?> pts
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="extrato-vazio">Nenhuma movimentação registrada ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <p class="loja-nota">
                <i class="fa-solid fa-circle-info"></i> Todos os pontos ganhos geram uma notificação automática no sininho da sua barra superior.
            </p>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bannerBox = document.getElementById('livePreviewBanner');
            const avatarBox = document.getElementById('livePreviewAvatar');
            const statusLabel = document.getElementById('previewStatusLabel');

            document.querySelectorAll('[data-testar-combo-fundo]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const fundo = this.dataset.testarComboFundo;
                    const avatar = this.dataset.testarComboAvatar;
                    const icone = this.dataset.testarComboIcone;
                    const nome = this.dataset.testarComboNome;

                    if (bannerBox) {
                        bannerBox.className = 'loja-preview-fundo preview-banner-box visual-' + fundo;
                    }
                    if (avatarBox) {
                        avatarBox.className = 'loja-preview-avatar preview-avatar-box visual-' + avatar;
                        avatarBox.replaceChildren();
                        const i = document.createElement('i');
                        i.className = 'fa-solid ' + (icone || 'fa-user-graduate');
                        avatarBox.appendChild(i);
                    }
                    if (statusLabel) {
                        statusLabel.textContent = 'Testando: ' + nome;
                    }
                    bannerBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            });
        });
    </script>
</body>
</html>
