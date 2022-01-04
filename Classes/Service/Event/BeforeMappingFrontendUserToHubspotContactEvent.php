<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\FrontendUserTrait;

/**
 * Called before a frontend user's fields is mapped to a Hubspot contact's properties.
 *
 * @see ContactSynchronizationService::mapFrontendUserToHubspotContactProperties()
 */
class BeforeMappingFrontendUserToHubspotContactEvent extends AbstractContactSynchronizationEvent
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
