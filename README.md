# API Monitor – External API & Web Service Monitoring Dashboard

A lightweight, reliable, and secure web application designed to monitor the availability, response times, and payload integrity of external APIs and web services.

Built with **PHP 8**, **PostgreSQL**, **JavaScript (ES6+)**, **HTML5**, **CSS3**, and **cURL**.

---

## 1. Project Overview & Features

API Monitor simulates an IT operations observability dashboard:
- **Real-Time Operational Dashboard**: Displays live health status for configured endpoints.
- **Service Administration**: Protected administrative interface to register, edit, and manage services.
- **Background Monitoring Engine**: CLI script executing cURL requests with timeouts, SSL options, and telemetry tracking.

---

## Prerequisites
- **PHP 8.0+** with `curl`, `pdo_pgsql`, and `session` extensions.
- **PostgreSQL 14+** running locally.