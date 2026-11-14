<p align="center">
  <img src="assets/img/logoAthenas.png" alt="UNIVAG Athenas Logo" width="220">
</p>

<h1 align="center">UNIVAG Athenas</h1>

<p align="center">
  <strong>Plataforma Integrada de Apoio Acadêmico, Monitorias e Aprendizagem Colaborativa</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/MySQL-8.4-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8.4">
  <img src="https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License MIT">
</p>

---

## 📖 Sobre o Projeto

O **UNIVAG Athenas** é um sistema web desenvolvido para conectar alunos e monitores acadêmicos. A plataforma permite agendamento de atendimentos, conversas em tempo real com compartilhamento de materiais de estudo, registro de presenças, avaliações mútuas e gamificação com pontos que desbloqueiam itens de personalização de perfil.

---

## ✨ Funcionalidades Principais

* **🧑‍🎓 Área do Aluno & 👨‍🏫 Área do Monitor**: Perfis distintos com fluxos dedicados para solicitação e aceitação de atendimentos.
* **📅 Gestão de Agenda**: Visualização mensal e semanal de monitorias com filtros por status (solicitado, confirmado, cancelado).
* **💬 Chat em Tempo Real**: Conversas individuais entre alunos e monitores com suporte a envio de links de reuniões (Zoom, Google Meet, Teams) e polling otimizado com a *Page Visibility API*.
* **🔔 Central de Notificações**: Avisos de novas mensagens, agendamentos confirmados e lembretes com controle de lidos.
* **📂 Envio e Download de Materiais**: Upload de arquivos de apoio vinculados aos atendimentos com validação segura de armazenamento.
* **🎮 Gamificação e Loja Virtual**: Recompensa de pontos por presença confirmada, permitindo a compra de fundos e avatares exclusivos.
* **📜 Certificados e Horas Complementares**: Acompanhamento de progresso acadêmico e emissão de comprovantes de monitoria.

---

## 🛠️ Stack Tecnológica

* **Back-end:** PHP 8.3 (Apache com `opcache`, `mod_rewrite`, `mod_headers`, `mod_deflate`)
* **Banco de Dados:** MySQL 8.4 InnoDB com suporte completo a UTF-8 (`utf8mb4`)
* **Front-end:** HTML5 semântico, CSS3 moderno com temas personalizados (Aluno/Monitor) e JavaScript vanilla
* **Infraestrutura:** Docker e Docker Compose com rede interna protegida e volumes persistentes

---

## 📂 Estrutura de Diretórios

```text
univag_athenas/
├── .github/              # Configurações do GitHub (CI Actions, Templates de Issue e PR)
├── actions/              # Controladores e endpoints da API (Chat, Monitorias, Notificações, Auth)
├── assets/               # Folhas de estilo (CSS), scripts (JS) e imagens (SVG, PNG)
├── config/               # Conexão PDO e configurações centrais
├── includes/             # Componentes reutilizáveis (Header, Navbar, Footer, Auth Helper)
├── sqlAthenas/           # Scripts DDL, migrações incrementais e schema do banco de dados
├── views/                # Telas da aplicação organizadas por contexto (Aluno, Monitor, Agenda, Auth)
├── .editorconfig         # Padrões de formatação entre editores de código
├── .env.example          # Modelo de variáveis de ambiente
├── .gitignore            # Regras de exclusão do controle de versão
├── .htaccess             # Proteção de arquivos sensíveis e cabeçalhos de segurança HTTP
├── docker-compose.yml    # Orquestração dos containers (app, db, reminders)
├── Dockerfile            # Imagem PHP 8.3 Apache customizada com extensões
└── php.ini               # Parâmetros de sessão segura, upload e OPcache
```

---

## 🚀 Como Executar o Projeto

### Pré-requisitos
* [Docker](https://www.docker.com/) e [Docker Compose](https://docs.docker.com/compose/) instalados.

### 1. Clonar o repositório
```bash
git clone https://github.com/eduardoclemes1-tech/univag_athenas.git
cd univag_athenas
```

### 2. Configurar as variáveis de ambiente
Copie o arquivo `.env.example` para `.env` e defina senhas fortes para o banco de dados:
```bash
cp .env.example .env
```

Edite o `.env`:
```ini
DB_PASSWORD=sua_senha_segura
DB_ROOT_PASSWORD=sua_senha_root_segura
```

### 3. Iniciar os containers
```bash
docker compose up -d --build
```

Os seguintes serviços serão inicializados:
* **`app`**: Servidor Web Apache + PHP 8.3 respondendo em `http://localhost:8000`.
* **`db`**: MySQL 8.4 com inicialização automática do schema em `sqlAthenas/schema.sql`.
* **`reminders`**: Worker em segundo plano processando lembretes automáticos de monitorias.

---

## 🔒 Segurança e Otimizações Implementadas

* **Proteção de Dados Sensíveis:** Arquivos `.env`, `.git`, `.sql` e configurações de infraestrutura bloqueados para requisições web via `.htaccess`.
* **Segurança de Sessão:** Cookies protegidos com flags `HttpOnly`, `SameSite=Lax` e `use_strict_mode`.
* **Otimização de Polling:** Redução automática do intervalo de requisições quando a aba está inativa via `Page Visibility API`.
* **Cache Inteligente:** Validação condicional com `ETag` (HTTP 304) para imagens e avatares.
* **OPcache Ativo:** Pré-compilação de bytecode PHP em memória para aceleração do tempo de resposta.

---

## 🤝 Como Contribuir

Contribuições são muito bem-vindas! Consulte o arquivo [CONTRIBUTING.md](CONTRIBUTING.md) para diretrizes de desenvolvimento, boas práticas de código e envio de Pull Requests.

---

## 📄 Licença

Este projeto é distribuído sob a licença **MIT**. Para mais detalhes, consulte o arquivo [LICENSE](LICENSE).
