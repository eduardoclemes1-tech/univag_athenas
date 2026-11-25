#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

echo "========================================================"
echo "  UNIVAG ATHENAS - INICIALIZADOR AUTOMÁTICO (LINUX)"
echo "========================================================"

# 1. Se o Docker estiver rodando e acessível, sobe via Docker Compose
if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
    echo "[OK] Docker detectado! Subindo containers..."
    docker compose up -d --build
    echo "🚀 Sistema ativo em: http://localhost:8000"
    exit 0
fi

# 2. Caso contrário, inicia com PHP local (o banco athenas é criado sozinho pelo config/conexao.php!)
echo "[OK] Iniciando servidor PHP em http://localhost:8000 ..."
echo "(O banco de dados 'athenas' será criado e populado automaticamente no primeiro acesso!)"
exec php -S localhost:8000
