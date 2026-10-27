<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirUsuarioAutenticado('aluno');

$materias = [
    [
        'titulo' => 'Lógica de Programação',
        'categoria' => 'Programação',
        'imagem' => 'programacao.svg',
        'disciplina' => 'Fundamentos de programação',
        'descricao' => 'Aprenda a transformar problemas em soluções organizadas. A matéria apresenta os fundamentos que você vai usar em qualquer linguagem de programação.',
        'conteudos' => ['Algoritmos e fluxogramas', 'Variáveis e tipos de dados', 'Decisões e repetições', 'Primeiros passos com Python'],
        'indicada' => 'Quem está começando a programar ou quer reforçar a lógica antes de avançar.',
        'monitorBusca' => 'Lógica de Programação',
    ],
    [
        'titulo' => 'Banco de Dados',
        'categoria' => 'Dados',
        'imagem' => 'banco-de-dados.svg',
        'disciplina' => 'Modelagem e SQL',
        'descricao' => 'Entenda como os dados são organizados, relacionados e consultados. Da modelagem de tabelas às consultas SQL, com exemplos próximos de situações reais.',
        'conteudos' => ['Modelo entidade-relacionamento', 'Chaves e relacionamentos', 'Comandos SQL', 'Consultas e junções'],
        'indicada' => 'Quem quer aprender a modelar informações e consultar bancos relacionais.',
        'monitorBusca' => 'Banco de Dados',
    ],
    [
        'titulo' => 'Estruturas de Dados',
        'categoria' => 'Programação',
        'imagem' => 'estruturas-de-dados.svg',
        'disciplina' => 'Organização e algoritmos',
        'descricao' => 'Explore diferentes formas de organizar informações e entenda como a escolha de uma estrutura pode tornar um programa mais claro e eficiente.',
        'conteudos' => ['Vetores e listas', 'Pilhas e filas', 'Árvores e grafos', 'Busca e ordenação'],
        'indicada' => 'Quem já conhece o básico de programação e quer aprofundar algoritmos e organização de dados.',
        'monitorBusca' => 'Estruturas de Dados',
    ],
    [
        'titulo' => 'Desenvolvimento Mobile',
        'categoria' => 'Desenvolvimento',
        'imagem' => 'desenvolvimento-mobile.svg',
        'disciplina' => 'Aplicativos para dispositivos móveis',
        'descricao' => 'Conheça os princípios por trás de aplicativos mobile: interfaces pensadas para telas pequenas, navegação intuitiva e integração com recursos do dispositivo.',
        'conteudos' => ['Componentes de interface', 'Navegação entre telas', 'Consumo de APIs', 'Experiência mobile'],
        'indicada' => 'Quem quer entender como aplicativos são planejados e construídos para celulares.',
        'monitorBusca' => 'Desenvolvimento Mobile',
    ],
    [
        'titulo' => 'Redes de Computadores',
        'categoria' => 'Infraestrutura',
        'imagem' => 'redes-de-computadores.svg',
        'disciplina' => 'Conectividade e protocolos',
        'descricao' => 'Entenda como dispositivos se comunicam e como a internet entrega informações. Aprenda os fundamentos das redes, dos endereços aos protocolos.',
        'conteudos' => ['Modelos OSI e TCP/IP', 'Endereçamento IP', 'Roteamento e DNS', 'Noções de segurança em redes'],
        'indicada' => 'Quem tem curiosidade sobre como redes, serviços e a internet funcionam por trás das telas.',
        'monitorBusca' => 'Redes de Computadores',
    ],
    [
        'titulo' => 'Desenvolvimento Web',
        'categoria' => 'Desenvolvimento',
        'imagem' => 'desenvolvimento-web.svg',
        'disciplina' => 'Criação de páginas e interfaces',
        'descricao' => 'Descubra como uma página web ganha forma e interação. Combine estrutura, estilo e comportamento para criar interfaces claras e funcionais.',
        'conteudos' => ['Estrutura com HTML', 'Estilos com CSS', 'Interatividade com JavaScript', 'Acessibilidade e responsividade'],
        'indicada' => 'Quem quer criar páginas web ou aprender a construir interfaces do zero.',
        'monitorBusca' => 'Desenvolvimento Web',
    ],
];

$categorias = array_values(array_unique(array_column($materias, 'categoria')));
sort($categorias);
$categoriaSelecionada = isset($_GET['categoria']) && is_string($_GET['categoria'])
    ? $_GET['categoria']
    : '';
if (!in_array($categoriaSelecionada, $categorias, true)) {
    $categoriaSelecionada = '';
}
$termoBusca = isset($_GET['busca']) && is_string($_GET['busca'])
    ? trim($_GET['busca'])
    : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas matérias - UNIVAG ATHENAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/aluno.css?v=2">
    <link rel="stylesheet" href="../../assets/css/materias.css?v=1">
</head>
<body class="aluno-theme">
    <?php include '../../includes/navbar.php'; ?>

    <main class="aluno-main materias-main">
        <div class="aluno-layout">
            <header class="materias-cabecalho">
                <div>
                    <a class="materias-voltar" href="inicio.php">
                        <i class="fa-solid fa-arrow-left"></i> Painel do aluno
                    </a>
                    <span class="aluno-eyebrow">TECNOLOGIA · APRENDA NO SEU RITMO</span>
                    <h1>Explore tecnologia</h1>
                    <p>Encontre um assunto de tecnologia e descubra por onde começar.</p>
                </div>
                <div class="materias-icone" aria-hidden="true">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </header>

            <section class="materias-controles" aria-label="Buscar e filtrar matérias">
                <label class="materias-busca">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input
                        id="buscaMaterias"
                        type="search"
                        placeholder="Buscar matéria ou assunto"
                        value="<?php echo htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8'); ?>"
                        autocomplete="off"
                    >
                </label>
                <label class="materias-filtro">
                    <span>Trilha de tecnologia</span>
                    <select id="filtroCategoria">
                        <option value="">Todas as trilhas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $categoriaSelecionada === $categoria ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </section>

            <section class="materias-listagem" aria-labelledby="tituloMaterias">
                <div class="materias-listagem-topo">
                    <div>
                        <h2 id="tituloMaterias">Tecnologia para explorar</h2>
                        <p id="contadorMaterias" aria-live="polite"></p>
                    </div>
                    <span class="materias-instrucao"><i class="fa-regular fa-hand-pointer"></i> Selecione uma capa para ver os detalhes</span>
                </div>

                <div class="materias-grade" id="gradeMaterias">
                    <?php foreach ($materias as $indice => $materia): ?>
                        <?php
                        $textoBusca = mb_strtolower(
                            $materia['titulo'] . ' ' . $materia['categoria'] . ' ' . $materia['disciplina'] . ' ' . implode(' ', $materia['conteudos']),
                            'UTF-8'
                        );
                        ?>
                        <article
                            class="materia-card"
                            data-indice="<?php echo $indice; ?>"
                            data-titulo="<?php echo htmlspecialchars($materia['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-categoria="<?php echo htmlspecialchars($materia['categoria'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-disciplina="<?php echo htmlspecialchars($materia['disciplina'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-descricao="<?php echo htmlspecialchars($materia['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-conteudos="<?php echo htmlspecialchars(implode('|', $materia['conteudos']), ENT_QUOTES, 'UTF-8'); ?>"
                            data-indicada="<?php echo htmlspecialchars($materia['indicada'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-monitor-busca="<?php echo htmlspecialchars($materia['monitorBusca'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-busca="<?php echo htmlspecialchars($textoBusca, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <button class="materia-capa-botao" type="button" data-abrir-materia aria-label="Ver detalhes de <?php echo htmlspecialchars($materia['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
                                <img
                                    class="materia-capa"
                                    src="../../assets/img/materias/<?php echo htmlspecialchars($materia['imagem'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt=""
                                    loading="lazy"
                                >
                                <span class="materia-capa-sobreposicao">
                                    <span class="materia-play" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                                    <span>Ver matéria</span>
                                </span>
                            </button>
                            <div class="materia-card-info">
                                <span class="materia-categoria"><?php echo htmlspecialchars($materia['categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <h3><?php echo htmlspecialchars($materia['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($materia['disciplina'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="materias-vazio" id="estadoVazioMaterias" hidden>
                    <span><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                    <h3>Nenhuma matéria encontrada</h3>
                    <p>Tente outro nome ou escolha uma trilha diferente.</p>
                    <button type="button" id="limparMaterias">Limpar filtros</button>
                </div>
                <p class="materias-aviso">Conteúdos e descrições são exemplos para demonstração.</p>
            </section>
        </div>
    </main>

    <dialog class="materia-dialog" id="dialogMateria" aria-labelledby="dialogTitulo">
        <div class="materia-dialog-capa">
            <img id="dialogImagem" src="" alt="">
            <button type="button" class="materia-dialog-fechar" data-fechar-materia aria-label="Fechar detalhes">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="materia-dialog-conteudo">
            <span class="materia-categoria" id="dialogCategoria"></span>
            <h2 id="dialogTitulo"></h2>
            <p class="materia-dialog-disciplina" id="dialogDisciplina"></p>
            <p class="materia-dialog-descricao" id="dialogDescricao"></p>
            <section class="materia-dialog-bloco">
                <h3>O que você vai encontrar</h3>
                <ul id="dialogConteudos"></ul>
            </section>
            <section class="materia-dialog-bloco">
                <h3>Para quem é</h3>
                <p id="dialogIndicada"></p>
            </section>
            <a class="materia-dialog-cta" id="dialogMonitorLink" href="monitores.php">
                <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                Encontrar monitor desta matéria
            </a>
            <p class="materia-dialog-aviso">Matéria de demonstração; conteúdos podem ser ajustados ao catálogo acadêmico real.</p>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const busca = document.getElementById('buscaMaterias');
            const filtroCategoria = document.getElementById('filtroCategoria');
            const cards = Array.from(document.querySelectorAll('.materia-card'));
            const contador = document.getElementById('contadorMaterias');
            const lista = document.getElementById('gradeMaterias');
            const estadoVazio = document.getElementById('estadoVazioMaterias');
            const dialogo = document.getElementById('dialogMateria');

            function normalizarTexto(texto) {
                return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
            }

            function atualizarLista() {
                const termo = normalizarTexto(busca.value.trim());
                const visiveis = cards.filter(function (card) {
                    return normalizarTexto(card.dataset.busca).includes(termo)
                        && (!filtroCategoria.value || card.dataset.categoria === filtroCategoria.value);
                });

                cards.forEach(function (card) {
                    card.hidden = !visiveis.includes(card);
                });
                contador.textContent = visiveis.length === 1
                    ? '1 matéria encontrada'
                    : visiveis.length + ' matérias encontradas';
                lista.hidden = visiveis.length === 0;
                estadoVazio.hidden = visiveis.length !== 0;
            }

            function abrirDetalhes(card) {
                const imagem = card.querySelector('.materia-capa').getAttribute('src');
                dialogo.querySelector('#dialogImagem').src = imagem;
                dialogo.querySelector('#dialogCategoria').textContent = card.dataset.categoria;
                dialogo.querySelector('#dialogTitulo').textContent = card.dataset.titulo;
                dialogo.querySelector('#dialogDisciplina').textContent = card.dataset.disciplina;
                dialogo.querySelector('#dialogDescricao').textContent = card.dataset.descricao;
                dialogo.querySelector('#dialogIndicada').textContent = card.dataset.indicada;

                const conteudos = dialogo.querySelector('#dialogConteudos');
                conteudos.replaceChildren();
                card.dataset.conteudos.split('|').forEach(function (conteudo) {
                    const item = document.createElement('li');
                    item.textContent = conteudo;
                    conteudos.appendChild(item);
                });

                const urlMonitores = new URL('monitores.php', window.location.href);
                urlMonitores.searchParams.set('busca', card.dataset.monitorBusca);
                dialogo.querySelector('#dialogMonitorLink').href = urlMonitores.toString();
                dialogo.showModal();
            }

            busca.addEventListener('input', atualizarLista);
            filtroCategoria.addEventListener('change', atualizarLista);
            document.getElementById('limparMaterias').addEventListener('click', function () {
                busca.value = '';
                filtroCategoria.value = '';
                atualizarLista();
                busca.focus();
            });
            lista.addEventListener('click', function (evento) {
                const botao = evento.target.closest('[data-abrir-materia]');
                if (botao) {
                    abrirDetalhes(botao.closest('.materia-card'));
                }
            });
            dialogo.querySelector('[data-fechar-materia]').addEventListener('click', function () {
                dialogo.close();
            });
            dialogo.addEventListener('click', function (evento) {
                if (evento.target === dialogo) {
                    dialogo.close();
                }
            });

            atualizarLista();
        });
    </script>
</body>
</html>
