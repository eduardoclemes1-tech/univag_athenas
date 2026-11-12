<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');
require_once __DIR__ . '/../../config/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$usuarioId = (int) $_SESSION['usuario_id'];
$erros = [];
$sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] === '1';

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
    } elseif (!in_array($acao, ['comprar', 'equipar', 'remover'], true)) {
        $erros[] = 'Ação da loja inválida.';
    } elseif ($acao !== 'remover' && !$itemId) {
        $erros[] = 'Selecione um item válido.';
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
                $categoria = $_POST['categoria'] ?? '';
                if (!in_array($categoria, ['fundo', 'avatar'], true)) {
                    $pdo->rollBack();
                    $erros[] = 'Personalização inválida.';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM loja_equipamentos WHERE usuario_id = ? AND categoria = ?');
                    $stmt->execute([$usuarioId, $categoria]);
                    $pdo->commit();
                    header('Location: loja.php?sucesso=1');
                    exit;
                }
            } else {
                $stmt = $pdo->prepare(
                    'SELECT id, nome, categoria, preco
                     FROM loja_itens WHERE id = ? AND ativo = 1'
                );
                $stmt->execute([$itemId]);
                $item = $stmt->fetch();
                if (!$item) {
                    $pdo->rollBack();
                    $erros[] = 'Este item não está disponível na loja.';
                } elseif ($acao === 'comprar') {
                    $stmt = $pdo->prepare('SELECT item_id FROM loja_compras WHERE usuario_id = ? AND item_id = ?');
                    $stmt->execute([$usuarioId, $itemId]);
                    if ($stmt->fetch()) {
                        $pdo->rollBack();
                        $erros[] = 'Você já possui este item.';
                    } else {
                        $stmt = $pdo->prepare(
                            'SELECT COALESCE(SUM(pontos), 0) FROM pontos_transacoes WHERE usuario_id = ?'
                        );
                        $stmt->execute([$usuarioId]);
                        $saldo = (int) $stmt->fetchColumn();
                        if ($saldo < (int) $item['preco']) {
                            $pdo->rollBack();
                            $erros[] = 'Você ainda não tem pontos suficientes para este item.';
                        } else {
                            $stmt = $pdo->prepare('INSERT INTO loja_compras (usuario_id, item_id) VALUES (?, ?)');
                            $stmt->execute([$usuarioId, $itemId]);
                            $stmt = $pdo->prepare(
                                "INSERT INTO pontos_transacoes (usuario_id, item_id, evento, pontos)
                                 VALUES (?, ?, 'compra', ?)"
                            );
                            $stmt->execute([$usuarioId, $itemId, -(int) $item['preco']]);
                            $pdo->commit();
                            header('Location: loja.php?sucesso=1');
                            exit;
                        }
                    }
                } else {
                    $stmt = $pdo->prepare(
                        'SELECT 1 FROM loja_compras WHERE usuario_id = ? AND item_id = ?'
                    );
                    $stmt->execute([$usuarioId, $itemId]);
                    if (!$stmt->fetch()) {
                        $pdo->rollBack();
                        $erros[] = 'Compre o item antes de equipá-lo.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO loja_equipamentos (usuario_id, categoria, item_id)
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)'
                        );
                        $stmt->execute([$usuarioId, $item['categoria'], $itemId]);
                        $pdo->commit();
                        header('Location: loja.php?sucesso=1');
                        exit;
                    }
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($e->getMessage());
            $erros[] = 'Não foi possível concluir a operação da loja. Tente novamente.';
        }
    }
}

try {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(pontos), 0) FROM pontos_transacoes WHERE usuario_id = ?');
    $stmt->execute([$usuarioId]);
    $saldo = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT i.id, i.slug, i.nome, i.descricao, i.categoria, i.preco, i.visual,
                (c.item_id IS NOT NULL) AS possui,
                (e.item_id = i.id) AS equipado
         FROM loja_itens i
         LEFT JOIN loja_compras c ON c.item_id = i.id AND c.usuario_id = ?
         LEFT JOIN loja_equipamentos e
            ON e.usuario_id = ? AND e.categoria = i.categoria AND e.item_id = i.id
         WHERE i.ativo = 1
         ORDER BY FIELD(i.categoria, "fundo", "avatar"), i.preco, i.nome'
    );
    $stmt->execute([$usuarioId, $usuarioId]);
    $itens = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar a loja.');
}

$itensPorCategoria = ['fundo' => [], 'avatar' => []];
foreach ($itens as $item) {
    $itensPorCategoria[$item['categoria']][] = $item;
}
$imagensAvatares = [
    'asas-capelo' => '../../assets/img/loja/avatar-asas-capelo.svg',
    'capivara' => '../../assets/img/loja/avatar-capivara-caloura.svg',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lojinha - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/aluno.css?v=2">
    <link rel="stylesheet" href="../../assets/css/loja.css?v=2">
    <link rel="stylesheet" href="../../assets/css/personalizacao.css?v=1">
</head>
<body class="aluno-theme">
    <?php include '../../includes/navbar.php'; ?>
    <main class="aluno-main loja-main">
        <header class="loja-header">
            <div>
                <span class="aluno-eyebrow">PERSONALIZE SUA ÁREA</span>
                <h1>Lojinha Athenas</h1>
                <p>Use seus pontos para escolher fundos e avatares para o seu perfil.</p>
            </div>
            <div class="loja-saldo" aria-label="Seu saldo de pontos">
                <i class="fa-solid fa-star" aria-hidden="true"></i>
                <strong><?php echo $saldo; ?></strong>
                <span>pontos</span>
            </div>
        </header>

        <?php if ($sucesso): ?>
            <div class="loja-aviso loja-sucesso" role="status">Sua personalização foi atualizada.</div>
        <?php endif; ?>
        <?php if ($erros): ?>
            <div class="loja-aviso loja-erro" role="alert">
                <?php foreach ($erros as $erro): ?>
                    <p><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php foreach (['fundo' => 'Fundos de perfil', 'avatar' => 'Avatares de perfil'] as $categoria => $titulo): ?>
            <section class="loja-secao" aria-labelledby="loja-<?php echo $categoria; ?>">
                <div class="loja-secao-titulo">
                    <div>
                        <span class="aluno-eyebrow"><?php echo $categoria === 'fundo' ? 'CAPAS' : 'AVATARES'; ?></span>
                        <h2 id="loja-<?php echo $categoria; ?>"><?php echo $titulo; ?></h2>
                    </div>
                    <?php if ($categoria === 'fundo'): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="categoria" value="fundo">
                            <button class="loja-link-button" type="submit">Usar fundo padrão</button>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="categoria" value="avatar">
                            <button class="loja-link-button" type="submit">Usar foto enviada ou iniciais</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="loja-grade">
                    <?php foreach ($itensPorCategoria[$categoria] as $item): ?>
                        <article class="loja-item">
                            <?php if ($categoria === 'fundo'): ?>
                                <div class="loja-preview-fundo visual-<?php echo htmlspecialchars($item['visual'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
                                    <span>Seu perfil</span>
                                    <?php if (in_array($item['visual'], ['nebulosa', 'aurora-animada', 'estrelas'], true)): ?>
                                        <small class="loja-selo-animado"><i class="fa-solid fa-wand-magic-sparkles"></i> Animado</small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="loja-preview-avatar visual-<?php echo htmlspecialchars($item['visual'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
                                    <?php if (isset($imagensAvatares[$item['visual']])): ?>
                                        <img src="<?php echo htmlspecialchars($imagensAvatares[$item['visual']], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                    <?php else: ?>
                                        <i class="fa-solid fa-user-graduate"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="loja-item-info">
                                <h3><?php echo htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="loja-item-acao">
                                <?php if ((bool) $item['equipado']): ?>
                                    <span class="loja-equipado"><i class="fa-solid fa-check"></i> Em uso</span>
                                <?php elseif ((bool) $item['possui']): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="acao" value="equipar">
                                        <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                                        <button class="loja-botao" type="submit">Equipar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="acao" value="comprar">
                                        <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                                        <button class="loja-botao" type="submit">
                                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                                            <?php echo (int) $item['preco']; ?> pontos
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <p class="loja-nota">Você ganha 10 pontos quando você e o monitor confirmam participação na mesma monitoria.</p>
    </main>
</body>
</html>
