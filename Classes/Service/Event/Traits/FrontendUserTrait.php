<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service\Event\Traits;

trait FrontendUserTrait
{
    /**
     * @var array A frontend user row.
     */
    protected $frontendUser;

    /**
     * @return array
     */
    public function getFrontendUser(): array
    {
        return $this->frontendUser;
    }

    /**
     * @param array $frontendUser
     */
    public function setFrontendUser(array $frontendUser)
    {
        $this->frontendUser = $frontendUser;
    }
}
