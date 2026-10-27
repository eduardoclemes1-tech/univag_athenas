<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');

$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Aluno';
$primeiroNome = explode(' ', trim($nomeUsuario))[0] ?: 'Aluno';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início do aluno - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/aluno.css?v=2">
</head>
<body class="aluno-theme">
    <?php include '../../includes/navbar.php'; ?>

    <main class="aluno-main">
        <div class="aluno-layout">
            <div class="aluno-banner" aria-hidden="true"></div>
            <div class="aluno-content">
                <section class="aluno-boas-vindas">
                    <div>
                        <span class="aluno-eyebrow">ÁREA DO ALUNO</span>
                        <h1>Olá, <?php echo htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8'); ?>!</h1>
                        <p>Encontre apoio para seus estudos e organize suas próximas monitorias.</p>
                        <div class="aluno-acoes">
                            <a class="aluno-botao aluno-botao-principal" href="monitores.php">
                                <i class="fa-solid fa-magnifying-glass"></i> Explorar monitorias
                            </a>
                            <a class="aluno-botao aluno-botao-secundario" href="../agenda/index.php">
                                <i class="fa-regular fa-calendar"></i> Ver minha agenda
                            </a>
                            <a class="aluno-botao aluno-botao-secundario" href="materias.php">
                                <i class="fa-solid fa-book-open"></i> Explorar matérias
                            </a>
                        </div>
                    </div>
                    <div class="aluno-hero-icone" aria-hidden="true">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </section>

                <section class="aluno-resumo" aria-label="Resumo do aluno">
                    <article class="aluno-card resumo-card">
                        <span class="resumo-icone icone-azul"><i class="fa-solid fa-calendar-check"></i></span>
                        <div>
                            <span class="resumo-label">Próximas monitorias</span>
                            <strong>Consulte sua agenda</strong>
                            <a href="../agenda/index.php">Abrir calendário <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>
                    <article class="aluno-card resumo-card">
                        <span class="resumo-icone icone-verde"><i class="fa-solid fa-award"></i></span>
                        <div>
                            <span class="resumo-label">Horas complementares</span>
                            <strong>Acompanhe seu progresso</strong>
                            <p>Suas horas validadas serão exibidas aqui.</p>
                        </div>
                    </article>
                    <article class="aluno-card resumo-card">
                        <span class="resumo-icone icone-roxo"><i class="fa-solid fa-user-group"></i></span>
                        <div>
                            <span class="resumo-label">Precisa de ajuda?</span>
                            <strong>Encontre um monitor</strong>
                            <a href="monitores.php">Ver monitorias <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>
                </section>

                <section class="aluno-paineis">
                    <article class="aluno-card painel-agenda">
                        <div class="painel-titulo">
                            <div>
                                <span class="aluno-eyebrow">ORGANIZAÇÃO</span>
                                <h2>Suas próximas atividades</h2>
                            </div>
                            <a href="../agenda/index.php">Ver agenda <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div class="aluno-estado-vazio">
                            <span class="estado-icone"><i class="fa-regular fa-calendar"></i></span>
                            <h3>Consulte sua agenda</h3>
                            <p>Veja monitorias, aulas e atividades no calendário.</p>
                            <a class="aluno-botao aluno-botao-secundario" href="../agenda/index.php">Abrir calendário</a>
                        </div>
                    </article>

                    <aside class="aluno-card painel-dica">
                        <span class="dica-icone"><i class="fa-solid fa-lightbulb"></i></span>
                        <span class="aluno-eyebrow">DICA DE ESTUDO</span>
                        <h2>Uma dúvida de cada vez.</h2>
                        <p>Explore as monitorias disponíveis e encontre apoio para os conteúdos que quer reforçar.</p>
                        <a href="monitores.php">Conhecer monitorias <i class="fa-solid fa-arrow-right"></i></a>
                    </aside>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
