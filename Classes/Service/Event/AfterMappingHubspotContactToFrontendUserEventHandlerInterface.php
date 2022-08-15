<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface AfterMappingHubspotContactToFrontendUserEventHandlerInterface
{
    /**
     * @param AfterMappingHubspotContactToFrontendUserEvent $event
     * @return void
     */
    public function __invoke(AfterMappingHubspotContactToFrontendUserEvent $event): void;
}
