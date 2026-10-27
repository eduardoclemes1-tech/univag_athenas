<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('monitor');

$tituloPagina = "Meus Certificados - UNIVAG ATHENAS";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ===== 2. METADADOS E ESTILOS ===== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?></title>
    
    <!-- Ícones do FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Exclusivo de Certificados -->
    <link rel="stylesheet" href="../../assets/css/certificados.css">
</head>
<body>

    <!-- ===== 3. NAVBAR UNIVERSAL ===== -->
    <?php include '../../includes/navbar.php'; ?>

    <!-- ===== 4. CONTAINER PRINCIPAL ===== -->
    <main class="certificados-main-container">
        <div class="certificados-content-wrapper">

            <!-- ===== 5. CABEÇALHO DA PÁGINA ===== -->
            <header class="certificados-header">
                <h1>Certificados</h1>
                <p>Veja aqui os certificados de suas monitorias concluídas..</p>
            </header>

            <!-- ===== 6. CARD PRINCIPAL E SEPARADORES ===== -->
            <section class="card-certificados-container">
                
                <!-- Separadores (Tabs) -->
                <div class="tabs-certificados">
                    <button type="button" class="btn-tab ativo" id="tabDisponiveis">Disponíveis</button>
                    <button type="button" class="btn-tab" id="tabEmBreve">Em breve</button>
                </div>

                <!-- Painel de Certificados Disponíveis -->
                <div class="painel-certificados ativo" id="painelDisponiveis">
                    <div class="lista-certificados">

                        <!-- Item Certificado 1 -->
                        <article class="item-certificado">
                            <div class="certificado-icone-bloco">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            <div class="certificado-info">
                                <h2>Python</h2>
                                <p>Monitor: Marcos Silva</p>
                                <small>Data: 18/05/2026 • 2h</small>
                            </div>
                            <button type="button" class="btn-baixar-certificado">Baixar</button>
                        </article>

                        <!-- Item Certificado 2 -->
                        <article class="item-certificado">
                            <div class="certificado-icone-bloco">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            <div class="certificado-info">
                                <h2>Banco de dados - SQL</h2>
                                <p>Monitor: Marcos Silva</p>
                                <small>Data: 20/05/2026 • 2h</small>
                            </div>
                            <button type="button" class="btn-baixar-certificado">Baixar</button>
                        </article>

                    </div>
                </div>

                <!-- Painel de Certificados Em Breve -->
                <div class="painel-certificados" id="painelEmBreve" style="display: none;">
                    <div class="estado-vazio-certificados">
                        <i class="fa-solid fa-clock"></i>
                        <p>Nenhum certificado pendente no momento.</p>
                    </div>
                </div>

            </section>

        </div>
    </main>

    <!-- ===== 7. JAVASCRIPT DE ALTERNÂNCIA DE TABS ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabDisponiveis    = document.getElementById('tabDisponiveis');
        const tabEmBreve        = document.getElementById('tabEmBreve');
        const painelDisponiveis = document.getElementById('painelDisponiveis');
        const painelEmBreve     = document.getElementById('painelEmBreve');

        tabDisponiveis.addEventListener('click', function () {
            tabEmBreve.classList.remove('ativo');
            this.classList.add('ativo');
            painelEmBreve.style.display = 'none';
            painelDisponiveis.style.display = 'block';
        });

        tabEmBreve.addEventListener('click', function () {
            tabDisponiveis.classList.remove('ativo');
            this.classList.add('ativo');
            painelDisponiveis.style.display = 'none';
            painelEmBreve.style.display = 'block';
        });
    });
    </script>

</body>
</html>