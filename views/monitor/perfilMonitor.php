<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado();

// Configurações e metadados da página
$tituloPagina = "Perfil do Monitor - UNIVAG ATHENAS";

$tipoUsuario = $_SESSION['usuario_tipo'];

// Controla se a visão é do dono da conta (Monitor) ou de um visitante (Aluno)
$ehDono = ($tipoUsuario === 'monitor');
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
    
    <!-- CSS Unificado do Perfil do Monitor -->
    <link rel="stylesheet" href="../../assets/css/perfilMonitor.css">
</head>
<body>

    <!-- ===== 3. NAVBAR UNIVERSAL ===== -->
    <?php include '../../includes/navbar.php'; ?>

    <!-- ===== 4. CONTAINER PRINCIPAL ===== -->
    <main class="perfil-main-container">
        <div class="perfil-content-wrapper">

            <!-- ===== 5. GRID DE CARDS (COLUNA ESQUERDA E DIREITA) ===== -->
            <div class="perfil-grid">

                <!-- ===== 6. COLUNA ESQUERDA: PERFIL E CONTEÚDO PEDAGÓGICO ===== -->
                <div class="coluna-esquerda">

                    <!-- CARD 1: DADOS PRINCIPAIS DO MONITOR -->
                    <article class="card-perfil-base">
                        <div class="card-header-acoes">
                            <?php if ($ehDono): ?>
                                <!-- Botão exibido apenas para o Monitor editar seu perfil -->
                                <button type="button" class="btn-acao-secundario" data-modal-target="modalEditarPerfil">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar Perfil
                                </button>
                            <?php else: ?>
                                <!-- Botão de agendamento rápido exibido para Alunos visitantes -->
                                <button type="button" class="btn-acao-primario" id="btnAgendarMonitoriaPerfil">
                                    <i class="fa-solid fa-calendar-plus"></i> Solicitar monitoria
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="monitor-identificacao">
                            <div class="monitor-avatar" id="displayAvatar">M</div>
                            <div class="monitor-dados-pessoais">
                                <h2 id="displayNomeMonitor">Marcos Silva Pereira</h2>
                                <span class="badge-papel">Monitor Ativo</span>
                                <span class="badge-subpapel">Estudante</span>
                                <div class="monitor-avaliacao">
                                    <i class="fa-solid fa-star"></i>
                                    <strong>4,8</strong>
                                    <small>(32 avaliações)</small>
                                </div>
                                <p class="monitor-info-item"><i class="fa-solid fa-user-graduate"></i> <span id="displayAreaAtuacao1">Técnico em Informática</span></p>
                                <p class="monitor-info-item"><i class="fa-solid fa-location-dot"></i> <span id="displayLocalAtendimento">Online e Presencial</span></p>
                            </div>
                        </div>

                        <div class="divisor-vertical"></div>

                        <div class="monitor-detalhes-academicos">
                            <div class="item-academico">
                                <i class="fa-solid fa-book"></i>
                                <div>
                                    <small>Disciplina</small>
                                    <strong id="displayDisciplina">Banco de Dados</strong>
                                </div>
                            </div>
                            <div class="item-academico">
                                <i class="fa-solid fa-tag"></i>
                                <div>
                                    <small>Área de atuação</small>
                                    <strong id="displayAreaAtuacao2">Técnico em Informática</strong>
                                </div>
                            </div>
                            <div class="item-academico">
                                <i class="fa-solid fa-clock"></i>
                                <div>
                                    <small>Duração da monitoria</small>
                                    <strong id="displayDuracao">1 hora</strong>
                                </div>
                            </div>
                            <div class="item-academico">
                                <i class="fa-solid fa-users"></i>
                                <div>
                                    <small>Modalidade</small>
                                    <strong id="displayModalidade">Individual/grupo</strong>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- CARD 2: SOBRE MIM, METODOLOGIA E CONTEÚDOS -->
                    <article class="card-conteudo-metodologia">
                        <?php if ($ehDono): ?>
                            <div class="card-header-acoes">
                                <button type="button" class="btn-acao-secundario" data-modal-target="modalEditarConteudo">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar Conteúdo
                                </button>
                            </div>
                        <?php endif; ?>

                        <section class="secao-detalhe">
                            <div class="secao-titulo">
                                <i class="fa-solid fa-user"></i>
                                <h3>Sobre mim</h3>
                            </div>
                            <p class="secao-texto" id="displaySobreTexto">
                                Sou estudante e monitor da área de Banco de Dados. Gosto de ajudar a entender o conteúdo de forma simples e prática, buscando tornar o aprendizado tranquilo.
                            </p>
                        </section>

                        <div class="grid-subsecoes">
                            <section class="secao-detalhe">
                                <div class="secao-titulo">
                                    <i class="fa-solid fa-lightbulb"></i>
                                    <h3>Metodologia</h3>
                                </div>
                                <div class="caixa-destaque" id="displayMetodologia">
                                    Aulas dinâmicas, com explicações passo a passo, exemplos práticos e exercícios para você evoluir.
                                </div>
                            </section>

                            <section class="secao-detalhe">
                                <div class="secao-titulo">
                                    <i class="fa-solid fa-bullseye"></i>
                                    <h3>Ideal para</h3>
                                </div>
                                <div class="caixa-destaque" id="displayIdealPara">
                                    Iniciantes, estudantes com dúvidas específicas ou quem quer reforçar o conteúdo.
                                </div>
                            </section>
                        </div>

                        <section class="secao-detalhe">
                            <div class="secao-titulo">
                                <i class="fa-solid fa-layer-group"></i>
                                <h3>Conteúdos de auxílio</h3>
                            </div>
                            <ul class="lista-tags" id="displayListaTopicos">
                                <li>SQL</li>
                                <li>Modelagem de Banco de Dados</li>
                                <li>DER</li>
                                <li>Normalização</li>
                                <li>Procedures e Functions</li>
                                <li>Triggers</li>
                            </ul>
                        </section>
                    </article>

                </div>

                <!-- ===== 7. COLUNA DIREITA: HORÁRIOS, FALE COMIGO E FEEDBACKS ===== -->
                <div class="coluna-direita">

                    <!-- CARD 3: QUADRO DE HORÁRIOS DE ATENDIMENTO -->
                    <article class="card-disponibilidade">
                        <div class="disponibilidade-header">
                            <div class="disponibilidade-titulo">
                                <i class="fa-solid fa-calendar-days"></i>
                                <h2>Horários de atendimento</h2>
                            </div>
                            <?php if ($ehDono): ?>
                                <button type="button" class="btn-acao-secundario" data-modal-target="modalEditarHorarios">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </button>
                            <?php endif; ?>
                        </div>

                        <ul class="lista-horarios">
                            <li class="item-horario" data-dia="Seg">
                                <span class="badge-dia">Segunda</span>
                                <span class="status-badge disponivel"><i class="fa-solid fa-circle"></i> Disponível</span>
                                <span class="horario-texto">19h às 21h</span>
                            </li>
                            <li class="item-horario" data-dia="Ter">
                                <span class="badge-dia">Terça</span>
                                <span class="status-badge disponivel"><i class="fa-solid fa-circle"></i> Disponível</span>
                                <span class="horario-texto">19h às 21h</span>
                            </li>
                            <li class="item-horario" data-dia="Qua">
                                <span class="badge-dia">Quarta</span>
                                <span class="status-badge disponivel"><i class="fa-solid fa-circle"></i> Disponível</span>
                                <span class="horario-texto">14h às 16h</span>
                            </li>
                            <li class="item-horario" data-dia="Qui">
                                <span class="badge-dia">Quinta</span>
                                <span class="status-badge disponivel"><i class="fa-solid fa-circle"></i> Disponível</span>
                                <span class="horario-texto">15h às 18h</span>
                            </li>
                            <li class="item-horario" data-dia="Sex">
                                <span class="badge-dia">Sexta</span>
                                <span class="status-badge indisponivel"><i class="fa-solid fa-circle"></i> Indisponível</span>
                                <span class="horario-texto">—</span>
                            </li>
                            <li class="item-horario" data-dia="Sáb">
                                <span class="badge-dia">Sábado</span>
                                <span class="status-badge indisponivel"><i class="fa-solid fa-circle"></i> Indisponível</span>
                                <span class="horario-texto">—</span>
                            </li>
                            <li class="item-horario" data-dia="Dom">
                                <span class="badge-dia">Domingo</span>
                                <span class="status-badge indisponivel"><i class="fa-solid fa-circle"></i> Indisponível</span>
                                <span class="horario-texto">—</span>
                            </li>
                        </ul>

                        <?php if (!$ehDono): ?>
                            <!-- Botões de contato direto exibidos para Alunos -->
                            <div class="container-fale-comigo">
                                <button type="button" class="btn-acao-primario btn-bloco" id="btnIniciarConversaPerfil">
                                    <i class="fa-solid fa-comments"></i> Iniciar conversa
                                </button>
                                <button type="button" class="btn-acao-outline btn-bloco" id="btnSolicitarMonitoriaPerfil">
                                    <i class="fa-solid fa-calendar-check"></i> Solicitar monitoria
                                </button>
                            </div>
                        <?php endif; ?>
                    </article>

                    <!-- CARD 4: AVALIAÇÕES E FEEDBACKS DOS ALUNOS -->
                    <article class="card-feedbacks">
                        <div class="secao-titulo">
                            <i class="fa-solid fa-comments"></i>
                            <h3>Feedbacks</h3>
                        </div>
                        <div class="lista-feedbacks">
                            <div class="item-feedback">
                                <div class="feedback-avatar"><i class="fa-solid fa-user"></i></div>
                                <div class="feedback-conteudo">
                                    <div class="estrelas"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                                    <p>"Explicou o conteúdo de um jeito que consegui entender. Super paciente!"</p>
                                    <small>— Gabriel E. • 10/04/2026</small>
                                </div>
                            </div>
                            <div class="item-feedback">
                                <div class="feedback-avatar"><i class="fa-solid fa-user"></i></div>
                                <div class="feedback-conteudo">
                                    <div class="estrelas"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                                    <p>"Muito didático! Me ajudou a ver o conteúdo de outra forma. Recomendo!"</p>
                                    <small>— Mariana L. • 28/03/2026</small>
                                </div>
                            </div>
                        </div>
                    </article>

                </div>

            </div>

        </div>
    </main>

    <?php if ($ehDono): ?>
    <!-- ===== 8. MODAIS DE EDIÇÃO (EXCLUSIVOS PARA O MONITOR) ===== -->
    
    <!-- MODAL 1: EDITAR PERFIL -->
    <div class="modal-overlay" id="modalEditarPerfil">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-pen"></i> Editar Perfil</h3>
                <button type="button" class="btn-modal-fechar" data-modal-close><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="formEditarPerfil" class="modal-form">
                <div class="form-group">
                    <label for="inputNome">Nome do Monitor</label>
                    <input type="text" id="inputNome" class="form-input" value="Marcos Silva Pereira" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="inputDisciplina">Disciplina</label>
                        <input type="text" id="inputDisciplina" class="form-input" value="Banco de Dados" required>
                    </div>
                    <div class="form-group">
                        <label for="inputArea">Área de Atuação</label>
                        <input type="text" id="inputArea" class="form-input" value="Técnico em Informática" required>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="inputDuracao">Duração da Monitoria</label>
                        <select id="inputDuracao" class="form-select">
                            <option value="30 minutos">30 minutos</option>
                            <option value="1 hora" selected>1 hora</option>
                            <option value="1h 30min">1h 30min</option>
                            <option value="2 horas">2 horas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inputModalidade">Modalidade</label>
                        <select id="inputModalidade" class="form-select">
                            <option value="Individual">Individual</option>
                            <option value="Em grupo">Em grupo</option>
                            <option value="Individual/grupo" selected>Individual/grupo</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputLocal">Atendimento</label>
                    <input type="text" id="inputLocal" class="form-input" value="Online e Presencial" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar Perfil</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDITAR CONTEÚDO -->
    <div class="modal-overlay" id="modalEditarConteudo">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-pen"></i> Editar Conteúdo</h3>
                <button type="button" class="btn-modal-fechar" data-modal-close><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="formEditarConteudo" class="modal-form">
                <div class="form-group">
                    <label for="inputSobre">Sobre mim</label>
                    <textarea id="inputSobre" class="form-textarea" rows="3" required>Sou estudante e monitor da área de Banco de Dados. Gosto de ajudar a entender o conteúdo de forma simples e prática, buscando tornar o aprendizado tranquilo.</textarea>
                </div>
                <div class="form-group">
                    <label for="inputMetodologia">Metodologia</label>
                    <textarea id="inputMetodologia" class="form-textarea" rows="2" required>Aulas dinâmicas, com explicações passo a passo, exemplos práticos e exercícios para você evoluir.</textarea>
                </div>
                <div class="form-group">
                    <label for="inputIdealPara">Ideal para</label>
                    <textarea id="inputIdealPara" class="form-textarea" rows="2" required>Iniciantes, estudantes com dúvidas específicas ou quem quer reforçar o conteúdo.</textarea>
                </div>
                <div class="form-group">
                    <label for="inputTopicos">Conteúdos de auxílio (separados por vírgula)</label>
                    <input type="text" id="inputTopicos" class="form-input" value="SQL, Modelagem de Banco de Dados, DER, Normalização, Procedures e Functions, Triggers" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar Conteúdo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: EDITAR HORÁRIOS -->
    <div class="modal-overlay" id="modalEditarHorarios">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fa-solid fa-calendar-pen"></i> Editar Horários</h3>
                <button type="button" class="btn-modal-fechar" data-modal-close><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="formEditarHorarios" class="modal-form">
                <div class="form-horario-linha">
                    <strong>Segunda</strong>
                    <input type="text" id="inputHorarioSeg" class="form-input" value="19h às 21h">
                </div>
                <div class="form-horario-linha">
                    <strong>Terça</strong>
                    <input type="text" id="inputHorarioTer" class="form-input" value="19h às 21h">
                </div>
                <div class="form-horario-linha">
                    <strong>Quarta</strong>
                    <input type="text" id="inputHorarioQua" class="form-input" value="14h às 16h">
                </div>
                <div class="form-horario-linha">
                    <strong>Quinta</strong>
                    <input type="text" id="inputHorarioQui" class="form-input" value="15h às 18h">
                </div>
                <div class="form-horario-linha">
                    <strong>Sexta</strong>
                    <input type="text" id="inputHorarioSex" class="form-input" value="" placeholder="Indisponível">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar Horários</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== 9. INTERATIVIDADE JAVASCRIPT ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Gerenciador unificado de modais
        const botoesAbrir  = document.querySelectorAll('[data-modal-target]');
        const botoesFechar = document.querySelectorAll('[data-modal-close]');
        const overlays     = document.querySelectorAll('.modal-overlay');

        function fecharTodosModais() {
            overlays.forEach(modal => modal.classList.remove('ativo'));
        }

        botoesAbrir.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-modal-target');
                const modalTarget = document.getElementById(targetId);
                if (modalTarget) {
                    fecharTodosModais();
                    modalTarget.classList.add('ativo');
                }
            });
        });

        botoesFechar.forEach(btn => {
            btn.addEventListener('click', fecharTodosModais);
        });

        overlays.forEach(overlay => {
            overlay.addEventListener('click', function (e) {
                if (e.target === this) fecharTodosModais();
            });
        });

        // Atualização ao vivo - Perfil
        const formPerfil = document.getElementById('formEditarPerfil');
        if (formPerfil) {
            formPerfil.addEventListener('submit', function (e) {
                e.preventDefault();
                const nome       = document.getElementById('inputNome').value;
                const disciplina = document.getElementById('inputDisciplina').value;
                const area       = document.getElementById('inputArea').value;
                const duracao    = document.getElementById('inputDuracao').value;
                const modalidade = document.getElementById('inputModalidade').value;
                const local      = document.getElementById('inputLocal').value;

                document.getElementById('displayNomeMonitor').textContent = nome;
                document.getElementById('displayAvatar').textContent      = nome.charAt(0).toUpperCase();
                document.getElementById('displayDisciplina').textContent  = disciplina;
                document.getElementById('displayAreaAtuacao1').textContent = area;
                document.getElementById('displayAreaAtuacao2').textContent = area;
                document.getElementById('displayDuracao').textContent     = duracao;
                document.getElementById('displayModalidade').textContent  = modalidade;
                document.getElementById('displayLocalAtendimento').textContent = local;

                fecharTodosModais();
            });
        }

        // Atualização ao vivo - Conteúdo
        const formConteudo = document.getElementById('formEditarConteudo');
        if (formConteudo) {
            formConteudo.addEventListener('submit', function (e) {
                e.preventDefault();
                const sobre       = document.getElementById('inputSobre').value;
                const metodologia = document.getElementById('inputMetodologia').value;
                const idealPara   = document.getElementById('inputIdealPara').value;
                const topicosRaw  = document.getElementById('inputTopicos').value;

                document.getElementById('displaySobreTexto').textContent  = sobre;
                document.getElementById('displayMetodologia').textContent = metodologia;
                document.getElementById('displayIdealPara').textContent   = idealPara;

                const listaContainer = document.getElementById('displayListaTopicos');
                listaContainer.innerHTML = '';
                topicosRaw.split(',').forEach(topico => {
                    if (topico.trim().length > 0) {
                        const li = document.createElement('li');
                        li.textContent = topico.trim();
                        listaContainer.appendChild(li);
                    }
                });

                fecharTodosModais();
            });
        }

        const botaoIniciarConversaPerfil = document.getElementById('btnIniciarConversaPerfil');
        if (botaoIniciarConversaPerfil) {
            botaoIniciarConversaPerfil.addEventListener('click', function () {
                const chatToggle = document.getElementById('btnMessages');
                if (chatToggle) {
                    chatToggle.click();
                    const selectContato = document.getElementById('chatContactSelect');
                    if (selectContato && !selectContato.value) {
                        selectContato.focus();
                    }
                }
            });
        }

        const botaoSolicitarMonitoriaPerfil = document.getElementById('btnSolicitarMonitoriaPerfil');
        const botaoAgendarMonitoriaPerfil = document.getElementById('btnAgendarMonitoriaPerfil');
        const acaoMonitoriaPerfil = function () {
            const chatToggle = document.getElementById('btnMessages');
            if (chatToggle) {
                chatToggle.click();
                const abertura = document.getElementById('requestAppointment');
                if (abertura) {
                    abertura.click();
                }
            }
        };

        if (botaoSolicitarMonitoriaPerfil) {
            botaoSolicitarMonitoriaPerfil.addEventListener('click', acaoMonitoriaPerfil);
        }
        if (botaoAgendarMonitoriaPerfil) {
            botaoAgendarMonitoriaPerfil.addEventListener('click', acaoMonitoriaPerfil);
        }
    });
    </script>

</body>
</html>