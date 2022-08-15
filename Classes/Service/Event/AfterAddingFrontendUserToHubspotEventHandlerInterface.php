<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface AfterAddingFrontendUserToHubspotEventHandlerInterface
{
    /**
     * @param AfterAddingFrontendUserToHubspotEvent $event
     * @return void
     */
    public function __invoke(AfterAddingFrontendUserToHubspotEvent $event): void;
}
