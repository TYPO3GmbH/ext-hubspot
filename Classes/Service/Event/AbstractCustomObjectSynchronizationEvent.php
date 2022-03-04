<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\AbstractSynchronizationService;
use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\CustomObjectSynchronizationService;

/**
 * Abstract custom object synchronization event.
 */
abstract class AbstractCustomObjectSynchronizationEvent extends AbstractSynchronizationEvent
{
    /**
     * @param CustomObjectSynchronizationService $synchronizationService
     * @param array $configuration
     */
    public function __construct(
        CustomObjectSynchronizationService $synchronizationService,
        array $configuration
    )
    {
        parent::__construct($synchronizationService, $configuration);
    }
}
