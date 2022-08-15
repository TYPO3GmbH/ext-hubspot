<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface AfterAddingHubspotContactToFrontendUsersEventHandlerInterface
{
    /**
     * @param AfterAddingHubspotContactToFrontendUsersEvent $event
     * @return void
     */
    public function __invoke(AfterAddingHubspotContactToFrontendUsersEvent $event): void;
}
