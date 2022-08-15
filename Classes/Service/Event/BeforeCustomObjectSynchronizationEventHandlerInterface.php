<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeCustomObjectSynchronizationEventHandlerInterface
{
    /**
     * @param BeforeCustomObjectSynchronizationEvent $event
     * @return void
     */
    public function __invoke(BeforeCustomObjectSynchronizationEvent $event): void;
}
