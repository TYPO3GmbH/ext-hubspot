<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Exception;

use T3G\Hubspot\Service\ContactSynchronizationService;

/**
 * Exception thrown while synchronizing contacts. Will lead to synchronization ending early with no further records
 * being processed. This exception should be non-fatal and always caught within ContactSynchronizationService.
 *
 * @see ContactSynchronizationService
 */
class StopRecordSynchronizationException extends AbstractContactSynchronizationException
{
}
