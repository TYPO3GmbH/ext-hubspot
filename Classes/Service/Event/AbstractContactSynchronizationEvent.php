<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\AbstractSynchronizationService;
use T3G\Hubspot\Service\ContactSynchronizationService;

/**
 * Abstract contact synchronization event.
 */
abstract class AbstractContactSynchronizationEvent extends AbstractSynchronizationEvent
{
    /**
     * @param ContactSynchronizationService $synchronizationService
     * @param array $configuration
     */
    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration
    )
    {
        parent::__construct($synchronizationService, $configuration);
    }
}
