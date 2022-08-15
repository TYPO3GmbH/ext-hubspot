<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeAddingHubspotContactToFrontendUsersEventHandlerInterface
{
    /**
     * @param BeforeAddingHubspotContactToFrontendUsersEvent $event
     * @return void
     */
    public function __invoke(BeforeAddingHubspotContactToFrontendUsersEvent $event): void;
}
