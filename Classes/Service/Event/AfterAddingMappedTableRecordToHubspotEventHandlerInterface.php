<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface AfterAddingMappedTableRecordToHubspotEventHandlerInterface
{
    /**
     * @param AfterAddingMappedTableRecordToHubspotEvent $event
     * @return void
     */
    public function __invoke(AfterAddingMappedTableRecordToHubspotEvent $event): void;
}
