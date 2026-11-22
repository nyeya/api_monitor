# API Monitor – External API & Web Service Monitoring Dashboard

A lightweight, reliable, and secure web application designed to monitor the availability, response times, and payload integrity of external APIs and web services.

Built with **PHP 8**, **PostgreSQL**, **JavaScript (ES6+)**, **HTML5**, **CSS3**, and **cURL**.

---

## 1. Project Overview & Features

API Monitor simulates an IT operations observability dashboard:
- **Real-Time Operational Dashboard (`/index.php`)**: Displays live health status for all configured endpoints with color-coded status badges, response times, HTTP status codes, 24-hour and 7-day availability percentages, and last-checked timestamps. Automatically refreshes every 30 seconds via asynchronous AJAX polling with a visual countdown timer.
- **Service Administration (`/admin.php`)**: Protected administrative interface to view, edit, toggle, or delete monitored services.
- **Add Service (`/add-service.php`)**: Protected standalone page for registering new monitored services with full CSRF protection, input validation, and SSRF prevention.
- **Detailed History & Analytics (`/history.php?id=X`)**: Visualizes historical performance with a dynamic **Chart.js** line chart, uptime percentages, average response times, and incident logs.
- **Background Monitoring Engine (`cli/check_service.php`)**: CLI script executing cURL requests with timeouts, SSL options, custom User-Agents, JSON structure verification, rate-limit awareness, and cross-platform process concurrency locking (`flock`).
- **Continuous Worker (`cli/worker.php`)**: Automated background poller for Windows and local environments that continuously checks due services every 10 seconds.
- **Local Mock API (`/mock_api.php`)**: Predictable test endpoint supporting simulated HTTP status codes (200, 404, 500, 503, 429), configurable delays, and timeouts for live demonstration.

---

## 2. Visual Status Indicators

| Indicator | Status | Criteria |
| :--- | :--- | :--- |
| 🟢 | **ONLINE** | Service responded with the expected HTTP status code (typically 200), valid JSON payload, and response time below the warning threshold. |
| 🟡 | **SLOW** | Service responded correctly with expected HTTP status and valid payload, but response time exceeded `warning_threshold_ms`. |
| 🔴 | **OFFLINE** | Service failed connection, timed out, returned an unexpected HTTP code, or failed JSON payload validation. |
| ⚪ | **UNKNOWN / DISABLED** | Service has not yet been inspected or monitoring is currently paused. |

---

## 3. Advanced Features

### Challenge C – Availability Statistics
- Calculates uptime availability percentage across three scopes in `history.php` and on the Live Dashboard (`index.php`):
  1. **All-Time Uptime %**: All recorded checks since inception (History view).
  2. **Last 24 Hours Uptime %**: Rolling 24-hour health window (Live Dashboard & History view).
  3. **Last 7 Days Uptime %**: Rolling 7-day health window (Live Dashboard & History view).
- **Formula**:
  $$\text{Availability } \% = \left( \frac{\text{Successful Checks (ONLINE or SLOW)}}{\text{Total Checks Recorded}} \right) \times 100$$
- **Handling Missing Data**: Time intervals with zero scheduled checks are omitted from the denominator (`NULLIF(COUNT(*), 0)`) to prevent division-by-zero errors. Unchecked intervals do not penalize uptime.

---

## 4. Tested Public APIs & Expected Behavior

The system includes pre-configured seed data for the 5 required public testing endpoints:

| # | Service Name | Endpoint | Testing Purpose |
| :- | :--- | :--- | :--- |
| **1** | **JSONPlaceholder** | `https://jsonplaceholder.typicode.com/posts/1` | Public REST API; validates HTTP 200 and confirms JSON contains `id`. |
| **2** | **Open-Meteo Weather** | `https://api.open-meteo.com/v1/forecast?latitude=28.1&longitude=-15.4&current=temperature_2m` | Weather telemetry API; validates HTTP 200 and presence of `current` object. |
| **3** | **GitHub PHP-Src** | `https://api.github.com/repos/php/php-src` | GitHub REST API; validates custom `User-Agent` requirement and handles HTTP 429 / rate limits. |
| **4** | **HTTPBin Endpoints** | `https://httpbin.org/status/200`<br>`https://httpbin.org/status/404`<br>`https://httpbin.org/delay/2`<br>`https://httpbin.org/delay/10` | Test service for HTTP status codes, slow latency detection (threshold > 1000ms), and connection timeouts (timeout set to 2s). |
| **5** | **DummyJSON** | `https://dummyjson.com/products/1` | Product catalog API; validates JSON response and product `id` identifier. |

---

## 5. Security & SSRF Protection

1. **Prepared SQL Statements**: 100% of database interactions utilize PDO with parameterized queries to prevent SQL Injection.
2. **SSRF Prevention**: `isSafeUrl()` validates that destinations resolve only to public IP addresses, strictly blocking loopback (`127.0.0.1`, `localhost`), link-local (`169.254.169.254`), and private RFC 1918 networks (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`).
3. **Safe Redirect Policy**: `CURLOPT_FOLLOWLOCATION` is disabled in the monitoring engine to prevent open-redirect SSRF bypasses.
4. **Output Escaping**: All dynamic variables rendered in HTML are sanitized using `htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
5. **Admin Access Control**: Session-authenticated login (`admin.php`) protected with CSRF tokens (`hash_equals`).

---

## 6. Installation & Quick Start

### Prerequisites
- **PHP 8.0+** with `curl`, `pdo_pgsql`, and `session` extensions enabled.
- **PostgreSQL 14+** running locally (default port `5432`).

### Step 1: Database Initialization
Run the database setup script:

```bash
php seed.php
```

*This drops any existing `api_monitor` database, creates it fresh, and executes `schema.sql`.*

*(If your PostgreSQL credentials differ from `postgres` / `root`, set environment variables `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_HOST`, `DB_PORT`, or update `config/db.php`).*

### Step 2: Start the Web Server
From the project root folder:

```bash
php -S localhost:8000 -t public
```

### Step 3: Run the Monitoring Engine
In a second terminal, execute checks manually or launch the continuous poller:

```bash
# Single execution:
php cli/check_service.php

# Or continuous background poller (checks every 10 seconds):
php cli/worker.php
```

#### Production Linux Cron Configuration
To run every minute via Linux cron:
```cron
* * * * * /usr/bin/php /var/www/api_monitor/cli/check_service.php >> /var/log/api_monitor.log 2>&1
```

---

## 7. Web Navigation & Credentials

- **Live Operational Dashboard**: `http://localhost:8000/index.php`
- **Manage Services (Admin)**: `http://localhost:8000/admin.php`
  - **Username**: `admin`
  - **Password**: `admin123`
- **Add Service**: `http://localhost:8000/add-service.php`
- **Local Mock API**: `http://localhost:8000/mock_api.php`

---

## 8. Live Demonstration Walkthrough

During your final evaluation or video conference presentation, demonstrate the required operational criteria using this sequence:

1. **Successful API Response (HTTP 200)**:
   - Point to `JSONPlaceholder API` or `HTTPBin Status 200` on the Live Dashboard.
   - Show status 🟢 **ONLINE**, HTTP `200`, and fast latency (~150-300ms).
2. **HTTP Error Detection**:
   - Point to `HTTPBin 404 (Test Error)` on the Live Dashboard.
   - Show status 🔴 **OFFLINE** and error details `Expected HTTP 200, received 404`.
3. **Slow Latency Threshold Detection**:
   - Point to `HTTPBin Delay (Test Slow)`.
   - Show status 🟡 **SLOW** (latency ~2300ms exceeds warning threshold 1000ms).
4. **Connection Timeout Handling**:
   - Point to `HTTPBin Timeout (Test Timeout)`.
   - Show error message `Operation timed out after 2001 milliseconds with 0 bytes received`.
5. **Dynamic Dashboard Refresh**:
   - Observe the 30-second countdown in the upper-right corner.
   - Click **"Refresh Now"** to demonstrate instantaneous AJAX update without reloading the page.
6. **PostgreSQL History & Charts**:
   - Click **"View History"** on any service.
   - Show the Chart.js response-time timeline, 24h/7d availability percentages, and recent checks log.

---

## 9. Team Responsibilities & Collaboration

- **Developer 1 (Backend)**:
  - Database schema design (`services`, `monitoring_history`), table constraints, and indexes.
  - cURL request implementation, connection timeouts, SSL handling, and custom headers.
  - JSON payload validation and HTTP 429 rate-limit error handling.
  - Failure detection hysteresis logic (Challenge A).
  - Background execution script (`check_service.php`) and file-locking mechanism.
- **Developer 2 (Frontend)**:
  - Responsive user interface and CSS design (`style.css`).
  - Visual status indicators (🟢, 🟡, 🔴, ⚪) and live health summary cards.
  - AJAX dynamic auto-refresh engine (`dashboard.js`) with 30s countdown timer.
  - Chart.js interactive response time chart and statistics grid (`history.php`).
  - Real-time availability statistics and service management forms (`admin.php`).
