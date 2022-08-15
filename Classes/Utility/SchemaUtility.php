<?php

declare(strict_types=1);


namespace T3G\Hubspot\Utility;

/**
 * Convenience methods related to Hubspot schemas.
 */
class SchemaUtility
{
    /**
     * Returns a fully qualified schema name of the format `p<portalId>_<name>`, e.g. "p123456789_schemaName".
     *
     * @param string $name
     * @return string
     */
    public static function makeFullyQualifiedName(string $name)
    {
        return 'p' . getenv('APP_HUBSPOT_PORTALID') . '_' . $name;
    }
}
