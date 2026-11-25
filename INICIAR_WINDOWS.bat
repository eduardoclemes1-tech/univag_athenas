@echo off
chcp 65001 >nul
title UNIVAG Athenas - Inicializador Rapido

echo ========================================================
echo   UNIVAG ATHENAS - INICIALIZADOR AUTOMATICO (WINDOWS)
echo ========================================================
echo.

cd /d "%~dp0"

REM 1. Verifica se o Docker Desktop esta rodando na maquina
docker info >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] Docker Desktop detectado! Subindo os containers...
    docker compose up -d --build
    echo.
    echo [SUCESSO] Sistema ativo em: http://localhost:8000
    start http://localhost:8000
    pause
    exit /b 0
)

REM 2. Caso nao tenha Docker, verifica se o XAMPP esta instalado em C:\xampp
if exist "C:\xampp\php\php.exe" (
    echo [OK] XAMPP detectado em C:\xampp!
    tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
    if "%ERRORLEVEL%"=="1" (
        echo Iniciando o banco de dados MySQL do XAMPP...
        start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
        timeout /t 3 /nobreak >nul
    )
    echo.
    echo [SUCESSO] O banco de dados sera criado automaticamente no primeiro acesso!
    echo Abrindo navegador em: http://localhost:8000
    start http://localhost:8000
    "C:\xampp\php\php.exe" -S localhost:8000
    exit /b 0
)

REM 3. Caso tenha PHP instalado no PATH do Windows
where php >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] PHP detectado no Windows!
    echo Certifique-se de que o MySQL esteja ativo.
    start http://localhost:8000
    php -S localhost:8000
    exit /b 0
)

echo [AVISO] Nao encontramos o Docker Desktop aberto nem o XAMPP em C:\xampp.
echo Abra o Docker Desktop ou instale o XAMPP para rodar o projeto.
pause
