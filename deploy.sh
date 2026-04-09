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
  if ! command -v openssl >/dev/null 2>&1; then
    echo "JWT keys are missing and openssl is not installed on the host."
    echo "Install openssl or create keys manually:"
    echo "  openssl genrsa -out config/jwt/private.pem 4096"
    echo "  openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem"
    echo "  chmod 644 config/jwt/private.pem config/jwt/public.pem"
    exit 1
  fi
  echo "Generating JWT RSA keys (not in git; first deploy on this server)..."
  openssl genrsa -out config/jwt/private.pem 4096
  openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem
  chmod 644 config/jwt/private.pem config/jwt/public.pem
fi

echo "Building and starting containers..."
docker compose -f docker-compose.server.yml up -d --build

echo "Verifying JWT keys are readable by php-fpm (www-data)..."
if ! docker compose -f docker-compose.server.yml exec -T -u www-data php sh -c 'test -r /var/www/html/config/jwt/private.pem && test -r /var/www/html/config/jwt/public.pem'; then
  echo "ERROR: PHP-FPM runs as www-data; it cannot read mounted JWT keys."
  echo "On the host, fix permissions, e.g.:"
  echo "  chmod 644 config/jwt/private.pem config/jwt/public.pem"
  exit 1
fi

echo "Running migrations..."
docker compose -f docker-compose.server.yml exec -T php php bin/console doctrine:migrations:migrate --no-interaction

echo "Publishing bundle assets to public/bundles (nginx serves the host tree, not the image)..."
docker compose -f docker-compose.server.yml exec -T php php bin/console assets:install public --no-interaction
rm -rf public/bundles
mkdir -p public/bundles
docker compose -f docker-compose.server.yml cp "php:/var/www/html/public/bundles/." "./public/bundles/"

echo "Clearing cache..."
docker compose -f docker-compose.server.yml exec -T php php bin/console cache:clear

echo "Deployment completed."
