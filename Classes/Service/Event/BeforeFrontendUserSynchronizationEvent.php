<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\FrontendUserTrait;

/**
 * Event called before a frontend user record is synchronized.
 *
 * @see ContactSynchronizationService::synchronizeFrontendUser()
 */
class BeforeFrontendUserSynchronizationEvent extends AbstractContactSynchronizationEvent
{
    use FrontendUserTrait;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $frontendUser
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->frontendUser = $frontendUser;
    }
}
