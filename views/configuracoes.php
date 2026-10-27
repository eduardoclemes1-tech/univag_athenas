<?php
require_once __DIR__ . '/../includes/auth.php';
exigirUsuarioAutenticado();
require_once __DIR__ . '/../config/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$tipoUsuario = $_SESSION['usuario_tipo'];
$urlVoltar = $tipoUsuario === 'aluno'
    ? 'aluno/inicio.php'
    : 'monitor/perfilMonitor.php';
$erros = [];
$sucesso = match ($_GET['salvo'] ?? '') {
    'senha' => 'Sua senha foi alterada.',
    'preferencias' => 'Suas preferências foram salvas.',
    default => '',
};
$abaAtiva = $_GET['aba'] ?? 'notificacoes';

try {
    $stmt = $pdo->prepare(
        'SELECT notificar_mensagens, notificar_agendamentos
         FROM usuarios WHERE id = ? AND tipo = ?'
    );
    $stmt->execute([$_SESSION['usuario_id'], $tipoUsuario]);
    $preferencias = $stmt->fetch();
    if (!$preferencias) {
        http_response_code(404);
        exit('Conta não encontrada.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar as configurações.');
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Método não permitido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $erros[] = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($acao === 'preferencias') {
        $mensagens = ($_POST['notificar_mensagens'] ?? '') === '1' ? 1 : 0;
        $agendamentos = ($_POST['notificar_agendamentos'] ?? '') === '1' ? 1 : 0;
        try {
            $stmt = $pdo->prepare(
                'UPDATE usuarios
                 SET notificar_mensagens = ?, notificar_agendamentos = ?
                 WHERE id = ? AND tipo = ?'
            );
            $stmt->execute([$mensagens, $agendamentos, $_SESSION['usuario_id'], $tipoUsuario]);
            header('Location: configuracoes.php?salvo=preferencias#notificacoes');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erros[] = 'Não foi possível salvar suas preferências. Tente novamente.';
        }
    } elseif ($acao === 'senha') {
        $abaAtiva = 'seguranca';
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $senhaNova = $_POST['senha_nova'] ?? '';
        $confirmacao = $_POST['confirmar_senha'] ?? '';
        if (!is_string($senhaAtual) || !is_string($senhaNova) || !is_string($confirmacao)) {
            $erros[] = 'Preencha os campos de senha corretamente.';
        } elseif ($senhaAtual === '' || $senhaNova === '' || $confirmacao === '') {
            $erros[] = 'Preencha todos os campos para alterar a senha.';
        } elseif (mb_strlen($senhaNova, 'UTF-8') < 6 || strlen($senhaNova) > 72) {
            $erros[] = 'A nova senha deve ter pelo menos 6 caracteres e no máximo 72 bytes.';
        } elseif ($senhaNova !== $confirmacao) {
            $erros[] = 'A confirmação não corresponde à nova senha.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT senha FROM usuarios WHERE id = ? AND tipo = ?');
                $stmt->execute([$_SESSION['usuario_id'], $tipoUsuario]);
                $hashAtual = $stmt->fetchColumn();
                if (!is_string($hashAtual) || !password_verify($senhaAtual, $hashAtual)) {
                    $erros[] = 'A senha atual está incorreta.';
                } elseif (password_verify($senhaNova, $hashAtual)) {
                    $erros[] = 'Escolha uma senha diferente da atual.';
                } else {
                    $hashNovo = password_hash($senhaNova, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ? AND tipo = ?');
                    $stmt->execute([$hashNovo, $_SESSION['usuario_id'], $tipoUsuario]);
                    session_regenerate_id(true);
                    header('Location: configuracoes.php?salvo=senha#seguranca');
                    exit;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $erros[] = 'Não foi possível alterar sua senha. Tente novamente.';
            }
        }
    } else {
        $erros[] = 'Ação de configuração inválida.';
    }

    if ($acao === 'preferencias') {
        $preferencias['notificar_mensagens'] = ($_POST['notificar_mensagens'] ?? '') === '1' ? 1 : 0;
        $preferencias['notificar_agendamentos'] = ($_POST['notificar_agendamentos'] ?? '') === '1' ? 1 : 0;
    }
}

$ehAluno = $tipoUsuario === 'aluno';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($ehAluno): ?>
        <link rel="stylesheet" href="../assets/css/aluno.css?v=2">
    <?php endif; ?>
    <link rel="stylesheet" href="../assets/css/configuracoes.css?v=1">
</head>
<body class="configuracoes-page<?php echo $ehAluno ? ' aluno-theme' : ''; ?>">
    <?php include '../includes/navbar.php'; ?>

    <main class="configuracoes-main">
        <div class="configuracoes-layout">
            <header class="configuracoes-cabecalho">
                <div>
                    <span class="configuracoes-eyebrow">SUA CONTA</span>
                    <h1>Configurações</h1>
                    <p>Gerencie seus avisos e mantenha sua conta protegida.</p>
                </div>
                <a class="configuracoes-voltar" href="<?php echo htmlspecialchars($urlVoltar, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Voltar
                </a>
            </header>

            <?php if ($sucesso !== ''): ?>
                <div class="configuracoes-feedback feedback-sucesso" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($erros): ?>
                <div class="configuracoes-feedback feedback-erro" role="alert">
                    <strong>Não foi possível concluir:</strong>
                    <ul>
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="configuracoes-card" id="notificacoes">
                <div class="configuracoes-card-heading">
                    <span class="configuracoes-icone"><i class="fa-regular fa-bell" aria-hidden="true"></i></span>
                    <div>
                        <h2>Notificações</h2>
                        <p>Escolha quais avisos quer receber no Athenas.</p>
                    </div>
                </div>
                <form method="post" class="configuracoes-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="acao" value="preferencias">
                    <label class="configuracao-opcao">
                        <span class="opcao-texto">
                            <strong>Mensagens do chat</strong>
                            <small>Avisos de novas mensagens e atualizações no chat.</small>
                        </span>
                        <input type="checkbox" name="notificar_mensagens" value="1" <?php echo (bool) $preferencias['notificar_mensagens'] ? 'checked' : ''; ?>>
                        <span class="configuracao-switch" aria-hidden="true"></span>
                    </label>
                    <label class="configuracao-opcao">
                        <span class="opcao-texto">
                            <strong>Agendamentos e monitorias</strong>
                            <small>Solicitações, confirmações, recusas e links de reunião.</small>
                        </span>
                        <input type="checkbox" name="notificar_agendamentos" value="1" <?php echo (bool) $preferencias['notificar_agendamentos'] ? 'checked' : ''; ?>>
                        <span class="configuracao-switch" aria-hidden="true"></span>
                    </label>
                    <div class="configuracoes-form-rodape">
                        <span>As conversas continuam disponíveis mesmo com avisos desativados.</span>
                        <button class="configuracoes-botao" type="submit">Salvar preferências</button>
                    </div>
                </form>
            </section>

            <section class="configuracoes-card" id="seguranca">
                <div class="configuracoes-card-heading">
                    <span class="configuracoes-icone icone-seguranca"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                    <div>
                        <h2>Segurança da conta</h2>
                        <p>Altere sua senha usando a senha atual para confirmar sua identidade.</p>
                    </div>
                </div>
                <form method="post" class="configuracoes-form configuracoes-senha-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="acao" value="senha">
                    <label class="configuracoes-campo">
                        <span>Senha atual</span>
                        <input type="password" name="senha_atual" autocomplete="current-password" required>
                    </label>
                    <div class="configuracoes-senha-grade">
                        <label class="configuracoes-campo">
                            <span>Nova senha</span>
                            <input type="password" name="senha_nova" minlength="6" maxlength="72" autocomplete="new-password" required>
                            <small>Pelo menos 6 caracteres.</small>
                        </label>
                        <label class="configuracoes-campo">
                            <span>Confirmar nova senha</span>
                            <input type="password" name="confirmar_senha" minlength="6" maxlength="72" autocomplete="new-password" required>
                        </label>
                    </div>
                    <div class="configuracoes-form-rodape">
                        <span>Sua senha nunca é exibida nem armazenada em texto aberto.</span>
                        <button class="configuracoes-botao" type="submit">Alterar senha</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
