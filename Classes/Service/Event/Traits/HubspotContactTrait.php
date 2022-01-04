<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event\Traits;

trait HubspotContactTrait
{
    /**
     * @var array
     */
    protected $hubspotContact;

    /**
     * @return array
     */
    public function getHubspotContact(): array
    {
        return $this->hubspotContact;
    }

    /**
     * @param array $hubspotContact
     */
    public function setHubspotContact(array $hubspotContact)
    {
        $this->hubspotContact = $hubspotContact;
    }
}
