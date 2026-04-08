#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${1:-main}"

cd "$PROJECT_DIR"

if [[ ! -f ".env.server" ]]; then
  echo "Missing .env.server. Create it from .env.server.example before deploy."
  exit 1
fi

if [[ ! -d ".git" ]]; then
  echo "This script must be run inside the git repository."
  exit 1
fi

echo "Fetching latest changes..."
git fetch origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

mkdir -p config/jwt

if [[ ! -f "config/jwt/private.pem" || ! -f "config/jwt/public.pem" ]]; then
  echo "JWT keys are missing. Generate them on the server before deploy."
  echo "Example:"
  echo "  openssl genrsa -out config/jwt/private.pem 4096"
  echo "  openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem"
  exit 1
fi

echo "Building and starting containers..."
docker compose --env-file .env.server -f docker-compose.server.yml up -d --build

echo "Running migrations..."
docker compose --env-file .env.server -f docker-compose.server.yml exec -T php php bin/console doctrine:migrations:migrate --no-interaction

echo "Clearing cache..."
docker compose --env-file .env.server -f docker-compose.server.yml exec -T php php bin/console cache:clear

echo "Deployment completed."
