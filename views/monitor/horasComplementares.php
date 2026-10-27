<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('monitor');

// Configura o título da página e recupera o tipo de usuário logado
$tituloPagina = "Horas Complementares - UNIVAG ATHENAS";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ===== 2. METADADOS E BIBLIOTECAS ===== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?></title>
    
    <!-- Ícones do FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Exclusivo de Horas Complementares -->
    <link rel="stylesheet" href="../../assets/css/horasComplementares.css">
</head>
<body>

    <!-- ===== 3. INCLUSÃO DA NAVBAR UNIVERSAL ===== -->
    <?php include '../../includes/navbar.php'; ?>

    <!-- ===== 4. CONTAINER PRINCIPAL ===== -->
    <main class="horas-main-container">
        <div class="horas-content-wrapper">

            <!-- ===== 5. CABEÇALHO DA PÁGINA ===== -->
            <header class="horas-header-bloco">
                <h1>Horas complementares</h1>
                <p>Acompanhe suas horas e veja como está seu progresso.</p>
            </header>

            <!-- ===== 6. CARD HERO DE PROGRESSO DE HORAS ===== -->
            <section class="card-hero-progresso">
                <div class="hero-total-bloco">
                    <small>Total de horas</small>
                    <div class="hero-valor-horas">
                        <strong>32h</strong>
                        <span>de 60h necessárias</span>
                    </div>
                </div>

                <div class="divisor-hero-vertical"></div>

                <div class="hero-barra-bloco">
                    <div class="barra-progresso-container">
                        <div class="barra-progresso-preenchimento" style="width: 53%;"></div>
                    </div>
                    <div class="meta-rotulo">
                        <span>Metas do semestre</span>
                        <strong>53%</strong>
                    </div>
                </div>
            </section>

            <!-- ===== 7. CARD DA TABELA DE ÚLTIMAS ATIVIDADES ===== -->
            <section class="card-tabela-atividades">
                <div class="tabela-header">
                    <h2>Últimas atividades</h2>
                    <a href="javascript:void(0);" class="link-ver-todas">Ver todas</a>
                </div>

                <div class="tabela-responsive-wrapper">
                    <table class="tabela-atividades">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Monitoria</th>
                                <th>Horas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>18/04/2026</td>
                                <td><strong>Python - Nível Intermediário</strong></td>
                                <td>2h</td>
                                <td><span class="badge-status status-validado">Validado</span></td>
                            </tr>
                            <tr>
                                <td>12/04/2026</td>
                                <td><strong>Banco de dados - SQL</strong></td>
                                <td>2h</td>
                                <td><span class="badge-status status-validacao">Em validação</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

</body>
</html>