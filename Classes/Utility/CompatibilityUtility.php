<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Utility;

use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Miscellaneous functions relating to compatibility with different TYPO3 versions
 *
 * @extensionScannerIgnoreFile
 */
class CompatibilityUtility
{
    /**
     * Compatibility for Bootstrap::initializeBackendAuthentication()
     *
     * Became stateless in TYPO3 9.2
     */
    public static function initializeBackendAuthentication()
    {
        if (self::typo3VersionIsGreaterThanOrEqualTo('9.2')) {
            Bootstrap::initializeBackendAuthentication();
            return;
        }

        Bootstrap::getInstance()->initializeBackendAuthentication();
    }

    /**
     * Returns true if the installation is in composer mode
     *
     * @return bool
     */
    public static function isComposerMode(): bool
    {
        if (self::typo3VersionIsLessThan('9.2')) {
            return TYPO3_COMPOSER_MODE;
        }

        return Environment::isComposerMode();
    }

    /**
     * Returns true if the current TYPO3 version is less than $version
     *
     * @param string $version
     * @return bool
     */
    public static function typo3VersionIsLessThan($version)
    {
        return self::getTypo3VersionInteger() < VersionNumberUtility::convertVersionNumberToInteger($version);
    }

    /**
     * Returns true if the current TYPO3 version is less than or equal to $version
     *
     * @param string $version
     * @return bool
     */
    public static function typo3VersionIsLessThanOrEqualTo($version)
    {
        return self::getTypo3VersionInteger() <= VersionNumberUtility::convertVersionNumberToInteger($version);
    }

    /**
     * Returns true if the current TYPO3 version is greater than $version
     *
     * @param string $version
     * @return bool
     */
    public static function typo3VersionIsGreaterThan($version)
    {
        return self::getTypo3VersionInteger() > VersionNumberUtility::convertVersionNumberToInteger($version);
    }

    /**
     * Returns true if the current TYPO3 version is greater than or equal to $version
     *
     * @param string $version
     * @return bool
     */
    public static function typo3VersionIsGreaterThanOrEqualTo($version)
    {
        return self::getTypo3VersionInteger() >= VersionNumberUtility::convertVersionNumberToInteger($version);
    }

    /**
     * Returns the TYPO3 version as an integer
     *
     * @return int
     */
    public static function getTypo3VersionInteger()
    {
        return VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version());
    }

    /**
     * Dispatch an event as PSR-14 in TYPO3 v10+ and signal in TYPO3 v9.
     *
     * @param object $event
     * @return object
     */
    public static function dispatchEvent(object $event): object
    {
        if (self::typo3VersionIsLessThan('10')) {
            /** @var Dispatcher $signalSlotDispatcher */
            $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);

            $eventClassName = get_class($event);

            $signalSlotDispatcher->dispatch(
                $eventClassName,
                self::classNameToSignalName($eventClassName),
                [$event]
            );

            return $event;
        }

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);

        return $eventDispatcher->dispatch($event);
    }

    /**
     * Register a PSR-14 event as a signal slot in TYPO3 v9.
     *
     * @param string $eventClassName
     * @param string $eventHandlerClassName
     */
    public static function registerEventHandlerAsSignalSlot(string $eventClassName, string $eventHandlerClassName)
    {
        if (self::typo3VersionIsGreaterThanOrEqualTo('10')) {
            return;
        }

        /** @var Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);

        $signalSlotDispatcher->connect(
            $eventClassName,
            self::classNameToSignalName($eventClassName),
            $eventHandlerClassName,
            '__invoke'
        );
    }

    /**
     * Returns "className" from "Foo\Bar\ClassName".
     *
     * @param string $className
     * @return string
     */
    protected static function classNameToSignalName(string $className)
    {
        $classNameParts = explode('\\', $className);

        return lcfirst(array_pop($classNameParts));
    }
}
