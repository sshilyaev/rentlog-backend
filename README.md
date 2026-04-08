# Rentlog Backend

Бэкенд проекта Rentlog на Symfony.

## Что уже заложено

- foundation структура проекта
- Docker-окружение с `php`, `nginx`, `postgres`, `redis`
- API `v1`
- health-check endpoint
- задел под JWT-аутентификацию
- базовая модульная структура `Shared`, `Auth`, `Health`, `Property`, `Contract`

## Запуск

Сначала нужно установить зависимости Composer:

```bash
composer install
```

Сгенерировать JWT-ключи:

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem
```

Поднять инфраструктуру:

```bash
docker compose up --build -d
```

## Полезные команды

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php bin/console debug:router
```

## Базовые маршруты

- `GET /api/v1/health`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `GET /api/v1/properties/types`
- `POST /api/v1/properties`
- `GET /api/v1/properties`
- `GET /api/v1/properties/{id}`
- `GET /api/v1/properties/{id}/members`
- `POST /api/v1/properties/{id}/members`
- `POST /api/v1/properties/{id}/members/{memberId}/invite`
- `POST /api/v1/invitations/claim`

## Важное замечание

Если зависимости не установлены, `public/index.php` и `bin/console` возвращают понятное сообщение о том, что нужно выполнить `composer install`.

## Деплой на сервер

Для сервера подготовлены:

- `docker-compose.server.yml`
- `docker/php/Dockerfile.prod`
- `.env.server.example`
- `deploy.sh`

Базовый сценарий:

```bash
cp .env.server.example .env.server
chmod +x deploy.sh
./deploy.sh
```

Серверный `nginx` публикуется на хосте на порту `8080`:

```txt
8080:80
```

Это соответствует текущей схеме reverse proxy на сервере.
