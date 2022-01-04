<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\FrontendUserTrait;
use T3G\Hubspot\Service\Event\Traits\HubspotContactTrait;

/**
 * Event called after a frontend user is compared with a hubspot contact and updated.
 *
 * @see ContactSynchronizationService::compareAndUpdateFrontendUserAndHubspotContact()
 */
class AfterUpdatingFrontendUserAndHubspotContactEvent extends AbstractContactSynchronizationEvent
{
    use FrontendUserTrait;
    use HubspotContactTrait;

    /**
     * @var array
     */
    protected $mappedFrontendUserProperties;

    /**
     * @var array
     */
    protected $mappedHubspotContactProperties;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $frontendUser,
        array $hubspotContact,
        array $mappedFrontendUserProperties,
        array $mappedHubspotContactProperties
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->frontendUser = $frontendUser;
        $this->hubspotContact = $hubspotContact;
        $this->mappedFrontendUserProperties = $mappedFrontendUserProperties;
        $this->mappedHubspotContactProperties = $mappedHubspotContactProperties;
    }

    /**
     * @return array
     */
    public function getMappedFrontendUserProperties(): array
    {
        return $this->mappedFrontendUserProperties;
    }

    /**
     * @param array $mappedFrontendUserProperties
     */
    public function setMappedFrontendUserProperties(array $mappedFrontendUserProperties)
    {
        $this->mappedFrontendUserProperties = $mappedFrontendUserProperties;
    }

    /**
     * @return array
     */
    public function getMappedHubspotContactProperties(): array
    {
        return $this->mappedHubspotContactProperties;
    }

    /**
     * @param array $mappedHubspotContactProperties
     */
    public function setMappedHubspotContactProperties(array $mappedHubspotContactProperties)
    {
        $this->mappedHubspotContactProperties = $mappedHubspotContactProperties;
    }
}
