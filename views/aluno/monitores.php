<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');

$monitores = [
    [
        'nome' => 'Marcos Silva Pereira',
        'iniciais' => 'MS',
        'area' => 'Dados',
        'disciplina' => 'Banco de Dados',
        'tags' => ['SQL', 'Modelagem de dados', 'DER'],
        'modalidade' => 'Online e presencial',
        'disponivel' => true,
        'horarios' => 'Segunda e quarta, das 14h às 18h',
        'avaliacao' => '4,9',
        'avaliacoes' => 28,
        'curso' => 'Sistemas de Informação · 5º semestre',
        'sobre' => 'Gosto de explicar banco de dados com exemplos do dia a dia, começando pelos conceitos e avançando para exercícios práticos de SQL.',
        'cor' => 'verde',
    ],
    [
        'nome' => 'Ana Beatriz Costa',
        'iniciais' => 'AC',
        'area' => 'Programação',
        'disciplina' => 'Lógica de Programação',
        'tags' => ['Python', 'Algoritmos', 'Iniciantes'],
        'modalidade' => 'Online',
        'disponivel' => true,
        'horarios' => 'Terça e quinta, das 16h às 19h',
        'avaliacao' => '4,8',
        'avaliacoes' => 21,
        'curso' => 'Ciência da Computação · 4º semestre',
        'sobre' => 'Ajudo quem está começando a transformar problemas em passos simples e escrever os primeiros programas com confiança.',
        'cor' => 'azul',
    ],
    [
        'nome' => 'Lucas Mendes Rocha',
        'iniciais' => 'LR',
        'area' => 'Desenvolvimento',
        'disciplina' => 'Desenvolvimento Web',
        'tags' => ['HTML e CSS', 'JavaScript', 'Projetos'],
        'modalidade' => 'Presencial',
        'disponivel' => false,
        'horarios' => 'Novos horários em breve',
        'avaliacao' => '4,7',
        'avaliacoes' => 16,
        'curso' => 'Sistemas de Informação · 6º semestre',
        'sobre' => 'Trabalho com projetos web e gosto de ensinar construindo páginas junto com o aluno, do primeiro layout à interação.',
        'cor' => 'laranja',
    ],
    [
        'nome' => 'Juliana Ferreira Lima',
        'iniciais' => 'JL',
        'area' => 'Programação',
        'disciplina' => 'Estruturas de Dados',
        'tags' => ['Algoritmos', 'Árvores', 'Ordenação'],
        'modalidade' => 'Online e presencial',
        'disponivel' => true,
        'horarios' => 'Segunda a sexta, das 10h às 12h',
        'avaliacao' => '5,0',
        'avaliacoes' => 19,
        'curso' => 'Ciência da Computação · 5º semestre',
        'sobre' => 'Ajudo a visualizar como os dados se organizam em listas, árvores e outras estruturas, relacionando teoria e implementação.',
        'cor' => 'roxo',
    ],
    [
        'nome' => 'Rafael Oliveira Santos',
        'iniciais' => 'RS',
        'area' => 'Infraestrutura',
        'disciplina' => 'Redes de Computadores',
        'tags' => ['TCP/IP', 'Endereçamento', 'Conectividade'],
        'modalidade' => 'Online',
        'disponivel' => true,
        'horarios' => 'Quarta e sexta, das 13h às 17h',
        'avaliacao' => '4,8',
        'avaliacoes' => 14,
        'curso' => 'Redes de Computadores · 6º semestre',
        'sobre' => 'Explico os fundamentos de redes e protocolos com exemplos práticos de comunicação entre dispositivos.',
        'cor' => 'azul',
    ],
    [
        'nome' => 'Camila Nunes Almeida',
        'iniciais' => 'CA',
        'area' => 'Desenvolvimento',
        'disciplina' => 'Desenvolvimento Mobile',
        'tags' => ['Interfaces', 'Aplicativos', 'APIs'],
        'modalidade' => 'Presencial',
        'disponivel' => true,
        'horarios' => 'Terça e quinta, das 9h às 12h',
        'avaliacao' => '4,9',
        'avaliacoes' => 25,
        'curso' => 'Sistemas de Informação · 6º semestre',
        'sobre' => 'Compartilho práticas para criar interfaces mobile simples, acessíveis e conectadas a serviços web.',
        'cor' => 'verde',
    ],
];

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
    <link rel="stylesheet" href="../../assets/css/catalogoMonitores.css?v=1">
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
                    <p>Explore as áreas e escolha quem pode ajudar você a avançar.</p>
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
                            data-nome="<?php echo htmlspecialchars($monitor['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-area="<?php echo htmlspecialchars($monitor['area'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-disciplina="<?php echo htmlspecialchars($monitor['disciplina'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-tags="<?php echo htmlspecialchars(implode(', ', $monitor['tags']), ENT_QUOTES, 'UTF-8'); ?>"
                            data-modalidade="<?php echo htmlspecialchars($monitor['modalidade'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-disponivel="<?php echo $monitor['disponivel'] ? '1' : '0'; ?>"
                            data-horarios="<?php echo htmlspecialchars($monitor['horarios'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-avaliacao="<?php echo htmlspecialchars($monitor['avaliacao'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-avaliacoes="<?php echo $monitor['avaliacoes']; ?>"
                            data-curso="<?php echo htmlspecialchars($monitor['curso'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-sobre="<?php echo htmlspecialchars($monitor['sobre'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-iniciais="<?php echo htmlspecialchars($monitor['iniciais'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-cor="<?php echo htmlspecialchars($monitor['cor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-busca="<?php echo htmlspecialchars($buscaCard, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <div class="monitor-card-topo">
                                <span class="monitor-avatar avatar-<?php echo htmlspecialchars($monitor['cor'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($monitor['iniciais'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span class="monitor-nota" aria-label="Nota <?php echo htmlspecialchars($monitor['avaliacao'], ENT_QUOTES, 'UTF-8'); ?> de 5">
                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($monitor['avaliacao'], ENT_QUOTES, 'UTF-8'); ?>
                                    <small>(<?php echo $monitor['avaliacoes']; ?>)</small>
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
                                    <?php echo $monitor['disponivel'] ? 'Com horários' : 'Sem horários no momento'; ?>
                                </span>
                                <button class="monitor-detalhes" type="button" data-abrir-perfil>
                                    Ver perfil <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
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
                <p class="catalogo-aviso">Perfis e horários exibidos são exemplos para demonstração.</p>
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
            <p class="perfil-dialog-aviso">Este é um perfil de demonstração. Horários e avaliações são exemplos.</p>
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
                        && (!modalidade.value || cartao.dataset.modalidade.includes(modalidade.value))
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
                dialogo.querySelector('#perfilAvatar').textContent = cartao.dataset.iniciais;
                dialogo.querySelector('#perfilAvatar').className = 'monitor-avatar avatar-' + cartao.dataset.cor;
                dialogo.querySelector('#perfilArea').textContent = cartao.dataset.area;
                dialogo.querySelector('#perfilNome').textContent = cartao.dataset.nome;
                dialogo.querySelector('#perfilCurso').textContent = cartao.dataset.curso;
                dialogo.querySelector('#perfilNota').textContent =
                    '★ ' + cartao.dataset.avaliacao + ' · ' + cartao.dataset.avaliacoes + ' avaliações de exemplo';
                dialogo.querySelector('#perfilSobre').textContent = cartao.dataset.sobre;
                dialogo.querySelector('#perfilDisciplina').textContent = cartao.dataset.disciplina;
                dialogo.querySelector('#perfilModalidade').textContent = cartao.dataset.modalidade;
                dialogo.querySelector('#perfilHorarios').textContent = cartao.dataset.horarios;

                const tags = dialogo.querySelector('#perfilTags');
                tags.replaceChildren();
                cartao.dataset.tags.split(', ').forEach(function (nomeTag) {
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
