<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeFrontendUserSynchronizationEventHandlerInterface
{
    /**
     * @param BeforeFrontendUserSynchronizationEvent $event
     * @return void
     */
    public function __invoke(BeforeFrontendUserSynchronizationEvent $event): void;
}
