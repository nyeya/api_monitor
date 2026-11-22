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
