<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;

use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\FrontendUserTrait;

/**
 * Called after a frontend user's fields is mapped to a Hubspot contact's properties.
 *
 * @see ContactSynchronizationService::mapFrontendUserToHubspotContactProperties()
 */
class AfterMappingFrontendUserToHubspotContactPropertiesEvent extends AbstractContactSynchronizationEvent
{
    use FrontendUserTrait;

    /**
     * @var array
     */
    protected $hubspotProperties;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $frontendUser,
        array $hubspotProperties
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->frontendUser = $frontendUser;
        $this->hubspotProperties = $hubspotProperties;
    }

    /**
     * @return array
     */
    public function getHubspotProperties(): array
    {
        return $this->hubspotProperties;
    }

    /**
     * @param array $hubspotProperties
     */
    public function setHubspotProperties(array $hubspotProperties)
    {
        $this->hubspotProperties = $hubspotProperties;
    }
}
