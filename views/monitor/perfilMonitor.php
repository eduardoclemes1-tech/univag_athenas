<?php
session_start();

// Dados simulados do monitor para exibição no perfil
$monitor = [
    'nome'              => $_SESSION['usuario_nome'] ?? 'Marcos Silva Pereira',
    'status'            => 'Monitor Ativo',
    'tipo'              => 'Estudante',
    'materia_principal' => 'Banco de Dados',
    'slogan'            => 'Juntos o aprendizado vai mais longe!',
    'alunos_auxiliados' => 24,
    'avaliacao'         => '4,8/5',
    'sobre'             => 'Sou estudante e monitor da área de Banco de Dados. Gosto de ajudar a entender o conteúdo de forma simples e prática, buscando tornar o aprendizado tranquilo.',
    'tags'              => ['SQL', 'Modelagem de Banco de Dados', 'DER', 'Normalização', 'Procedures e Functions', 'Triggers'],
    'desde_ano'         => '2026',
    'atendimentos_total'=> 32,
    'alunos_total'      => 27
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Monitor - Univag Athenas</title>
    
    <!-- Ícones FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- CSS do Perfil do Monitor -->
    <link rel="stylesheet" href="../../assets/css/perfilMonitor.css">
</head>
<body>

    <!-- Inclui a Barra de Navegação -->
    <?php require_once '../../includes/navbar.php'; ?>

    <main class="page-container">
        <!-- Banner do Monitor -->
        <section class="card profile-header-card">
            <div class="profile-avatar-container">
                <div class="profile-avatar">M</div>
                <span class="status-dot-online"></span>
            </div>

            <div class="profile-info-main">
                <div class="profile-title-row">
                    <h2><?php echo htmlspecialchars($monitor['nome']); ?></h2>
                    <span class="badge badge-green"><i class="fa-solid fa-circle text-xs"></i> <?php echo $monitor['status']; ?></span>
                    <span class="badge badge-gray"><i class="fa-solid fa-graduation-cap"></i> <?php echo $monitor['tipo']; ?></span>
                </div>

                <div class="profile-subtitle">
                    <i class="fa-solid fa-database"></i>
                    <span>Monitor de <?php echo $monitor['materia_principal']; ?></span>
                </div>

                <p class="profile-slogan"><?php echo $monitor['slogan']; ?></p>
            </div>

            <button class="btn-outline-blue btn-edit-profile">
                <i class="fa-solid fa-pen"></i> Editar Perfil
            </button>
        </section>

        <!-- Métricas Rápidas -->
        <section class="card metrics-card">
            <div class="metric-item">
                <i class="fa-solid fa-book-open metric-icon"></i>
                <div class="metric-text">
                    <span class="metric-label">Matéria</span>
                    <strong class="metric-value"><?php echo $monitor['materia_principal']; ?></strong>
                </div>
            </div>

            <div class="metric-item">
                <i class="fa-solid fa-users metric-icon"></i>
                <div class="metric-text">
                    <span class="metric-label">Alunos auxiliados</span>
                    <strong class="metric-value"><?php echo $monitor['alunos_auxiliados']; ?></strong>
                </div>
            </div>

            <div class="metric-item">
                <i class="fa-solid fa-star metric-icon yellow-star"></i>
                <div class="metric-text">
                    <span class="metric-label">Avaliações</span>
                    <strong class="metric-value"><?php echo $monitor['avaliacao']; ?></strong>
                </div>
            </div>

            <div class="metric-item">
                <i class="fa-solid fa-stopwatch metric-icon"></i>
                <div class="metric-text">
                    <span class="metric-label">Disponibilidade</span>
                    <strong class="metric-value text-green">
                        <i class="fa-solid fa-circle text-xs"></i> Online agora
                    </strong>
                    <small class="metric-subtext">Pode chamar no chat!</small>
                </div>
            </div>
        </section>

        <!-- Grid Principal de Conteúdo -->
        <div class="profile-grid">
            
            <!-- Coluna 1: Sobre mim e Tags -->
            <div class="card card-section">
                <div class="section-title">
                    <i class="fa-regular fa-user"></i>
                    <h3>Sobre mim</h3>
                </div>
                <p class="about-text"><?php echo $monitor['sobre']; ?></p>

                <div class="section-title margin-top-md">
                    <i class="fa-solid fa-book-open"></i>
                    <h3>Conteúdos de auxílio</h3>
                </div>
                <div class="tags-container">
                    <?php foreach ($monitor['tags'] as $tag): ?>
                        <span class="tag-pill"><?php echo $tag; ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="section-title margin-top-md">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <h3>Formação e experiência</h3>
                </div>
                <div class="experience-subgrid">
                    <div class="exp-item">
                        <i class="fa-solid fa-calendar-days exp-icon"></i>
                        <div>
                            <small>Desde</small>
                            <strong><?php echo $monitor['desde_ano']; ?></strong>
                            <small class="block-sub">Como monitor</small>
                        </div>
                    </div>

                    <div class="exp-item">
                        <i class="fa-regular fa-comments exp-icon"></i>
                        <div>
                            <small>Atendimentos</small>
                            <strong><?php echo $monitor['atendimentos_total']; ?></strong>
                            <small class="block-sub">Desde o início</small>
                        </div>
                    </div>

                    <div class="exp-item">
                        <i class="fa-solid fa-users exp-icon"></i>
                        <div>
                            <small>Auxiliados</small>
                            <strong><?php echo $monitor['alunos_total']; ?></strong>
                            <small class="block-sub">Desde o início</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna 2: Feedbacks -->
            <div class="card card-section">
                <div class="section-title">
                    <i class="fa-regular fa-comments"></i>
                    <h3>Feedbacks</h3>
                </div>

                <div class="feedback-list">
                    <div class="feedback-box">
                        <div class="feedback-user-icon"><i class="fa-regular fa-user"></i></div>
                        <div class="feedback-body">
                            <div class="stars">★★★★★</div>
                            <p class="feedback-quote">“Explicou o conteúdo de um jeito que consegui entender. Super paciente!”</p>
                            <span class="feedback-author">— Gabriel S. • 10/04/2026</span>
                        </div>
                    </div>

                    <div class="feedback-box">
                        <div class="feedback-user-icon"><i class="fa-regular fa-user"></i></div>
                        <div class="feedback-body">
                            <div class="stars">★★★★★</div>
                            <p class="feedback-quote">“Muito didático! Me ajudou a ver o conteúdo de outra forma. Recomendo!”</p>
                            <span class="feedback-author">— Mariana L. • 28/03/2026</span>
                        </div>
                    </div>

                    <div class="feedback-box">
                        <div class="feedback-user-icon"><i class="fa-regular fa-user"></i></div>
                        <div class="feedback-body">
                            <div class="stars">★★★★★</div>
                            <p class="feedback-quote">“O melhor mentor! Explica com calma e se importa com o nosso aprendizado.”</p>
                            <span class="feedback-author">— Lucas T. • 15/03/2026</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna 3: Horários de Atendimento e Ações -->
            <div class="card card-section">
                <div class="section-title">
                    <i class="fa-regular fa-calendar"></i>
                    <h3>Horários de atendimento</h3>
                </div>

                <ul class="schedule-list">
                    <li><span>Segunda</span> <strong class="text-green"><i class="fa-solid fa-circle text-xs"></i> Disponível</strong> <span>19h às 21h</span></li>
                    <li><span>Terça</span> <strong class="text-green"><i class="fa-solid fa-circle text-xs"></i> Disponível</strong> <span>19h às 21h</span></li>
                    <li><span>Quarta</span> <strong class="text-green"><i class="fa-solid fa-circle text-xs"></i> Disponível</strong> <span>14h às 16h</span></li>
                    <li><span>Quinta</span> <strong class="text-green"><i class="fa-solid fa-circle text-xs"></i> Disponível</strong> <span>15h às 18h</span></li>
                    <li><span>Sexta</span> <strong class="text-gray"><i class="fa-solid fa-circle text-xs"></i> Indisponível</strong> <span>—</span></li>
                    <li><span>Sábado</span> <strong class="text-gray"><i class="fa-solid fa-circle text-xs"></i> Indisponível</strong> <span>—</span></li>
                    <li><span>Domingo</span> <strong class="text-gray"><i class="fa-solid fa-circle text-xs"></i> Indisponível</strong> <span>—</span></li>
                </ul>

                <div class="section-title margin-top-md">
                    <i class="fa-regular fa-comment-dots"></i>
                    <h3>Fale comigo</h3>
                </div>

                <div class="action-buttons">
                    <button class="btn-solid-blue">
                        <i class="fa-regular fa-comment-dots"></i> Iniciar conversa
                    </button>
                    <button class="btn-light-blue">
                        <i class="fa-regular fa-calendar-check"></i> Agendar Atendimento
                    </button>
                </div>
            </div>

        </div>
    </main>

</body>
</html>