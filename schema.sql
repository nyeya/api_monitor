-- ==============================================================================
-- API MONITOR - Database Schema
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