# API для клиентского приложения (Rentlog backend)

Документ для разработки мобильного/веб-клиента: маршруты, тела запросов, **фактические** поля успешных ответов и типичные ошибки парсинга на стороне клиента.

Базовый префикс HTTP: **`/api/v1`**. Полный путь = префикс + путь из таблицы (например `POST /api/v1/auth/login`).

Исходники контроллеров указаны от корня репозитория `rentlog-backend/`.

---

## 1. Общие правила

### 1.1 Успешный ответ (наш формат)

Почти все действия возвращают:

```json
{
  "success": true,
  "data": { }
}
```

**Клиент обязан читать полезную нагрузку из `data`, а не с корня JSON.** Если ожидать поля на верхнем уровне, они «пропадут».

Исключение: обработчик `json_login` для входа формирует ответ вручную, но структура та же: `success` + `data` (см. ниже).

### 1.2 Ошибка (наш формат)

```json
{
  "success": false,
  "error": {
    "code": "строка-код",
    "message": "человекочитаемое сообщение"
  }
}
```

**Важно — два семейства кодов:**

| Где | Поле `error.code` | Пример |
|-----|-------------------|--------|
| Модуль **Auth** (`src/Auth/Presentation/Http/AuthController.php`, `LoginFailureHandler`) | Ключи с префиксом, как в переводах | `error.invalid_credentials`, `error.validation_failed` |
| Остальные контроллеры (property, billing, rent, invitations) | Короткие коды **без** префикса `error.` | `invalid_payload`, `property_not_found`, `unauthenticated` |

Клиенту надёжнее проверять и то и другое (например нормализовать: если нет префикса — добавить `error.` для сравнения).

Сообщения для Auth зависят от заголовка **`Accept-Language`** (`ru` или `en`, иначе по умолчанию сервера — `ru`). Остальные модули сейчас отдают русские тексты в `message` без i18n.

### 1.3 JWT: отсутствует или невалидный токен

Для защищённых маршрутов при проблемах с JWT отвечает **Lexik JWT bundle**, формат **не** обязательно совпадает с `ApiJsonResponse`. Типично встречается тело вроде `401` с полем `message` от бандла. Клиент для «не авторизован» должен ориентироваться на **HTTP 401**, а не только на `success: false`.

### 1.4 Заголовки

- Защищённые запросы: `Authorization: Bearer <accessToken>`
- Локаль сообщений Auth: `Accept-Language: ru` или `en`

---

## 2. Auth

Файл: `src/Auth/Presentation/Http/AuthController.php`  
Вход по паролю: `json_login` в `config/packages/security.yaml` → успех: `src/Auth/Presentation/Security/LoginSuccessHandler.php`, ошибка: `LoginFailureHandler.php`.

| Метод | Путь | Auth | Тело запроса (JSON) | Успех `data` (основные поля) |
|-------|------|------|---------------------|------------------------------|
| POST | `/auth/register` | нет | `email`, `password` (≥8), `fullName` | `message`, `linkedPropertyMembersCount`, `user`, `token` — см. §2.1 |
| POST | `/auth/login` | нет | `email`, `password` | `user`, `token` — см. §2.1 (без `message` / без `linkedPropertyMembersCount`) |
| POST | `/auth/refresh` | нет | `refreshToken` | `user`, `token` |
| POST | `/auth/logout` | JWT | пустое тело | `message` |
| POST | `/auth/forgot-password` | нет | `email` | `message` |
| POST | `/auth/reset-password` | нет | `token`, `password` (≥8) | `message` |
| GET | `/auth/verify-email?token=` | нет | — | `message` |
| GET | `/auth/me` | JWT | — | `user` (без блока `token`) |

### 2.1 Структура `user` и `token`

Фабрика: `src/Auth/Application/Service/AuthResponseFactory.php`.

```json
"user": {
  "id": "<uuid>",
  "email": "...",
  "fullName": "...",
  "roles": ["ROLE_USER", "..."],
  "emailVerified": true
},
"token": {
  "accessToken": "<jwt>",
  "refreshToken": "<opaque string>",
  "tokenType": "Bearer",
  "expiresIn": 3600
}
```

- **Регистрация** дополнительно в `data`: `message`, `linkedPropertyMembersCount` (число).
- **Логин** через `LoginSuccessHandler`: в `data` только `user` + `token` (если клиент ожидает те же поля, что после регистрации — учитывать разницу).

### 2.2 Письма и ссылки

- Подтверждение email: ссылка ведёт на `GET /api/v1/auth/verify-email?token=...` (удобно открыть из браузера; ответ JSON).
- Сброс пароля: в письме ссылка на **клиентский** экран `{DEFAULT_URI}/reset-password?token=...` (см. `AuthEmailService`), затем приложение шлёт `POST /auth/reset-password` с `token` и новым `password`.

---

## 3. Health

Файл: `src/Health/Presentation/Http/HealthController.php`

| Метод | Путь | Auth | `data` при успехе |
|-------|------|------|-------------------|
| GET | `/health` | нет | `status`, `service`, `version` |

---

## 4. Объекты (properties)

Файл: `src/Property/Presentation/Http/PropertyController.php`

| Метод | Путь | Auth | `data` при успехе |
|-------|------|------|-------------------|
| GET | `/properties/types` | JWT | `items`: массив `{ code, label, notes }` |
| GET | `/properties` | JWT | `items`: массив **property** (может быть `[]`) |
| POST | `/properties` | JWT | `property`: объект |
| GET | `/properties/{propertyId}` | JWT | `property`, `currentMember` (объект участника или **null**) |
| GET | `/properties/{propertyId}/members` | JWT | `items`: массив участников |
| POST | `/properties/{propertyId}/members` | JWT | `member`: объект |
| POST | `/properties/{propertyId}/members/{memberId}/invite` | JWT | `invitation`: объект |

Тело `POST /properties` (JSON): `title`, `typeCode`, `address`, опционально `description`, `metadata` — см. `CreatePropertyRequestDto`.

Поле **property** (функция `propertyData` в том же файле): `id`, `title`, `typeCode`, `status`, `address`, `description`, `metadata`, `createdAt`, `updatedAt` (ISO 8601).

**member**: `id`, `role`, `status`, `fullName`, `email`, `phone`, `linkedUserId` (может быть `null`).

---

## 5. Приглашения

Файл: `src/Property/Presentation/Http/InvitationController.php`

| Метод | Путь | Auth | Тело | `data` при успехе |
|-------|------|------|------|-------------------|
| POST | `/invitations/claim` | JWT | `code` | `message`, `invitation`, `propertyId`, `propertyMemberId` |

---

## 6. Условия аренды (rent terms)

Файл: `src/Rent/Presentation/Http/RentTermsController.php`

| Метод | Путь | `data` при успехе |
|-------|------|-------------------|
| GET | `/properties/{propertyId}/rent-terms` | `rentTerms`: объект **или `null`** (если базовые условия ещё не заданы) |
| PUT | `/properties/{propertyId}/rent-terms` | `rentTerms`: объект |
| GET | `/properties/{propertyId}/members/{memberId}/rent-terms` | `baseRentTerms`, `memberRentTerms`, `effectiveRentTerms` — каждый **может быть `null`** |
| PUT | `/properties/{propertyId}/members/{memberId}/rent-terms` | `rentTerms`: объект |

Объект `rentTerms` (метод `rentTermsData`): `id`, `propertyMemberId`, `baseRentAmount`, `currency`, `billingDay`, `startsAt`, `endsAt`, `notes`, `status`, `createdAt`, `updatedAt`.

**Типичная ошибка клиента:** ожидать всегда непустой объект на `GET .../rent-terms` — сервер законно отдаёт `rentTerms: null`.

---

## 7. Биллинг (счётчики, параметры, тарифы)

Файл: `src/Billing/Presentation/Http/BillingController.php`  
Все пути ниже с префиксом `/properties/{propertyId}/...`, `{propertyId}` — UUID.

| Метод | Путь | `data` при успехе |
|-------|------|-------------------|
| GET | `.../meters` | `items`: массив счётчиков |
| POST | `.../meters` | `meter`: объект |
| POST | `.../meters/{meterId}/initial-reading` | `reading`: объект |
| GET | `.../meters/{meterId}/readings` | `items`: массив показаний |
| POST | `.../meters/{meterId}/readings` | `reading`: объект |
| GET | `.../billing/parameters` | `items`: массив параметров |
| POST | `.../billing/parameters` | `parameter`: объект |
| GET | `.../billing/parameters/{parameterId}/tariffs` | `items`: массив тарифов |
| POST | `.../billing/parameters/{parameterId}/tariffs` | `tariff`: объект |

- **meter** (`meterData`): `id`, `code`, `title`, `unit`: `{ code, label }`, `isActive`, `createdAt`, `updatedAt`.
- **reading** (`readingData`): `id`, `type`, `billingYear`, `billingMonth`, `value`, `comment`, `recordedByUserId`, `recordedAt`.
- **parameter** (`parameterData`): `id`, `code`, `title`, `category`, `categoryLabel`, `sourceType`, `sourceTypeLabel`, `meterId` (или `null`), `unit`, `isActive`, `createdAt`, `updatedAt`.
- **tariff** (`tariffData`): `id`, `pricingType`, `price`, `currency`, `effectiveFrom`, `effectiveTo`, `createdAt`.

Тела POST см. DTO в `src/Billing/Application/Dto/` (`CreateMeterRequestDto`: поле `unit` — строка-код из enum, например `m3`, `kwh`).

Права: часть операций только для «арендодателя» (landlord) — при отказе приходит `property_forbidden` (403).

---

## 8. Частые ошибки интеграции на клиенте

1. **Игнорирование обёртки `data`** — все успешные ответы через `ApiJsonResponse` кладут полезную нагрузку в `data`.
2. **Разные форматы `error.code`** — auth с префиксом `error.`, остальные без; парсер ошибок должен быть устойчивым.
3. **`rentTerms === null`** на GET — нормальная ситуация.
4. **Пустые списки** — `items: []` вместо отсутствия ключа.
5. **Логин vs регистрация** — в `data` разный набор полей (у регистрации есть `message`, `linkedPropertyMembersCount`).
6. **401 от JWT** — тело может быть не в формате `ApiJsonResponse`; ориентироваться на статус-код и обновление токена через `/auth/refresh`.

---

## 9. Соответствие файлов и HTTP-методов (шпаргалка)

| Область | Файл контроллера |
|---------|------------------|
| Auth | `src/Auth/Presentation/Http/AuthController.php` |
| Login success/failure | `src/Auth/Presentation/Security/LoginSuccessHandler.php`, `LoginFailureHandler.php` |
| Health | `src/Health/Presentation/Http/HealthController.php` |
| Properties | `src/Property/Presentation/Http/PropertyController.php` |
| Invitations | `src/Property/Presentation/Http/InvitationController.php` |
| Rent terms | `src/Rent/Presentation/Http/RentTermsController.php` |
| Billing | `src/Billing/Presentation/Http/BillingController.php` |
| Обёртка JSON | `src/Shared/Presentation/Http/ApiJsonResponse.php` |

Маршрутизация префикса `/api/v1`: `config/routes/api_v1.yaml`.
