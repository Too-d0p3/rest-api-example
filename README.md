# Projekt API pro Správu Uživatelů a Článků

Toto je ukázkový projekt REST API vytvořené v PHP 8.2 a Symfony frameworku. API umožňuje správu uživatelů a článků s jednoduchou logikou rolí.

## Obsah

1.  [Použité Technologie](#pouzite-technologie)
2.  [Architektura Projektu](#architektura-projektu)
3.  [Požadavky](#pozadavky)
4.  [Instalace a Konfigurace](#instalace-a-konfigurace)
    *   [Klonování Repozitáře](#klonovani-repozitare)
    *   [Instalace Závislostí](#instalace-zavislosti)
    *   [Konfigurace Prostředí (.env)](#konfigurace-prostredi-env)
    *   [Generování APP_SECRET](#generovani-app_secret)
    *   [Nastavení Databáze (SQLite)](#nastaveni-databaze-sqlite)
    *   [Nastavení JWT (JSON Web Tokens)](#nastaveni-jwt-json-web-tokens)
    *   [Spuštění Migrací](#spusteni-migraci)
5.  [Spuštění Aplikace](#spusteni-aplikace)
6.  [Spuštění Testů](#spusteni-testu)
7.  [Docker](#docker)
8.  [Popis Rolí](#popis-roli)
9.  [API Endpoints a Příklady Volání](#api-endpoints-a-priklady-volani)
    *   [Autentizace](#autentizace)
    *   [Správa Uživatelů (Admin)](#sprava-uzivatelu-admin)
    *   [Správa Článků](#sprava-clanku)

## Použité Technologie

*   **PHP 8.2**
*   **Symfony 6.x**
*   **Doctrine ORM** (pro práci s databází)
*   **SQLite** (jako výchozí databáze pro jednoduchost)
*   **LexikJWTAuthenticationBundle** (pro autentizaci pomocí JSON Web Tokens)
*   **PHPUnit** (pro funkční a unit testy)
*   **Docker & Docker Compose** (pro snadné spuštění a alternativní databázová prostředí)

## Architektura Projektu

Projekt se snaží dodržovat principy čisté architektury a je rozdělen do následujících hlavních vrstev:

*   **Presentation Layer (`app/src/Presentation/`)**: Obsahuje API kontrolery, které přijímají HTTP požadavky, delegují práci na aplikační vrstvu a formátují odpovědi (včetně chybových odpovědí dle RFC 7807).
*   **Domain Layer (`app/src/Domain/`)**: Jádro aplikace obsahující entity, doménové DTOs, repozitáře (abstrakce nad databázovým úložištěm) a doménové výjimky.
*   **Shared Kernel (`app/src/Shared/`)**: Komponenty sdílené napříč vrstvami, jako jsou obecné DTOs, validační nástroje, bezpečnostní pomocníci nebo sdílené výjimky.

Používají se DTOs pro přenos dat mezi vrstvami a pro validaci vstupních dat.

## Požadavky

*   PHP 8.2 nebo vyšší
*   Composer
*   Git
*   Symfony CLI (doporučeno pro lokální vývoj)
*   Docker a Docker Compose (pokud chcete používat databázi v Dockeru, např. PostgreSQL)
*   SQLite3 PHP extension

## Instalace a Konfigurace

### Klonování Repozitáře

```bash
git clone <URL_VASEHO_REPOZITARE>
cd <NAZEV_SLOZKY_PROJEKTU>
```

### Instalace Závislostí

```bash
composer install
```

### Konfigurace Prostředí (.env)

1.  Zkopírujte ukázkový soubor `.env.example` do nového souboru `.env`:
    ```bash
    cp .env.example .env
    ```
    (Na Windows použijte `copy .env.example .env`)

2.  Otevřete soubor `.env` a upravte proměnné podle potřeby.

### Generování APP_SECRET

Proměnná `APP_SECRET` v souboru `.env` musí být unikátní a tajná. Pokud tam je placeholder, vygenerujte si nový:

*   Pomocí PHP:
    ```bash
    php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
    ```
*   Nebo pomocí OpenSSL:
    ```bash
    openssl rand -hex 32
    ```
    Vygenerovaný řetězec vložte do `APP_SECRET` ve vašem `.env` souboru (nebo lépe v `.env.local`, který není commitován).

### Nastavení Databáze (SQLite)

Výchozí konfigurace v `.env.example` (a tedy i ve vašem `.env` po zkopírování) používá SQLite. Databázový soubor bude vytvořen v `var/data_dev.db`.

```env
# Váš .env soubor
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_dev.db"
```
Ujistěte se, že adresář `var/` je zapisovatelný PHP procesem.

### Nastavení JWT (JSON Web Tokens)

Pro autentizaci se používají JWT. Je potřeba vygenerovat privátní a veřejný klíč a nastavit přístupovou frázi (passphrase).

1.  **JWT_PASSPHRASE**: V souboru `.env` nastavte silnou přístupovou frázi pro `JWT_PASSPHRASE`. Tuto frázi si zapamatujte, budete ji potřebovat pro generování klíčů.
    ```env
    # Váš .env soubor
    JWT_PASSPHRASE=VaseSilnaUnikatniFrazeZde!
    ```

2.  **Generování klíčů**: Spusťte následující příkaz. Budete dotázáni na výše uvedenou `JWT_PASSPHRASE`. Klíče se standardně ukládají do `config/jwt/`.
    ```bash
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
    ```
    Ujistěte se, že adresář `config/jwt/` je zapisovatelný.

    Proměnné `JWT_SECRET_KEY` a `JWT_PUBLIC_KEY` v `.env` již odkazují na správné cesty k těmto souborům.

### Spuštění Migrací

Po nastavení databáze je potřeba vytvořit databázové schéma spuštěním migrací:

```bash
php bin/console doctrine:migrations:migrate
```
Při prvním spuštění budete dotázáni na potvrzení.

## Spuštění Testů

Funkční testy ověřují klíčové funkce API a logiku rolí. Spustíte je příkazem:

```bash
php bin/phpunit
```
Nebo pokud nemáte PHPUnit globálně:
```bash
./vendor/bin/phpunit
```
Testy používají vlastní SQLite databázi (typicky `var/data_test.db`), která se automaticky vytváří a maže.

## Docker

Projekt obsahuje `docker-compose.yml` a `Dockerfile`, které primárně slouží pro spuštění alternativní databáze (např. PostgreSQL) nebo pro kompletní kontejnerizaci aplikace v budoucnu.

Pokud chcete použít PostgreSQL místo SQLite:
1.  Ujistěte se, že máte Docker a Docker Compose nainstalované.
2.  V souboru `.env` změňte `DATABASE_URL` na PostgreSQL variantu (příklad je v `.env.example`):
    ```env
    DATABASE_URL="postgresql://app:!ChangeMe!@db:5432/app?serverVersion=16&charset=utf8"
    ```
    Nahraďte `app`, `!ChangeMe!` a `app` za skutečné uživatelské jméno, heslo a název databáze, které odpovídají konfiguraci v `docker-compose.yml` (služba `db`).
3.  Spusťte Docker kontejner s databází:
    ```bash
    docker-compose up -d db
    ```
    (Pokud chcete spustit všechny služby definované v `docker-compose.yml`, použijte `docker-compose up -d`)
4.  Následně spusťte migrace atd.

## Popis Rolí

*   **`admin`**: Může spravovat uživatele (vytvářet, číst, upravovat, mazat) a články (vytvářet, číst, upravovat, mazat jakékoli).
*   **`author`**: Může vytvářet nové články. Může číst, upravovat a mazat pouze své vlastní články. Nemůže spravovat uživatele.
*   **`reader`**: Může pouze číst seznam všech článků a detaily jednotlivých článků. Nemůže vytvářet, upravovat ani mazat články ani uživatele.

## API Endpoints a Příklady Volání

Následující příklady používají `curl` a předpokládají, že API běží na `http://localhost:8080`.
Pro operace vyžadující autentizaci je potřeba získat JWT token a posílat ho v `Authorization: Bearer <TOKEN>` hlavičce.

---

### Autentizace

**1. Registrace nového uživatele**
*   **Endpoint**: `POST /auth/register`
*   **Popis**: Vytvoří nového uživatele.
*   **Příklad (registrace autora)**:
    ```bash
    curl -X POST http://localhost:8080/auth/register \
    -H "Content-Type: application/json" \
    -d '{
        "email": "author@example.com",
        "name": "Test Author",
        "password": "password123",
        "role": "author"
    }'
    ```
*   **Úspěšná odpověď (201 Created)**:
    ```json
    {
        "id": "a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11",
        "email": "author@example.com",
        "name": "Test Author",
        "roles": ["author"]
    }
    ```

**2. Přihlášení uživatele**
*   **Endpoint**: `POST /auth/login`
*   **Popis**: Přihlásí uživatele a vrátí JWT token.
*   **Příklad**:
    ```bash
    curl -X POST http://localhost:8080/auth/login \
    -H "Content-Type: application/json" \
    -d '{
        "email": "author@example.com",
        "password": "password123"
    }'
    ```
*   **Úspěšná odpověď (200 OK)**:
    ```json
    {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOj..."
    }
    ```
    (Získaný `token` uložte pro další autentizované požadavky.)

**3. Získání informací o přihlášeném uživateli**
*   **Endpoint**: `GET /auth/me`
*   **Popis**: Vrátí data aktuálně přihlášeného uživatele (na základě tokenu).
*   **Vyžaduje**: Platný JWT token.
*   **Příklad**: (Nahraďte `<TOKEN>` za váš skutečný token)
    ```bash
    curl -X GET http://localhost:8080/auth/me \
    -H "Authorization: Bearer <TOKEN>"
    ```
*   **Úspěšná odpověď (200 OK)**:
    ```json
    {
        "id": "a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11",
        "email": "author@example.com",
        "name": "Test Author",
        "roles": ["author"]
    }
    ```

---

### Správa Uživatelů (Admin)

Všechny následující endpointy vyžadují roli `admin`.

**1. Získání seznamu všech uživatelů**
*   **Endpoint**: `GET /users`
*   **Příklad**:
    ```bash
    curl -X GET http://localhost:8080/users \
    -H "Authorization: Bearer <ADMIN_TOKEN>"
    ```
*   **Úspěšná odpověď (200 OK)**:
    ```json
    [
        {
            "id": "a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11",
            "email": "admin@example.com",
            "name": "Admin User",
            "role": "admin"
        },
        {
            "id": "b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a12",
            "email": "author@example.com",
            "name": "Test Author",
            "role": "author"
        }
    ]
    ```

**2. Vytvoření nového uživatele (Admin)**
*   **Endpoint**: `POST /users`
*   **Příklad**:
    ```bash
    curl -X POST http://localhost:8080/users \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <ADMIN_TOKEN>" \
    -d '{
        "email": "newreader@example.com",
        "name": "New Reader",
        "password": "securePassword123",
        "role": "reader"
    }'
    ```
*   **Úspěšná odpověď (201 Created)**: (Obsahuje data nově vytvořeného uživatele)

**3. Získání detailu konkrétního uživatele**
*   **Endpoint**: `GET /users/{id}`
*   **Příklad**: (Nahraďte `{id}` za ID uživatele)
    ```bash
    curl -X GET http://localhost:8080/users/b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a12 \
    -H "Authorization: Bearer <ADMIN_TOKEN>"
    ```
*   **Úspěšná odpověď (200 OK)**: (Obsahuje data konkrétního uživatele)

**4. Úprava uživatele**
*   **Endpoint**: `PUT /users/{id}`
*   **Příklad**: (Nahraďte `{id}` za ID uživatele)
    ```bash
    curl -X PUT http://localhost:8080/users/b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a12 \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <ADMIN_TOKEN>" \
    -d '{
        "email": "updated.author@example.com",
        "name": "Updated Test Author",
        "role": "author"
    }'
    ```
*   **Úspěšná odpověď (200 OK)**: (Obsahuje aktualizovaná data uživatele)

**5. Smazání uživatele**
*   **Endpoint**: `DELETE /users/{id}`
*   **Příklad**: (Nahraďte `{id}` za ID uživatele)
    ```bash
    curl -X DELETE http://localhost:8080/users/b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a12 \
    -H "Authorization: Bearer <ADMIN_TOKEN>"
    ```
*   **Úspěšná odpověď (204 No Content)**

---

### Správa Článků

**1. Získání seznamu všech článků**
*   **Endpoint**: `GET /articles`
*   **Vyžaduje**: Platný JWT token (jakákoli role).
*   **Příklad**:
    ```bash
    curl -X GET http://localhost:8080/articles \
    -H "Authorization: Bearer <USER_TOKEN>"
    ```
*   **Úspěšná odpověď (200 OK)**: (Seznam článků)
    ```json
    [
        {
            "id": "c2eebc99-9c0b-4ef8-bb6d-6bb9bd380a13",
            "title": "My First Article",
            "content": "This is the content of my first article.",
            "author": {
                "id": "b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a12",
                "email": "author@example.com",
                "name": "Test Author"
            },
            "created_at": "2023-10-27T10:00:00+00:00",
            "updated_at": "2023-10-27T10:00:00+00:00"
        }
    ]
    ```

**2. Vytvoření nového článku**
*   **Endpoint**: `POST /articles`
*   **Vyžaduje**: Role `author` nebo `admin`.
*   **Příklad (autor vytváří článek)**:
    ```bash
    curl -X POST http://localhost:8080/articles \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <AUTHOR_OR_ADMIN_TOKEN>" \
    -d '{
        "title": "My New Awesome Article",
        "content": "Detailed content of the new awesome article which is long enough."
    }'
    ```
*   **Úspěšná odpověď (201 Created)**: (Obsahuje data nově vytvořeného článku)

**3. Získání detailu konkrétního článku**
*   **Endpoint**: `GET /articles/{id}`
*   **Vyžaduje**: Platný JWT token (jakákoli role).
*   **Příklad**: (Nahraďte `{id}` za ID článku)
    ```bash
    curl -X GET http://localhost:8080/articles/c2eebc99-9c0b-4ef8-bb6d-6bb9bd380a13 \
    -H "Authorization: Bearer <USER_TOKEN>"
    ```
*   **Úspěšná odpověď (200 OK)**: (Obsahuje data konkrétního článku)

**4. Úprava článku**
*   **Endpoint**: `PUT /articles/{id}`
*   **Vyžaduje**: Role `admin`, nebo role `author` pokud je vlastníkem článku.
*   **Příklad (autor upravuje svůj článek)**: (Nahraďte `{id}` za ID článku)
    ```bash
    curl -X PUT http://localhost:8080/articles/c2eebc99-9c0b-4ef8-bb6d-6bb9bd380a13 \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <OWNER_AUTHOR_OR_ADMIN_TOKEN>" \
    -d '{
        "title": "My Updated Article Title",
        "content": "The content has been updated and is still sufficient."
    }'
    ```
*   **Úspěšná odpověď (200 OK)**: (Obsahuje aktualizovaná data článku)

**5. Smazání článku**
*   **Endpoint**: `DELETE /articles/{id}`
*   **Vyžaduje**: Role `admin`, nebo role `author` pokud je vlastníkem článku.
*   **Příklad (autor maže svůj článek)**: (Nahraďte `{id}` za ID článku)
    ```bash
    curl -X DELETE http://localhost:8080/articles/c2eebc99-9c0b-4ef8-bb6d-6bb9bd380a13 \
    -H "Authorization: Bearer <OWNER_AUTHOR_OR_ADMIN_TOKEN>"
    ```
*   **Úspěšná odpověď (204 No Content)**

---

### Příklad chybové odpovědi (RFC 7807)

Pokud například požadavek selže kvůli validaci:
*   **Odpověď (422 Unprocessable Entity)**:
    ```json
    {
        "type": "/errors/validation-failed",
        "title": "Validation Failed",
        "status": 422,
        "detail": "Validation failed",
        "invalid-params": [
            {
                "name": "title",
                "reason": "This value should not be blank."
            },
            {
                "name": "content",
                "reason": "This value is too short. It should have 10 characters or more."
            }
        ]
    }
    ```
Nebo pokud uživatel nemá oprávnění:
*   **Odpověď (403 Forbidden)**:
    ```json
    {
        "type": "/errors/forbidden",
        "title": "Forbidden",
        "status": 403,
        "detail": "You do not have permission to access this resource."
    }
    ```
---

## 👤 Autor

**Ondřej Nevřela**  
[🌐 ondrejnevrela.cz](https://ondrejnevrela.cz/)  
[💼 LinkedIn](https://www.linkedin.com/in/ondrej-nevrela/)


---