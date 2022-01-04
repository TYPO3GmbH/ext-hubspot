<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\HubspotContactTrait;

/**
 * Event called after a frontend user is created based on a hubspot contact.
 *
 * @see ContactSynchronizationService::addHubspotContactToFrontendUsers()
 */
class AfterAddingHubspotContactToFrontendUsersEvent extends AbstractContactSynchronizationEvent
{
    use HubspotContactTrait;

    /**
     * @var int
     */
    protected $frontendUserIdentifier;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $hubspotContact,
        int $frontendUserIdentifier
    ) {
        parent::__construct($synchronizationService, $configuration);

        $this->hubspotContact = $hubspotContact;
        $this->frontendUserIdentifier = $frontendUserIdentifier;
    }

    /**
     * @return int
     */
    public function getFrontendUserIdentifier(): int
    {
        return $this->frontendUserIdentifier;
    }

    /**
     * @param int $frontendUserIdentifier
     */
    public function setFrontendUserIdentifier(int $frontendUserIdentifier)
    {
        $this->frontendUserIdentifier = $frontendUserIdentifier;
    }
}
