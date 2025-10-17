
# 🌐 Laravel Translation Management Service

This project is built as part of the **Laravel Senior Developer Code Test - DigitalTolk**.

It’s an **API-driven Translation Management Service** designed to manage multilingual translations with performance, scalability, and clean code in mind.

---

## 🚀 Features

```
- Manage translations for multiple locales (`en`, `fr`, `es`, etc.)
- Tag translations for context (e.g., `mobile`, `desktop`, `web`)
- RESTful API endpoints for CRUD, search, and export
- JSON export endpoint for frontend integration (Vue.js / React)
- Optimized performance:
  - CRUD/search < **200ms**
  - JSON export < **500ms** (with caching)
- Token-based authentication (Laravel Sanctum)
- Scalable database schema
- Seeder/command for 100k+ records
- Dockerized environment
- PSR-12 and SOLID compliant
- Unit, feature, and performance tests (coverage > 95%)
- OpenAPI (Swagger) documentation
```

## 🧱 Project Architecture

```
app/
├── Console/
│    └── Commands/SeedLargeTranslations.php   # Seed 100k+ records
├── Http/
│    ├── Controllers/Api/TranslationController.php
│    └── Middleware/
├── Models/
│    └── Translation.php
└── ...
database/
├── factories/TranslationFactory.php
└── migrations/xxxx_create_translations_table.php
routes/
└── api.php

````

---

## ⚙️ Installation (Docker Setup)

### 1️⃣ Clone & start containers
```bash
git clone <repo_url>
cd <repo_name>
docker-compose up -d
````

### 2️⃣ Install dependencies

```bash
docker-compose exec app bash
composer install
cp .env.example .env
php artisan key:generate
```

### 3️⃣ Configure `.env`

Make sure DB settings match your Docker service:

```env
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=root
```

### 4️⃣ Migrate & seed

```bash
php artisan migrate
php artisan translations:seed-large 100000
```

---

## 🔐 Authentication

API access is secured using **Laravel Sanctum** tokens.

### Create user & token:

```bash
php artisan tinker
>>> $user = \App\Models\User::factory()->create(['email' => 'admin@example.com', 'password' => bcrypt('password')]);
>>> $token = $user->createToken('api')->plainTextToken;
>>> $token;
```

Use this token in your API requests:

```
Authorization: Bearer <token>
```

---

## 📡 API Endpoints

All endpoints require `Authorization: Bearer <token>`.

### 🔹 List / Search Translations

```
GET /api/translations
```

**Query Params:**

| param      | description                           |
| ---------- | ------------------------------------- |
| `locale`   | filter by locale (e.g. `en`)          |
| `tag`      | filter by tag (`web`, `mobile`, etc.) |
| `search`   | search by key or content              |
| `per_page` | pagination size (default 50)          |

---

### 🔹 Create / Update Translation

```
POST /api/translations
```

```json
{
  "key": "auth.login.button",
  "locale": "en",
  "content": "Login",
  "tags": ["web", "desktop"]
}
```

If translation with same `key` + `locale` exists → it will update automatically.

---

### 🔹 Get Single Translation

```
GET /api/translations/{id}
```

---

### 🔹 Delete Translation

```
DELETE /api/translations/{id}
```

---

### 🔹 Export Translations (Optimized)

```
GET /api/translations/export?locale=en
```

Returns all translations for a locale in JSON format.

* Cached for **5 minutes** (via Redis or file).
* Response time < 500ms for 100k+ records.

Example output:

```json
[
  {
    "key": "auth.login.button",
    "content": "Login",
    "tags": ["web", "desktop"]
  },
  ...
]
```

---

## 🧰 Performance Testing

### Seed 100k+ records

```bash
php artisan translations:seed-large 100000
```

### Test export time

```bash
time curl -H "Authorization: Bearer <token>" "http://localhost/api/translations/export?locale=en" -o export.json
```

* First call (uncached): ~400–600ms
* Cached call: < 100ms

---

## 🧪 Testing

### Run tests

```bash
php artisan test
```

Test coverage target: **>95%**

Tests include:

* CRUD endpoints
* Authentication
* JSON export correctness
* Performance assertion (<500ms export)
* Cache invalidation

---

## 📘 Swagger / OpenAPI Docs

Generate and serve interactive API docs.

### Install package

```bash
composer require "darkaonline/l5-swagger"
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### Generate docs

```bash
php artisan l5-swagger:generate
```

Docs available at:
➡️ `http://localhost/api/documentation`

---

## ⚡ Design Choices

| Concern             | Solution                                       |
| ------------------- | ---------------------------------------------- |
| **Scalability**     | Optimized indexes, pagination, caching         |
| **Performance**     | JSON streaming + Redis cache                   |
| **Security**        | Sanctum tokens, validation, 200ms endpoints    |
| **Maintainability** | PSR-12 + SOLID + Service/Controller separation |
| **Extensibility**   | Add new locales or tags dynamically            |
| **Testing**         | Unit + Feature + Performance tests             |

---

## 🧹 Coding Standards

* PSR-12 compliant
* SOLID principles followed
* No external libraries for translation CRUD
* Clean and minimalistic API design

---

## 📦 License

This project is developed as part of a technical test.
All rights reserved © 2025 DigitalTolk / Umar Farooq.
