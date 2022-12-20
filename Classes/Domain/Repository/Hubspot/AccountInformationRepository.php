<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Repository\Hubspot;

/**
 * Repository for Hubspot custom object definitions.
 */
class AccountInformationRepository extends AbstractHubspotRepository
{
    /**
     * Get account details for a HubSpot account.
     */
    public function getAccountDetails(): array
    {
        return $this->factory->accountInformation()->getDetails()->toArray();
    }

    /**
     * Get the daily API usage and limits for a HubSpot account.
     */
    public function getAccountApiUsageDaily(): array
    {
        return $this->factory->accountInformation()->getApiUsageDaily()->toArray();
    }
}
