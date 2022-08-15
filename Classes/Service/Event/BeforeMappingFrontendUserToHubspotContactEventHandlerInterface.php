<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeMappingFrontendUserToHubspotContactEventHandlerInterface
{
    /**
     * @param BeforeMappingFrontendUserToHubspotContactEvent $event
     * @return void
     */
    public function __invoke(BeforeMappingFrontendUserToHubspotContactEvent $event): void;
}
