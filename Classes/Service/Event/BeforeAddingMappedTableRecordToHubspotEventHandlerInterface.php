<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

interface BeforeAddingMappedTableRecordToHubspotEventHandlerInterface
{
    /**
     * @param BeforeAddingMappedTableRecordToHubspotEvent $event
     * @return void
     */
    public function __invoke(BeforeAddingMappedTableRecordToHubspotEvent $event): void;
}
