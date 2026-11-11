<?php

/*
 * Vercel entry point (vercel-php runtime).
 *
 * Vercel runs PHP as short-lived serverless functions with a read-only file system,
 * so everything Laravel normally writes to disk is pointed at /tmp, or kept in
 * memory and cookies instead. Real environment variables set in the Vercel
 * dashboard always win over these defaults.
 */

$defaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'LOG_CHANNEL' => 'stderr',
    'SESSION_DRIVER' => 'cookie',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
];

// The Vercel + Neon integration provides DATABASE_URL(_UNPOOLED); use it for PostgreSQL.
// The direct (unpooled) connection is preferred: Neon's pooler failed on schema changes,
// and a small app does not need pooling.
$databaseUrl = getenv('DATABASE_URL_UNPOOLED') ?: getenv('DATABASE_URL');
if ($databaseUrl && ! getenv('DB_URL')) {
    $defaults['DB_CONNECTION'] = 'pgsql';
    $defaults['DB_URL'] = $databaseUrl;
}

foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

if (! is_dir('/tmp/views')) {
    @mkdir('/tmp/views', 0755, true);
}

// Make Laravel see the request as if it came through public/index.php.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
