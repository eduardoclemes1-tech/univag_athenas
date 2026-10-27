<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado();

$tipoUsuario = $_SESSION['usuario_tipo'];
$ehMonitor = ($tipoUsuario === 'monitor');
$tituloPagina = "Agenda - UNIVAG ATHENAS";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ===== 2. CONFIGURAÇÕES E METADADOS ===== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?></title>
    
    <!-- Ícones do FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Exclusivo do Calendário -->
    <link rel="stylesheet" href="../../assets/css/calendario.css?v=3">
</head>
<body class="<?php echo $ehMonitor ? 'monitor-theme' : 'aluno-theme'; ?>">

    <!-- ===== 3. NAVBAR UNIVERSAL ===== -->
    <?php include '../../includes/navbar.php'; ?>

    <!-- ===== 4. CONTAINER PRINCIPAL ===== -->
    <main class="calendario-main-container">
        <div class="calendario-content-wrapper">

            <!-- ===== 5. GRID PRINCIPAL ===== -->
            <div class="calendario-grid">

                <!-- ===== 6. COLUNA ESQUERDA: GRID DO CALENDÁRIO MENSAL/SEMANAL ===== -->
                <section class="coluna-esquerda">
                    <article class="card-calendario-principal">
                        <div class="agenda-aviso" role="note">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            <p>
                                Esta agenda ainda não está conectada aos agendamentos do sistema. Nenhum compromisso ou ação nesta tela é salvo.
                                Para solicitar, confirmar ou acessar uma monitoria, use o chat.
                            </p>
                        </div>
                        
                        <!-- Cabeçalho do Calendário e Alternador de Visão -->
                        <div class="calendario-header">
                            <div class="calendario-titulo-bloco">
                                <i class="fa-solid fa-calendar-days icone-titulo"></i>
                                <div>
                                    <h1>Agenda do <?php echo $ehMonitor ? 'Monitor' : 'Aluno'; ?></h1>
                                    <p>
                                        <?php if ($ehMonitor): ?>
                                        Acompanhe seus estudos e acesse suas conversas com alunos
                                        <?php else: ?>
                                        Organize seus estudos e acesse suas conversas com monitores
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Controles de Mês/Semana e Alternador -->
                            <div class="controles-topo-wrapper">
                                <!-- Botões para Alternar Visão (Mês vs Semana) -->
                                <div class="seletor-visao">
                                    <button type="button" class="btn-visao ativo" id="btnVisaoMes">Mês</button>
                                    <button type="button" class="btn-visao" id="btnVisaoSemana">Semana</button>
                                </div>

                                <!-- Navegação de Período -->
                                <div class="controles-mes">
                                    <button type="button" class="btn-nav-mes" id="btnMesAnterior" title="Anterior">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <div class="seletor-mes-wrapper">
                                        <span id="labelMesAno"></span>
                                    </div>
                                    <button type="button" class="btn-nav-mes" id="btnProximoMes" title="Próximo">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Dias da Semana -->
                        <div class="grid-dias-semana">
                            <span>Seg</span>
                            <span>Ter</span>
                            <span>Qua</span>
                            <span>Qui</span>
                            <span>Sex</span>
                            <span>Sáb</span>
                            <span>Dom</span>
                        </div>

                        <!-- Células Dinâmicas (Renderizadas pelo JS) -->
                        <div class="grid-dias-mes" id="gridDiasMes">
                            <!-- Injetado via JavaScript -->
                        </div>

                        <!-- Legenda dos Marcadores com Filtro Interativo -->
                        <div class="legenda-calendario">
                            <span class="label-filtro">Filtrar:</span>
                            <button type="button" class="item-legenda ativo" data-filtro="todos">
                                <i class="fa-solid fa-layer-group"></i> Todos
                            </button>
                            <button type="button" class="item-legenda" data-filtro="aula">
                                <i class="fa-solid fa-circle dot-aula"></i> <?php echo $ehMonitor ? 'Atendimentos' : 'Aulas / Monitorias'; ?>
                            </button>
                            <button type="button" class="item-legenda" data-filtro="reuniao">
                                <i class="fa-solid fa-circle dot-reuniao"></i> Reuniões
                            </button>
                            <button type="button" class="item-legenda" data-filtro="tarefa">
                                <i class="fa-solid fa-circle dot-tarefa"></i> Tarefas
                            </button>
                        </div>

                    </article>
                </section>

                <!-- ===== 7. COLUNA DIREITA: DETALHES DO DIA SELECIONADO ===== -->
                <aside class="coluna-direita">
                    <article class="card-detalhes-dia">
                        
                        <!-- Cabeçalho do Painel Lateral -->
                        <div class="detalhes-dia-header">
                            <div class="detalhes-dia-titulo">
                                <i class="fa-solid fa-calendar-day"></i>
                                <h2 id="tituloDiaSelecionado"></h2>
                            </div>
                            <span class="badge-dia-semana" id="badgeDiaSemana"></span>
                        </div>

                        <!-- Lista Dinâmica de Compromissos do Dia -->
                        <div class="lista-compromissos" id="listaCompromissos">
                            <!-- Injetado via JavaScript -->
                        </div>

                        <div class="ilustracao-rodape">
                            <i class="fa-solid fa-book-bookmark"></i>
                        </div>

                    </article>
                </aside>

            </div>

        </div>
    </main>

    <!-- ===== INTERATIVIDADE DO CALENDÁRIO ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const hoje = new Date();

        const estado = {
            ano: hoje.getFullYear(),
            mes: hoje.getMonth(),
            dia: hoje.getDate(),
            visao: 'mes',
            filtro: 'todos'
        };

        const bancoCompromissos = {};

        const nomesMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const nomesDiasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];

        // Elementos do DOM
        const gridDias          = document.getElementById('gridDiasMes');
        const labelMesAno       = document.getElementById('labelMesAno');
        const tituloDia         = document.getElementById('tituloDiaSelecionado');
        const badgeDiaSemana    = document.getElementById('badgeDiaSemana');
        const listaCompromissos = document.getElementById('listaCompromissos');
        const btnMesAnterior    = document.getElementById('btnMesAnterior');
        const btnProximoMes     = document.getElementById('btnProximoMes');
        const btnVisaoMes       = document.getElementById('btnVisaoMes');
        const btnVisaoSemana    = document.getElementById('btnVisaoSemana');
        const botoesFiltro      = document.querySelectorAll('.item-legenda');

        function padZero(n) { return n < 10 ? '0' + n : n; }

        function renderizarCompromissos() {
            const chaveData = `${estado.ano}-${padZero(estado.mes + 1)}-${padZero(estado.dia)}`;
            const dataObj = new Date(estado.ano, estado.mes, estado.dia);
            
            const dataHoje = estado.dia === hoje.getDate()
                && estado.mes === hoje.getMonth()
                && estado.ano === hoje.getFullYear();
            tituloDia.textContent = dataHoje
                ? `Hoje, ${estado.dia} de ${nomesMeses[estado.mes]}`
                : `${estado.dia} de ${nomesMeses[estado.mes]}`;
            badgeDiaSemana.textContent = nomesDiasSemana[dataObj.getDay()];

            let itens = bancoCompromissos[chaveData] || [];

            if (estado.filtro !== 'todos') {
                itens = itens.filter(i => i.tipo === estado.filtro);
            }

            listaCompromissos.innerHTML = '';

            if (itens.length === 0) {
                listaCompromissos.innerHTML = `
                    <div class="estado-vazio-compromissos">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <p>Nenhum compromisso para esta visualização.</p>
                    </div>`;
                return;
            }

            itens.forEach(item => {
                const el = document.createElement('div');
                el.className = `item-compromisso tipo-${item.tipo}`;
                
                el.innerHTML = `
                    <div class="horario-compromisso">${item.horario}</div>
                    <div class="info-compromisso">
                        <span class="dot-compromisso"></span>
                        <div>
                            <strong>${item.titulo}</strong>
                            <small>${item.desc}</small>
                        </div>
                    </div>
                `;
                listaCompromissos.appendChild(el);
            });
        }

        // Renderiza a Grade do Calendário (Visão Mês ou Semana)
        function renderizarCalendario() {
            gridDias.innerHTML = '';
            labelMesAno.textContent = `${nomesMeses[estado.mes]} ${estado.ano}`;

            if (estado.visao === 'mes') {
                gridDias.classList.remove('modo-semana');
                const primeiroDia = new Date(estado.ano, estado.mes, 1);
                let diaInicio = primeiroDia.getDay() - 1;
                if (diaInicio === -1) diaInicio = 6;

                const totalDias = new Date(estado.ano, estado.mes + 1, 0).getDate();
                const totalDiasAnt = new Date(estado.ano, estado.mes, 0).getDate();

                // Dias do Mês Anterior
                for (let i = diaInicio - 1; i >= 0; i--) {
                    const celula = document.createElement('div');
                    celula.className = 'celula-dia dia-outro-mes';
                    celula.innerHTML = `<span class="numero-dia">${totalDiasAnt - i}</span>`;
                    gridDias.appendChild(celula);
                }

                // Dias do Mês Atual
                for (let dia = 1; dia <= totalDias; dia++) {
                    desenharCelulaDia(dia);
                }

                // Dias do Próximo Mês
                const celulasAtuais = diaInicio + totalDias;
                const celulasRestantes = (celulasAtuais > 35 ? 42 : 35) - celulasAtuais;
                for (let i = 1; i <= celulasRestantes; i++) {
                    const celula = document.createElement('div');
                    celula.className = 'celula-dia dia-outro-mes';
                    celula.innerHTML = `<span class="numero-dia">${i}</span>`;
                    gridDias.appendChild(celula);
                }
            } else {
                gridDias.classList.add('modo-semana');
                const inicioSemana = new Date(estado.ano, estado.mes, estado.dia);
                inicioSemana.setDate(inicioSemana.getDate() - ((inicioSemana.getDay() + 6) % 7));

                for (let deslocamento = 0; deslocamento < 7; deslocamento++) {
                    const data = new Date(inicioSemana);
                    data.setDate(inicioSemana.getDate() + deslocamento);
                    desenharCelulaData(data);
                }
            }
        }

        function desenharCelulaDia(dia) {
            desenharCelulaData(new Date(estado.ano, estado.mes, dia));
        }

        function desenharCelulaData(data) {
            const celula = document.createElement('div');
            celula.className = 'celula-dia';
            const ano = data.getFullYear();
            const mes = data.getMonth();
            const dia = data.getDate();

            if (dia === hoje.getDate() && mes === hoje.getMonth() && ano === hoje.getFullYear()) {
                celula.classList.add('dia-hoje');
            }
            if (dia === estado.dia && mes === estado.mes && ano === estado.ano) {
                celula.classList.add('dia-selecionado');
            }

            const chave = `${ano}-${padZero(mes + 1)}-${padZero(dia)}`;
            let comps = bancoCompromissos[chave] || [];

            if (estado.filtro !== 'todos') {
                comps = comps.filter(c => c.tipo === estado.filtro);
            }

            let htmlDots = '';
            if (comps.length > 0) {
                htmlDots = '<div class="container-dots-dia">';
                comps.forEach(c => { htmlDots += `<span class="dot-dia dot-${c.tipo}"></span>`; });
                htmlDots += '</div>';
            }

            celula.innerHTML = `<span class="numero-dia">${dia}</span>${htmlDots}`;
            celula.addEventListener('click', function () {
                document.querySelectorAll('.celula-dia').forEach(c => c.classList.remove('dia-selecionado'));
                celula.classList.add('dia-selecionado');
                estado.ano = ano;
                estado.mes = mes;
                estado.dia = dia;
                labelMesAno.textContent = `${nomesMeses[mes]} ${ano}`;
                renderizarCompromissos();
            });

            gridDias.appendChild(celula);
        }

        // Eventos dos Filtros de Categoria (A1)
        botoesFiltro.forEach(btn => {
            btn.addEventListener('click', function () {
                botoesFiltro.forEach(b => b.classList.remove('ativo'));
                this.classList.add('ativo');
                estado.filtro = this.getAttribute('data-filtro');
                renderizarCalendario();
                renderizarCompromissos();
            });
        });

        // Alternador Mês/Semana (A3)
        btnVisaoMes.addEventListener('click', function () {
            btnVisaoSemana.classList.remove('ativo');
            this.classList.add('ativo');
            estado.visao = 'mes';
            renderizarCalendario();
        });

        btnVisaoSemana.addEventListener('click', function () {
            btnVisaoMes.classList.remove('ativo');
            this.classList.add('ativo');
            estado.visao = 'semana';
            renderizarCalendario();
        });

        // Navegação de Meses
        btnMesAnterior.addEventListener('click', function () {
            estado.mes--; if (estado.mes < 0) { estado.mes = 11; estado.ano--; }
            estado.dia = Math.min(estado.dia, new Date(estado.ano, estado.mes + 1, 0).getDate());
            renderizarCalendario(); renderizarCompromissos();
        });

        btnProximoMes.addEventListener('click', function () {
            estado.mes++; if (estado.mes > 11) { estado.mes = 0; estado.ano++; }
            estado.dia = Math.min(estado.dia, new Date(estado.ano, estado.mes + 1, 0).getDate());
            renderizarCalendario(); renderizarCompromissos();
        });

        // Inicialização
        renderizarCalendario();
        renderizarCompromissos();
    });
    </script>

</body>
</html>