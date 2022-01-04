<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;

/**
 * Abstract contact synchronization event.
 */
abstract class AbstractContactSynchronizationEvent
{
    /**
     * @var ContactSynchronizationService
     */
    protected $synchronizationService;

    /**
     * @var array
     */
    protected $configuration;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration
    )
    {
        $this->synchronizationService = $synchronizationService;
        $this->configuration = $configuration;
    }

    /**
     * @return ContactSynchronizationService
     */
    public function getSynchronizationService(): ContactSynchronizationService
    {
        return $this->synchronizationService;
    }

    /**
     * @param ContactSynchronizationService $synchronizationService
     */
    public function setSynchronizationService(ContactSynchronizationService $synchronizationService)
    {
        $this->synchronizationService = $synchronizationService;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }
}
