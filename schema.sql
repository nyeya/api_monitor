-- ==============================================================================
-- API MONITOR - Database Schema and Seed Data
-- PostgreSQL 14+ compatible
-- ==============================================================================

-- Drop existing tables if re-initializing
DROP TABLE IF EXISTS monitoring_history CASCADE;
DROP TABLE IF EXISTS services CASCADE;

-- ------------------------------------------------------------------------------
-- Table: services
-- Stores the configuration of monitored services.
-- ------------------------------------------------------------------------------
CREATE TABLE services (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    url TEXT NOT NULL,
    http_method VARCHAR(10) NOT NULL DEFAULT 'GET',
    expected_http_code INTEGER NOT NULL DEFAULT 200,
    timeout_seconds INTEGER NOT NULL DEFAULT 5,
    warning_threshold_ms INTEGER NOT NULL DEFAULT 1000,
    check_interval_seconds INTEGER NOT NULL DEFAULT 60,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Constraints
ALTER TABLE services ADD CONSTRAINT chk_http_method CHECK (http_method IN ('GET', 'HEAD'));
ALTER TABLE services ADD CONSTRAINT chk_timeout CHECK (timeout_seconds BETWEEN 1 AND 120);
ALTER TABLE services ADD CONSTRAINT chk_warning_threshold CHECK (warning_threshold_ms > 0);
ALTER TABLE services ADD CONSTRAINT chk_check_interval CHECK (check_interval_seconds >= 5);

-- Indexes for performance
CREATE INDEX idx_services_enabled ON services(enabled);

-- ------------------------------------------------------------------------------
-- Table: monitoring_history
-- Stores the results of each monitoring request.
-- ------------------------------------------------------------------------------
CREATE TABLE monitoring_history (
    id SERIAL PRIMARY KEY,
    service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    checked_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    http_code INTEGER,
    response_time_ms NUMERIC(10, 2),
    status VARCHAR(20) NOT NULL, -- ONLINE, SLOW, OFFLINE
    error_message TEXT
);

CREATE INDEX idx_history_service_checked ON monitoring_history(service_id, checked_at DESC);
CREATE INDEX idx_history_checked_at ON monitoring_history(checked_at DESC);

-- ------------------------------------------------------------------------------
-- Initial Seed Data: Required Public APIs & Testing Endpoints
-- ------------------------------------------------------------------------------
INSERT INTO services (name, description, url, http_method, expected_http_code, timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled)
VALUES
(
    'JSONPlaceholder API',
    'Public REST API returning sample post data (verifies JSON structure)',
    'https://jsonplaceholder.typicode.com/posts/1',
    'GET',
    200,
    5,
    800,
    60,
    TRUE
),
(
    'Open-Meteo Weather API',
    'Public weather forecast API returning live temperature telemetry',
    'https://api.open-meteo.com/v1/forecast?latitude=28.1&longitude=-15.4&current=temperature_2m',
    'GET',
    200,
    5,
    1200,
    60,
    TRUE
),
(
    'GitHub PHP-Src API',
    'Public GitHub API for PHP source repo (verifies User-Agent requirement)',
    'https://api.github.com/repos/php/php-src',
    'GET',
    200,
    5,
    1000,
    60,
    TRUE
),
(
    'HTTPBin Status 200',
    'Public HTTP test service confirming expected HTTP 200 response',
    'https://httpbin.org/status/200',
    'GET',
    200,
    5,
    1500,
    60,
    TRUE
),
(
    'DummyJSON Products API',
    'Public product catalog API returning product entity',
    'https://dummyjson.com/products/1',
    'GET',
    200,
    5,
    1000,
    60,
    TRUE
),
(
    'HTTPBin 404 (Test Error)',
    'Deliberate 404 error endpoint to demonstrate error detection',
    'https://httpbin.org/status/404',
    'GET',
    200,
    5,
    1000,
    60,
    TRUE
),
(
    'HTTPBin Delay (Test Slow)',
    'Deliberate 2-second delay endpoint to demonstrate SLOW status threshold',
    'https://httpbin.org/delay/2',
    'GET',
    200,
    6,
    1000,
    60,
    TRUE
),
(
    'HTTPBin Timeout (Test Timeout)',
    'Deliberate 10-second delay endpoint with 2-second timeout to demonstrate timeout handling',
    'https://httpbin.org/delay/10',
    'GET',
    200,
    2,
    1000,
    60,
    TRUE
);
