<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface AfterUpdatingFrontendUserAndHubspotContactEventHandlerInterface
{
    /**
     * @param AfterUpdatingFrontendUserAndHubspotContactEvent $event
     * @return void
     */
    public function __invoke(AfterUpdatingFrontendUserAndHubspotContactEvent $event): void;
}
