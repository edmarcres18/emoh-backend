@echo off
REM EMOH Chatbot Setup Script for Windows
REM This script sets up the AI chatbot backend

echo ==========================================
echo EMOH AI Chatbot Setup
echo ==========================================
echo.

REM Check if .env file exists
if not exist .env (
    echo Error: .env file not found!
    echo Please create a .env file first.
    pause
    exit /b 1
)

REM Check if OpenRouter API key is set
findstr /C:"OPENROUTER_API_KEY=your_openrouter_api_key_here" .env >nul
if %errorlevel% equ 0 (
    echo Warning: OpenRouter API key is not configured!
    echo Please update OPENROUTER_API_KEY in your .env file
    echo.
    set /p continue="Do you want to continue anyway? (y/n): "
    if /i not "%continue%"=="y" exit /b 1
)

echo Step 1: Running database migrations...
php artisan migrate --path=database/migrations/2024_10_21_000001_create_client_conversations_table.php
php artisan migrate --path=database/migrations/2024_10_21_000002_create_client_messages_table.php

if %errorlevel% neq 0 (
    echo Error: Migration failed
    pause
    exit /b 1
)

echo Migrations completed successfully
echo.

echo Step 2: Clearing configuration cache...
php artisan config:clear
php artisan cache:clear

if %errorlevel% neq 0 (
    echo Error: Cache clear failed
    pause
    exit /b 1
)

echo Cache cleared successfully
echo.

echo Step 3: Optimizing application...
php artisan optimize

echo.
echo ==========================================
echo Chatbot Backend Setup Complete!
echo ==========================================
echo.
echo Next Steps:
echo 1. Add your OpenRouter API key to .env file:
echo    OPENROUTER_API_KEY=your_actual_api_key
echo.
echo 2. Build frontend assets:
echo    cd ..\emoh-frontend
echo    npm run build
echo.
echo 3. Test the chatbot:
echo    - Login to client dashboard
echo    - Click chatbot icon (bottom-right)
echo    - Send a test message
echo.
echo For detailed documentation, see: CHATBOT_SETUP_GUIDE.md
echo.
pause
