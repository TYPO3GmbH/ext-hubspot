<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface AfterMappingFrontendUserToHubspotContactPropertiesEventHandlerInterface
{
    /**
     * @param AfterMappingFrontendUserToHubspotContactPropertiesEvent $event
     * @return void
     */
    public function __invoke(AfterMappingFrontendUserToHubspotContactPropertiesEvent $event): void;
}
