<!-- Chamada automática do CSS exclusivo da Navbar -->
<link rel="stylesheet" href="../../assets/css/navbar.css">

<nav class="navbar-container">
    <div class="navbar-content">
        <!-- Logo Univag Athenas -->
        <div class="navbar-logo">
            <a href="../monitor/perfilMonitor.php">
                <img src="../../assets/img/logoAthenas.png" alt="UNIVAG ATHENAS" class="logo-img">
            </a>
        </div>

        <!-- Barra de Pesquisa + Dropdown "Explorar" -->
        <div class="navbar-search-wrapper">
            <div class="navbar-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchInput" placeholder="Buscar alunos, matérias...." autocomplete="off">
            </div>

            <!-- Card Modal Dropdown (Estilo Figma) -->
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

                <div class="dropdown-grid">
                    <a href="#" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-code"></i></div>
                        <span>Programação</span>
                    </a>
                    <a href="#" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-database"></i></div>
                        <span>Dados</span>
                    </a>
                    <a href="#" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                        <span>Mobile</span>
                    </a>
                    <a href="#" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <span>Cibersegurança</span>
                    </a>
                    <a href="#" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-brain"></i></div>
                        <span>IA</span>
                    </a>
                    <a href="#" class="category-card">
                        <div class="category-icon"><i class="fa-solid fa-ellipsis"></i></div>
                        <span>Outros</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Ícones de Navegação -->
        <div class="navbar-actions">
            <a href="#" class="nav-icon-btn" title="Início"><i class="fa-solid fa-house"></i></a>
            <a href="#" class="nav-icon-btn" title="Matérias"><i class="fa-solid fa-book-bookmark"></i></a>
            <a href="#" class="nav-icon-btn" title="Monitores"><i class="fa-solid fa-user-graduate"></i></a>
            <a href="#" class="nav-icon-btn" title="Agenda"><i class="fa-solid fa-calendar-days"></i></a>
            <a href="#" class="nav-icon-btn" title="Mensagens"><i class="fa-solid fa-comments"></i></a>
            <a href="#" class="nav-icon-btn badge-btn" title="Notificações">
                <i class="fa-solid fa-bell"></i>
                <span class="notification-badge">15+</span>
            </a>

            <div class="nav-divider"></div>

            <div class="nav-user-dropdown">
                <div class="nav-user-avatar">M</div>
                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const searchDropdown = document.getElementById('searchDropdown');
        const btnCloseDropdown = document.getElementById('btnCloseDropdown');

        if (searchInput && searchDropdown) {
            searchInput.addEventListener('focus', function () {
                searchDropdown.classList.add('active');
            });

            if (btnCloseDropdown) {
                btnCloseDropdown.addEventListener('click', function (e) {
                    e.stopPropagation();
                    searchDropdown.classList.remove('active');
                });
            }

            document.addEventListener('click', function (e) {
                if (!searchDropdown.contains(e.target) && e.target !== searchInput) {
                    searchDropdown.classList.remove('active');
                }
            });
        }
    });
</script>