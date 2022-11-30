<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Repository\Hubspot;

use T3G\Hubspot\Domain\Repository\Hubspot\AbstractHubspotRepository;
use T3G\Hubspot\Hubspot\Factory;

/**
 * Repository for Hubspot custom object definitions.
 */
class AccountInformationRepository extends AbstractHubspotRepository
{
    /**
     * @param Factory|null $factory
     */
    public function __construct(Factory $factory = null)
    {
        parent::__construct($factory);
    }

    /**
     * Get account details for a HubSpot account.
     *
     * @return array
     */
    public function getAccountDetails(): array
    {
        return $this->factory->accountInformation()->getDetails()->toArray();
    }

    /**
     * Get the daily API usage and limits for a HubSpot account.
     *
     * @return array
     */
    public function getAccountApiUsageDaily(): array
    {
        return $this->factory->accountInformation()->getApiUsageDaily()->toArray();
    }
}
