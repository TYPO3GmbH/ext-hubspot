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
class AfterMappingHubspotContactToFrontendUserEvent extends AbstractContactSynchronizationEvent
{
    use HubspotContactTrait;

    /**
     * @var array
     */
    protected $frontendUserProperties;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $hubspotContact,
        array $frontendUserProperties
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->hubspotContact = $hubspotContact;
        $this->frontendUserProperties = $frontendUserProperties;
    }

    /**
     * @return array
     */
    public function getFrontendUserProperties(): array
    {
        return $this->frontendUserProperties;
    }

    /**
     * @param array $frontendUserProperties
     */
    public function setFrontendUserProperties(array $frontendUserProperties)
    {
        $this->frontendUserProperties = $frontendUserProperties;
    }
}
