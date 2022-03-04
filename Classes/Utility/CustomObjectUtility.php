<?php

declare(strict_types=1);


namespace T3G\Hubspot\Utility;

use T3G\Hubspot\Domain\Repository\Hubspot\CustomObjectSchemaRepository;
use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Convenience methods related to Hubspot Custom Objects and their schemas.
 */
class CustomObjectUtility
{
    /**
     * @var array
     * @see CustomObjectUtility::getPropertySchemas()
     */
    protected static $propertySchemas = [];

    /**
     * @var string[]
     * @see CustomObjectUtility::getNamesOfUniqueProperties()
     */
    protected static $namesOfUniquePropertiesCache = [];

    /**
     * @var string[]
     * @see CustomObjectUtility::getPropertyNames()
     */
    protected static $propertyNamesCache = [];


    public static function getPropertySchemas(string $objectName, bool $excludeHubspotInternal = true)
    {
        $cacheKey = $objectName . '_internal' . $excludeHubspotInternal;

        if (!isset(self::$propertySchemas[$cacheKey])) {
            $properties = GeneralUtility::makeInstance(CustomObjectSchemaRepository::class)
                    ->findByName($objectName)['properties'] ?? [];


        }

        return self::$propertySchemas[$cacheKey];
    }

    /**
     * Returns the names of properties in a custom object.
     *
     * @param string $objectName
     * @param bool $excludeHubspotInternal
     * @return string[]
     */
    public static function getPropertyNames(string $objectName, bool $excludeHubspotInternal = true)
    {
        $cacheKey = $objectName . '_internal' . $excludeHubspotInternal;

        if (!isset(self::$propertyNamesCache[$cacheKey])) {
            $properties = GeneralUtility::makeInstance(CustomObjectSchemaRepository::class)
                ->findByName($objectName)['properties'] ?? [];

            if ($excludeHubspotInternal) {
                $properties = CustomObjectUtility::removeHubspotInternalProperties($properties);
            }

            self::$propertyNamesCache[$cacheKey] = array_column($properties, 'name');
        }

        return self::$propertyNamesCache[$cacheKey];
    }

    /**
     * Returns a list of names of unique properties in $objectName (`hasUniqueValue` is true).
     *
     * @param string $objectName
     * @param bool $excludeHubspotInternal
     * @return array|mixed
     */
    public static function getNamesOfUniqueProperties(string $objectName, bool $excludeHubspotInternal = true)
    {
        $cacheKey = $objectName . '_internal' . $excludeHubspotInternal;

        if (!isset(self::$namesOfUniquePropertiesCache[$cacheKey])) {

            $properties = GeneralUtility::makeInstance(CustomObjectSchemaRepository::class)
                ->findByName($objectName)['properties'] ?? [];

            if ($excludeHubspotInternal) {
                $properties = CustomObjectUtility::removeHubspotInternalProperties($properties);
            }

            $uniquePropertyNames = [];

            foreach ($properties as $property) {
                if ($property['hasUniqueValue']) {
                    $uniquePropertyNames[] = $property['name'];
                }
            }

            self::$namesOfUniquePropertiesCache[$cacheKey] = $uniquePropertyNames;
        }

        return self::$namesOfUniquePropertiesCache[$cacheKey];
    }

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
                return !CustomObjectUtility::isHubspotInternalPropertyName($item['name']);
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
                return !CustomObjectUtility::isHubspotInternalPropertyName($item);
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
