<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event;

interface AfterContactSynchronizationEventHandlerInterface
{
    /**
     * @param AfterContactSynchronizationEvent $event
     * @return void
     */
    public function __invoke(AfterContactSynchronizationEvent $event): void;
}
