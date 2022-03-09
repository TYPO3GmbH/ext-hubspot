<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeAddingFrontendUserToHubspotEventHandlerInterface
{
    /**
     * @param BeforeAddingFrontendUserToHubspotEvent $event
     * @return void
     */
    public function __invoke(BeforeAddingFrontendUserToHubspotEvent $event): void;
}
