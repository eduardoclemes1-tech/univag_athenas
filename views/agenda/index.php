<?php
/* ===== 1. INICIALIZAÇÃO DE SESSÃO E VERIFICAÇÃO DE PERMISSÕES ===== */
// Inicializa a sessão do PHP caso não esteja ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Recupera o tipo de usuário logado (fallback para 'monitor' em testes)
$tipoUsuario  = $_SESSION['usuario_tipo'] ?? 'monitor';$ehMonitor    = ($tipoUsuario === 'monitor');$tituloPagina = "Calendário de Compromissos - UNIVAG ATHENAS";
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
    <link rel="stylesheet" href="../../assets/css/calendario.css">
</head>
<body>

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
                        
                        <!-- Cabeçalho do Calendário e Alternador de Visão -->
                        <div class="calendario-header">
                            <div class="calendario-titulo-bloco">
                                <i class="fa-solid fa-calendar-days icone-titulo"></i>
                                <div>
                                    <h1>Calendário do <?php echo $ehMonitor ? 'Monitor' : 'Aluno'; ?></h1>
                                    <p>
                                        <?php if ($ehMonitor): ?>
                                            Gerencie seus atendimentos, compromissos e solicitações de alunos
                                        <?php else: ?>
                                            Visualise suas monitorias agendadas, aulas e atividades em só um lugar
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
                                        <span id="labelMesAno">Setembro 2026</span>
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
                                <h2 id="tituloDiaSelecionado">Hoje, 18 de Setembro</h2>
                            </div>
                            <span class="badge-dia-semana" id="badgeDiaSemana">Sexta-feira</span>
                        </div>

                        <?php if ($ehMonitor): ?>
                            <!-- Botão de Ação Rápida Exclusivo para o Monitor -->
                            <div class="container-acao-monitor">
                                <button type="button" class="btn-acao-bloqueio" data-modal-target="modalBloquearHorario">
                                    <i class="fa-solid fa-ban"></i> Bloquear Horário / Imprevisto
                                </button>
                            </div>
                        <?php endif; ?>

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

    <?php if ($ehMonitor): ?>
    <!-- ===== 8. MODAL DE BLOQUEIO DE HORÁRIO (EXCLUSIVO DO MONITOR) ===== -->
    <div class="modal-overlay" id="modalBloquearHorario">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-ban"></i> Bloquear Horário de Atendimento</h3>
                <button type="button" class="btn-modal-fechar" data-modal-close><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="formBloquearHorario" class="modal-form">
                <div class="form-group">
                    <label for="inputDataBloqueio">Data do Bloqueio</label>
                    <input type="date" id="inputDataBloqueio" class="form-input" value="2026-09-18" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="inputHoraInicio">Hora Início</label>
                        <input type="time" id="inputHoraInicio" class="form-input" value="14:00" required>
                    </div>
                    <div class="form-group">
                        <label for="inputHoraFim">Hora Fim</label>
                        <input type="time" id="inputHoraFim" class="form-input" value="16:00" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputMotivoBloqueio">Motivo / Observação</label>
                    <input type="text" id="inputMotivoBloqueio" class="form-input" placeholder="Ex: Prova presencial / Imprevisto pessoal" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-salvar">Confirmar Bloqueio</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== 9. INTERATIVIDADE E MOTOR REATIVO DO CALENDÁRIO ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ehMonitor = <?php echo $ehMonitor ? 'true' : 'false'; ?>;

        // Objeto de Estado do Calendário
        const estado = {
            ano: 2026,
            mes: 8, // Setembro (índice 0 = Jan)
            dia: 18,
            visao: 'mes', // 'mes' ou 'semana'
            filtro: 'todos' // 'todos', 'aula', 'reuniao', 'tarefa'
        };

        // Base de Dados Simulado do Monitor
        const compromissosMonitor = {
            '2026-09-02': [
                { id: 101, horario: '14:00 - 16:00', tipo: 'aula', status: 'confirmado', titulo: 'Atendimento: Gabriel Souza', desc: 'Dúvidas em Modelagem DER', urlSala: 'https://meet.google.com' }
            ],
            '2026-09-18': [
                { id: 102, horario: '08:00 - 10:00', tipo: 'aula', status: 'pendente', titulo: 'Solicitação: Gabriel Souza', desc: 'Projeto de Banco de Dados e SQL', aluno: 'Gabriel S.' },
                { id: 103, horario: '10:30 - 12:00', tipo: 'reuniao', status: 'confirmado', titulo: 'Reunião de Grupo', desc: 'Estrutura de Dados', urlSala: 'https://meet.google.com' },
                { id: 104, horario: '14:00 - 16:00', tipo: 'tarefa', status: 'confirmado', titulo: 'Preparar Exercícios', desc: 'Elaboração de lista de Joins' }
            ]
        };

        // Base de Dados Simulado do Aluno
        const compromissosAluno = {
            '2026-09-02': [
                { id: 201, horario: '14:00 - 16:00', tipo: 'aula', status: 'confirmado', titulo: 'Monitoria - Banco de Dados', desc: 'Com Monitor Marcos Silva', urlSala: 'https://meet.google.com' }
            ],
            '2026-09-18': [
                { id: 202, horario: '08:00 - 10:00', tipo: 'aula', status: 'pendente', titulo: 'Monitoria - Banco de Dados', desc: 'Aguardando confirmação de Marcos' },
                { id: 203, horario: '10:30 - 12:00', tipo: 'reuniao', status: 'confirmado', titulo: 'Grupo de Estudos', desc: 'Estrutura de Dados' }
            ]
        };

        let bancoCompromissos = ehMonitor ? compromissosMonitor : compromissosAluno;

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

        // Renderiza os Compromissos do Dia Selecionado com as Interatividades Solicitadas
        function renderizarCompromissos() {
            const chaveData = `${estado.ano}-${padZero(estado.mes + 1)}-${padZero(estado.dia)}`;
            const dataObj = new Date(estado.ano, estado.mes, estado.dia);
            
            tituloDia.textContent = (estado.dia === 18 && estado.mes === 8 && estado.ano === 2026) ? `Hoje, ${estado.dia} de ${nomesMeses[estado.mes]}` : `${estado.dia} de ${nomesMeses[estado.mes]}`;
            badgeDiaSemana.textContent = nomesDiasSemana[dataObj.getDay()];

            let itens = bancoCompromissos[chaveData] || [];

            // Aplica o filtro por categoria da legenda
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
                
                // Botão de Sala Virtual (A2)
                let btnSalaHtml = item.urlSala ? `<a href="${item.urlSala}" target="_blank" class="btn-link-sala" title="Acessar Sala"><i class="fa-solid fa-video"></i> Sala</a>` : '';
                
                // Botões de Ação Dinâmicos (B1, B2, C2)
                let acoesHtml = '';
                if (ehMonitor) {
                    if (item.status === 'pendente') {
                        acoesHtml = `
                            <div class="acoes-item-grupo">
                                <button type="button" class="btn-notif-accept btn-acao-m" data-acao="aceitar" data-id="${item.id}" title="Aceitar"><i class="fa-solid fa-check"></i></button>
                                <button type="button" class="btn-notif-decline btn-acao-m" data-acao="recusar" data-id="${item.id}" title="Recusar"><i class="fa-solid fa-xmark"></i></button>
                                <button type="button" class="btn-chat-m" data-acao="chat" title="Abrir Chat"><i class="fa-solid fa-comments"></i></button>
                            </div>
                        `;
                    } else {
                        acoesHtml = `<button type="button" class="btn-chat-m" data-acao="chat" title="Abrir Chat"><i class="fa-solid fa-comments"></i></button>`;
                    }
                } else {
                    // Visão do Aluno: Cancelamento (C2)
                    acoesHtml = `<button type="button" class="btn-cancelar-m" data-acao="cancelar" data-id="${item.id}" title="Cancelar Solicitação"><i class="fa-solid fa-trash"></i></button>`;
                }

                el.innerHTML = `
                    <div class="horario-compromisso">${item.horario}</div>
                    <div class="info-compromisso">
                        <span class="dot-compromisso"></span>
                        <div>
                            <strong>${item.titulo}</strong>
                            <small>${item.desc}</small>
                        </div>
                    </div>
                    <div class="botoes-card-container">
                        ${btnSalaHtml}
                        ${acoesHtml}
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
                // Visão de Semana (A3)
                gridDias.classList.add('modo-semana');
                for (let dia = 14; dia <= 20; dia++) {
                    desenharCelulaDia(dia);
                }
            }
        }

        function desenharCelulaDia(dia) {
            const celula = document.createElement('div');
            celula.className = 'celula-dia';
            
            if (dia === 18 && estado.mes === 8 && estado.ano === 2026) celula.classList.add('dia-hoje');
            if (dia === estado.dia) celula.classList.add('dia-selecionado');

            const chave = `${estado.ano}-${padZero(estado.mes + 1)}-${padZero(dia)}`;
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
                estado.dia = dia;
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

        // Eventos Delegados nas Ações dos Cards
        listaCompromissos.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-acao]');
            if (!btn) return;

            const acao = btn.getAttribute('data-acao');
            const id = btn.getAttribute('data-id');

            if (acao === 'aceitar') {
                alert(`Solicitação #${id} aceita com sucesso!`);
            } else if (acao === 'recusar') {
                alert(`Solicitação #${id} recusada.`);
            } else if (acao === 'chat') {
                alert('Redirecionando para a central de mensagens...');
            } else if (acao === 'cancelar') {
                if (confirm('Deseja realmente cancelar este agendamento?')) {
                    alert('Agendamento cancelado com sucesso.');
                }
            }
        });

        // Gerenciador de Modais Generic
        const modaisAbrir = document.querySelectorAll('[data-modal-target]');
        const modaisFechar = document.querySelectorAll('[data-modal-close]');
        const overlays = document.querySelectorAll('.modal-overlay');

        function fecharModais() { overlays.forEach(m => m.classList.remove('ativo')); }

        modaisAbrir.forEach(b => b.addEventListener('click', function() {
            const m = document.getElementById(this.getAttribute('data-modal-target'));
            if (m) m.classList.add('ativo');
        }));

        modaisFechar.forEach(b => b.addEventListener('click', fecharModais));

        // Submit do Bloqueio Rápido (B3)
        const formBloqueio = document.getElementById('formBloquearHorario');
        if (formBloqueio) {
            formBloqueio.addEventListener('submit', function (e) {
                e.preventDefault();
                alert('Horário bloqueado com sucesso na sua agenda!');
                fecharModais();
            });
        }

        // Navegação de Meses
        btnMesAnterior.addEventListener('click', function () {
            estado.mes--; if (estado.mes < 0) { estado.mes = 11; estado.ano--; }
            renderizarCalendario(); renderizarCompromissos();
        });

        btnProximoMes.addEventListener('click', function () {
            estado.mes++; if (estado.mes > 11) { estado.mes = 0; estado.ano++; }
            renderizarCalendario(); renderizarCompromissos();
        });

        // Inicialização
        renderizarCalendario();
        renderizarCompromissos();
    });
    </script>

</body>
</html>