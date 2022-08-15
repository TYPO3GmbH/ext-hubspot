<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;


use T3G\Hubspot\Domain\Repository\Database\MappedTableRepository;
use T3G\Hubspot\Service\CustomObjectSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\MappedTableRecordTrait;

/**
 * Event called before a mapped table record is added to hubspot as a custom object.
 */
class BeforeAddingMappedTableRecordToHubspotEvent extends AbstractMappedTableCustomObjectSynchronizationEvent
{
    use MappedTableRecordTrait;

    public function __construct(
        CustomObjectSynchronizationService $synchronizationService,
        array $configuration,
        MappedTableRepository $mappedTableRepository,
        array $mappedTableRecord
    ) {
        parent::__construct($synchronizationService, $configuration, $mappedTableRepository);

        $this->mappedTableRecord = $mappedTableRecord;
    }
}
