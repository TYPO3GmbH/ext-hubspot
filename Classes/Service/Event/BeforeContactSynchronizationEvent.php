<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;

/**
 * Event called before contact syncronization starts.
 *
 * @see ContactSynchronizationService::synchronize()
 */
class BeforeContactSynchronizationEvent extends AbstractContactSynchronizationEvent
{
}
