<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Service;

/**
 * Synchronization service for Hubspot custom objects
 */
class CustomObjectSynchronizationService
{
    public function synchronize(int $defaultPid = null)
    {
        // TODO: Read TypoScript configuration from tx_hubspot.settings.synchronizeCustomObjects

        // TODO: Iterate through objects that have not yet been synched, then objects with changes

        // TODO: Check if relation exists. How do we deal with relations that do not exist? Sync them in a later pass
        // TODO: or wait with syncing? How do we deal with existing objects in Hubspot that have a relation that is
        // TODO: different to what we have on the TYPO3 side? (Suggestion: we do not sync)

        // TODO: Check if object exists in Hubspot using uniqueFields list (for example product type and serial number).
        // TODO: This is the unique key for the Hubspot object when we don't yet have a Hubspot ID for it.

        // TODO: Write object data to Hubspot

        // TODO: Write relations to Hubspot

        // TODO: Persist Hubspot unique id to database, creating a record in tx_hubspot_object_foreigntable_mm

        // TODO: For later: Synchronize the other way, back to TYPO3.
    }
}
