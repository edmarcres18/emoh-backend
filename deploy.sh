#!/bin/bash

# Emoh Backend Docker Deployment Script
# Usage: ./deploy.sh [dev|prod]

set -e

ENVIRONMENT=${1:-dev}

echo "🚀 Deploying Emoh Backend in $ENVIRONMENT mode..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from template..."
    cp env.docker.example .env
    echo "⚠️  Please update .env file with your configuration before running again."
    exit 1
fi

# Generate application key if not set
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating application key..."
    docker run --rm -v $(pwd):/app -w /app php:8.2-cli php -r "echo 'APP_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;" >> .env.tmp
    grep -v "APP_KEY=" .env > .env.tmp2
    cat .env.tmp2 .env.tmp > .env
    rm .env.tmp .env.tmp2
fi

if [ "$ENVIRONMENT" = "prod" ]; then
    echo "🏭 Deploying in production mode..."

    # Check for required environment variables
    required_vars=("DB_DATABASE" "DB_USERNAME" "DB_PASSWORD" "DB_ROOT_PASSWORD" "REDIS_PASSWORD")
    for var in "${required_vars[@]}"; do
        if ! grep -q "^$var=" .env || grep -q "^$var=$" .env; then
            echo "❌ Required environment variable $var is not set in .env"
            exit 1
        fi
    done

    # Build and start production containers
    docker compose -f docker-compose.prod.yml down
    docker compose -f docker-compose.prod.yml build --no-cache
    docker compose -f docker-compose.prod.yml up -d

    # Wait for services to be healthy
    echo "⏳ Waiting for services to be healthy..."
    docker-compose -f docker-compose.prod.yml ps

    # Run Laravel setup commands
    echo "🔧 Running Laravel setup commands..."
    docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
    docker compose -f docker-compose.prod.yml exec app php artisan config:cache
    docker compose -f docker-compose.prod.yml exec app php artisan route:cache
    docker compose -f docker-compose.prod.yml exec app php artisan view:cache

else
    echo "🛠️  Deploying in development mode..."

    # Build and start development containers
    docker compose down
    docker compose build --no-cache
    docker compose up -d

    # Wait for services to be healthy
    echo "⏳ Waiting for services to be healthy..."
    docker compose ps

    # Run Laravel setup commands
    echo "🔧 Running Laravel setup commands..."
    docker compose exec app php artisan migrate
    docker compose exec app php artisan db:seed
fi

echo "✅ Deployment completed successfully!"
echo ""
echo "🌐 Application URLs:"
echo "   - Web: http://localhost:2001"
echo "   - phpMyAdmin: http://localhost:2002"
echo "   - Database: localhost:3030"
echo "   - Redis: localhost:6379"
echo ""
echo "📊 Check container status:"
if [ "$ENVIRONMENT" = "prod" ]; then
    echo "   docker-compose -f docker-compose.prod.yml ps"
else
    echo "   docker-compose ps"
fi
