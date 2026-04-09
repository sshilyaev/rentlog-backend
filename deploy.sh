#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${1:-main}"

cd "$PROJECT_DIR"

if [[ ! -f ".env" ]]; then
  echo "Missing .env. On the server keep production values in .env (see .env.dev for local dev template)."
  exit 1
fi

if [[ ! -d ".git" ]]; then
  echo "This script must be run inside the git repository."
  exit 1
fi

echo "Fetching latest changes..."
git fetch origin
git checkout "$BRANCH"
PREV_HEAD="$(git rev-parse HEAD)"
git pull --ff-only origin "$BRANCH"

if git diff --name-only "$PREV_HEAD" HEAD | grep -q '^\.env$'; then
  echo ""
  echo "Note: .env changed in this pull. If the server uses git-tracked .env, review secrets and prod-only values."
  echo ""
fi

mkdir -p config/jwt

if [[ ! -f "config/jwt/private.pem" || ! -f "config/jwt/public.pem" ]]; then
  echo "JWT keys are missing. Generate them on the server before deploy."
  echo "Example:"
  echo "  openssl genrsa -out config/jwt/private.pem 4096"
  echo "  openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem"
  exit 1
fi

echo "Building and starting containers..."
docker compose -f docker-compose.server.yml up -d --build

echo "Running migrations..."
docker compose -f docker-compose.server.yml exec -T php php bin/console doctrine:migrations:migrate --no-interaction

echo "Clearing cache..."
docker compose -f docker-compose.server.yml exec -T php php bin/console cache:clear

echo "Deployment completed."
