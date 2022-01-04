<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\FrontendUserTrait;
use T3G\Hubspot\Service\Event\Traits\HubspotContactTrait;

/**
 * Event called before a frontend user is compared with a hubspot contact and compared and updated.
 *
 * @see ContactSynchronizationService::compareAndUpdateFrontendUserAndHubspotContact()
 */
class BeforeComparingFrontendUserAndHubspotContactEvent extends AbstractContactSynchronizationEvent
{
    use FrontendUserTrait;
    use HubspotContactTrait;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $frontendUser,
        array $hubspotContact
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->frontendUser = $frontendUser;
        $this->hubspotContact = $hubspotContact;
    }
}
