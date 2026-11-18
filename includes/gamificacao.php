<?php
/**
 * Centraliza o catálogo de personalização, insígnias, fundos animados
 * e cálculo de Maestria de Matérias (Nível 1 a 10) do UNIVAG Athenas.
 */

function athenasCatalogoConjuntos(): array
{
    return [
        'matrix-cyber' => [
            'avatar_visual' => 'hacker-neon',
            'avatar_nome' => 'Mestre do Terminal',
            'icone' => 'fa-terminal',
            'peculiaridade' => 'Malha Matrix viva + Aura Verde Root na Navbar',
        ],
        'oceano-abissal' => [
            'avatar_visual' => 'leviata-dados',
            'avatar_nome' => 'Senhor dos Mares',
            'icone' => 'fa-anchor',
            'peculiaridade' => 'Bioluminescência abissal + Insígnia Tridente Ciano',
        ],
        'templo-atena' => [
            'avatar_visual' => 'coruja-atena',
            'avatar_nome' => 'Coruja Sábia de Atena',
            'icone' => 'fa-feather-pointed',
            'peculiaridade' => 'Constelações em rotação + Aura Safira de Atena',
        ],
        'floresta-runica' => [
            'avatar_visual' => 'espirito-pantanal',
            'avatar_nome' => 'Guardião Bio-Tech',
            'icone' => 'fa-leaf',
            'peculiaridade' => 'Partículas douradas vivas + Aura Bio-Esmeralda',
        ],
        'horizonte-synth' => [
            'avatar_visual' => 'piloto-synth',
            'avatar_nome' => 'Visor Cyber-Synth',
            'icone' => 'fa-vr-cardboard',
            'peculiaridade' => 'Sol Outrun & grade laser + Halo Neon Magenta',
        ],
        'tempestade-plasma' => [
            'avatar_visual' => 'nucleo-quantico',
            'avatar_nome' => 'Núcleo Quântico',
            'icone' => 'fa-atom',
            'peculiaridade' => 'Descargas de alta voltagem + Reator Atômico Violeta',
        ],
        'protocolo-fantasma' => [
            'avatar_visual' => 'ninja-cyber',
            'avatar_nome' => 'Sombra Furtiva',
            'icone' => 'fa-user-ninja',
            'peculiaridade' => 'Feixes furtivos carbono + Insígnia Ninja Ametista',
        ],
        'singularidade' => [
            'avatar_visual' => 'viajante-astral',
            'avatar_nome' => 'Viajante Interestelar',
            'icone' => 'fa-user-astronaut',
            'peculiaridade' => 'Horizonte de eventos + Halo Fotônico Dourado',
        ],
        'forja-dragao' => [
            'avatar_visual' => 'dragao-codigo',
            'avatar_nome' => 'Dragão Compilador',
            'icone' => 'fa-dragon',
            'peculiaridade' => 'Brasas vulcânicas vivas + Insígnia Dracônica Rubi',
        ],
        'eclipse-carmesim' => [
            'avatar_visual' => 'soberano-eclipse',
            'avatar_nome' => 'Soberano do Eclipse',
            'icone' => 'fa-fire-flame-curved',
            'peculiaridade' => 'Coroa solar carmesim + Chama Imperial Escarlate',
        ],
        'supernova-prisma' => [
            'avatar_visual' => 'fenix-cosmica',
            'avatar_nome' => 'Cristal de Supernova',
            'icone' => 'fa-gem',
            'peculiaridade' => 'Explosão holográfica + Joia Astral Multicolorida',
        ],
        'chama-olimpica' => [
            'avatar_visual' => 'coroa-lendaria',
            'avatar_nome' => 'Lenda do Campus UNIVAG',
            'icone' => 'fa-crown',
            'peculiaridade' => 'Fogo sagrado de Atena + Coroa Dourada Suprema',
        ],
    ];
}

function athenasPremiosMaestria10(): array
{
    return [
        'maestria-banco-dados' => [
            'trilha' => 'dados',
            'avatar_visual' => 'maestria-analista-dados',
            'avatar_nome' => 'O Analista de Dados (Literal)',
            'icone' => 'fa-dice',
            'piada' => 'Um banco de praça feito de dados de 6 faces 🎲 + Analista examinando dados na lupa!',
        ],
        'maestria-cacador-bugs' => [
            'trilha' => 'logica',
            'avatar_visual' => 'maestria-domador-bugs',
            'avatar_nome' => 'Criador Oficial de Bugs',
            'icone' => 'fa-bug',
            'piada' => 'Insetos literais de cartola e gravata borboleta tomando café: "Não é bug, é feature!"',
        ],
        'maestria-arvore-pilha' => [
            'trilha' => 'estruturas',
            'avatar_visual' => 'maestria-lenhador-binario',
            'avatar_nome' => 'Lenhador de Árvore Invertida',
            'icone' => 'fa-tree',
            'piada' => 'Árvore plantada de ponta-cabeça (raiz no céu, folhas no chão) + Pilha Alcalina LIFO!',
        ],
        'maestria-centralizador-div' => [
            'trilha' => 'dev',
            'avatar_visual' => 'maestria-mestre-div',
            'avatar_nome' => 'Senhor do margin: auto',
            'icone' => 'fa-arrows-to-dot',
            'piada' => 'A lendária <div> centralizada com mira a laser + Pilha de Panquecas "Full-Stack"!',
        ],
        'maestria-rede-dormir' => [
            'trilha' => 'redes',
            'avatar_visual' => 'maestria-admin-rede',
            'avatar_nome' => 'Admin de Rede (de Dormir)',
            'icone' => 'fa-umbrella-beach',
            'piada' => 'Uma rede de varanda balançando tranquila ao lado de um muro pegando fogo (Firewall)!',
        ],
    ];
}

function athenasIconesAvatares(): array
{
    return [
        'hacker-neon' => 'fa-terminal',
        'leviata-dados' => 'fa-anchor',
        'coruja-atena' => 'fa-feather-pointed',
        'espirito-pantanal' => 'fa-leaf',
        'piloto-synth' => 'fa-vr-cardboard',
        'nucleo-quantico' => 'fa-atom',
        'ninja-cyber' => 'fa-user-ninja',
        'viajante-astral' => 'fa-user-astronaut',
        'dragao-codigo' => 'fa-dragon',
        'soberano-eclipse' => 'fa-fire-flame-curved',
        'fenix-cosmica' => 'fa-gem',
        'coroa-lendaria' => 'fa-crown',
        'maestria-analista-dados' => 'fa-dice',
        'maestria-domador-bugs' => 'fa-bug',
        'maestria-lenhador-binario' => 'fa-tree',
        'maestria-mestre-div' => 'fa-arrows-to-dot',
        'maestria-admin-rede' => 'fa-umbrella-beach',
    ];
}

function athenasFundosPermitidos(): array
{
    return array_merge(
        array_keys(athenasCatalogoConjuntos()),
        array_keys(athenasPremiosMaestria10())
    );
}

function athenasAvataresPermitidos(): array
{
    return array_keys(athenasIconesAvatares());
}

function athenasTrilhasMaestriaBase(): array
{
    return [
        'dados' => [
            'chave' => 'dados',
            'titulo' => 'Banco de Dados & SQL',
            'insignia' => 'O Analista de Dados (Literal)',
            'icone' => 'fa-database',
            'cor' => 'cyan',
            'busca' => 'Banco de Dados',
            'palavras' => ['banco', 'sql', 'dados', 'modelagem', 'der', 'normalizacao', 'normalização'],
            'premio_nome' => 'O Literal Banco de Dados',
            'premio_avatar_nome' => 'O Analista de Dados (Literal)',
            'premio_piada' => 'Um banco de praça feito de dados de 6 faces 🎲 + Analista examinando dados na lupa!',
            'premio_fundo' => 'maestria-banco-dados',
            'premio_avatar' => 'maestria-analista-dados',
            'premio_icone' => 'fa-dice',
            'minutos' => 0,
            'sessoes' => 0,
        ],
        'logica' => [
            'chave' => 'logica',
            'titulo' => 'Lógica & Algoritmos',
            'insignia' => 'Criador Oficial de Bugs',
            'icone' => 'fa-code',
            'cor' => 'emerald',
            'busca' => 'Lógica de Programação',
            'palavras' => ['logica', 'lógica', 'algoritmo', 'python', 'programacao', 'programação'],
            'premio_nome' => 'Na Minha Máquina Funciona™',
            'premio_avatar_nome' => 'Criador Oficial de Bugs',
            'premio_piada' => 'Insetos de cartola e gravata borboleta tomando café: "Não é bug, é feature VIP!"',
            'premio_fundo' => 'maestria-cacador-bugs',
            'premio_avatar' => 'maestria-domador-bugs',
            'premio_icone' => 'fa-bug',
            'minutos' => 0,
            'sessoes' => 0,
        ],
        'estruturas' => [
            'chave' => 'estruturas',
            'titulo' => 'Estruturas de Dados',
            'insignia' => 'Lenhador de Árvore Invertida',
            'icone' => 'fa-diagram-project',
            'cor' => 'purple',
            'busca' => 'Estruturas de Dados',
            'palavras' => ['estrutura', 'arvore', 'árvore', 'grafo', 'pilha', 'fila', 'lista'],
            'premio_nome' => 'Árvore Invertida & Pilha Alcalina',
            'premio_avatar_nome' => 'Lenhador de Árvore Invertida',
            'premio_piada' => 'Árvore de cabeça para baixo (raiz no céu, folhas no chão) + Pilha Alcalina LIFO!',
            'premio_fundo' => 'maestria-arvore-pilha',
            'premio_avatar' => 'maestria-lenhador-binario',
            'premio_icone' => 'fa-tree',
            'minutos' => 0,
            'sessoes' => 0,
        ],
        'dev' => [
            'chave' => 'dev',
            'titulo' => 'Web & Mobile Full-Stack',
            'insignia' => 'Senhor do margin: auto',
            'icone' => 'fa-bolt',
            'cor' => 'amber',
            'busca' => 'Desenvolvimento',
            'palavras' => ['web', 'mobile', 'html', 'css', 'javascript', 'interface', 'app', 'api'],
            'premio_nome' => 'O Escolhido que Centralizou a <div>',
            'premio_avatar_nome' => 'Senhor do margin: auto',
            'premio_piada' => 'A lendária <div> centralizada com mira a laser + Pilha de Panquecas "Full-Stack"!',
            'premio_fundo' => 'maestria-centralizador-div',
            'premio_avatar' => 'maestria-mestre-div',
            'premio_icone' => 'fa-arrows-to-dot',
            'minutos' => 0,
            'sessoes' => 0,
        ],
        'redes' => [
            'chave' => 'redes',
            'titulo' => 'Redes & Cibersegurança',
            'insignia' => 'Admin de Rede (de Dormir)',
            'icone' => 'fa-shield-halved',
            'cor' => 'blue',
            'busca' => 'Redes de Computadores',
            'palavras' => ['rede', 'tcp', 'ip', 'dns', 'roteamento', 'infraestrutura', 'seguranca'],
            'premio_nome' => 'Rede de Varanda & Parede em Chamas',
            'premio_avatar_nome' => 'Admin de Rede (de Dormir)',
            'premio_piada' => 'Uma rede de varanda balançando tranquila ao lado de um muro pegando fogo (Firewall)!',
            'premio_fundo' => 'maestria-rede-dormir',
            'premio_avatar' => 'maestria-admin-rede',
            'premio_icone' => 'fa-umbrella-beach',
            'minutos' => 0,
            'sessoes' => 0,
        ],
    ];
}

function calcularPatenteMaestria(int $minutos): array
{
    $metaTotal = 540;
    $nivel = min(10, max(1, (int) floor($minutos / 60) + 1));
    if ($minutos >= $metaTotal) {
        $nivel = 10;
    }
    if ($nivel >= 10) {
        return ['nivel' => 10, 'patente' => 'Maestria 10 · Lenda', 'meta' => $metaTotal, 'classe' => 'tier-grandmaster'];
    }
    if ($nivel >= 7) {
        return ['nivel' => $nivel, 'patente' => 'Mestre Ouro', 'meta' => $metaTotal, 'classe' => 'tier-gold'];
    }
    if ($nivel >= 4) {
        return ['nivel' => $nivel, 'patente' => 'Especialista Prata', 'meta' => $metaTotal, 'classe' => 'tier-silver'];
    }
    if ($nivel >= 2) {
        return ['nivel' => $nivel, 'patente' => 'Aprendiz Bronze', 'meta' => $metaTotal, 'classe' => 'tier-bronze'];
    }
    return ['nivel' => 1, 'patente' => 'Iniciante', 'meta' => $metaTotal, 'classe' => 'tier-starter'];
}
