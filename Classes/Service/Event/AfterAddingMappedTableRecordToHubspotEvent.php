<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;


use T3G\Hubspot\Domain\Repository\Database\MappedTableRepository;
use T3G\Hubspot\Service\CustomObjectSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\MappedTableRecordTrait;

class AfterAddingMappedTableRecordToHubspotEvent extends AbstractMappedTableCustomObjectSynchronizationEvent
{
    use MappedTableRecordTrait;

    /**
     * @var int The ID of the corresponding Hubspot custom object
     */
    protected $hubspotCustomObjectId;

    public function __construct(
        CustomObjectSynchronizationService $synchronizationService,
        array $configuration,
        MappedTableRepository $mappedTableRepository,
        array $mappedTableRecord,
        int $hubspotCustomObjectId
    ) {
        parent::__construct($synchronizationService, $configuration, $mappedTableRepository);

        $this->mappedTableRecord = $mappedTableRecord;
        $this->hubspotCustomObjectId = $hubspotCustomObjectId;
    }
    /**
     * Returns the ID of the corresponding Hubspot custom object.
     *
     * @return int
     */
    public function getHubspotCustomObjectId(): int
    {
        return $this->hubspotCustomObjectId;
    }
}
