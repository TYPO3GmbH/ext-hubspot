<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;

/**
 * Event called after contact syncronization has completed.
 *
 * @see ContactSynchronizationService::synchronize()
 */
class AfterContactSynchronizationEvent extends AbstractContactSynchronizationEvent
{
    /**
     * @var array
     */
    protected $processedRecords;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $processedRecords
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->processedRecords = $processedRecords;
    }

    /**
     * @return array
     */
    public function getProcessedRecords(): array
    {
        return $this->processedRecords;
    }

    /**
     * @param array $processedRecords
     */
    public function setProcessedRecords(array $processedRecords)
    {
        $this->processedRecords = $processedRecords;
    }
}
