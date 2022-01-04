<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\HubspotContactTrait;

/**
 * Called before mapping a hubspot contact's properties to frontend user fields.
 *
 * @see ContactSynchronizationService::mapHubspotContactToFrontendUserProperties()
 */
class BeforeMappingHubspotContactToFrontendUserEvent extends AbstractContactSynchronizationEvent
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
