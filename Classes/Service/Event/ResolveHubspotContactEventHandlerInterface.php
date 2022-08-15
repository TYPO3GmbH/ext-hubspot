<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface ResolveHubspotContactEventHandlerInterface
{
    /**
     * @param ResolveHubspotContactEvent $event
     * @return void
     */
    public function __invoke(ResolveHubspotContactEvent $event): void;
}
