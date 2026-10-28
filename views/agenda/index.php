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
    <?php if ($ehMonitor): ?>
        <link rel="stylesheet" href="../../assets/css/monitorTheme.css?v=5">
    <?php endif; ?>
    <link rel="stylesheet" href="../../assets/css/calendario.css?v=7">
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
                                <div
                                    class="agenda-aviso"
                                    id="agendaStatus"
                                    role="status"
                                    data-api="../../actions/chat.php"
                                    data-monitorias-api="../../actions/monitorias.php"
                                    data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-user-type="<?php echo htmlspecialchars($tipoUsuario, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    <p>Acompanhe monitorias, confirme presença e compartilhe materiais com os participantes da sessão.</p>
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
        const monitoriasApi = document.getElementById('agendaStatus').dataset.monitoriasApi;
        const csrfToken = document.getElementById('agendaStatus').dataset.csrf;
        const tipoUsuario = document.getElementById('agendaStatus').dataset.userType;
        const ehMonitor = tipoUsuario === 'monitor';

        function padZero(n) { return n < 10 ? '0' + n : n; }

        function carregarAgendamentos() {
            return fetch(monitoriasApi)
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload.erro || 'Não foi possível carregar os agendamentos.');
                        }
                        return payload;
                    });
                })
                .then(function (payload) {
                    Object.keys(bancoCompromissos).forEach(function (chave) {
                        delete bancoCompromissos[chave];
                    });
                    (payload.agendamentos || []).forEach(function (agendamento) {
                        const inicio = new Date(String(agendamento.agendado_para).replace(' ', 'T'));
                        if (Number.isNaN(inicio.getTime())) return;
                        const fim = new Date(inicio.getTime() + Number(agendamento.duracao_minutos) * 60000);
                        const chave = [
                            inicio.getFullYear(),
                            padZero(inicio.getMonth() + 1),
                            padZero(inicio.getDate())
                        ].join('-');
                        bancoCompromissos[chave] = bancoCompromissos[chave] || [];
                        bancoCompromissos[chave].push({
                            id: Number(agendamento.id),
                            tipo: 'aula',
                            titulo: agendamento.assunto,
                            desc: ehMonitor ? agendamento.aluno_nome : agendamento.monitor_nome,
                            horario: padZero(inicio.getHours()) + ':' + padZero(inicio.getMinutes())
                                + ' – ' + padZero(fim.getHours()) + ':' + padZero(fim.getMinutes()),
                            inicio: inicio,
                            fim: fim,
                            linkZoom: agendamento.link_zoom,
                            presencaAluno: agendamento.presenca_aluno,
                            presencaMonitor: agendamento.presenca_monitor,
                            avaliacaoNota: agendamento.avaliacao_nota,
                            avaliacaoComentario: agendamento.avaliacao_comentario,
                            assuntoEstudado: agendamento.assunto_estudado,
                            registroObservacoes: agendamento.registro_observacoes,
                            materiais: agendamento.materiais || []
                        });
                    });
                    renderizarCalendario();
                    renderizarCompromissos();
                });
        }

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

            listaCompromissos.replaceChildren();

            if (itens.length === 0) {
                const vazio = document.createElement('div');
                vazio.className = 'estado-vazio-compromissos';
                const icone = document.createElement('i');
                icone.className = 'fa-solid fa-calendar-xmark';
                const texto = document.createElement('p');
                texto.textContent = 'Nenhum compromisso para esta visualização.';
                vazio.append(icone, texto);
                listaCompromissos.appendChild(vazio);
                return;
            }

            itens.forEach(item => {
                const el = document.createElement('div');
                el.className = `item-compromisso tipo-${item.tipo}`;

                const cabecalho = document.createElement('div');
                cabecalho.className = 'compromisso-cabecalho';
                const horario = document.createElement('div');
                horario.className = 'horario-compromisso';
                horario.textContent = item.horario;
                const info = document.createElement('div');
                info.className = 'info-compromisso';
                const dot = document.createElement('span');
                dot.className = 'dot-compromisso';
                const detalhes = document.createElement('div');
                const titulo = document.createElement('strong');
                titulo.textContent = item.titulo;
                const participante = document.createElement('small');
                participante.textContent = item.desc;
                detalhes.append(titulo, participante);
                info.append(dot, detalhes);
                cabecalho.append(horario, info);
                if (item.linkZoom) {
                    try {
                        const zoom = new URL(item.linkZoom);
                        if (
                            zoom.protocol === 'https:'
                            && /(^|\.)zoom\.us$/i.test(zoom.hostname)
                        ) {
                            const link = document.createElement('a');
                            link.href = zoom.href;
                            link.target = '_blank';
                            link.rel = 'noopener noreferrer';
                            link.className = 'btn-link-sala';
                            link.textContent = 'Zoom';
                            cabecalho.appendChild(link);
                        }
                    } catch (error) {
                        console.error('Link de reunião inválido no agendamento.', error);
                    }
                }
                el.appendChild(cabecalho);

                const acoes = document.createElement('div');
                acoes.className = 'compromisso-acoes';
                const presencaPropria = ehMonitor ? item.presencaMonitor : item.presencaAluno;
                const presencaOutra = ehMonitor ? item.presencaAluno : item.presencaMonitor;
                const statusPresenca = document.createElement('p');
                statusPresenca.className = 'compromisso-status';
                statusPresenca.textContent = 'Sua presença: ' + rotuloPresenca(presencaPropria)
                    + ' · outra pessoa: ' + rotuloPresenca(presencaOutra);
                acoes.appendChild(statusPresenca);

                if (item.assuntoEstudado !== null) {
                    const registro = document.createElement('div');
                    registro.className = 'compromisso-registro';
                    const tituloRegistro = document.createElement('strong');
                    tituloRegistro.textContent = 'Conteúdo estudado: ' + item.assuntoEstudado;
                    registro.appendChild(tituloRegistro);
                    if (item.registroObservacoes) {
                        const observacoesRegistro = document.createElement('p');
                        observacoesRegistro.textContent = item.registroObservacoes;
                        registro.appendChild(observacoesRegistro);
                    }
                    acoes.appendChild(registro);
                }

                if (item.fim < new Date() && presencaPropria === null) {
                    const confirmar = document.createElement('button');
                    confirmar.type = 'button';
                    confirmar.className = 'btn-agenda-acao';
                    confirmar.dataset.presenca = '1';
                    confirmar.dataset.appointmentId = String(item.id);
                    confirmar.textContent = 'Registrar presença';
                    const ausencia = document.createElement('button');
                    ausencia.type = 'button';
                    ausencia.className = 'btn-agenda-acao btn-agenda-secundario';
                    ausencia.dataset.presenca = '0';
                    ausencia.dataset.appointmentId = String(item.id);
                    ausencia.textContent = 'Registrar ausência';
                    acoes.append(confirmar, ausencia);
                }

                if (ehMonitor && item.avaliacaoNota !== null) {
                    const avaliacao = document.createElement('p');
                    avaliacao.className = 'compromisso-avaliacao';
                    avaliacao.textContent = 'Avaliação do aluno: ' + item.avaliacaoNota + '/5'
                        + (item.avaliacaoComentario ? ' · ' + item.avaliacaoComentario : '');
                    acoes.appendChild(avaliacao);
                }

                if (
                    !ehMonitor && item.fim < new Date()
                    && Number(item.presencaAluno) === 1 && Number(item.presencaMonitor) === 1
                ) {
                    if (item.avaliacaoNota === null) {
                        const avaliacaoForm = document.createElement('form');
                        avaliacaoForm.className = 'avaliacao-form';
                        avaliacaoForm.dataset.avaliacaoForm = '1';
                        avaliacaoForm.dataset.appointmentId = String(item.id);
                        const nota = document.createElement('select');
                        nota.name = 'nota';
                        nota.required = true;
                        nota.setAttribute('aria-label', 'Nota da monitoria');
                        nota.add(new Option('Avaliar de 1 a 5', ''));
                        for (let valor = 1; valor <= 5; valor++) {
                            nota.add(new Option(valor + ' estrela' + (valor > 1 ? 's' : ''), String(valor)));
                        }
                        const comentario = document.createElement('textarea');
                        comentario.name = 'comentario';
                        comentario.maxLength = 1000;
                        comentario.placeholder = 'Comentário opcional';
                        const enviarAvaliacao = document.createElement('button');
                        enviarAvaliacao.type = 'submit';
                        enviarAvaliacao.className = 'btn-agenda-acao';
                        enviarAvaliacao.textContent = 'Enviar avaliação';
                        avaliacaoForm.append(nota, comentario, enviarAvaliacao);
                        acoes.appendChild(avaliacaoForm);
                    } else {
                        const avaliacao = document.createElement('p');
                        avaliacao.className = 'compromisso-avaliacao';
                        avaliacao.textContent = 'Sua avaliação: ' + item.avaliacaoNota + '/5'
                            + (item.avaliacaoComentario ? ' · ' + item.avaliacaoComentario : '');
                        acoes.appendChild(avaliacao);
                    }
                }

                if (ehMonitor && item.fim < new Date() && Number(item.presencaMonitor) === 1) {
                    const registroForm = document.createElement('form');
                    registroForm.className = 'registro-form';
                    registroForm.dataset.registroForm = '1';
                    registroForm.dataset.appointmentId = String(item.id);
                    const assuntoEstudado = document.createElement('input');
                    assuntoEstudado.name = 'assunto_estudado';
                    assuntoEstudado.maxLength = 180;
                    assuntoEstudado.placeholder = 'Conteúdo estudado';
                    assuntoEstudado.required = true;
                    assuntoEstudado.value = item.assuntoEstudado || '';
                    const observacoes = document.createElement('textarea');
                    observacoes.name = 'observacoes';
                    observacoes.maxLength = 2000;
                    observacoes.placeholder = 'Observações e próximos passos (opcional)';
                    observacoes.value = item.registroObservacoes || '';
                    const salvarRegistro = document.createElement('button');
                    salvarRegistro.type = 'submit';
                    salvarRegistro.className = 'btn-agenda-acao btn-agenda-secundario';
                    salvarRegistro.textContent = item.assuntoEstudado ? 'Atualizar registro' : 'Salvar registro';
                    registroForm.append(assuntoEstudado, observacoes, salvarRegistro);
                    acoes.appendChild(registroForm);
                }

                if (item.materiais.length) {
                    const listaMateriais = document.createElement('ul');
                    listaMateriais.className = 'compromisso-materiais';
                    item.materiais.forEach(function (material) {
                        const linha = document.createElement('li');
                        const link = document.createElement('a');
                        link.textContent = material.nome_original;
                        if (material.url) {
                            try {
                                const url = new URL(material.url);
                                if (url.protocol !== 'https:') return;
                                link.href = url.href;
                                link.target = '_blank';
                                link.rel = 'noopener noreferrer';
                            } catch (error) {
                                return;
                            }
                        } else {
                            link.href = monitoriasApi + '?arquivo=' + encodeURIComponent(material.id);
                        }
                        linha.append(link);
                        const autoria = document.createElement('small');
                        autoria.textContent = 'Compartilhado por ' + material.remetente_nome;
                        linha.appendChild(autoria);
                        listaMateriais.appendChild(linha);
                    });
                    acoes.appendChild(listaMateriais);
                }

                const formArquivo = document.createElement('form');
                formArquivo.className = 'material-form';
                formArquivo.dataset.materialUpload = '1';
                formArquivo.dataset.appointmentId = String(item.id);
                const arquivoInput = document.createElement('input');
                arquivoInput.type = 'file';
                arquivoInput.name = 'arquivo';
                arquivoInput.accept = '.pdf,.png,.jpg,.jpeg,.webp,.txt,.docx,.xlsx,.pptx';
                arquivoInput.required = true;
                arquivoInput.setAttribute('aria-label', 'Arquivo para compartilhar');
                const enviarArquivo = document.createElement('button');
                enviarArquivo.type = 'submit';
                enviarArquivo.className = 'btn-agenda-acao btn-agenda-secundario';
                enviarArquivo.textContent = 'Anexar arquivo';
                formArquivo.append(arquivoInput, enviarArquivo);

                const formLink = document.createElement('form');
                formLink.className = 'material-form material-link-form';
                formLink.dataset.materialLink = '1';
                formLink.dataset.appointmentId = String(item.id);
                const nomeLink = document.createElement('input');
                nomeLink.name = 'nome';
                nomeLink.maxLength = 255;
                nomeLink.placeholder = 'Nome do link';
                nomeLink.required = true;
                const urlLink = document.createElement('input');
                urlLink.name = 'url';
                urlLink.type = 'url';
                urlLink.placeholder = 'https://...';
                urlLink.required = true;
                const enviarLink = document.createElement('button');
                enviarLink.type = 'submit';
                enviarLink.className = 'btn-agenda-acao btn-agenda-secundario';
                enviarLink.textContent = 'Compartilhar link';
                formLink.append(nomeLink, urlLink, enviarLink);
                acoes.append(formArquivo, formLink);
                el.appendChild(acoes);
                listaCompromissos.appendChild(el);
            });
        }

        function rotuloPresenca(valor) {
            if (valor === null) return 'pendente';
            return Number(valor) === 1 ? 'presença confirmada' : 'ausência registrada';
        }

        async function enviarAcao(acao, dados, arquivo) {
            const opcoes = {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken }
            };
            if (arquivo) {
                const formulario = new FormData();
                Object.entries(dados).forEach(function ([chave, valor]) {
                    formulario.append(chave, valor);
                });
                formulario.append('arquivo', arquivo);
                opcoes.body = formulario;
            } else {
                opcoes.headers['Content-Type'] = 'application/json';
                opcoes.body = JSON.stringify({ acao: acao, ...dados });
            }
            const resposta = await fetch(monitoriasApi, opcoes);
            const payload = await resposta.json();
            if (!resposta.ok) throw new Error(payload.erro || 'Não foi possível concluir a ação.');
            return payload;
        }

        function informarAcao(mensagem) {
            document.querySelector('#agendaStatus p').textContent = mensagem;
        }

        listaCompromissos.addEventListener('click', async function (event) {
            const botao = event.target.closest('button[data-presenca]');
            if (!botao) return;
            botao.disabled = true;
            try {
                await enviarAcao('presenca', {
                    agendamento_id: Number(botao.dataset.appointmentId),
                    presente: botao.dataset.presenca === '1'
                });
                informarAcao('Sua confirmação de presença foi registrada.');
                await carregarAgendamentos();
            } catch (error) {
                informarAcao(error.message);
                botao.disabled = false;
            }
        });

        listaCompromissos.addEventListener('submit', async function (event) {
            const formulario = event.target;
            if (!formulario.matches('form[data-avaliacao-form], form[data-registro-form], form[data-material-upload], form[data-material-link]')) return;
            event.preventDefault();
            const botao = formulario.querySelector('button[type="submit"]');
            botao.disabled = true;
            try {
                if (formulario.matches('[data-avaliacao-form]')) {
                    await enviarAcao('avaliar', {
                        agendamento_id: Number(formulario.dataset.appointmentId),
                        nota: Number(formulario.elements.nota.value),
                        comentario: formulario.elements.comentario.value.trim()
                    });
                    informarAcao('Obrigado pela avaliação da monitoria.');
                } else if (formulario.matches('[data-registro-form]')) {
                    await enviarAcao('registrar_atendimento', {
                        agendamento_id: Number(formulario.dataset.appointmentId),
                        assunto_estudado: formulario.elements.assunto_estudado.value.trim(),
                        observacoes: formulario.elements.observacoes.value.trim()
                    });
                    informarAcao('Registro do atendimento salvo e compartilhado com o aluno.');
                } else if (formulario.matches('[data-material-upload]')) {
                    const arquivo = formulario.elements.arquivo.files[0];
                    if (!arquivo || arquivo.size > 10 * 1024 * 1024) {
                        throw new Error('Selecione um arquivo de até 10 MB.');
                    }
                    await enviarAcao('material', {
                        acao: 'material',
                        agendamento_id: Number(formulario.dataset.appointmentId)
                    }, arquivo);
                    informarAcao('Material anexado e compartilhado com o participante.');
                } else {
                    await enviarAcao('material', {
                        agendamento_id: Number(formulario.dataset.appointmentId),
                        nome: formulario.elements.nome.value.trim(),
                        url: formulario.elements.url.value.trim()
                    });
                    informarAcao('Link compartilhado com o participante.');
                }
                await carregarAgendamentos();
            } catch (error) {
                informarAcao(error.message);
                botao.disabled = false;
            }
        });

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

        window.addEventListener('athenas:appointment-created', function () {
            carregarAgendamentos().catch(function () {
                document.getElementById('agendaStatus').querySelector('p').textContent =
                    'O agendamento foi salvo, mas não foi possível atualizar a agenda agora. Recarregue a página.';
            });
        });

        // Inicialização
        renderizarCalendario();
        renderizarCompromissos();
        carregarAgendamentos().catch(function () {
            document.getElementById('agendaStatus').querySelector('p').textContent =
                'Não foi possível carregar os agendamentos. Recarregue a página e tente novamente.';
        });
    });
    </script>

</body>
</html>