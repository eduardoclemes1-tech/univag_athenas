<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');
require_once __DIR__ . '/../../config/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erros = [];
$salvo = isset($_GET['salvo']) && $_GET['salvo'] === '1';
$dadosFormulario = [
    'nome' => $_SESSION['usuario_nome'] ?? '',
    'bio' => '',
    'curso' => '',
    'periodo' => '',
];

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Método não permitido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $erros[] = 'Sua sessão expirou. Atualize a página e tente novamente.';
    }

    foreach (array_keys($dadosFormulario) as $campo) {
        $valor = $_POST[$campo] ?? '';
        $dadosFormulario[$campo] = is_string($valor) ? trim($valor) : '';
    }

    if (mb_strlen($dadosFormulario['nome'], 'UTF-8') < 3 || mb_strlen($dadosFormulario['nome'], 'UTF-8') > 100) {
        $erros[] = 'O nome deve ter entre 3 e 100 caracteres.';
    }
    if (mb_strlen($dadosFormulario['bio'], 'UTF-8') > 500) {
        $erros[] = 'A apresentação deve ter no máximo 500 caracteres.';
    }
    if (mb_strlen($dadosFormulario['curso'], 'UTF-8') > 120) {
        $erros[] = 'O curso deve ter no máximo 120 caracteres.';
    }
    if (mb_strlen($dadosFormulario['periodo'], 'UTF-8') > 40) {
        $erros[] = 'O período deve ter no máximo 40 caracteres.';
    }

    $fotoBinaria = null;
    $mimeFoto = null;
    $removerFoto = ($_POST['remover_foto'] ?? '') === '1';
    $arquivoFoto = $_FILES['foto_perfil'] ?? null;
    $enviouFoto = is_array($arquivoFoto) && ($arquivoFoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($removerFoto && $enviouFoto) {
        $erros[] = 'Escolha entre enviar uma nova foto ou remover a atual.';
    }

    if ($enviouFoto) {
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
            if ($fotoBinaria !== null) {
                $stmt = $pdo->prepare(
                    'UPDATE usuarios SET nome = ?, bio = ?, curso = ?, periodo = ?, foto_perfil = ?, foto_mime = ?
                     WHERE id = ? AND tipo = ?'
                );
                $stmt->execute([
                    $dadosFormulario['nome'],
                    $dadosFormulario['bio'],
                    $dadosFormulario['curso'],
                    $dadosFormulario['periodo'],
                    $fotoBinaria,
                    $mimeFoto,
                    $_SESSION['usuario_id'],
                    'aluno',
                ]);
            } elseif ($removerFoto) {
                $stmt = $pdo->prepare(
                    'UPDATE usuarios SET nome = ?, bio = ?, curso = ?, periodo = ?, foto_perfil = NULL, foto_mime = NULL
                     WHERE id = ? AND tipo = ?'
                );
                $stmt->execute([
                    $dadosFormulario['nome'],
                    $dadosFormulario['bio'],
                    $dadosFormulario['curso'],
                    $dadosFormulario['periodo'],
                    $_SESSION['usuario_id'],
                    'aluno',
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE usuarios SET nome = ?, bio = ?, curso = ?, periodo = ?
                     WHERE id = ? AND tipo = ?'
                );
                $stmt->execute([
                    $dadosFormulario['nome'],
                    $dadosFormulario['bio'],
                    $dadosFormulario['curso'],
                    $dadosFormulario['periodo'],
                    $_SESSION['usuario_id'],
                    'aluno',
                ]);
            }

            $_SESSION['usuario_nome'] = $dadosFormulario['nome'];
            header('Location: perfil.php?salvo=1');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erros[] = 'Não foi possível salvar seu perfil. Tente novamente.';
        }
    }
}

try {
    $stmt = $pdo->prepare(
        'SELECT nome, email, bio, curso, periodo, foto_mime, criado_em
         FROM usuarios WHERE id = ? AND tipo = ?'
    );
    $stmt->execute([$_SESSION['usuario_id'], 'aluno']);
    $aluno = $stmt->fetch();
    if (!$aluno) {
        http_response_code(404);
        exit('Perfil de aluno não encontrado.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar seu perfil.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $dadosFormulario = [
        'nome' => $aluno['nome'],
        'bio' => $aluno['bio'],
        'curso' => $aluno['curso'],
        'periodo' => $aluno['periodo'],
    ];
}
$inicialNome = mb_strtoupper(mb_substr($aluno['nome'], 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/aluno.css?v=2">
    <link rel="stylesheet" href="../../assets/css/perfilAluno.css?v=1">
</head>
<body class="aluno-theme">
    <?php include '../../includes/navbar.php'; ?>

    <main class="aluno-main perfil-aluno-main">
        <div class="aluno-layout">
            <header class="perfil-aluno-topo">
                <div>
                    <span class="aluno-eyebrow">SUA ÁREA</span>
                    <h1>Meu perfil</h1>
                    <p>Apresente-se e mantenha suas informações acadêmicas atualizadas.</p>
                </div>
                <a class="perfil-voltar-painel" href="inicio.php">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Voltar ao painel
                </a>
            </header>

            <?php if ($salvo): ?>
                <div class="perfil-aviso perfil-sucesso" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Seu perfil foi atualizado.
                </div>
            <?php endif; ?>
            <?php if ($erros): ?>
                <div class="perfil-aviso perfil-erro" role="alert">
                    <strong>Confira estes dados:</strong>
                    <ul>
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="perfil-aluno-card" aria-label="Resumo do perfil">
                <div class="perfil-aluno-capa" aria-hidden="true"></div>
                <div class="perfil-aluno-resumo">
                    <?php if ($aluno['foto_mime']): ?>
                        <img
                            class="perfil-aluno-foto"
                            src="../../actions/fotoPerfil.php"
                            alt="Foto de perfil de <?php echo htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    <?php else: ?>
                        <div class="perfil-aluno-foto perfil-aluno-inicial" aria-hidden="true">
                            <?php echo htmlspecialchars($inicialNome, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="perfil-aluno-identidade">
                        <span class="perfil-aluno-tag"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Aluno</span>
                        <h2><?php echo htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?php echo htmlspecialchars($aluno['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($aluno['curso'] || $aluno['periodo']): ?>
                            <span class="perfil-aluno-curso">
                                <?php echo htmlspecialchars(implode(' · ', array_filter([$aluno['curso'], $aluno['periodo']])), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <a class="perfil-editar-atalho" href="#editar-perfil">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i> Editar perfil
                    </a>
                </div>
                <div class="perfil-aluno-bio">
                    <span>Sobre mim</span>
                    <p><?php echo $aluno['bio']
                        ? nl2br(htmlspecialchars($aluno['bio'], ENT_QUOTES, 'UTF-8'))
                        : 'Adicione uma apresentação para que as pessoas conheçam um pouco mais sobre você.'; ?></p>
                </div>
            </section>

            <section class="perfil-edicao-card" id="editar-perfil">
                <div class="perfil-secao-titulo">
                    <div>
                        <span class="aluno-eyebrow">PERSONALIZE SUA CONTA</span>
                        <h2>Editar informações</h2>
                    </div>
                    <i class="fa-regular fa-id-card" aria-hidden="true"></i>
                </div>
                <form method="post" enctype="multipart/form-data" class="perfil-formulario">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="perfil-form-grade">
                        <label class="perfil-campo perfil-campo-foto" for="fotoPerfil">
                            <span>Foto de perfil</span>
                            <small>JPG, PNG ou WEBP · até 2 MB</small>
                            <span class="perfil-upload-controle">
                                <i class="fa-regular fa-image" aria-hidden="true"></i>
                                <span id="nomeFotoSelecionada">Escolher uma foto</span>
                            </span>
                            <input id="fotoPerfil" type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp">
                        </label>

                        <label class="perfil-campo" for="nomeAluno">
                            <span>Nome</span>
                            <input id="nomeAluno" type="text" name="nome" value="<?php echo htmlspecialchars($dadosFormulario['nome'], ENT_QUOTES, 'UTF-8'); ?>" minlength="3" maxlength="100" required>
                        </label>

                        <label class="perfil-campo perfil-campo-largo" for="bioAluno">
                            <span>Sobre mim</span>
                            <textarea id="bioAluno" name="bio" rows="4" maxlength="500" placeholder="Conte um pouco sobre você e seus objetivos de estudo."><?php echo htmlspecialchars($dadosFormulario['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <small><span id="contadorBio"><?php echo mb_strlen($dadosFormulario['bio'], 'UTF-8'); ?></span>/500 caracteres</small>
                        </label>

                        <label class="perfil-campo" for="cursoAluno">
                            <span>Curso</span>
                            <input id="cursoAluno" type="text" name="curso" value="<?php echo htmlspecialchars($dadosFormulario['curso'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120" placeholder="Ex.: Sistemas de Informação">
                        </label>

                        <label class="perfil-campo" for="periodoAluno">
                            <span>Período ou semestre</span>
                            <input id="periodoAluno" type="text" name="periodo" value="<?php echo htmlspecialchars($dadosFormulario['periodo'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="40" placeholder="Ex.: 3º semestre">
                        </label>
                    </div>

                    <?php if ($aluno['foto_mime']): ?>
                        <label class="perfil-remover-foto">
                            <input type="checkbox" name="remover_foto" value="1">
                            <span>Remover minha foto atual</span>
                        </label>
                    <?php endif; ?>

                    <div class="perfil-form-rodape">
                        <p><i class="fa-solid fa-lock" aria-hidden="true"></i> Seus dados de perfil ficam associados à sua conta.</p>
                        <button class="perfil-salvar" type="submit">
                            <i class="fa-solid fa-check" aria-hidden="true"></i> Salvar alterações
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <script>
        const profilePhotoInput = document.getElementById('fotoPerfil');
        profilePhotoInput.addEventListener('change', function () {
            document.getElementById('nomeFotoSelecionada').textContent =
                this.files.length ? this.files[0].name : 'Escolher uma foto';
        });

        const bioField = document.getElementById('bioAluno');
        bioField.addEventListener('input', function () {
            document.getElementById('contadorBio').textContent = String(this.value.length);
        });
    </script>
</body>
</html>
