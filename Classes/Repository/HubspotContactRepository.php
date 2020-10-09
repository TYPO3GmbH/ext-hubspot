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

    const PROPERTY_TYPO3_UID = 'typo3_uid';

    const PROPERTY_SYNC_TIMESTAMP = 'typo3_sync_timestamp';

    const REGISTRY_PROPERTIES_CREATED = 'hubspot_properties_created';

    /**
     *
     */
    public function getContacts()
    {
        return $this->factory->contacts()->all()->toArray();
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
     * Create the default properties in the Hubspot contact for synchronization tracking
     */
    public function createDefaultProperties()
    {
        $registry = GeneralUtility::makeInstance(Registry::class);

        if ($registry->get('tx_hubspot', static::REGISTRY_PROPERTIES_CREATED)) {
            return;
        }

        try {
            $this->factory->contactProperties()->createGroup([
                'name' => 'typo3',
                'displayName' => 'TYPO3'
            ]);
        } catch (BadRequest $exception) {
            // Dismiss if the property group already exists
            if ($exception->getCode() !== 409) {
                throw $exception;
            }
        }

        try {
            $this->factory->contactProperties()->create([
                'name' => static::PROPERTY_TYPO3_UID,
                'label' => 'TYPO3 ID',
                'formField' => false,
                'type' => 'number',
                'fieldType' => 'number',
                'groupName' => 'typo3'
            ]);
        } catch (BadRequest $exception) {
            // Dismiss if the property already exists
            if ($exception->getCode() !== 409) {
                throw $exception;
            }
        }

        try {
            $this->factory->contactProperties()->create([
                'name' => static::PROPERTY_SYNC_TIMESTAMP,
                'label' => 'Last TYPO3 sync',
                'formField' => false,
                'type' => 'datetime',
                'fieldType' => 'date',
                'groupName' => 'typo3'
            ]);
        } catch (BadRequest $exception) {
            // Dismiss if the property already exists
            if ($exception->getCode() !== 409) {
                throw $exception;
            }
        }

        $registry->set('tx_hubspot', static::REGISTRY_PROPERTIES_CREATED, true);
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
