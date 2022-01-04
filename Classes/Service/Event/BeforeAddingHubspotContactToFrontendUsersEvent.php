<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\HubspotContactTrait;

/**
 * Event called before a frontend user is created based on a hubspot contact.
 *
 * @see ContactSynchronizationService::addHubspotContactToFrontendUsers()
 */
class BeforeAddingHubspotContactToFrontendUsersEvent extends AbstractContactSynchronizationEvent
{
    use HubspotContactTrait;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $hubspotContact
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->hubspotContact = $hubspotContact;
    }
}
