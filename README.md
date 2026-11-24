# API Monitor – External API & Web Service Monitoring Dashboard

A clean, reliable, and secure web application that monitors the availability, response times, and JSON payload integrity of external APIs and web services.

Built with **PHP 8**, **PostgreSQL**, **JavaScript (ES6+)**, **HTML5**, **CSS3**, and **cURL**.

---

## 1. Project Structure

Each folder has one clear responsibility (using consistent `snake_case` naming):

```text
api monitor/
├── config/
│   └── database.php              # PostgreSQL PDO connection
├── database/
│   ├── schema.sql                # Tables, constraints, and indexes
│   ├── seed.sql                  # Initial 5 public APIs + HTTPBin test endpoints
│   └── setup.php                 # CLI script to initialize & seed the database
├── src/                          # Reusable PHP backend logic (no HTML)
│   ├── auth.php                  # Session login, logout, CSRF tokens
│   ├── helpers.php               # HTML escaping, ID validation, status badges
│   ├── security.php              # Approved host allowlist, SSRF check, form validation
│   └── service_repository.php    # Parameterized PostgreSQL queries
├── cli/
│   ├── check_services.php        # Background monitoring engine (Linux cron)
│   └── worker.php                # Continuous local dev worker (Windows/local)
├── views/                        # HTML templates (no SQL)
│   ├── partials/
│   │   └── sidebar.php
│   ├── admin/
│   │   ├── login.php
│   │   ├── manage_services.php
│   │   ├── add_service.php
│   │   ├── service_form.php
│   │   ├── service_table.php
│   │   └── confirm_delete.php
│   ├── dashboard.php
│   ├── history.php
│   └── public_status.php
└── public/                       # Web server document root
    ├── index.php                 # Live Operational Dashboard (login required)
    ├── history.php               # Service History & Chart.js (login required)
    ├── status.php                # Read-only Public Status Page (no login)
    ├── admin.php                 # Manage Services (login required)
    ├── add_service.php           # Add New Service (login required)
    ├── mock_api.php              # Local predictable test API
    ├── api/
    │   ├── dashboard_status.php  # Authenticated JSON API for index.php
    │   └── public_status.php     # Public read-only JSON API for status.php
    ├── css/
    │   └── style.css
    └── js/
        ├── sidebar.js
        ├── status_board.js       # 30s AJAX refresh for index.php & status.php
        └── history_chart.js      # Chart.js response-time line chart
```

---

## 2. Visual Status Indicators

| Indicator | Status | Criteria |
| :--- | :--- | :--- |
| 🟢 | **ONLINE** | Service returned the expected HTTP code, valid JSON (if applicable), and response time within `warning_threshold_ms`. |
| 🟡 | **SLOW** | Service responded correctly, but response time exceeded `warning_threshold_ms`. |
| 🟠 | **RATE_LIMITED** | Service returned HTTP `429` (rate limit reached). Logged distinctly so it is not misclassified as a server outage. |
| 🔴 | **OFFLINE** | Connection error, timeout, unexpected HTTP status code, or invalid/incomplete JSON payload. |
| ⚪ | **UNKNOWN / DISABLED** | Service has not been checked yet or monitoring is paused. |

---

## 3. Tested Public APIs

Pre-seeded via `database/seed.sql`:

| # | Service Name | Endpoint | Purpose |
| :- | :--- | :--- | :--- |
| **1** | **JSONPlaceholder** | `https://jsonplaceholder.typicode.com/posts/1` | Verifies HTTP 200 and valid JSON containing `id`. |
| **2** | **Open-Meteo Weather** | `https://api.open-meteo.com/v1/forecast?latitude=28.1&longitude=-15.4&current=temperature_2m` | Verifies HTTP 200 and valid JSON containing `current`. |
| **3** | **GitHub PHP-Src** | `https://api.github.com/repos/php/php-src` | Sends a custom `User-Agent` header and handles HTTP 429 rate limits cleanly. |
| **4** | **HTTPBin Endpoints** | `https://httpbin.org/status/200`, `/status/404`, `/delay/2`, `/delay/10` | Tests HTTP 200 success, 404 error, slow latency (`SLOW`), and connection timeout (`OFFLINE`). |
| **5** | **DummyJSON** | `https://dummyjson.com/products/1` | Verifies HTTP 200 and valid JSON containing product `id`. |

---

## 4. Security Measures

1. **Parameterized SQL Queries (`src/service_repository.php`)**: All queries use PDO with `PDO::ATTR_EMULATE_PREPARES => false`.
2. **Approved Destination Allowlist & SSRF Prevention (`src/security.php`)**: `isSafeUrl()` enforces `http`/`https`, checks `APPROVED_HOSTS` (with `localhost`/`127.0.0.1` permitted only for `/mock_api.php`), and verifies all resolved IPs via `gethostbynamel()` exclude private and reserved ranges. Enforced both on admin form submission and at runtime in `cli/check_services.php`.
3. **Safe Redirects & TLS (`cli/check_services.php`)**: `CURLOPT_FOLLOWLOCATION` is disabled and TLS certificate verification is enabled using the OS native CA store (`CURLSSLOPT_NATIVE_CA`).
4. **Access Control (`src/auth.php`)**: Dashboard (`/index.php`), History (`/history.php`), Admin (`/admin.php`, `/add_service.php`), and `/api/dashboard_status.php` require an authenticated session (`session_regenerate_id(true)` on login, `hash_equals()` credential check, CSRF tokens on all POST forms). Only `/status.php` and `/api/public_status.php` are public and read-only.
5. **Output Escaping (`src/helpers.php`)**: All dynamic HTML is escaped with `escapeHtml()`.

---

## 5. Installation & Quick Start

### Prerequisites
- **PHP 8.0+** with `curl`, `pdo_pgsql`, and `session` extensions enabled.
- **PostgreSQL 14+** running locally (default port `5432`).

### Step 1: Initialize & Seed the Database
```bash
php database/setup.php
```
*(Optional environment variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `ADMIN_USER`, `ADMIN_PASS`.)*

### Step 2: Start the Web Server
```bash
php -S localhost:8000 -t public
```

### Step 3: Run the Monitoring Engine
```bash
# Single check run:
php cli/check_services.php

# Or continuous local worker (checks due services every 10s):
php cli/worker.php
```

#### Linux Cron Configuration
```cron
* * * * * /usr/bin/php /path/to/project/cli/check_services.php >> /var/log/api_monitor.log 2>&1
```

---

## 6. URLs & Default Credentials

- **Public Status Page (No Login)**: `http://localhost:8000/status.php`
- **Operational Dashboard (Login Required)**: `http://localhost:8000/index.php`
- **Manage Services (Login Required)**: `http://localhost:8000/admin.php`
- **Add Service (Login Required)**: `http://localhost:8000/add_service.php`
- **Local Mock API**: `http://localhost:8000/mock_api.php`
- **Default Admin Login** *(override via `ADMIN_USER` / `ADMIN_PASS`)*: `admin` / `admin123`
