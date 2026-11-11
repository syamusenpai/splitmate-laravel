<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * PostgreSQL connector that can tell Neon which endpoint to use.
 *
 * Some hosts (e.g. the Vercel PHP runtime) ship an old libpq without SNI support.
 * Neon then needs the endpoint ID as a connection option: options='endpoint=<id>'.
 * See https://neon.com/sni
 */
class NeonPostgresConnector extends PostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        if (! empty($config['neon_endpoint']) && ! str_contains($dsn, 'options=')) {
            $dsn .= ";options='endpoint=".preg_replace('/[^a-z0-9-]/i', '', $config['neon_endpoint'])."'";
        }

        return $dsn;
    }
}
