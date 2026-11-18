<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');
require_once __DIR__ . '/../../config/conexao.php';

$nomesDiasCurto = [
    1 => 'Seg',
    2 => 'Ter',
    3 => 'Qua',
    4 => 'Qui',
    5 => 'Sex',
    6 => 'Sáb',
    7 => 'Dom',
];
$coresAvatar = ['verde', 'azul', 'laranja', 'roxo'];
$monitores = [];

try {
    $stmt = $pdo->query(
        "SELECT u.id, u.nome, u.bio, u.curso, u.periodo, u.foto_mime,
                pm.area_atuacao, pm.disciplina, pm.local_atendimento,
                pm.duracao_monitoria, pm.modalidade, pm.metodologia,
                pm.ideal_para, pm.topicos,
                (SELECT ROUND(AVG(av.nota), 1) FROM avaliacoes_monitorias av WHERE av.monitor_id = u.id) AS media_nota,
                (SELECT COUNT(*) FROM avaliacoes_monitorias av WHERE av.monitor_id = u.id) AS total_avaliacoes
         FROM usuarios u
         LEFT JOIN perfis_monitores pm ON pm.monitor_id = u.id
         WHERE u.tipo = 'monitor'
         ORDER BY u.id ASC"
    );
    $registros = $stmt->fetchAll();

    $stmtDisp = $pdo->query(
        'SELECT monitor_id, dia_semana, hora_inicio, hora_fim
         FROM disponibilidades_monitores
         WHERE disponivel = 1
         ORDER BY monitor_id, dia_semana'
    );
    $dispPorMonitor = [];
    foreach ($stmtDisp->fetchAll() as $disp) {
        $dispPorMonitor[(int) $disp['monitor_id']][] = $disp;
    }

    foreach ($registros as $idx => $reg) {
        $monitorId = (int) $reg['id'];
        $partesNome = preg_split('/\s+/u', trim($reg['nome'])) ?: ['M'];
        $iniciais = mb_strtoupper(
            mb_substr($partesNome[0] ?? 'M', 0, 1, 'UTF-8')
            . (isset($partesNome[1]) ? mb_substr($partesNome[1], 0, 1, 'UTF-8') : ''),
            'UTF-8'
        );

        $disps = $dispPorMonitor[$monitorId] ?? [];
        $disponivel = count($disps) > 0;
        if ($disponivel) {
            $resumosDias = [];
            foreach ($disps as $d) {
                $diaLabel = $nomesDiasCurto[(int) $d['dia_semana']] ?? '';
                $ini = substr((string) $d['hora_inicio'], 0, 5);
                $fim = substr((string) $d['hora_fim'], 0, 5);
                $resumosDias[] = "{$diaLabel} ({$ini}–{$fim})";
            }
            $horariosTexto = implode(' · ', $resumosDias);
        } else {
            $horariosTexto = 'Consulte horários pelo chat';
        }

        $topicosLista = array_values(array_filter(array_map('trim', explode(',', (string) ($reg['topicos'] ?? '')))));
        if (!$topicosLista) {
            $topicosLista = [$reg['disciplina'] ?: 'Apoio acadêmico'];
        }

        $cursoFormatado = implode(' · ', array_filter([$reg['curso'], $reg['periodo']])) ?: 'Monitoria Acadêmica UNIVAG';
        $totalAvaliacoes = (int) ($reg['total_avaliacoes'] ?? 0);
        $mediaNota = $reg['media_nota'] !== null
            ? number_format((float) $reg['media_nota'], 1, ',', '.')
            : '5,0';

        $monitores[] = [
            'id' => $monitorId,
            'nome' => $reg['nome'],
            'iniciais' => $iniciais ?: 'M',
            'tem_foto' => !empty($reg['foto_mime']),
            'area' => trim((string) ($reg['area_atuacao'] ?? '')) ?: 'Tecnologia',
            'disciplina' => trim((string) ($reg['disciplina'] ?? '')) ?: 'Monitoria Geral',
            'tags' => array_slice($topicosLista, 0, 5),
            'modalidade' => trim((string) ($reg['modalidade'] ?? '')) ?: 'Online e presencial',
            'disponivel' => $disponivel,
            'horarios' => $horariosTexto,
            'avaliacao' => $mediaNota,
            'avaliacoes' => $totalAvaliacoes,
            'curso' => $cursoFormatado,
            'sobre' => trim((string) ($reg['bio'] ?? '')) ?: 'Monitor acadêmico disponível para auxiliar nos estudos e resolução de exercícios.',
            'cor' => $coresAvatar[$idx % count($coresAvatar)],
        ];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$areas = array_values(array_unique(array_column($monitores, 'area')));
sort($areas);
$termoBusca = isset($_GET['busca']) && is_string($_GET['busca'])
    ? trim($_GET['busca'])
    : '';
$areaSelecionada = isset($_GET['area']) && is_string($_GET['area'])
    ? $_GET['area']
    : '';
if (!in_array($areaSelecionada, $areas, true)) {
    $areaSelecionada = '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitores disponíveis - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/aluno.css?v=2">
    <link rel="stylesheet" href="../../assets/css/catalogoMonitores.css?v=2">
</head>
<body class="aluno-theme">
    <?php include '../../includes/navbar.php'; ?>

    <main class="aluno-main catalogo-main">
        <div class="aluno-layout">
            <header class="catalogo-cabecalho">
                <div>
                    <a class="catalogo-voltar" href="inicio.php">
                        <i class="fa-solid fa-arrow-left"></i> Painel do aluno
                    </a>
                    <span class="aluno-eyebrow">APOIO PARA SEUS ESTUDOS</span>
                    <h1>Encontre seu monitor</h1>
                    <p>Explore as áreas, converse em tempo real e agende sua monitoria.</p>
                </div>
                <div class="catalogo-resumo-icone" aria-hidden="true">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            </header>

            <section class="catalogo-filtros" aria-label="Buscar e filtrar monitores">
                <label class="catalogo-busca">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input
                        id="filtroBusca"
                        type="search"
                        placeholder="Busque por monitor, disciplina ou assunto"
                        value="<?php echo htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8'); ?>"
                        autocomplete="off"
                    >
                </label>

                <label class="catalogo-select-label">
                    <span>Área</span>
                    <select id="filtroArea">
                        <option value="">Todas as áreas</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo htmlspecialchars($area, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $areaSelecionada === $area ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($area, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="catalogo-select-label">
                    <span>Modalidade</span>
                    <select id="filtroModalidade">
                        <option value="">Todas</option>
                        <option value="Online">Online</option>
                        <option value="Presencial">Presencial</option>
                    </select>
                </label>

                <label class="catalogo-disponiveis">
                    <input id="filtroDisponiveis" type="checkbox">
                    <span class="catalogo-checkbox-visual" aria-hidden="true"></span>
                    <span>Com horários</span>
                </label>

                <button class="catalogo-limpar" id="limparFiltros" type="button">Limpar</button>
            </section>

            <section class="catalogo-listagem" aria-labelledby="tituloResultados">
                <div class="catalogo-listagem-cabecalho">
                    <div>
                        <h2 id="tituloResultados">Monitores para suas disciplinas</h2>
                        <p id="contadorResultados" aria-live="polite"></p>
                    </div>
                    <label class="catalogo-ordenacao">
                        <span>Ordenar:</span>
                        <select id="ordenarMonitores">
                            <option value="recomendados">Recomendados</option>
                            <option value="avaliacao">Melhor avaliados</option>
                            <option value="nome">Nome A–Z</option>
                        </select>
                    </label>
                </div>

                <div class="catalogo-grade" id="listaMonitores">
                    <?php foreach ($monitores as $indice => $monitor): ?>
                        <?php
                        $buscaCard = mb_strtolower(
                            $monitor['nome'] . ' ' . $monitor['area'] . ' ' . $monitor['disciplina'] . ' ' . implode(' ', $monitor['tags']),
                            'UTF-8'
                        );
                        ?>
                        <article
                            class="monitor-card"
                            data-index="<?php echo $indice; ?>"
                            data-id="<?php echo (int) $monitor['id']; ?>"
                            data-nome="<?php echo htmlspecialchars($monitor['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-area="<?php echo htmlspecialchars($monitor['area'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-disciplina="<?php echo htmlspecialchars($monitor['disciplina'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-tags="<?php echo htmlspecialchars(implode(', ', $monitor['tags']), ENT_QUOTES, 'UTF-8'); ?>"
                            data-modalidade="<?php echo htmlspecialchars($monitor['modalidade'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-disponivel="<?php echo $monitor['disponivel'] ? '1' : '0'; ?>"
                            data-horarios="<?php echo htmlspecialchars($monitor['horarios'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-avaliacao="<?php echo htmlspecialchars($monitor['avaliacao'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-avaliacoes="<?php echo (int) $monitor['avaliacoes']; ?>"
                            data-curso="<?php echo htmlspecialchars($monitor['curso'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-sobre="<?php echo htmlspecialchars($monitor['sobre'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-iniciais="<?php echo htmlspecialchars($monitor['iniciais'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-tem-foto="<?php echo $monitor['tem_foto'] ? '1' : '0'; ?>"
                            data-cor="<?php echo htmlspecialchars($monitor['cor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-busca="<?php echo htmlspecialchars($buscaCard, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <div class="monitor-card-topo">
                                <span class="monitor-avatar avatar-<?php echo htmlspecialchars($monitor['cor'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if ($monitor['tem_foto']): ?>
                                        <img src="../../actions/fotoPerfil.php?id=<?php echo (int) $monitor['id']; ?>" alt="Foto de <?php echo htmlspecialchars($monitor['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($monitor['iniciais'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="monitor-nota" aria-label="Nota <?php echo htmlspecialchars($monitor['avaliacao'], ENT_QUOTES, 'UTF-8'); ?> de 5">
                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($monitor['avaliacao'], ENT_QUOTES, 'UTF-8'); ?>
                                    <small>(<?php echo (int) $monitor['avaliacoes']; ?>)</small>
                                </span>
                            </div>
                            <span class="monitor-area"><?php echo htmlspecialchars($monitor['area'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <h3><?php echo htmlspecialchars($monitor['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="monitor-curso"><?php echo htmlspecialchars($monitor['curso'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="monitor-disciplina">
                                <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($monitor['disciplina'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="monitor-tags">
                                <?php foreach ($monitor['tags'] as $tag): ?>
                                    <span><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="monitor-card-rodape">
                                <span class="monitor-status <?php echo $monitor['disponivel'] ? 'status-disponivel' : 'status-indisponivel'; ?>">
                                    <span aria-hidden="true"></span>
                                    <?php echo $monitor['disponivel'] ? 'Com horários' : 'Sob consulta'; ?>
                                </span>
                                <div class="monitor-card-acoes">
                                    <?php if ($monitor['disponivel']): ?>
                                        <button
                                            class="monitor-agendar-rapido"
                                            type="button"
                                            data-solicitar-monitoria-monitor="<?php echo (int) $monitor['id']; ?>"
                                        >
                                            <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i> Agendar
                                        </button>
                                    <?php endif; ?>
                                    <button class="monitor-detalhes" type="button" data-abrir-perfil>
                                        Ver perfil <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="catalogo-vazio" id="estadoVazio" hidden>
                    <span><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                    <h3>Nenhum monitor encontrado</h3>
                    <p>Tente mudar a busca ou remover algum filtro.</p>
                    <button class="catalogo-limpar" type="button" data-limpar-vazio>Limpar filtros</button>
                </div>
                <p class="catalogo-aviso">Catálogo integrado em tempo real aos monitores cadastrados na plataforma.</p>
            </section>
        </div>
    </main>

    <dialog class="perfil-dialog" id="dialogPerfil" aria-labelledby="perfilNome">
        <div class="perfil-dialog-cabecalho">
            <span class="monitor-avatar" id="perfilAvatar"></span>
            <button type="button" class="dialog-fechar" data-fechar-dialog aria-label="Fechar perfil">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="perfil-dialog-conteudo">
            <span class="monitor-area" id="perfilArea"></span>
            <h2 id="perfilNome"></h2>
            <p class="monitor-curso" id="perfilCurso"></p>
            <div class="perfil-dialog-nota" id="perfilNota"></div>
            <div class="perfil-dialog-bloco">
                <h3>Sobre a monitoria</h3>
                <p id="perfilSobre"></p>
            </div>
            <div class="perfil-dialog-bloco">
                <h3>Disciplina e assuntos</h3>
                <p id="perfilDisciplina"></p>
                <div class="monitor-tags" id="perfilTags"></div>
            </div>
            <div class="perfil-dialog-bloco perfil-dialog-horarios">
                <h3>Modalidade e horários</h3>
                <p id="perfilModalidade"></p>
                <p id="perfilHorarios"></p>
            </div>
            <div class="perfil-dialog-acoes">
                <button type="button" class="dialog-btn-primario" id="dialogBtnSolicitar" data-solicitar-monitoria-monitor="">
                    <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i> Solicitar monitoria
                </button>
                <button type="button" class="dialog-btn-secundario" id="dialogBtnConversar" data-iniciar-conversa-monitor="">
                    <i class="fa-solid fa-comments" aria-hidden="true"></i> Iniciar conversa
                </button>
                <a class="dialog-btn-link" id="dialogBtnPaginaCompleta" href="../monitor/perfilMonitor.php">
                    Ver página completa <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const busca = document.getElementById('filtroBusca');
            const area = document.getElementById('filtroArea');
            const modalidade = document.getElementById('filtroModalidade');
            const apenasDisponiveis = document.getElementById('filtroDisponiveis');
            const ordenacao = document.getElementById('ordenarMonitores');
            const lista = document.getElementById('listaMonitores');
            const cartoes = Array.from(lista.querySelectorAll('.monitor-card'));
            const contador = document.getElementById('contadorResultados');
            const estadoVazio = document.getElementById('estadoVazio');
            const dialogo = document.getElementById('dialogPerfil');

            function atualizarCatalogo() {
                const termo = busca.value.trim().toLocaleLowerCase('pt-BR');
                const visiveis = cartoes.filter(function (cartao) {
                    return cartao.dataset.busca.includes(termo)
                        && (!area.value || cartao.dataset.area === area.value)
                        && (!modalidade.value || cartao.dataset.modalidade.toLocaleLowerCase('pt-BR').includes(modalidade.value.toLocaleLowerCase('pt-BR')))
                        && (!apenasDisponiveis.checked || cartao.dataset.disponivel === '1');
                });

                visiveis.sort(function (a, b) {
                    if (ordenacao.value === 'avaliacao') {
                        return Number(b.dataset.avaliacao.replace(',', '.'))
                            - Number(a.dataset.avaliacao.replace(',', '.'));
                    }
                    if (ordenacao.value === 'nome') {
                        return a.dataset.nome.localeCompare(b.dataset.nome, 'pt-BR');
                    }
                    return Number(a.dataset.index) - Number(b.dataset.index);
                });

                cartoes.forEach(function (cartao) {
                    cartao.hidden = !visiveis.includes(cartao);
                });
                visiveis.forEach(function (cartao) {
                    lista.appendChild(cartao);
                });

                contador.textContent = visiveis.length === 1
                    ? '1 monitor encontrado'
                    : visiveis.length + ' monitores encontrados';
                estadoVazio.hidden = visiveis.length !== 0;
                lista.hidden = visiveis.length === 0;
            }

            function limparFiltros() {
                busca.value = '';
                area.value = '';
                modalidade.value = '';
                apenasDisponiveis.checked = false;
                ordenacao.value = 'recomendados';
                atualizarCatalogo();
                busca.focus();
            }

            function abrirPerfil(cartao) {
                const avatarEl = dialogo.querySelector('#perfilAvatar');
                avatarEl.className = 'monitor-avatar avatar-' + cartao.dataset.cor;
                avatarEl.replaceChildren();
                if (cartao.dataset.temFoto === '1') {
                    const img = document.createElement('img');
                    img.src = '../../actions/fotoPerfil.php?id=' + encodeURIComponent(cartao.dataset.id);
                    img.alt = 'Foto de ' + cartao.dataset.nome;
                    avatarEl.appendChild(img);
                } else {
                    avatarEl.textContent = cartao.dataset.iniciais;
                }

                dialogo.querySelector('#perfilArea').textContent = cartao.dataset.area;
                dialogo.querySelector('#perfilNome').textContent = cartao.dataset.nome;
                dialogo.querySelector('#perfilCurso').textContent = cartao.dataset.curso;
                const qtdAvaliacoes = Number(cartao.dataset.avaliacoes || 0);
                dialogo.querySelector('#perfilNota').textContent = qtdAvaliacoes > 0
                    ? '★ ' + cartao.dataset.avaliacao + ' · ' + qtdAvaliacoes + (qtdAvaliacoes === 1 ? ' avaliação recebida' : ' avaliações recebidas')
                    : '★ ' + cartao.dataset.avaliacao + ' · Monitor verificado UNIVAG';
                dialogo.querySelector('#perfilSobre').textContent = cartao.dataset.sobre;
                dialogo.querySelector('#perfilDisciplina').textContent = cartao.dataset.disciplina;
                dialogo.querySelector('#perfilModalidade').textContent = cartao.dataset.modalidade;
                dialogo.querySelector('#perfilHorarios').textContent = cartao.dataset.horarios;

                dialogo.querySelector('#dialogBtnSolicitar').dataset.solicitarMonitoriaMonitor = cartao.dataset.id;
                dialogo.querySelector('#dialogBtnConversar').dataset.iniciarConversaMonitor = cartao.dataset.id;
                dialogo.querySelector('#dialogBtnPaginaCompleta').href =
                    '../monitor/perfilMonitor.php?id=' + encodeURIComponent(cartao.dataset.id);

                const tags = dialogo.querySelector('#perfilTags');
                tags.replaceChildren();
                cartao.dataset.tags.split(', ').forEach(function (nomeTag) {
                    if (!nomeTag) return;
                    const tag = document.createElement('span');
                    tag.textContent = nomeTag;
                    tags.appendChild(tag);
                });
                dialogo.showModal();
            }

            [busca, area, modalidade, apenasDisponiveis, ordenacao].forEach(function (controle) {
                controle.addEventListener('input', atualizarCatalogo);
                controle.addEventListener('change', atualizarCatalogo);
            });

            document.getElementById('limparFiltros').addEventListener('click', limparFiltros);
            document.querySelector('[data-limpar-vazio]').addEventListener('click', limparFiltros);
            lista.addEventListener('click', function (evento) {
                const botao = evento.target.closest('[data-abrir-perfil]');
                if (botao) {
                    abrirPerfil(botao.closest('.monitor-card'));
                }
            });
            dialogo.querySelector('[data-fechar-dialog]').addEventListener('click', function () {
                dialogo.close();
            });
            dialogo.addEventListener('click', function (evento) {
                if (evento.target === dialogo) {
                    dialogo.close();
                }
            });

            atualizarCatalogo();
        });
    </script>
</body>
</html>
