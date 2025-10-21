#!/bin/bash

# EMOH Chatbot Setup Script
# This script sets up the AI chatbot backend

echo "=========================================="
echo "EMOH AI Chatbot Setup"
echo "=========================================="
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please create a .env file first."
    exit 1
fi

# Check if OpenRouter API key is set
if grep -q "OPENROUTER_API_KEY=your_openrouter_api_key_here" .env; then
    echo "⚠️  Warning: OpenRouter API key is not configured!"
    echo "Please update OPENROUTER_API_KEY in your .env file"
    echo ""
    read -p "Do you want to continue anyway? (y/n) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "Step 1: Running database migrations..."
php artisan migrate --path=database/migrations/2024_10_21_000001_create_client_conversations_table.php
php artisan migrate --path=database/migrations/2024_10_21_000002_create_client_messages_table.php

if [ $? -eq 0 ]; then
    echo "✅ Migrations completed successfully"
else
    echo "❌ Migration failed"
    exit 1
fi

echo ""
echo "Step 2: Clearing configuration cache..."
php artisan config:clear
php artisan cache:clear

if [ $? -eq 0 ]; then
    echo "✅ Cache cleared successfully"
else
    echo "❌ Cache clear failed"
    exit 1
fi

echo ""
echo "Step 3: Optimizing application..."
php artisan optimize

echo ""
echo "=========================================="
echo "✅ Chatbot Backend Setup Complete!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Add your OpenRouter API key to .env file:"
echo "   OPENROUTER_API_KEY=your_actual_api_key"
echo ""
echo "2. Build frontend assets:"
echo "   cd ../emoh-frontend"
echo "   npm run build"
echo ""
echo "3. Test the chatbot:"
echo "   - Login to client dashboard"
echo "   - Click chatbot icon (bottom-right)"
echo "   - Send a test message"
echo ""
echo "For detailed documentation, see: CHATBOT_SETUP_GUIDE.md"
echo ""
