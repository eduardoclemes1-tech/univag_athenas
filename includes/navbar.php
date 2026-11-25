<?php
/* ===== 1. INICIALIZAÇÃO DE SESSÃO E RECUPERAÇÃO DE DADOS ===== */
// Inicializa a sessão do PHP caso não tenha sido iniciada pela página principal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// As páginas que incluem a navegação validam a sessão antes de renderizá-la.
$nomeUsuario  = $_SESSION['usuario_nome'] ?? 'Usuário';
$emailUsuario = $_SESSION['usuario_email'] ?? '';
$tipoUsuario  = $_SESSION['usuario_tipo'] ?? 'aluno';
$inicialNome  = mb_strtoupper(mb_substr($nomeUsuario, 0, 1, 'UTF-8'), 'UTF-8');

/* ===== 2. CÁLCULO DINÂMICO DO CAMINHO BASE (PREVINE ERRO 404) ===== */
// Analisa o caminho da página atual para calcular o número de níveis até a raiz 'univag_athenas'
$scriptPath  = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
$univagIndex = array_search('univag_athenas', $scriptPath);

if ($univagIndex !== false) {
    $depth = count($scriptPath) - 2 - $univagIndex;
} else {
    $depth = count($scriptPath) - 1;
}
$basePath = ($depth > 0) ? str_repeat('../', $depth) : './';

$navTemFoto = false;
$navAvatarVisual = '';
$navAvatarImg = '';
require_once __DIR__ . '/gamificacao.php';
$mapaIconesNav = athenasIconesAvatares();
if (!empty($_SESSION['usuario_id'])) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/../config/conexao.php';
        }
        $stmtNav = $pdo->prepare(
            "SELECT u.foto_mime,
                    (SELECT i.visual
                     FROM loja_equipamentos e JOIN loja_itens i ON i.id = e.item_id
                     WHERE e.usuario_id = u.id AND e.categoria = 'avatar' AND i.ativo = 1
                     LIMIT 1) AS avatar_visual
             FROM usuarios u WHERE u.id = ?"
        );
        $stmtNav->execute([(int) $_SESSION['usuario_id']]);
        $dadosNav = $stmtNav->fetch();
        if ($dadosNav) {
            $navTemFoto = !empty($dadosNav['foto_mime']);
            $avataresValidos = athenasAvataresPermitidos();
            if (in_array($dadosNav['avatar_visual'] ?? '', $avataresValidos, true)) {
                $navAvatarVisual = $dadosNav['avatar_visual'];
                $navAvatarImg = '';
            }
        }
    } catch (Throwable $e) {
        // Falha silenciosa para não interromper a renderização da barra
    }
}

/* ===== 3. MAPEAMENTO DE ROTAS DA NAVBAR ===== */
// Rotas dinâmicas de perfil conforme o tipo de usuário logado (aluno ou monitor)
if ($tipoUsuario === 'aluno') {
    $urlHome   = $basePath . 'views/aluno/inicio.php';
    $urlPerfil = $basePath . 'views/aluno/perfil.php';
    $urlPainel = $basePath . 'views/aluno/inicio.php';
} else {
    $urlHome   = $basePath . 'views/monitor/inicio.php';
    $urlPerfil = $basePath . 'views/monitor/perfilMonitor.php';
    $urlPainel = $urlHome;
}

// Rota oficial apontando para o arquivo de Calendário criado em views/agenda/index.php
$urlAgenda = $basePath . 'views/agenda/index.php';
$urlConfiguracoes = $basePath . 'views/configuracoes.php';
$urlExplorarMonitores = $basePath . 'views/aluno/monitores.php';
$urlMaterias = $basePath . 'views/aluno/materias.php';
$urlLoja = $basePath . 'views/aluno/loja.php';

// Rotas exclusivas da gestão acadêmica do Monitor
$urlHoras        = $basePath . 'views/monitor/horasComplementares.php';
$urlCertificados = $basePath . 'views/monitor/certificados.php';

// Ações reais do sistema e assets estáticos
$urlLogout = $basePath . 'actions/logout.php';
$urlLogo   = $basePath . 'assets/img/logoAthenas.png';
$urlCss    = $basePath . 'assets/css/navbar.css?v=11';
$urlChatCss = $basePath . 'assets/css/mensagens.css?v=4';
$urlBuscaGlobal = $basePath . 'actions/buscaGlobal.php';
?>

<!-- ===== 4. CARREGAMENTO DO CSS DA NAVBAR ===== -->
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlCss); ?>">
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlChatCss); ?>">

<nav class="navbar-container">
    <div class="navbar-content">

        <!-- ===== 5. LOGO DO SISTEMA ===== -->
        <div class="navbar-logo">
            <a href="<?php echo htmlspecialchars($urlHome); ?>">
                <img src="<?php echo htmlspecialchars($urlLogo); ?>" alt="UNIVAG ATHENAS" class="logo-img">
            </a>
        </div>

        <!-- ===== 6. BARRA DE PESQUISA INTELIGENTE (EXPLORAR + BUSCA VIVA) ===== -->
        <div
            class="navbar-search-wrapper"
            id="navbarSearchWrapper"
            data-search-api="<?php echo htmlspecialchars($urlBuscaGlobal, ENT_QUOTES, 'UTF-8'); ?>"
            data-base-path="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>"
            data-user-type="<?php echo htmlspecialchars($tipoUsuario, ENT_QUOTES, 'UTF-8'); ?>"
        >
            <div class="navbar-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    type="text"
                    id="searchInput"
                    placeholder="<?php echo $tipoUsuario === 'aluno' ? 'Buscar monitores, alunos ou matérias...' : 'Buscar alunos, monitores/orientadores ou sessões...'; ?>"
                    autocomplete="off"
                >
                <kbd class="search-kbd" id="searchKbd">Ctrl+K</kbd>
                <button type="button" class="btn-clear-search" id="btnClearSearch" aria-label="Limpar busca">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Painel Inteligente de Busca e Comando -->
            <div class="search-dropdown" id="searchDropdown">
                <div class="dropdown-header">
                    <div>
                        <h3 id="searchDropdownTitle"><?php echo $tipoUsuario === 'monitor' ? 'Central do Monitor & Busca' : 'Explorar & Buscar'; ?></h3>
                        <p id="searchDropdownSubtitle"><?php echo $tipoUsuario === 'monitor' ? 'Localize alunos, orientadores, sessões ou execute ações rápidas' : 'Encontre monitores, colegas, disciplinas e atendimentos'; ?></p>
                    </div>
                    <button type="button" class="btn-close-dropdown" id="btnCloseDropdown" aria-label="Fechar busca">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Abas de Filtro da Busca -->
                <div class="search-filter-tabs" id="searchFilterTabs">
                    <button type="button" class="search-tab-btn active" data-search-tab="todos">Todos</button>
                    <button type="button" class="search-tab-btn" data-search-tab="alunos">
                        <i class="fa-solid fa-user-graduate"></i> Alunos
                    </button>
                    <button type="button" class="search-tab-btn" data-search-tab="monitores">
                        <i class="fa-solid fa-chalkboard-user"></i> Monitores / Prof.
                    </button>
                    <button type="button" class="search-tab-btn" data-search-tab="atendimentos">
                        <i class="fa-regular fa-calendar-check"></i> Sessões &amp; Matérias
                    </button>
                </div>

                <!-- Grade de Ações Rápidas (Exibida quando o campo está vazio na aba Todos) -->
                <div id="categoriesSection">
                    <div class="results-section-title">
                        <span><?php echo $tipoUsuario === 'monitor' ? 'Ferramentas Exclusivas do Monitor' : 'Trilhas e Áreas Acadêmicas'; ?></span>
                    </div>
                    <div class="dropdown-grid" id="categoriesGrid">
                        <?php if ($tipoUsuario === 'aluno'): ?>
                        <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Programação'); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-code"></i></div>
                            <span>Programação</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Dados'); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-database"></i></div>
                            <span>Dados &amp; SQL</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Desenvolvimento'); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-window-maximize"></i></div>
                            <span>Web &amp; Mobile</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Infraestrutura'); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-network-wired"></i></div>
                            <span>Redes &amp; Infra</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlMaterias); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-book-open"></i></div>
                            <span>Catálogo de Matérias</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlLoja); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-store"></i></div>
                            <span>Lojinha Athenas</span>
                        </a>
                        <?php else: ?>
                        <button type="button" class="category-card" data-agendar-com-aluno="">
                            <div class="category-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                            <span>Agendar com Aluno</span>
                            <small>Criar sessão direta</small>
                        </button>
                        <a href="<?php echo htmlspecialchars($urlAgenda); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                            <span>Validar Presenças</span>
                            <small id="quickMonitorPendencias">Agenda e registros</small>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlPerfil); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-business-time"></i></div>
                            <span>Meus Horários</span>
                            <small>Grade e bloqueios</small>
                        </a>
                        <button type="button" class="category-card" data-abrir-chat>
                            <div class="category-icon"><i class="fa-solid fa-comments"></i></div>
                            <span>Sala de Dúvidas</span>
                            <small>Atender no chat</small>
                        </button>
                        <a href="<?php echo htmlspecialchars($urlHoras); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <span>Minhas Horas</span>
                            <small>Progresso semestral</small>
                        </a>
                        <a href="<?php echo htmlspecialchars($urlCertificados); ?>" class="category-card">
                            <div class="category-icon"><i class="fa-solid fa-award"></i></div>
                            <span>Emitir Certificados</span>
                            <small>PDF autenticado</small>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resultados Dinâmicos em Tempo Real (Alunos, Monitores, Sessões e Matérias) -->
                <div class="search-live-results" id="liveResults"></div>
            </div>
        </div>

        <!-- ===== 7. ÍCONES DE AÇÃO E DROPDOWNS ===== -->
        <div class="navbar-actions">
            <!-- Botão Início (Universal para Aluno e Monitor) -->
            <a href="<?php echo htmlspecialchars($urlHome); ?>" class="nav-icon-btn" title="Início">
                <i class="fa-solid fa-house"></i>
            </a>

            <?php if ($tipoUsuario === 'aluno'): ?>
            <!-- Ícones exclusivos da visão do Aluno (Matérias, Explorar Monitores e Lojinha) -->
            <a href="<?php echo htmlspecialchars($urlMaterias); ?>" class="nav-icon-btn" title="Explorar matérias">
                <i class="fa-solid fa-book-bookmark"></i>
            </a>
            <a href="<?php echo htmlspecialchars($urlExplorarMonitores); ?>" class="nav-icon-btn" title="Explorar monitorias">
                <i class="fa-solid fa-user-graduate"></i>
            </a>
            <a href="<?php echo htmlspecialchars($urlLoja); ?>" class="nav-icon-btn" title="Lojinha & Maestrias">
                <i class="fa-solid fa-store"></i>
            </a>
            <?php else: ?>
            <!-- Ícones de gestão acadêmica do Monitor direto na Navbar -->
            <a href="<?php echo htmlspecialchars($urlHoras); ?>" class="nav-icon-btn" title="Horas Complementares" aria-label="Horas Complementares">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </a>
            <a href="<?php echo htmlspecialchars($urlCertificados); ?>" class="nav-icon-btn" title="Meus Certificados" aria-label="Meus Certificados">
                <i class="fa-solid fa-award"></i>
            </a>
            <?php endif; ?>

            <!-- Botão de Calendário (Agenda do Usuário) -->
            <a href="<?php echo htmlspecialchars($urlAgenda); ?>" class="nav-icon-btn" title="Agenda / Calendário">
                <i class="fa-solid fa-calendar-days"></i>
            </a>

            <?php if ($tipoUsuario === 'monitor'): ?>
            <button type="button" class="nav-icon-btn" id="btnScheduleMonitor" title="Agendar monitoria" aria-label="Agendar monitoria">
                <i class="fa-solid fa-calendar-plus"></i>
            </button>
            <?php endif; ?>

            <!-- Dropdown Mensagens -->
            <div class="nav-dropdown-wrapper">
                <button type="button" class="nav-icon-btn" id="btnMessages" title="Mensagens">
                    <i class="fa-solid fa-comments"></i>
                    <span class="notification-badge" id="chatUnreadBadge" hidden></span>
                </button>
                <div class="popover-dropdown" id="messagesDropdown">
                    <div class="popover-header">
                        <h4>Mensagens</h4>
                        <button type="button" class="header-action" data-abrir-chat>Tela cheia</button>
                    </div>
                    <div class="popover-body" id="chatRecentes">
                        <p class="popover-empty">Carregando conversas...</p>
                    </div>
                </div>
            </div>

            <!-- Dropdown Notificações -->
            <div class="nav-dropdown-wrapper">
                <button type="button" class="nav-icon-btn" id="btnNotifications" title="Notificações">
                    <i class="fa-solid fa-bell"></i>
                    <span class="notification-badge" id="notificationUnreadBadge" hidden></span>
                </button>
                <div class="popover-dropdown" id="notificationsDropdown">
                    <div class="popover-header">
                        <h4>Notificações</h4>
                        <button type="button" class="header-action" id="markNotificationsRead">Marcar lidas</button>
                    </div>
                    <div class="popover-body notification-list" id="notificationItems">
                        <p class="popover-empty">Carregando notificações...</p>
                    </div>
                </div>
            </div>

            <div class="nav-divider"></div>

            <!-- ===== 8. MENU DE USUÁRIO E STATUS ===== -->
            <div class="nav-user-wrapper" id="navUserWrapper">
                <div class="nav-user-dropdown" id="btnUserMenu">
                    <div class="nav-user-avatar<?php echo $navAvatarVisual ? ' nav-avatar-' . htmlspecialchars($navAvatarVisual, ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <?php if ($navAvatarVisual): ?>
                            <?php $iconeNav = $mapaIconesNav[$navAvatarVisual] ?? 'fa-user-graduate'; ?>
                            <?php if ($navAvatarImg): ?>
                                <img src="<?php echo htmlspecialchars($navAvatarImg, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                            <?php else: ?>
                                <i class="fa-solid <?php echo htmlspecialchars($iconeNav, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <?php endif; ?>
                        <?php elseif ($navTemFoto): ?>
                            <img src="<?php echo htmlspecialchars($basePath . 'actions/fotoPerfil.php?id=' . (int) $_SESSION['usuario_id'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <?php else: ?>
                            <?php echo htmlspecialchars($inicialNome); ?>
                        <?php endif; ?>
                    </div>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </div>

                <div class="user-menu-dropdown" id="userMenuDropdown">
                    <div class="user-menu-header">
                        <strong><?php echo htmlspecialchars($nomeUsuario); ?></strong>
                        <small><?php echo htmlspecialchars($emailUsuario); ?></small>
                        <span class="user-role-badge"><?php echo htmlspecialchars(strtoupper($tipoUsuario)); ?></span>
                    </div>

                    <?php if ($tipoUsuario === 'monitor'): ?>
                    <div class="status-selector">
                        <small>Status atual:</small>
                        <select class="status-select" id="statusSelect">
                            <option value="online">Online</option>
                            <option value="busy">Em Atendimento</option>
                            <option value="away">Ausente</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <ul class="user-menu-links">
                        <li>
                            <a href="<?php echo htmlspecialchars($urlPerfil); ?>">
                                <i class="fa-solid fa-id-card"></i>
                                Meu perfil
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars($urlPainel); ?>">
                                <i class="fa-solid fa-house"></i> Meu painel
                            </a>
                        </li>

                        <?php if ($tipoUsuario === 'aluno'): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($urlHoras); ?>">
                                <i class="fa-solid fa-clock-rotate-left"></i> Horas Complementares
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars($urlCertificados); ?>">
                                <i class="fa-solid fa-award"></i> Meus Certificados
                            </a>
                        </li>
                        <?php endif; ?>

                        <li>
                            <a href="<?php echo htmlspecialchars($urlConfiguracoes); ?>">
                                <i class="fa-solid fa-sliders"></i> Configurações
                            </a>
                        </li>
                        <li class="logout-item">
                            <a href="<?php echo htmlspecialchars($urlLogout); ?>">
                                <i class="fa-solid fa-right-from-bracket"></i> Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</nav>

<?php if ($tipoUsuario === 'monitor'): ?>
<dialog class="chat-schedule-dialog monitor-appointment-dialog" id="monitorAppointmentDialog">
    <form id="monitorAppointmentForm"
        data-api="<?php echo htmlspecialchars($basePath . 'actions/chat.php', ENT_QUOTES, 'UTF-8'); ?>"
        data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="chat-schedule-dialog-header">
            <div>
                <span>AGENDAMENTO</span>
                <h2>Agendar monitoria</h2>
            </div>
            <button type="button" data-fechar-monitor-agendamento aria-label="Fechar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <label>
            <span>Aluno</span>
            <select id="monitorAppointmentStudent" required>
                <option value="">Carregando alunos...</option>
            </select>
        </label>
        <label>
            <span>Assunto da monitoria</span>
            <input type="text" id="monitorAppointmentSubject" maxlength="180" required placeholder="Ex.: dúvidas sobre SQL">
        </label>
        <label>
            <span>Data</span>
            <input type="date" id="monitorAppointmentDate" required>
        </label>
        <label>
            <span>Duração</span>
            <select id="monitorAppointmentDuration" required>
                <option value="30">30 minutos</option>
                <option value="45">45 minutos</option>
                <option value="60" selected>1 hora</option>
                <option value="90">1 hora e 30 minutos</option>
            </select>
        </label>
        <label>
            <span>Horário disponível</span>
            <select id="monitorAppointmentTime" required disabled>
                <option value="">Escolha uma data primeiro</option>
            </select>
        </label>
        <label>
            <span>URL da reunião</span>
            <input type="url" id="monitorAppointmentUrl" maxlength="500" required placeholder="https://meet.google.com/...">
        </label>
        <p id="monitorAppointmentHint">Escolha a data para consultar seus horários livres cadastrados no perfil.</p>
        <div id="monitorAppointmentFeedback" role="status" aria-live="polite"></div>
        <div class="chat-schedule-actions">
            <button type="button" data-fechar-monitor-agendamento>Cancelar</button>
            <button type="submit">Agendar e notificar</button>
        </div>
    </form>
</dialog>
<script src="<?php echo htmlspecialchars($basePath . 'assets/js/agendamentoMonitor.js?v=4', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endif; ?>

<section
    class="chat-app"
    id="chatApp"
    aria-label="Mensagens do Athenas"
    aria-hidden="true"
    data-api="<?php echo htmlspecialchars($basePath . 'actions/chat.php', ENT_QUOTES, 'UTF-8'); ?>"
    data-notifications-api="<?php echo htmlspecialchars($basePath . 'actions/notificacoes.php', ENT_QUOTES, 'UTF-8'); ?>"
    data-agenda-url="<?php echo htmlspecialchars($urlAgenda, ENT_QUOTES, 'UTF-8'); ?>"
    data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
    data-user-id="<?php echo (int) ($_SESSION['usuario_id'] ?? 0); ?>"
    data-user-type="<?php echo htmlspecialchars($tipoUsuario, ENT_QUOTES, 'UTF-8'); ?>"
>
    <header class="chat-app-header">
        <div class="chat-app-heading">
            <span class="chat-header-icon"><i class="fa-solid fa-comments"></i></span>
            <div>
                <strong>Mensagens</strong>
                <small id="chatConnectionStatus">Conectando...</small>
            </div>
        </div>
        <div class="chat-app-actions">
            <button type="button" id="chatToggleSize" title="Expandir chat" aria-label="Expandir chat">
                <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
            </button>
            <button type="button" id="chatClose" title="Fechar chat" aria-label="Fechar chat">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </header>
    <div class="chat-app-body">
        <aside class="chat-sidebar">
            <div class="chat-sidebar-top">
                <label class="chat-contact-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="chatSearch" placeholder="Buscar conversa">
                </label>
                <label class="chat-new-contact">
                    <span>Nova conversa</span>
                    <select id="chatContactSelect">
                        <option value=""><?php echo $tipoUsuario === 'aluno' ? 'Escolha um monitor' : 'Escolha um aluno'; ?></option>
                    </select>
                </label>
            </div>
            <div class="chat-conversation-list" id="chatConversationList">
                <p class="chat-empty-state">Carregando conversas...</p>
            </div>
        </aside>
        <section class="chat-conversation" aria-label="Conversa selecionada">
            <header class="chat-conversation-header" id="chatConversationHeader">
                <div class="chat-avatar" id="chatActiveAvatar">?</div>
                <div>
                    <strong id="chatActiveName">Escolha uma conversa</strong>
                    <small id="chatActiveSubtitle">Suas mensagens ficam disponíveis nesta tela.</small>
                </div>
                <button type="button" id="requestAppointment" class="chat-schedule-button" hidden>
                    <i class="fa-regular fa-calendar-plus"></i> Solicitar monitoria
                </button>
            </header>
            <div class="chat-appointments" id="chatAppointments" hidden></div>
            <div class="chat-message-list" id="chatMessageList" aria-live="polite">
                <div class="chat-welcome">
                    <span><i class="fa-regular fa-paper-plane"></i></span>
                    <strong>Converse com seu monitor</strong>
                    <p>Selecione uma conversa ou inicie uma nova para tirar dúvidas e combinar um horário.</p>
                </div>
            </div>
            <form class="chat-compose" id="chatCompose" hidden>
                <textarea id="chatMessageInput" rows="1" maxlength="4000" placeholder="Escreva uma mensagem..." required></textarea>
                <button type="submit" title="Enviar mensagem" aria-label="Enviar mensagem">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </section>
    </div>
    <dialog class="chat-schedule-dialog" id="chatScheduleDialog">
        <form id="chatScheduleForm">
            <div class="chat-schedule-dialog-header">
                <div>
                    <span>AGENDAMENTO</span>
                    <h2>Solicitar monitoria</h2>
                </div>
                <button type="button" data-fechar-agendamento aria-label="Fechar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <label>
                <span>Assunto da monitoria</span>
                <input type="text" id="appointmentSubject" maxlength="180" required placeholder="Ex.: dúvidas sobre SQL">
            </label>
            <label>
                <span>Data</span>
                <input type="date" id="appointmentDate" required>
            </label>
            <label>
                <span>Duração</span>
                <select id="appointmentDuration" required>
                    <option value="30">30 minutos</option>
                    <option value="45">45 minutos</option>
                    <option value="60" selected>1 hora</option>
                    <option value="90">1 hora e 30 minutos</option>
                </select>
            </label>
            <label>
                <span>Horário disponível</span>
                <select id="appointmentTime" required disabled>
                    <option value="">Escolha uma data primeiro</option>
                </select>
            </label>
            <p id="appointmentFeedback" role="status" aria-live="polite">Escolha a data para consultar os horários livres do monitor.</p>
            <p>O monitor receberá seu pedido e poderá confirmar com a URL da reunião.</p>
            <div class="chat-schedule-actions">
                <button type="button" data-fechar-agendamento>Cancelar</button>
                <button type="submit">Enviar solicitação</button>
            </div>
        </form>
    </dialog>
</section>

<script src="<?php echo htmlspecialchars($basePath . 'assets/js/mensagens.js?v=6', ENT_QUOTES, 'UTF-8'); ?>" defer></script>

<!-- ===== 9. LÓGICA DE INTERATIVIDADE JAVASCRIPT ===== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchWrapper      = document.getElementById('navbarSearchWrapper');
    const searchInput        = document.getElementById('searchInput');
    const searchDropdown     = document.getElementById('searchDropdown');
    const btnCloseDropdown   = document.getElementById('btnCloseDropdown');
    const btnClearSearch     = document.getElementById('btnClearSearch');
    const searchKbd          = document.getElementById('searchKbd');
    const categoriesSection  = document.getElementById('categoriesSection');
    const liveResults        = document.getElementById('liveResults');
    const tabButtons         = Array.from(document.querySelectorAll('[data-search-tab]'));

    const searchApi          = searchWrapper ? searchWrapper.dataset.searchApi : '';
    const basePath           = searchWrapper ? searchWrapper.dataset.basePath : '../../';
    const userType           = searchWrapper ? searchWrapper.dataset.userType : 'aluno';
    const ehMonitor          = userType === 'monitor';

    const btnMessages           = document.getElementById('btnMessages');
    const messagesDropdown      = document.getElementById('messagesDropdown');
    const btnNotifications      = document.getElementById('btnNotifications');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const btnUserMenu           = document.getElementById('btnUserMenu');
    const userMenuDropdown      = document.getElementById('userMenuDropdown');

    const allDropdowns = [searchDropdown, messagesDropdown, notificationsDropdown, userMenuDropdown];

    let abaBuscaAtiva = 'todos';
    let ultimoPayloadBusca = null;
    let debounceTimer = null;
    let reqCounter = 0;

    function fecharTodos() {
        allDropdowns.forEach(drop => {
            if (drop) drop.classList.remove('active');
        });
    }

    function criarAvatarElemento(item) {
        const av = document.createElement('div');
        av.className = 'search-result-avatar';
        if (item.tem_foto) {
            const img = document.createElement('img');
            img.src = basePath + 'actions/fotoPerfil.php?id=' + encodeURIComponent(item.id);
            img.alt = item.nome;
            av.appendChild(img);
        } else {
            av.textContent = item.iniciais || '?';
        }
        return av;
    }

    function renderizarResultadosBusca(payload) {
        if (!payload || !liveResults) return;
        ultimoPayloadBusca = payload;
        const termo = (searchInput.value || '').trim();
        const mostrarCategorias = (termo === '' && abaBuscaAtiva === 'todos');
        if (categoriesSection) {
            categoriesSection.style.display = mostrarCategorias ? 'block' : 'none';
        }

        if (ehMonitor && payload.resumo_monitor) {
            const badgePend = document.getElementById('quickMonitorPendencias');
            if (badgePend) {
                const p = Number(payload.resumo_monitor.pendencias_presenca || 0);
                const s = Number(payload.resumo_monitor.solicitacoes_abertas || 0);
                const total = p + s;
                badgePend.textContent = total > 0 ? total + ' pendência(s) ativa(s)' : 'Tudo em dia';
            }
        }

        liveResults.replaceChildren();
        let totalRenderizados = 0;

        function criarTituloSecaoBusca(texto, total) {
            const secTitle = document.createElement('div');
            secTitle.className = 'results-section-title';
            const s1 = document.createElement('span');
            s1.textContent = texto;
            const s2 = document.createElement('span');
            s2.textContent = String(total);
            secTitle.append(s1, s2);
            return secTitle;
        }

        // 1. Seção de Alunos
        const exibirAlunos = (abaBuscaAtiva === 'todos' || abaBuscaAtiva === 'alunos');
        const listaAlunos = payload.alunos || [];
        if (exibirAlunos && listaAlunos.length > 0 && (ehMonitor || termo !== '' || abaBuscaAtiva === 'alunos')) {
            liveResults.appendChild(criarTituloSecaoBusca(
                termo === '' ? 'Alunos Recentes / Cadastrados' : 'Alunos Encontrados',
                listaAlunos.length
            ));

            listaAlunos.forEach(function (aluno) {
                totalRenderizados++;
                const row = document.createElement('div');
                row.className = 'search-result-row';

                const main = document.createElement('div');
                main.className = 'search-result-main';
                const info = document.createElement('div');
                info.className = 'search-result-info';
                const nome = document.createElement('strong');
                nome.textContent = aluno.nome;
                const sub = document.createElement('small');
                sub.textContent = aluno.curso + (aluno.total_sessoes > 0 ? ' · ' + aluno.total_sessoes + ' monitoria(s)' : ' · ' + aluno.email);
                info.append(nome, sub);
                main.append(criarAvatarElemento(aluno), info);

                const actions = document.createElement('div');
                actions.className = 'search-result-actions';

                if (ehMonitor) {
                    const btnChat = document.createElement('button');
                    btnChat.type = 'button';
                    btnChat.className = 'search-action-btn';
                    btnChat.dataset.iniciarConversaAluno = String(aluno.id);
                    btnChat.innerHTML = '<i class="fa-regular fa-comments"></i> Chat';

                    const btnAgendar = document.createElement('button');
                    btnAgendar.type = 'button';
                    btnAgendar.className = 'search-action-btn primary';
                    btnAgendar.dataset.agendarComAluno = String(aluno.id);
                    btnAgendar.innerHTML = '<i class="fa-solid fa-calendar-plus"></i> Agendar';

                    actions.append(btnChat, btnAgendar);
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'badge-online';
                    badge.textContent = 'Aluno';
                    actions.appendChild(badge);
                }

                row.append(main, actions);
                liveResults.appendChild(row);
            });
        }

        // 2. Seção de Monitores / Professores Orientadores
        const exibirMonitores = (abaBuscaAtiva === 'todos' || abaBuscaAtiva === 'monitores');
        const listaMonitores = payload.monitores || [];
        if (exibirMonitores && listaMonitores.length > 0 && (!ehMonitor || termo !== '' || abaBuscaAtiva === 'monitores')) {
            liveResults.appendChild(criarTituloSecaoBusca(
                termo === '' ? 'Monitores e Orientadores Disponíveis' : 'Monitores / Orientadores',
                listaMonitores.length
            ));

            listaMonitores.forEach(function (mon) {
                totalRenderizados++;
                const row = document.createElement('div');
                row.className = 'search-result-row';

                const main = document.createElement('a');
                main.className = 'search-result-main';
                main.href = basePath + 'views/monitor/perfilMonitor.php?id=' + encodeURIComponent(mon.id);

                const info = document.createElement('div');
                info.className = 'search-result-info';
                const nome = document.createElement('strong');
                nome.textContent = mon.nome + ' ★ ' + mon.nota;
                const sub = document.createElement('small');
                sub.textContent = mon.disciplina + ' · ' + mon.area + ' (' + mon.modalidade + ')';
                info.append(nome, sub);
                main.append(criarAvatarElemento(mon), info);

                const actions = document.createElement('div');
                actions.className = 'search-result-actions';

                const btnPerfil = document.createElement('a');
                btnPerfil.className = 'search-action-btn';
                btnPerfil.href = basePath + 'views/monitor/perfilMonitor.php?id=' + encodeURIComponent(mon.id);
                btnPerfil.innerHTML = 'Perfil';
                actions.appendChild(btnPerfil);

                if (!ehMonitor) {
                    const btnSolicitar = document.createElement('button');
                    btnSolicitar.type = 'button';
                    btnSolicitar.className = 'search-action-btn primary';
                    btnSolicitar.dataset.solicitarMonitoriaMonitor = String(mon.id);
                    btnSolicitar.innerHTML = '<i class="fa-regular fa-calendar-plus"></i> Agendar';
                    actions.appendChild(btnSolicitar);
                }

                row.append(main, actions);
                liveResults.appendChild(row);
            });
        }

        // 3. Seção de Atendimentos / Sessões e Matérias
        const exibirAtendimentos = (abaBuscaAtiva === 'todos' || abaBuscaAtiva === 'atendimentos');
        const listaAtend = payload.atendimentos || [];
        if (exibirAtendimentos && listaAtend.length > 0 && (termo !== '' || abaBuscaAtiva === 'atendimentos')) {
            liveResults.appendChild(criarTituloSecaoBusca('Monitorias & Sessões', listaAtend.length));

            listaAtend.forEach(function (atend) {
                totalRenderizados++;
                const row = document.createElement('div');
                row.className = 'search-result-row';

                const main = document.createElement('a');
                main.className = 'search-result-main';
                main.href = basePath + 'views/agenda/index.php';

                const icone = document.createElement('div');
                icone.className = 'result-icon-subject';
                icone.innerHTML = '<i class="fa-solid fa-calendar-day"></i>';

                const info = document.createElement('div');
                info.className = 'search-result-info';
                const titulo = document.createElement('strong');
                titulo.textContent = atend.assunto;
                const sub = document.createElement('small');
                sub.textContent = atend.data + ' · ' + atend.participante;
                info.append(titulo, sub);
                main.append(icone, info);

                const actions = document.createElement('div');
                actions.className = 'search-result-actions';
                if (atend.concluido) {
                    const linkCert = document.createElement('a');
                    linkCert.className = 'search-action-btn primary';
                    linkCert.href = basePath + 'views/monitor/emissaoCertificado.php?id=' + encodeURIComponent(atend.id);
                    linkCert.target = '_blank';
                    linkCert.innerHTML = '<i class="fa-solid fa-award"></i> Certificado';
                    actions.appendChild(linkCert);
                } else {
                    const linkAgenda = document.createElement('a');
                    linkAgenda.className = 'search-action-btn';
                    linkAgenda.href = basePath + 'views/agenda/index.php';
                    linkAgenda.textContent = 'Ver na Agenda';
                    actions.appendChild(linkAgenda);
                }

                row.append(main, actions);
                liveResults.appendChild(row);
            });
        }

        const listaMaterias = payload.materias || [];
        if (exibirAtendimentos && listaMaterias.length > 0 && (termo !== '' || abaBuscaAtiva === 'atendimentos')) {
            liveResults.appendChild(criarTituloSecaoBusca('Matérias e Disciplinas', listaMaterias.length));

            listaMaterias.slice(0, 4).forEach(function (mat) {
                totalRenderizados++;
                const row = document.createElement('div');
                row.className = 'search-result-row';

                const destino = ehMonitor
                    ? basePath + 'views/monitor/perfilMonitor.php'
                    : basePath + 'views/aluno/monitores.php?busca=' + encodeURIComponent(mat.titulo);

                const main = document.createElement('a');
                main.className = 'search-result-main';
                main.href = destino;

                const icone = document.createElement('div');
                icone.className = 'result-icon-subject';
                const iconeI = document.createElement('i');
                iconeI.className = 'fa-solid ' + String(mat.icone || 'fa-book').replace(/[^a-z0-9-]/gi, '');
                icone.appendChild(iconeI);

                const info = document.createElement('div');
                info.className = 'search-result-info';
                const titulo = document.createElement('strong');
                titulo.textContent = mat.titulo;
                const sub = document.createElement('small');
                sub.textContent = mat.categoria + ' · ' + mat.disciplina;
                info.append(titulo, sub);
                main.append(icone, info);

                const actions = document.createElement('div');
                actions.className = 'search-result-actions';
                const btnVer = document.createElement('a');
                btnVer.className = 'search-action-btn';
                btnVer.href = destino;
                btnVer.textContent = ehMonitor ? 'Minha disciplina' : 'Ver monitores';
                actions.appendChild(btnVer);

                row.append(main, actions);
                liveResults.appendChild(row);
            });
        }

        if (totalRenderizados === 0 && (!mostrarCategorias)) {
            const empty = document.createElement('div');
            empty.className = 'search-empty-msg';
            empty.textContent = 'Nenhum aluno, monitor ou atendimento encontrado para "' + termo + '".';
            liveResults.appendChild(empty);
        }
    }

    async function consultarBuscaGlobal(termo) {
        if (!searchApi) return;
        const idReq = ++reqCounter;
        try {
            const resp = await fetch(searchApi + '?q=' + encodeURIComponent(termo));
            const payload = await resp.json();
            if (idReq !== reqCounter || !resp.ok) return;
            renderizarResultadosBusca(payload);
        } catch (e) {
            // Falha silenciosa de rede
        }
    }

    if (searchInput && searchDropdown) {
        searchInput.addEventListener('click', function (e) {
            e.stopPropagation();
            const jaAberto = searchDropdown.classList.contains('active');
            fecharTodos();
            if (!jaAberto) {
                searchDropdown.classList.add('active');
                consultarBuscaGlobal(this.value.trim());
            }
        });

        searchInput.addEventListener('input', function () {
            const val = this.value.trim();
            if (!searchDropdown.classList.contains('active')) {
                fecharTodos();
                searchDropdown.classList.add('active');
            }
            btnClearSearch.style.display = this.value.length > 0 ? 'block' : 'none';
            if (searchKbd) {
                searchKbd.style.display = this.value.length > 0 ? 'none' : 'block';
            }
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(function () {
                consultarBuscaGlobal(val);
            }, 160);
        });

        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                tabButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                abaBuscaAtiva = this.dataset.searchTab || 'todos';
                if (ultimoPayloadBusca) {
                    renderizarResultadosBusca(ultimoPayloadBusca);
                } else {
                    consultarBuscaGlobal(searchInput.value.trim());
                }
            });
        });

        if (btnClearSearch) {
            btnClearSearch.addEventListener('click', function () {
                searchInput.value = '';
                btnClearSearch.style.display = 'none';
                if (searchKbd) searchKbd.style.display = 'block';
                consultarBuscaGlobal('');
                searchInput.focus();
            });
        }

        if (btnCloseDropdown) {
            btnCloseDropdown.addEventListener('click', function (e) {
                e.stopPropagation();
                searchDropdown.classList.remove('active');
            });
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (searchDropdown.classList.contains('active')) {
                    searchDropdown.classList.remove('active');
                    searchInput.blur();
                } else {
                    fecharTodos();
                    searchInput.focus();
                    searchDropdown.classList.add('active');
                    consultarBuscaGlobal(searchInput.value.trim());
                }
            } else if (e.key === 'Escape' && searchDropdown.classList.contains('active')) {
                searchDropdown.classList.remove('active');
                searchInput.blur();
            }
        });
    }

    // Toggle de exibição dos dropdowns
    function setupDropdownToggle(button, dropdown) {
        if (button && dropdown) {
            button.addEventListener('click', function (e) {
                e.stopPropagation();
                const estaAtivo = dropdown.classList.contains('active');
                fecharTodos();
                if (!estaAtivo) dropdown.classList.add('active');
            });
        }
    }

    setupDropdownToggle(btnMessages, messagesDropdown);
    setupDropdownToggle(btnNotifications, notificationsDropdown);
    setupDropdownToggle(btnUserMenu, userMenuDropdown);

    // Fechar dropdowns ao clicar fora do painel aberto
    document.addEventListener('click', function (e) {
        if (
            !e.target.closest('.search-dropdown')
            && !e.target.closest('.navbar-search')
            && !e.target.closest('.popover-dropdown')
            && !e.target.closest('.user-menu-dropdown')
        ) {
            fecharTodos();
        }
    });
});
</script>