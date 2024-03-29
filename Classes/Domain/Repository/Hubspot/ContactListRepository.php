<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Hubspot;

use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Domain\Repository\Hubspot\Exception\InvalidContactListTypeException;
use T3G\Hubspot\Domain\Repository\Hubspot\Exception\NoSuchContactListException;
use T3G\Hubspot\Domain\Repository\Traits\LimitResultTrait;

/**
 * Repository for manipulating contact lists via the Hubspot API
 */
class ContactListRepository extends AbstractHubspotRepository
{
    use LimitResultTrait;

    /**
     * @var array Cache of fetched lists. Key is list identifier.
     */
    protected static $listCache = [];

    /**
     * Find a contact list by identifier
     *
     * @param int $listIdentifier
     * @param bool $cached
     * @return array
     * @throws BadRequest
     * @throws NoSuchContactListException
     */
    public function findByIdentifier(int $listIdentifier, bool $cached = true): array
    {
        if ($cached && isset(self::$listCache[$listIdentifier])) {
            return self::$listCache[$listIdentifier];
        }

        try {
            $list = $this->factory->contactLists()->getById($listIdentifier);
        } catch (BadRequest $exception) {
            if ($exception->getCode() === 404) {
                throw new NoSuchContactListException(
                    'A contact list with the identifier ' . (int)$listIdentifier . ' doesn\'t exist.',
                    1603460485
                );
            }

            throw $exception;
        }

        self::$listCache[$listIdentifier] = $list->toArray();

        return self::$listCache[$listIdentifier];
    }

    /**
     * Add a single contact to a hubspot contact list
     *
     * @param int $listIdentifier
     * @param int $contactIdentifier
     */
    public function addContact(int $listIdentifier, int $contactIdentifier)
    {
        $this->addContacts($listIdentifier, [$contactIdentifier]);
    }

    /**
     * Add hubspot contacts to a hubpot contact list
     *
     * @param int $listIdentifier
     * @param array $contactIdentifiers
     * @throws InvalidContactListTypeException if the list is not static
     */
    public function addContacts(int $listIdentifier, array $contactIdentifiers)
    {
        if (count($contactIdentifiers) === 0) {
            return;
        }

        if ($this->findByIdentifier($listIdentifier)['listType'] !== 'STATIC') {
            throw new InvalidContactListTypeException(
                'Contact list with identifier ' . $listIdentifier . ' is not a static list.',
                1603461059
            );
        }

        $this->factory->contactLists()->addContact($listIdentifier, $contactIdentifiers);
    }
}
