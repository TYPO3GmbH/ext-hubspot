<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface BeforeContactSynchronizationEventHandlerInterface
{
    /**
     * @param BeforeContactSynchronizationEvent $event
     * @return void
     */
    public function __invoke(BeforeContactSynchronizationEvent $event): void;
}
