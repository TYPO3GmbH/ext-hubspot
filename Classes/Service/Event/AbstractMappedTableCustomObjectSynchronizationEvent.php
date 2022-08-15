<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;


use T3G\Hubspot\Domain\Repository\Database\MappedTableRepository;
use T3G\Hubspot\Service\CustomObjectSynchronizationService;

/**
 * Abstract custom object synchronization event with initialized MappedTableRepository.
 */
class AbstractMappedTableCustomObjectSynchronizationEvent extends AbstractCustomObjectSynchronizationEvent
{
    /**
     * @var MappedTableRepository
     */
    protected $mappedTableRepository;

    /**
     * @param CustomObjectSynchronizationService $synchronizationService
     * @param array $configuration
     * @param MappedTableRepository $mappedTableRepository
     */
    public function __construct(
        CustomObjectSynchronizationService $synchronizationService,
        array $configuration,
        MappedTableRepository $mappedTableRepository
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->mappedTableRepository = $mappedTableRepository;
    }

    /**
     * @return MappedTableRepository
     */
    public function getMappedTableRepository(): MappedTableRepository
    {
        return $this->mappedTableRepository;
    }
}
