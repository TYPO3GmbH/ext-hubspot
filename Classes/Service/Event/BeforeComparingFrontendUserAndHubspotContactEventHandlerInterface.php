<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeComparingFrontendUserAndHubspotContactEventHandlerInterface
{
    /**
     * @param BeforeComparingFrontendUserAndHubspotContactEvent $event
     * @return void
     */
    public function __invoke(BeforeComparingFrontendUserAndHubspotContactEvent $event): void;
}
