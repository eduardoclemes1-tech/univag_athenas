<?php
/* ===== 1. INICIALIZAÇÃO DE SESSÃO E RECUPERAÇÃO DE DADOS ===== */
// Inicializa a sessão do PHP caso não tenha sido iniciada pela página principal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Recupera os dados do usuário autenticado ou utiliza fallbacks para desenvolvimento
$nomeUsuario  = $_SESSION['usuario_nome']  ?? 'Marcos Silva Pereira';
$emailUsuario = $_SESSION['usuario_email'] ?? 'admin@univag.br';
$tipoUsuario  = $_SESSION['usuario_tipo']  ?? 'monitor';
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
    $urlHome   = $basePath . 'views/aluno/perfilAluno.php';
    $urlPerfil = $basePath . 'views/aluno/perfilAluno.php';
} else {
    $urlHome   = $basePath . 'views/monitor/perfilMonitor.php';
    $urlPerfil = $basePath . 'views/monitor/perfilMonitor.php';
}

// Rota oficial apontando para o arquivo de Calendário criado em views/agenda/index.php
$urlAgenda = $basePath . 'views/agenda/index.php';

// Rotas exclusivas da gestão acadêmica do Monitor
$urlHoras        = $basePath . 'views/monitor/horasComplementares.php';
$urlCertificados = $basePath . 'views/monitor/certificados.php';

// Ações reais do sistema e assets estáticos
$urlLogout = $basePath . 'actions/logout.php';
$urlLogo   = $basePath . 'assets/img/logoAthenas.png';
$urlCss    = $basePath . 'assets/css/navbar.css';

// Rota temporária para telas em desenvolvimento
$urlEmBreve = 'javascript:void(0);';
?>

<!-- ===== 4. CARREGAMENTO DO CSS DA NAVBAR ===== -->
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlCss); ?>">

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
                <input type="text" id="searchInput" placeholder="Buscar alunos, monitores ou matérias..." autocomplete="off">
                <button type="button" class="btn-clear-search" id="btnClearSearch">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Explorar (Exibido com o campo de busca vazio) -->
            <div class="search-dropdown" id="searchDropdown">
                <div class="dropdown-header">
                    <div>
                        <h3>Explorar</h3>
                        <p>Encontre um monitor pelo assunto que precisa</p>
                    </div>
                    <button type="button" class="btn-close-dropdown" id="btnCloseDropdown">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="dropdown-grid" id="categoriesGrid">
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
                </div>

                <!-- Painel de Resultados (Exibido ao digitar no campo) -->
                <div class="search-live-results" id="liveResults" style="display: none;">
                    <div class="results-section-title">Monitores Encontrados</div>
                    <a href="<?php echo htmlspecialchars($urlPerfil); ?>" class="result-item">
                        <div class="result-avatar">M</div>
                        <div class="result-info">
                            <strong>Marcos Silva Pereira</strong>
                            <small>Monitor de Banco de Dados e SQL</small>
                        </div>
                        <span class="badge-online">Online</span>
                    </a>

                    <div class="results-section-title">Matérias Relacionadas</div>
                    <a href="<?php echo $urlEmBreve; ?>" class="result-item" title="Em breve">
                        <div class="result-icon-subject"><i class="fa-solid fa-book"></i></div>
                        <div class="result-info">
                            <strong>Modelagem de Dados e DER</strong>
                            <small>Sistemas de Informação - 3º Semestre</small>
                        </div>
                    </a>
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
            <a href="<?php echo $urlEmBreve; ?>" class="nav-icon-btn" title="Matérias (Em breve)">
                <i class="fa-solid fa-book-bookmark"></i>
            </a>
            <a href="<?php echo $urlEmBreve; ?>" class="nav-icon-btn" title="Monitores (Em breve)">
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
                    <span class="notification-badge">2</span>
                </button>
                <div class="popover-dropdown" id="messagesDropdown">
                    <div class="popover-header">
                        <h4>Conversas Recentes</h4>
                        <a href="<?php echo $urlEmBreve; ?>">Ver todas</a>
                    </div>
                    <div class="popover-body">
                        <a href="<?php echo $urlEmBreve; ?>" class="message-item unread">
                            <div class="msg-avatar">G</div>
                            <div class="msg-content">
                                <strong>Gabriel Souza</strong>
                                <p>Consegue me ajudar hoje no laboratório?</p>
                                <small>14:20</small>
                            </div>
                        </a>
                        <a href="<?php echo $urlEmBreve; ?>" class="message-item">
                            <div class="msg-avatar">M</div>
                            <div class="msg-content">
                                <strong>Mariana Lima</strong>
                                <p>Obrigada pela explicação do exercício!</p>
                                <small>Ontem</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dropdown Notificações -->
            <div class="nav-dropdown-wrapper">
                <button type="button" class="nav-icon-btn" id="btnNotifications" title="Notificações">
                    <i class="fa-solid fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="popover-dropdown" id="notificationsDropdown">
                    <div class="popover-header">
                        <h4>Notificações</h4>
                        <a href="<?php echo $urlEmBreve; ?>">Marcar lidas</a>
                    </div>
                    <div class="popover-body">
                        <div class="notif-item unread">
                            <div class="notif-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            <div class="notif-content">
                                <p><strong>Gabriel S.</strong> solicitou agendamento</p>
                                <div class="notif-actions">
                                    <button type="button" class="btn-notif-accept">Aceitar</button>
                                    <button type="button" class="btn-notif-decline">Recusar</button>
                                </div>
                                <small class="notif-time">Há 5 min</small>
                            </div>
                        </div>
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

                    <div class="status-selector">
                        <small>Status atual:</small>
                        <select class="status-select" id="statusSelect">
                            <option value="online">Online</option>
                            <option value="busy">Em Atendimento</option>
                            <option value="away">Ausente</option>
                        </select>
                    </div>

                    <ul class="user-menu-links">
                        <li>
                            <a href="<?php echo htmlspecialchars($urlPerfil); ?>">
                                <i class="fa-solid fa-id-card"></i> Meu Perfil
                            </a>
                        </li>

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
                            <a href="<?php echo $urlEmBreve; ?>">
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