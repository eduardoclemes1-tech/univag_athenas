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
$inicialNome  = mb_substr($nomeUsuario, 0, 1, 'UTF-8');

/* ===== 2. CÁLCULO DINÂMICO DO CAMINHO BASE (PREVINE ERRO 404) ===== */
// Analisa o caminho da página atual para calcular o número de níveis até a raiz 'univag_athenas'
$scriptPath  = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
$univagIndex = array_search('univag_athenas', $scriptPath);

if ($univagIndex !== false) {
    $depth = count($scriptPath) - 2 - $univagIndex;
    $basePath = ($depth > 0) ? str_repeat('../', $depth) : './';
} else {
    $basePath = '../../';
}

/* ===== 3. MAPEAMENTO DE ROTAS DA NAVBAR ===== */
// Rotas dinâmicas de perfil conforme o tipo de usuário logado (aluno ou monitor)
if ($tipoUsuario === 'aluno') {
    $urlHome   = $basePath . 'views/aluno/inicio.php';
    $urlPerfil = $basePath . 'views/aluno/perfil.php';
    $urlPainel = $basePath . 'views/aluno/inicio.php';
} else {
    $urlHome   = $basePath . 'views/monitor/perfilMonitor.php';
    $urlPerfil = $basePath . 'views/monitor/perfilMonitor.php';
    $urlPainel = $urlPerfil;
}

// Rota oficial apontando para o arquivo de Calendário criado em views/agenda/index.php
$urlAgenda = $basePath . 'views/agenda/index.php';
$urlConfiguracoes = $basePath . 'views/configuracoes.php';
$urlExplorarMonitores = $basePath . 'views/aluno/monitores.php';
$urlMaterias = $basePath . 'views/aluno/materias.php';

// Rotas exclusivas da gestão acadêmica do Monitor
$urlHoras        = $basePath . 'views/monitor/horasComplementares.php';
$urlCertificados = $basePath . 'views/monitor/certificados.php';

// Ações reais do sistema e assets estáticos
$urlLogout = $basePath . 'actions/logout.php';
$urlLogo   = $basePath . 'assets/img/logoAthenas.png';
$urlCss    = $basePath . 'assets/css/navbar.css?v=4';
$urlChatCss = $basePath . 'assets/css/mensagens.css?v=1';

// Rota temporária para telas em desenvolvimento
$urlEmBreve = 'javascript:void(0);';
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
        <div class="navbar-search-wrapper">
            <div class="navbar-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchInput" placeholder="<?php echo $tipoUsuario === 'aluno' ? 'Buscar monitores ou matérias...' : 'Buscar alunos ou matérias...'; ?>" autocomplete="off">
                <button type="button" class="btn-clear-search" id="btnClearSearch">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Explorar (Exibido com o campo de busca vazio) -->
            <div class="search-dropdown" id="searchDropdown">
                <div class="dropdown-header">
                    <div>
                        <h3>Explorar</h3>
                        <p><?php echo $tipoUsuario === 'aluno' ? 'Encontre um monitor pelo assunto que precisa' : 'Acompanhe seus atendimentos e disciplinas'; ?></p>
                    </div>
                    <button type="button" class="btn-close-dropdown" id="btnCloseDropdown">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="dropdown-grid" id="categoriesGrid">
                    <?php if ($tipoUsuario === 'aluno'): ?>
                    <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Programação'); ?>" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-code"></i></div>
                        <span>Programação</span>
                    </a>
                    <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Dados'); ?>" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-database"></i></div>
                        <span>Dados</span>
                    </a>
                    <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Desenvolvimento'); ?>" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-window-maximize"></i></div>
                        <span>Desenvolvimento</span>
                    </a>
                    <a href="<?php echo htmlspecialchars($urlExplorarMonitores . '?area=Infraestrutura'); ?>" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-network-wired"></i></div>
                        <span>Infraestrutura</span>
                    </a>
                    <a href="<?php echo htmlspecialchars($urlExplorarMonitores); ?>" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-users"></i></div>
                        <span>Todas as áreas</span>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo $urlEmBreve; ?>" class="category-card" title="Em breve">
                        <div class="category-icon"><i class="fa-solid fa-code"></i></div>
                        <span>Programação</span>
                    </a>
                    <a href="<?php echo $urlEmBreve; ?>" class="category-card" title="Em breve">
                        <div class="category-icon"><i class="fa-solid fa-database"></i></div>
                        <span>Dados</span>
                    </a>
                    <a href="<?php echo $urlEmBreve; ?>" class="category-card" title="Em breve">
                        <div class="category-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                        <span>Mobile</span>
                    </a>
                    <a href="<?php echo $urlEmBreve; ?>" class="category-card" title="Em breve">
                        <div class="category-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <span>Cibersegurança</span>
                    </a>
                    <a href="<?php echo $urlEmBreve; ?>" class="category-card" title="Em breve">
                        <div class="category-icon"><i class="fa-solid fa-brain"></i></div>
                        <span>IA</span>
                    </a>
                    <a href="<?php echo $urlEmBreve; ?>" class="category-card" title="Em breve">
                        <div class="category-icon"><i class="fa-solid fa-ellipsis"></i></div>
                        <span>Outros</span>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Painel de Resultados (Exibido ao digitar no campo) -->
                <div class="search-live-results" id="liveResults" style="display: none;">
                    <?php if ($tipoUsuario === 'aluno'): ?>
                    <div class="results-section-title">Monitores</div>
                    <a
                        href="<?php echo htmlspecialchars($urlExplorarMonitores); ?>"
                        class="result-item"
                        id="monitorSearchResult"
                        data-catalog-url="<?php echo htmlspecialchars($urlExplorarMonitores); ?>"
                    >
                        <div class="result-avatar">M</div>
                        <div class="result-info">
                            <strong>Explorar monitores</strong>
                            <small>Filtrar por nome, disciplina ou assunto</small>
                        </div>
                        <span class="badge-online">Online</span>
                    </a>

                    <div class="results-section-title">Matérias</div>
                    <a
                        href="<?php echo htmlspecialchars($urlMaterias); ?>"
                        class="result-item"
                        id="materiaSearchResult"
                        data-catalog-url="<?php echo htmlspecialchars($urlMaterias); ?>"
                    >
                        <div class="result-icon-subject"><i class="fa-solid fa-book"></i></div>
                        <div class="result-info">
                            <strong>Explorar matérias</strong>
                            <small>Buscar por disciplina e conteúdo</small>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===== 7. ÍCONES DE AÇÃO E DROPDOWNS ===== -->
        <div class="navbar-actions">
            <!-- Botão Início (Universal para Aluno e Monitor) -->
            <a href="<?php echo htmlspecialchars($urlHome); ?>" class="nav-icon-btn" title="Início">
                <i class="fa-solid fa-house"></i>
            </a>

            <?php if ($tipoUsuario === 'aluno'): ?>
            <!-- Ícones exclusivos da visão do Aluno (Matérias e Explorar Monitores) -->
            <a href="<?php echo htmlspecialchars($urlMaterias); ?>" class="nav-icon-btn" title="Explorar matérias">
                <i class="fa-solid fa-book-bookmark"></i>
            </a>
            <a href="<?php echo htmlspecialchars($urlExplorarMonitores); ?>" class="nav-icon-btn" title="Explorar monitorias">
                <i class="fa-solid fa-user-graduate"></i>
            </a>
            <?php endif; ?>

            <!-- Botão de Calendário (Agenda do Usuário) -->
            <a href="<?php echo htmlspecialchars($urlAgenda); ?>" class="nav-icon-btn" title="Agenda / Calendário">
                <i class="fa-solid fa-calendar-days"></i>
            </a>

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
                    <div class="nav-user-avatar">
                        <?php echo htmlspecialchars($inicialNome); ?>
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
                        <?php if ($tipoUsuario === 'aluno'): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($urlPainel); ?>">
                                <i class="fa-solid fa-house"></i> Meu painel
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if ($tipoUsuario === 'monitor'): ?>
                        <!-- Links de gestão acadêmica exclusivos para a conta de Monitor -->
                        <li>
                            <a href="<?php echo htmlspecialchars($urlHoras); ?>">
                                <i class="fa-solid fa-clock-rotate-left"></i> Horas Complementares
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars($urlCertificados); ?>">
                                <i class="fa-solid fa-file-certificate"></i> Meus Certificados
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

<section
    class="chat-app"
    id="chatApp"
    aria-label="Mensagens do Athenas"
    aria-hidden="true"
    data-api="<?php echo htmlspecialchars($basePath . 'actions/chat.php', ENT_QUOTES, 'UTF-8'); ?>"
    data-notifications-api="<?php echo htmlspecialchars($basePath . 'actions/notificacoes.php', ENT_QUOTES, 'UTF-8'); ?>"
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
                        <option value="">Escolha um monitor</option>
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
                <span>Data e horário</span>
                <input type="datetime-local" id="appointmentDate" required>
            </label>
            <p>O monitor receberá seu pedido e poderá confirmar com o link da reunião.</p>
            <div class="chat-schedule-actions">
                <button type="button" data-fechar-agendamento>Cancelar</button>
                <button type="submit">Enviar solicitação</button>
            </div>
        </form>
    </dialog>
</section>

<script src="<?php echo htmlspecialchars($basePath . 'assets/js/mensagens.js?v=2', ENT_QUOTES, 'UTF-8'); ?>" defer></script>

<!-- ===== 9. LÓGICA DE INTERATIVIDADE JAVASCRIPT ===== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Elementos da busca dinâmica
    const searchInput        = document.getElementById('searchInput');
    const searchDropdown     = document.getElementById('searchDropdown');
    const btnCloseDropdown   = document.getElementById('btnCloseDropdown');
    const btnClearSearch     = document.getElementById('btnClearSearch');
    const categoriesGrid     = document.getElementById('categoriesGrid');
    const liveResults        = document.getElementById('liveResults');
    const monitorSearchResult = document.getElementById('monitorSearchResult');
    const materiaSearchResult = document.getElementById('materiaSearchResult');

    // Botões dos painéis flutuantes
    const btnMessages          = document.getElementById('btnMessages');
    const messagesDropdown     = document.getElementById('messagesDropdown');
    const btnNotifications     = document.getElementById('btnNotifications');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const btnUserMenu          = document.getElementById('btnUserMenu');
    const userMenuDropdown     = document.getElementById('userMenuDropdown');

    // Lista unificada para controle de fechamento
    const allDropdowns = [searchDropdown, messagesDropdown, notificationsDropdown, userMenuDropdown];

    // Fecha todos os modais/dropdowns
    function fecharTodos() {
        allDropdowns.forEach(drop => {
            if (drop) drop.classList.remove('active');
        });
    }

    // Comportamento do campo de busca
    if (searchInput && searchDropdown) {
        searchInput.addEventListener('focus', function () {
            fecharTodos();
            searchDropdown.classList.add('active');
        });

        searchInput.addEventListener('input', function () {
            if (this.value.trim().length >= 2) {
                if (monitorSearchResult) {
                    monitorSearchResult.href = monitorSearchResult.dataset.catalogUrl
                        + '?busca=' + encodeURIComponent(this.value.trim());
                }
                if (materiaSearchResult) {
                    materiaSearchResult.href = materiaSearchResult.dataset.catalogUrl
                        + '?busca=' + encodeURIComponent(this.value.trim());
                }
                categoriesGrid.style.display = 'none';
                liveResults.style.display    = 'block';
                btnClearSearch.style.display = 'block';
            } else {
                categoriesGrid.style.display = 'grid';
                liveResults.style.display    = 'none';
                btnClearSearch.style.display = (this.value.length > 0) ? 'block' : 'none';
            }
        });

        if (btnClearSearch) {
            btnClearSearch.addEventListener('click', function () {
                searchInput.value = '';
                categoriesGrid.style.display = 'grid';
                liveResults.style.display    = 'none';
                btnClearSearch.style.display = 'none';
                searchInput.focus();
            });
        }

        if (btnCloseDropdown) {
            btnCloseDropdown.addEventListener('click', function (e) {
                e.stopPropagation();
                searchDropdown.classList.remove('active');
            });
        }
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

    // Fechar dropdowns ao clicar fora da navbar
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.navbar-container')) {
            fecharTodos();
        }
    });
});
</script>