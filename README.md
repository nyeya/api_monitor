# API Monitor &mdash; External API &amp; Web Service Monitoring Dashboard

[![PHP](https://img.shields.io/badge/PHP-8.0%2B%20(cli%20%7C%20web)-777BB4?logo=php&logoColor=white)](#1-brief-technical-documentation)
[![Database](https://img.shields.io/badge/PostgreSQL-14%2B-4169E1?logo=postgresql&logoColor=white)](#database-schema)
[![Web Server](https://img.shields.io/badge/Web%20Server-Apache%202.4%20%7C%20PHP%20Built--in-D22128?logo=apache&logoColor=white)](#step-5-web-server-configuration)
[![Frontend](https://img.shields.io/badge/Frontend-HTML5%20%7C%20CSS3%20%7C%20ES6%2B%20%7C%20Chart.js-F7DF1E?logo=javascript&logoColor=black)](#1-brief-technical-documentation)

**API Monitor** is a lightweight, secure web application for monitoring the availability, response times, HTTP status codes, and JSON payload integrity of external APIs and web services. It combines a CLI background monitoring engine with an auto-refreshing operational dashboard, a public read-only status page, PostgreSQL-backed monitoring history with Chart.js latency graphs, and a protected administration panel.

---

## Table of Contents

- [1. Brief Technical Documentation](#1-brief-technical-documentation)
  - [Environment &amp; System Specifications](#environment--system-specifications)
  - [Directory Architecture](#directory-architecture)
  - [Database Schema](#database-schema)
  - [Monitoring Engine &amp; Status Classification](#monitoring-engine--status-classification)
  - [Web Routes &amp; JSON Endpoints](#web-routes--json-endpoints)
  - [Security Controls](#security-controls)
- [2. Installation &amp; Configuration Instructions](#2-installation--configuration-instructions)
  - [Step 1: Install Prerequisites](#step-1-install-prerequisites)
  - [Step 2: Prepare the Codebase](#step-2-prepare-the-codebase)
  - [Step 3: Database &amp; Environment Configuration](#step-3-database--environment-configuration)
  - [Step 4: Database Creation &amp; Seeding](#step-4-database-creation--seeding)
  - [Step 5: Web Server Configuration](#step-5-web-server-configuration)
  - [Step 6: Background Monitoring Scheduler](#step-6-background-monitoring-scheduler)
  - [Troubleshooting &amp; Common Pitfalls](#troubleshooting--common-pitfalls)
- [3. List of Tested Public APIs](#3-list-of-tested-public-apis)
- [4. Screenshots of the Monitoring Dashboard](#4-screenshots-of-the-monitoring-dashboard)
- [5. Access URLs &amp; Default Credentials](#5-access-urls--default-credentials)

---

## 1. Brief Technical Documentation

### Environment &amp; System Specifications

| Component | Requirement | Role in System |
| :--- | :--- | :--- |
| **PHP Runtime** | **PHP 8.0+** (CLI &amp; Web/Apache) | Backend logic, PDO database layer, cURL HTTP checks (`pdo`, `pdo_pgsql`, `curl`, `session`, `mbstring`) |
| **Database Engine** | **PostgreSQL 14+** | Stores service configurations (`services`) and time-series check results (`monitoring_history`) |
| **Web Server** | **Apache 2.4+** or **PHP Built-in Server** | Serves `public/` as `DocumentRoot` |
| **Frontend** | **HTML5, CSS3, JavaScript (ES6+), Chart.js 4.4.7** | 30-second AJAX auto-refresh status board and per-service response-time line charts |
| **Scheduler** | **Linux `cron`** or **`cli/worker.php`** | Executes `cli/check_services.php` to poll due services |

### Directory Architecture

```text
api monitor/
├── config/
│   └── database.php              # Singleton PostgreSQL PDO connection
├── database/
│   ├── schema.sql                # PostgreSQL database creation script (tables, constraints, indexes)
│   ├── seed.sql                  # Initial 8 services (5 public APIs + 3 HTTPBin test endpoints)
│   └── setup.php                 # One-command CLI database reset, schema load & seeder
├── src/                          # Reusable PHP backend logic (no HTML output)
│   ├── auth.php                  # Session login/logout, credential check, CSRF tokens
│   ├── helpers.php               # HTML escaping, ID validation, form helpers, status badges
│   ├── security.php              # APPROVED_HOSTS allowlist, SSRF checks, input validation
│   └── service_repository.php    # Parameterized PostgreSQL queries & 24h/7d uptime stats
├── cli/
│   ├── check_services.php        # Background monitoring engine (flock-protected, cron-ready)
│   └── worker.php                # Continuous local dev worker (polls every 10 seconds)
├── views/                        # Server-rendered HTML templates (no SQL)
│   ├── partials/sidebar.php      # Shared navigation sidebar
│   ├── admin/                    # login.php, manage_services.php, add_service.php, service_form.php, service_table.php, confirm_delete.php
│   ├── dashboard.php             # Authenticated operational dashboard view
│   ├── history.php               # Per-service availability stats, Chart.js graph & check log
│   └── public_status.php         # Read-only public status page view
└── public/                       # Web server DocumentRoot
    ├── index.php                 # Operational Dashboard (login required)
    ├── history.php               # Service History & Charts (login required)
    ├── status.php                # Public Status Page (no login)
    ├── admin.php                 # Manage/Edit/Toggle/Delete Services (login required)
    ├── add_service.php           # Register New Service (login required)
    ├── mock_api.php              # Local predictable test API (status, delay, timeout)
    ├── api/
    │   ├── dashboard_status.php  # Authenticated JSON feed for index.php
    │   └── public_status.php     # Public read-only JSON feed for status.php
    ├── css/style.css             # Application stylesheet
    └── js/
        ├── sidebar.js            # Sidebar toggle
        ├── modal.js              # Confirmation/UI modal handler
        ├── status_board.js       # 30s AJAX auto-refresh for index.php & status.php
        └── history_chart.js      # Chart.js response-time chart
```

### Database Schema

Defined in `database/schema.sql`:

- **`services`**: Stores monitored endpoints (`id`, `name`, `description`, `url`, `http_method`, `expected_http_code`, `timeout_seconds`, `warning_threshold_ms`, `check_interval_seconds`, `enabled`, `created_at`).
  - Constraints: `http_method IN ('GET', 'HEAD')`, `timeout_seconds BETWEEN 1 AND 120`, `warning_threshold_ms > 0`, `check_interval_seconds >= 5`.
  - Index: `idx_services_enabled` on `enabled`.
- **`monitoring_history`**: Stores every check result (`id`, `service_id`, `checked_at`, `http_code`, `response_time_ms`, `status`, `error_message`) with `service_id REFERENCES services(id) ON DELETE CASCADE`.
  - Indexes: `idx_history_service_checked` on `(service_id, checked_at DESC)` and `idx_history_checked_at` on `(checked_at DESC)` for fast latest-status queries and 24h/7d availability calculations.

### Monitoring Engine &amp; Status Classification

`cli/check_services.php` runs each monitoring cycle in five steps:

1. **Concurrency Lock**: Acquires a non-blocking exclusive lock (`api_monitor.lock` via `flock()`) to prevent overlapping runs.
2. **Due Service Query**: Selects enabled services whose last check is older than `check_interval_seconds` (or never checked).
3. **Runtime URL Check**: Re-validates each destination URL via `isSafeUrl()`.
4. **HTTP Request**: Executes a cURL `GET`/`HEAD` probe with `CURLOPT_FOLLOWLOCATION => false`, OS native TLS CA verification (`CURLSSLOPT_NATIVE_CA`), and a custom `User-Agent` (`ApiMonitor/1.0`).
5. **Evaluation &amp; Storage**: Evaluates HTTP status code, JSON validity (and required keys for seeded JSON APIs), and latency against `warning_threshold_ms`, then inserts a record into `monitoring_history`.

| Indicator | Status | Classification Criteria |
| :--- | :--- | :--- |
| 🟢 | **ONLINE** | Expected HTTP code returned, valid JSON (if applicable), and response time &le; `warning_threshold_ms`. |
| 🟡 | **SLOW** | Valid response, but `response_time_ms` exceeded `warning_threshold_ms`. |
| 🟠 | **RATE_LIMITED** | HTTP `429` returned (when not expected); tracked distinctly from outages. |
| 🔴 | **OFFLINE** | Connection error, timeout, unexpected HTTP code, invalid/incomplete JSON, or blocked URL. |
| ⚪ | **UNKNOWN / DISABLED** | Not checked yet (`UNKNOWN`) or monitoring disabled (`DISABLED`). |

### Web Routes &amp; JSON Endpoints

| Path | Access | Description |
| :--- | :--- | :--- |
| `/status.php` | Public (Read-only) | Live public status page for enabled services with 30s auto-refresh. |
| `/api/public_status.php` | Public (Read-only) | JSON API powering `/status.php` (omits internal fields and disabled services). |
| `/index.php` | Admin Login Required | Live operational dashboard showing all services, HTTP codes, uptime, and history links. |
| `/api/dashboard_status.php` | Admin Login Required | JSON API powering `/index.php` (returns HTTP `401` if unauthenticated). |
| `/history.php?id=<id>` | Admin Login Required | All-time, 24h, and 7d availability stats, Chart.js latency chart, and last 50 check logs. |
| `/admin.php` | Admin Login Required | Edit, enable/disable, or delete services (CSRF-protected POST actions). |
| `/add_service.php` | Admin Login Required | Register new monitored services (CSRF-protected). |
| `/mock_api.php` | Local Testing | Predictable mock endpoint for demonstrating HTTP errors, delays, and timeouts. |

### Security Controls

1. **Parameterized SQL (`src/service_repository.php`)**: PDO prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`.
2. **Allowlist &amp; SSRF Prevention (`src/security.php`)**: `isSafeUrl()` enforces `http`/`https`, restricts external hosts to `APPROVED_HOSTS`, allows `localhost`/`127.0.0.1` strictly for `/mock_api.php`, and verifies resolved IPv4 addresses via `gethostbynamel()` exclude private/reserved ranges.
3. **Safe cURL Execution (`cli/check_services.php`)**: Redirects disabled (`CURLOPT_FOLLOWLOCATION => false`) and TLS verification enabled via the OS CA store.
4. **Authentication &amp; CSRF (`src/auth.php`)**: Session authentication with `hash_equals()`, `session_regenerate_id(true)` on login, and per-session CSRF tokens on all admin POST forms.
5. **Output Escaping (`src/helpers.php`)**: All dynamic HTML output is escaped with `escapeHtml()`.

---

## 2. Installation &amp; Configuration Instructions

### Step 1: Install Prerequisites

**Ubuntu / Debian (Linux):**
```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-pgsql php-curl php-mbstring postgresql postgresql-contrib git
sudo systemctl enable --now apache2 postgresql
```

**Windows (Local Development):**
- Install **PHP 8.0+** and enable `extension=curl`, `extension=pdo_pgsql`, and `extension=mbstring` in `php.ini`.
- Install **PostgreSQL 14+** and verify the PostgreSQL service is running on port `5432`.

### Step 2: Prepare the Codebase

```bash
git clone <repository-url> api-monitor
cd api-monitor
```

### Step 3: Database &amp; Environment Configuration

All database and admin settings are read from environment variables with sensible local defaults:

| Variable | Default | Purpose |
| :--- | :--- | :--- |
| `DB_HOST` | `localhost` | PostgreSQL host |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_NAME` | `api_monitor` | Database name |
| `DB_USER` | `postgres` | PostgreSQL user |
| `DB_PASS` | `root` | PostgreSQL password |
| `ADMIN_USER` | `admin` | Admin login username |
| `ADMIN_PASS` | `admin123` | Admin login password |
| `CURL_INSECURE` | *(unset)* | Set to `1` only for local dev if OS CA certificates are unavailable |

**Linux / macOS (Bash):**
```bash
export DB_HOST=localhost DB_PORT=5432 DB_NAME=api_monitor DB_USER=postgres DB_PASS=root
export ADMIN_USER=admin ADMIN_PASS=admin123
```

**Windows (PowerShell):**
```powershell
$env:DB_HOST="localhost"; $env:DB_PORT="5432"; $env:DB_NAME="api_monitor"; $env:DB_USER="postgres"; $env:DB_PASS="root"
$env:ADMIN_USER="admin"; $env:ADMIN_PASS="admin123"
```

### Step 4: Database Creation &amp; Seeding

Run the one-command setup script (recreates `api_monitor`, applies `database/schema.sql`, and loads `database/seed.sql`):
```bash
php database/setup.php
```

Alternatively, apply the PostgreSQL scripts manually with `psql`:
```bash
psql -U postgres -c "CREATE DATABASE api_monitor;"
psql -U postgres -d api_monitor -f database/schema.sql
psql -U postgres -d api_monitor -f database/seed.sql
```

### Step 5: Web Server Configuration

#### Option A: PHP Built-in Server (Local Development)
```bash
php -S localhost:8000 -t public
```

#### Option B: Apache 2.4 VirtualHost (Linux)
Create `/etc/apache2/sites-available/api-monitor.conf`:
```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/api-monitor/public

    <Directory /var/www/html/api-monitor/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SetEnv DB_HOST localhost
    SetEnv DB_PORT 5432
    SetEnv DB_NAME api_monitor
    SetEnv DB_USER postgres
    SetEnv DB_PASS root
    SetEnv ADMIN_USER admin
    SetEnv ADMIN_PASS admin123

    ErrorLog ${APACHE_LOG_DIR}/api_monitor_error.log
    CustomLog ${APACHE_LOG_DIR}/api_monitor_access.log combined
</VirtualHost>
```
```bash
sudo a2dissite 000-default.conf
sudo a2ensite api-monitor.conf
sudo systemctl restart apache2
```

### Step 6: Background Monitoring Scheduler

```bash
# Single manual check run:
php cli/check_services.php

# Continuous local worker (Windows / local dev — polls every 10 seconds):
php cli/worker.php
```

**Linux Cron (Production — runs every minute):**
```cron
* * * * * /usr/bin/php /var/www/html/api-monitor/cli/check_services.php >> /var/log/api_monitor.log 2>&1
```

### Troubleshooting &amp; Common Pitfalls

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| `could not find driver` | `pdo_pgsql` extension is missing or disabled. | Install `php-pgsql` (Linux) or enable `extension=pdo_pgsql` in `php.ini` (Windows), then restart the server. |
| `Call to undefined function curl_init()` | `curl` extension is disabled. | Install `php-curl` (Linux) or enable `extension=curl` in `php.ini` (Windows). |
| `SQLSTATE[08006] Connection refused` / auth failed | PostgreSQL is stopped or credentials mismatch. | Verify PostgreSQL is running on port `5432` and check `DB_USER` / `DB_PASS`. |
| `SSL certificate problem` on Windows | PHP cURL cannot locate CA certificates. | Set `curl.cainfo` in `php.ini` (recommended), or set `CURL_INSECURE=1` for local dev only. |
| URL rejected or `[BLOCKED]` | Host not in `APPROVED_HOSTS` or resolves to a private IP. | Add approved domains to `APPROVED_HOSTS` in `src/security.php`, or use `http://localhost:8000/mock_api.php`. |

---

## 3. List of Tested Public APIs

Pre-seeded in `database/seed.sql` and allowlisted in `src/security.php`:

| # | Service Name | Endpoint | Expected Behavior |
| :- | :--- | :--- | :--- |
| **1** | **JSONPlaceholder API** | `https://jsonplaceholder.typicode.com/posts/1` | HTTP `200`, valid JSON containing `id` (`ONLINE`). |
| **2** | **Open-Meteo Weather API** | `https://api.open-meteo.com/v1/forecast?latitude=28.1&longitude=-15.4&current=temperature_2m` | HTTP `200`, valid JSON containing `current` (`ONLINE`). |
| **3** | **GitHub PHP-Src API** | `https://api.github.com/repos/php/php-src` | Sends custom `User-Agent`; handles HTTP `200` (`ONLINE`) and HTTP `429` (`RATE_LIMITED`). |
| **4** | **HTTPBin Status 200** | `https://httpbin.org/status/200` | Baseline HTTP `200` check (`ONLINE`). |
| **5** | **DummyJSON Products API** | `https://dummyjson.com/products/1` | HTTP `200`, valid JSON containing product `id` (`ONLINE`). |
| **6** | **HTTPBin 404 (Test Error)** | `https://httpbin.org/status/404` | Deliberate HTTP `404` error (`OFFLINE`). |
| **7** | **HTTPBin Delay (Test Slow)** | `https://httpbin.org/delay/2` | Deliberate 2s delay exceeding `1000ms` threshold (`SLOW`). |
| **8** | **HTTPBin Timeout (Test Timeout)** | `https://httpbin.org/delay/10` | Deliberate 10s delay with `2s` timeout (`OFFLINE` timeout). |

---

## 4. Screenshots of the Monitoring Dashboard

Place your captured dashboard screenshots in a `screenshots/` folder and link them below:

- **Operational Dashboard (`/index.php`)**: Live status overview, summary counters (`ONLINE`, `SLOW`, `OFFLINE`), and 30-second auto-refresh indicator.
- **Public Status Page (`/status.php`)**: Read-only public view of enabled services and 24h/7d availability.
- **Service History &amp; Chart (`/history.php?id=1`)**: Response-time Chart.js line graph and PostgreSQL check log table.
- **Admin Management (`/admin.php`)**: Service configuration table with Edit, Enable/Disable, and Delete controls.

<!-- Uncomment once screenshots are saved in screenshots/
![Operational Dashboard](screenshots/dashboard.png)
![Public Status Page](screenshots/public_status.png)
![Service History Chart](screenshots/history.png)
![Manage Services](screenshots/admin.png)
-->

---

## 5. Access URLs &amp; Default Credentials

| Page | URL (`php -S localhost:8000 -t public`) | Authentication |
| :--- | :--- | :--- |
| **Public Status Page** | `http://localhost:8000/status.php` | Public (None) |
| **Operational Dashboard** | `http://localhost:8000/index.php` | Admin Login |
| **Service History** | `http://localhost:8000/history.php?id=1` | Admin Login |
| **Manage Services** | `http://localhost:8000/admin.php` | Admin Login |
| **Add Service** | `http://localhost:8000/add_service.php` | Admin Login |
| **Local Mock API** | `http://localhost:8000/mock_api.php` | Public (Local testing) |

- **Default Admin Login:** `admin` / `admin123` *(override via `ADMIN_USER` / `ADMIN_PASS`)*
