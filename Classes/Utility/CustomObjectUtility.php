<?php

declare(strict_types=1);


namespace T3G\Hubspot\Utility;

use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;

/**
 * Convenience methods related to Hubspot Custom Objects and their schemas.
 */
class CustomObjectUtility
{
    /**
     * Returns an array without any properties that are Hubspot-internal.
     *
     * @param array $propertyNames
     * @return array
     */
    public static function removeHubspotInternalProperties(array $properties)
    {
        return array_filter(
            $properties,
            function($item) {
                return CustomObjectUtility::isHubspotInternalPropertyName($item['name']);
            }
        );
    }

    /**
     * Returns an array without any property name that is Hubspot-internal.
     *
     * @param array $propertyNames
     * @return array
     */
    public static function removeHubspotInternalPropertyNames(array $propertyNames)
    {
        return array_filter(
            $propertyNames,
            function($item) {
                return CustomObjectUtility::isHubspotInternalPropertyName($item);
            }
        );
    }

    /**
     * Returns true if the property name is that of an internal Hubspot property.
     *
     * @param string $name
     * @return bool
     */
    public static function isHubspotInternalPropertyName(string $name)
    {
        return strpos($name, 'hs_') === 0;
    }

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
