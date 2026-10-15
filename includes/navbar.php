<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nomeUsuario  = $_SESSION['usuario_nome'] ?? 'Marcos Silva Pereira';
$emailUsuario = $_SESSION['usuario_email'] ?? 'admin@univag.br';
$tipoUsuario  = $_SESSION['usuario_tipo'] ?? 'monitor';
$inicialNome  = mb_substr($nomeUsuario, 0, 1, 'UTF-8');
?>
<link rel="stylesheet" href="../../assets/css/navbar.css">

<nav class="navbar-container">
    <div class="navbar-content">
        
        <!-- 1. LOGO -->
        <div class="navbar-logo">
            <a href="../monitor/perfilMonitor.php">
                <img src="../../assets/img/logoAthenas.png" alt="UNIVAG ATHENAS" class="logo-img">
            </a>
        </div>

        <!-- 2. BARRA DE PESQUISA INTELIGENTE (EXPLORAR + RESULTADOS AO VIVO) -->
        <div class="navbar-search-wrapper">
            <div class="navbar-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchInput" placeholder="Buscar alunos, matérias, monitores...." autocomplete="off">
                <button type="button" class="btn-clear-search" id="btnClearSearch"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- MODAL EXPLORAR (EXIBIDO QUANDO O CAMPO ESTÁ VAZIO) -->
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

                <!-- CARDS DE CATEGORIAS DO FIGMA -->
                <div class="dropdown-grid" id="categoriesGrid">
                    <a href="../monitor/perfilMonitor.php?cat=programacao" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-code"></i></div>
                        <span>Programação</span>
                    </a>
                    <a href="../monitor/perfilMonitor.php?cat=dados" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-database"></i></div>
                        <span>Dados</span>
                    </a>
                    <a href="../monitor/perfilMonitor.php?cat=mobile" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                        <span>Mobile</span>
                    </a>
                    <a href="../monitor/perfilMonitor.php?cat=ciberseguranca" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <span>Cibersegurança</span>
                    </a>
                    <a href="../monitor/perfilMonitor.php?cat=ia" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-brain"></i></div>
                        <span>IA</span>
                    </a>
                    <a href="../monitor/perfilMonitor.php?cat=outros" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-ellipsis"></i></div>
                        <span>Outros</span>
                    </a>
                </div>

                <!-- PAINEL DE RESULTADOS AO VIVO (EXIBIDO AO DIGITAR) -->
                <div class="search-live-results" id="liveResults" style="display: none;">
                    <div class="results-section-title">Monitores Encontrados</div>
                    <a href="../monitor/perfilMonitor.php" class="result-item">
                        <div class="result-avatar">M</div>
                        <div class="result-info">
                            <strong>Marcos Silva Pereira</strong>
                            <small>Monitor de Banco de Dados e SQL</small>
                        </div>
                        <span class="badge-online">Online</span>
                    </a>

                    <div class="results-section-title">Matérias Relacionadas</div>
                    <a href="#" class="result-item">
                        <div class="result-icon-subject"><i class="fa-solid fa-book"></i></div>
                        <div class="result-info">
                            <strong>Modelagem de Dados e DER</strong>
                            <small>Sistemas de Informação • 3º Semestre</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. AÇÕES DA NAVBAR -->
        <div class="navbar-actions">
            
            <a href="../monitor/perfilMonitor.php" class="nav-icon-btn" title="Início">
                <i class="fa-solid fa-house"></i>
            </a>

            <a href="#" class="nav-icon-btn" title="Matérias">
                <i class="fa-solid fa-book-bookmark"></i>
            </a>

            <a href="#" class="nav-icon-btn" title="Monitores">
                <i class="fa-solid fa-user-graduate"></i>
            </a>

            <!-- ÍCONE RESERVADO PARA A TELA COMPLETA DE AGENDA/CALENDÁRIO -->
            <a href="#" class="nav-icon-btn" title="Agenda e Horários (Em breve página completa)">
                <i class="fa-solid fa-calendar-days"></i>
            </a>

            <!-- DROPDOWN MENSAGENS -->
            <div class="nav-dropdown-wrapper">
                <button type="button" class="nav-icon-btn" id="btnMessages" title="Mensagens">
                    <i class="fa-solid fa-comments"></i>
                    <span class="notification-badge">2</span>
                </button>
                <div class="popover-dropdown" id="messagesDropdown">
                    <div class="popover-header">
                        <h4>Conversas Recentes</h4>
                        <a href="#">Ver todas</a>
                    </div>
                    <div class="popover-body">
                        <a href="#" class="message-item unread">
                            <div class="msg-avatar">G</div>
                            <div class="msg-content">
                                <strong>Gabriel Souza</strong>
                                <p>Consegue me ajudar hoje no laboratório?</p>
                                <small>14:20</small>
                            </div>
                        </a>
                        <a href="#" class="message-item">
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

            <!-- DROPDOWN NOTIFICAÇÕES -->
            <div class="nav-dropdown-wrapper">
                <button type="button" class="nav-icon-btn" id="btnNotifications" title="Notificações">
                    <i class="fa-solid fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="popover-dropdown" id="notificationsDropdown">
                    <div class="popover-header">
                        <h4>Notificações</h4>
                        <a href="#">Marcar lidas</a>
                    </div>
                    <div class="popover-body">
                        <div class="notif-item unread">
                            <div class="notif-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                            <div class="notif-content">
                                <p><strong>Gabriel S.</strong> solicitou agendamento de <strong>SQL</strong>.</p>
                                <div class="notif-actions">
                                    <button class="btn-notif-accept">Aceitar</button>
                                    <button class="btn-notif-decline">Recusar</button>
                                </div>
                                <small class="notif-time">Há 5 min</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nav-divider"></div>

            <!-- 4. MENU DO USUÁRIO -->
            <div class="nav-user-wrapper" id="navUserWrapper">
                <div class="nav-user-dropdown" id="btnUserMenu">
                    <div class="nav-user-avatar"><?php echo htmlspecialchars($inicialNome); ?></div>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </div>

                <div class="user-menu-dropdown" id="userMenuDropdown">
                    <div class="user-menu-header">
                        <strong><?php echo htmlspecialchars($nomeUsuario); ?></strong>
                        <small><?php echo htmlspecialchars($emailUsuario); ?></small>
                        <span class="user-role-badge"><?php echo strtoupper($tipoUsuario); ?></span>
                    </div>

                    <div class="status-selector">
                        <small>Status atual:</small>
                        <select class="status-select">
                            <option value="online">🟢 Online</option>
                            <option value="busy">🔴 Em Atendimento</option>
                            <option value="away">🟡 Ausente</option>
                        </select>
                    </div>

                    <ul class="user-menu-links">
                        <li>
                            <a href="../monitor/perfilMonitor.php">
                                <i class="fa-solid fa-id-card"></i> Meu Perfil
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fa-solid fa-sliders"></i> Configurações
                            </a>
                        </li>
                        <li class="logout-item">
                            <a href="../../actions/logout.php">
                                <i class="fa-solid fa-right-from-bracket"></i> Sair da conta
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput           = document.getElementById('searchInput');
        const searchDropdown        = document.getElementById('searchDropdown');
        const btnCloseDropdown     = document.getElementById('btnCloseDropdown');
        const btnClearSearch        = document.getElementById('btnClearSearch');
        const categoriesGrid        = document.getElementById('categoriesGrid');
        const liveResults           = document.getElementById('liveResults');

        const btnMessages           = document.getElementById('btnMessages');
        const messagesDropdown      = document.getElementById('messagesDropdown');

        const btnNotifications      = document.getElementById('btnNotifications');
        const notificationsDropdown = document.getElementById('notificationsDropdown');

        const btnUserMenu           = document.getElementById('btnUserMenu');
        const userMenuDropdown      = document.getElementById('userMenuDropdown');

        const allDropdowns = [searchDropdown, messagesDropdown, notificationsDropdown, userMenuDropdown];

        function fecharTodos() {
            allDropdowns.forEach(drop => {
                if(drop) drop.classList.remove('active');
            });
        }

        // Lógica da Busca
        if (searchInput && searchDropdown) {
            searchInput.addEventListener('focus', function () {
                fecharTodos();
                searchDropdown.classList.add('active');
            });

            searchInput.addEventListener('input', function () {
                if (this.value.trim().length >= 2) {
                    categoriesGrid.style.display = 'none';
                    liveResults.style.display = 'block';
                    btnClearSearch.style.display = 'block';
                } else {
                    categoriesGrid.style.display = 'grid';
                    liveResults.style.display = 'none';
                    btnClearSearch.style.display = 'none';
                }
            });

            if (btnClearSearch) {
                btnClearSearch.addEventListener('click', function () {
                    searchInput.value = '';
                    categoriesGrid.style.display = 'grid';
                    liveResults.style.display = 'none';
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

        // Toggles para cada Dropdown (Mensagens, Notificações, Usuário)
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

        // Fechar ao clicar fora
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.navbar-search-wrapper') && !e.target.closest('.navbar-actions')) {
                fecharTodos();
            }
        });
    });
</script>