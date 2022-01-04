<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event;


use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Service\Event\Traits\FrontendUserTrait;
use T3G\Hubspot\Service\Event\Traits\HubspotContactTrait;

class ResolveHubspotContactEvent extends AbstractContactSynchronizationEvent
{
    use FrontendUserTrait;
    use HubspotContactTrait;

    public function __construct(
        ContactSynchronizationService $synchronizationService,
        array $configuration,
        array $frontendUser
    )
    {
        parent::__construct($synchronizationService, $configuration);

        $this->frontendUser = $frontendUser;
        $this->hubspotContact = null;
    }

    /**
     * @return array|null
     */
    public function getHubspotContact(): ?array
    {
        return $this->hubspotContact;
    }
}
