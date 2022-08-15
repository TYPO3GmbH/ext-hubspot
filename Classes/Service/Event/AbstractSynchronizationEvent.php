<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;


use T3G\Hubspot\Service\AbstractSynchronizationService;
use T3G\Hubspot\Service\ContactSynchronizationService;

class AbstractSynchronizationEvent
{
    /**
     * @var AbstractSynchronizationService
     */
    protected $synchronizationService;

    /**
     * @var array
     */
    protected $configuration;

    public function __construct(
        AbstractSynchronizationService $synchronizationService,
        array $configuration
    )
    {
        $this->synchronizationService = $synchronizationService;
        $this->configuration = $configuration;
    }

    /**
     * @return AbstractSynchronizationService
     */
    public function getSynchronizationService(): AbstractSynchronizationService
    {
        return $this->synchronizationService;
    }

    /**
     * @param AbstractSynchronizationService $synchronizationService
     */
    public function setSynchronizationService(AbstractSynchronizationService $synchronizationService)
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
