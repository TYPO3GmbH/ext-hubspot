<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeMappingHubspotContactToFrontendUserEventHandlerInterface
{
    /**
     * @param BeforeMappingHubspotContactToFrontendUserEvent $event
     * @return void
     */
    public function __invoke(BeforeMappingHubspotContactToFrontendUserEvent $event): void;
}
