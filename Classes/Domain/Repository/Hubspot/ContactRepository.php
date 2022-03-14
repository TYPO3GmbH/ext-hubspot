<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Hubspot;

use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Domain\Repository\Hubspot\Exception\ExistingContactConflictException;
use T3G\Hubspot\Domain\Repository\Traits\LimitResultTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for manipulating contact data via the Hubspot API
 */
class ContactRepository extends AbstractHubspotRepository implements LoggerAwareInterface
{
    use LimitResultTrait;
    use LoggerAwareTrait;

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
            $response = $this->factory->contacts()->getByEmail(urlencode($email));
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }

        return $response->toArray();
    }

    /**
     * Find contact by identifier
     *
     * @param int $identifier Hubspot identifier
     * @return array|null
     * @throws BadRequest
     */
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
     * Find multiple contacts by their identifiers
     *
     * @param array $identifiers
     * @return array|null
     */
    public function findMultipleByIdentifier(array $identifiers): ?array
    {
        if (count($identifiers) === 0) {
            return null;
        }

        $parameters = [];

        if ($this->getLimit() > 0) {
            $parameters['count'] = $this->getLimit();
        }

        $response = $this->factory->contacts()->getBatchByIds($identifiers, $parameters);

        if (count($response->toArray()) === 0) {
            return null;
        }

        return array_values($response->toArray());
    }

    /**
     * Find new Hubspot records from before millisecondTimestamp
     *
     * @param int $millisecondTimestamp Millisecond timestamp
     * @return array
     */
    public function findNewBefore(int $millisecondTimestamp): array
    {
        $parameters = [];

        if ($millisecondTimestamp > 0) {
            $parameters['timeOffset'] = $millisecondTimestamp;
        }

        if ($this->getLimit() > 0) {
            $parameters['count'] = $this->getLimit();
        }

        $identifiers = array_column($this->factory->contacts()->recentNew($parameters)->toArray()['contacts'], 'vid');

        if (count($identifiers) === 0) {
            return [];
        }

        return array_values($this->findMultipleByIdentifier($identifiers));
    }

    /**
     * Creates a new contact
     *
     * @param array $contactProperties Associative array of propertyName => value
     * @param bool $recoveryAttempt Internal. True if re-executing while trying to recover from a validation error.
     * @return int Contact identifier. Negative identifier of existing contact if the contact email address exists.
     */
    public function create(array $contactProperties, bool $recoveryAttempt = false): int
    {
        try {
            $response = $this->factory->contacts()->create(
                $this->convertAssociativeArrayToHubspotProperties($contactProperties)
            );
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 409) {
                throw new ExistingContactConflictException(
                    $exception->getMessage(),
                    1602243653
                );
            }

            if ($exception->getCode() === 400) {
                if ($recoveryAttempt) {
                    throw $exception;
                }

                $exception->getResponse()->getBody()->rewind();

                $parsedResponse = json_decode(
                    $exception->getResponse()->getBody()->getContents(),
                    true
                );

                if (count($parsedResponse['validationResults'] ?? []) === 0) {
                    throw $exception;
                }

                $this->logger->warning(
                    'Recovering from validation error by unsetting properties.',
                    [
                        'contactProperties' => $contactProperties,
                        'validationResults' => $parsedResponse['validationResults'],
                    ]
                );

                foreach ($parsedResponse['validationResults'] as $validationResult) {
                    unset($contactProperties[$validationResult['name']]);
                }

                if (count($contactProperties) === 0) {
                    $this->logger->error(
                        'Unable to recover from validation error. No properties left!',
                        [
                            'validationResults' => $parsedResponse['validationResults'],
                        ]
                    );

                    throw $exception;
                }

                return $this->create($contactProperties, true);
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
    public function convertAssociativeArrayToHubspotProperties(array $associativeProperties): array
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
