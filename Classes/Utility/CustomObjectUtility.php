<?php

declare(strict_types=1);


namespace T3G\Hubspot\Utility;

use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;

/**
 * Convenience methods related to Hubspot Custom Objects.
 */
class CustomObjectUtility
{
    /**
     * Register the location of a schema definition JSON file.
     *
     * @param string $path Path to a schema definition JSON file.
     */
    public static function addSchemaDefinitionFile(string $path)
    {
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'json') {
            throw new IllegalFileExtensionException(
                'The file extension must be ".json". Path: ' . $path,
                1641976708832
            );
        }

        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['hubspot'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['hubspot'] = [];
        }

        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['hubspot']['schemaFiles'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['hubspot']['schemaFiles'] = [];
        }

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['hubspot']['schemaFiles'][] = $path;
    }

    /**
     * Returns an array of schema definition file paths.
     *
     * @return string[]
     */
    public static function getSchemaDefinitionFiles(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['hubspot']['schemaFiles'] ?? [];
    }
}
