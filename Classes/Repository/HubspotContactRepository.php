<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Repository;

use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Repository\Exception\HubspotExistingContactConflictException;
use T3G\Hubspot\Repository\Traits\LimitResultTrait;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for manipulating contact data via the Hubspot API
 */
class HubspotContactRepository extends AbstractHubspotRepository
{
    use LimitResultTrait;

    /**
     * Returns up to $this->limit contacts.
     */
    public function findAll(): array
    {
        $parameters = [];

        if ($this->getLimit() > 0) {
            $parameters['count'] = $this->getLimit();
        }

        return $this->factory->contacts()->all($parameters)->toArray()['contacts'];
    }

    /**
     * Finds a contact by email address
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        if (!GeneralUtility::validEmail($email)) {
            return null;
        }

        try {
            $response = $this->factory->contacts()->getByEmail($email);
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }

        return $response->toArray();
    }

    public function findByIdentifier(int $identifier): ?array
    {
        if ($identifier <= 0) {
            return null;
        }

        try {
            $response = $this->factory->contacts()->getById($identifier);
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }

        return $response->toArray();
    }

    /**
     * @param int $timestamp
     * @return array
     */
    public function findNewSince(int $timestamp): array
    {
        if ($timestamp === 0) {
            return $this->findAll();
        }

        $parameters = [
            'timeOffset' => $timestamp,
        ];

        if ($this->getLimit() > 0) {
            $parameters['count'] = $this->getLimit();
        }

        return $this->factory->contacts()->recentNew($parameters)->toArray()['contacts'];
    }

    /**
     * Creates a new contact
     *
     * @param array $contactProperties Associative array of propertyName => value
     * @return int Contact identifier. Negative identifier of existing contact if the contact email address exists.
     */
    public function create(array $contactProperties): int
    {
        try {
            $response = $this->factory->contacts()->create(
                $this->convertAssociativeArrayToHubspotProperties($contactProperties)
            );
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 409) {
                throw new HubspotExistingContactConflictException(
                    $exception->getMessage(),
                    1602243653
                );
            }

            throw $exception;
        }

        return $response['vid'];
    }

    /**
     * Update a Hubspot Contact
     *
     * @param int $identifier Hubspot VID
     * @param array $properties as associative array
     */
    public function update(int $identifier, array $properties)
    {
        $this->factory->contacts()->update(
            $identifier,
            $this->convertAssociativeArrayToHubspotProperties($properties)
        );
    }

    /**
     * Converts an associative array to the type Hubspot likes
     *
     * [ 'propertyName' => 'theValue' ]
     *
     * becomes
     *
     * [
     *      'property' => 'propertyName',
     *      'value' => 'theValue',
     * ]
     *
     * @param array $associativeProperties
     * @return array
     */
    protected function convertAssociativeArrayToHubspotProperties(array $associativeProperties): array
    {
        $hubspotProperties = [];

        foreach ($associativeProperties as $property => $value) {
            $hubspotProperties[] = [
                'property' => $property,
                'value' => $value,
            ];
        }

        return $hubspotProperties;
    }
}