<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Database;

use Doctrine\DBAL\FetchMode;
use T3G\Hubspot\Domain\Repository\Database\Exception\DataHandlerErrorException;
use T3G\Hubspot\Domain\Repository\Database\Exception\InvalidSyncPassIdentifierScopeException;
use T3G\Hubspot\Domain\Repository\Traits\LimitResultTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository class for TYPO3 frontend users
 */
class FrontendUserRepository extends AbstractDatabaseRepository
{
    use LimitResultTrait;

    const TABLE_NAME = 'fe_users';

    /**
     * @var int Default storage PID
     */
    protected $defaultPageId = 0;

    /**
     * Find a frontend user by its UID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $queryBuilder = $this->getQueryBuilder();

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('uid', $id))
            ->execute()
            ->fetch(FetchMode::ASSOCIATIVE);

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * Get the Hubspot ID for a frontend user.
     *
     * @param int $id The local UID.
     * @return int The Hubspot ID.
     */
    public function getHubspotIdById(int $id): int
    {
        return $this->findById($id)['hubspot_id'] ?? 0;
    }

    /**
     * Finds frontend users that have not yet been synced
     *
     * @return array Frontend user rows
     */
    public function findNotYetSynchronized(): array
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from(static::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('hubspot_sync_timestamp', 0)
            );

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        if ($this->getLimit() > 0) {
            $queryBuilder->setMaxResults($this->getLimit());
        }

        return $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC) ?? [];
    }

    /**
     * Finds frontend users not yet included in current sync pass
     *
     * @return array Frontend user rows
     */
    public function findReadyForSyncPass(): array
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from(static::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->neq('hubspot_sync_pass', $this->getSyncPassIdentifier())
            );

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        if ($this->getLimit() > 0) {
            $queryBuilder->setMaxResults($this->getLimit());
        }

        return $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC) ?? [];
    }

    /**
     * Update a frontend user with supplied
     *
     * @param int $uid UID of the user to update
     * @param array $row The row properties to be updated. UID will be removed.
     * @param bool $setSyncPassIdentifier
     */
    public function update(int $uid, array $row, bool $setSyncPassIdentifier = true)
    {
        unset($row['uid']);

        $row['hubspot_sync_timestamp'] = time();

        if ($setSyncPassIdentifier) {
            $row['hubspot_sync_pass'] = $this->getSyncPassIdentifier();
        }

        $data = [
            static::TABLE_NAME => [
                $uid => $row
            ]
        ];

        $dataHandler = $this->getDataHandler();
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        if (count($dataHandler->errorLog) > 0) {
            throw new DataHandlerErrorException(
                'The DataHandler reported error: ' . implode(', ', $dataHandler->errorLog),
                1602246099
            );
        }
    }

    /**
     * Create a frontend user
     *
     * @param array $row Frontend user row
     * @param bool $setSyncPassIdentifier If true, the sync pass identifier is set.
     * @return int The frontend user UID.
     * @throws DataHandlerErrorException
     */
    public function create(array $row, bool $setSyncPassIdentifier = true): int
    {
        unset($row['uid']);

        $row['hubspot_sync_timestamp'] = time();

        if ($setSyncPassIdentifier) {
            $row['hubspot_sync_pass'] = $this->getSyncPassIdentifier();
        }

        if (!isset($row['pid'])) {
            $row['pid'] = $this->getDefaultPageId();
        }

        $uniqueNewHash = uniqid('NEW');

        $data = [
            static::TABLE_NAME => [
                $uniqueNewHash => $row
            ]
        ];

        $dataHandler = $this->getDataHandler();
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        if (count($dataHandler->errorLog) > 0) {
            throw new DataHandlerErrorException(
                'The DataHandler reported error: ' . implode(', ', $dataHandler->errorLog),
                1602246099
            );
        }

        return (int)$dataHandler->substNEWwithIDs_table[$uniqueNewHash];
    }

    /**
     * Silently sets the sync pass value (i.e. without updating tstamp)
     *
     * @param int $frontendUserUid
     * @return bool
     */
    public function setSyncPassSilently(int $frontendUserUid): bool
    {
        $queryBuilder = $this->getQueryBuilder();

        return (bool)$queryBuilder
            ->update(static::TABLE_NAME)
            ->set('hubspot_sync_pass', $this->getSyncPassIdentifier())
            ->where($queryBuilder->expr()->eq('uid', $frontendUserUid))
            ->execute();
    }

    /**
     * Calculates the syncPassIdentifier to use when updating a FrontendUser. This value identifies whether a record
     * has been
     *
     * @param bool $ignoreScopeError Internal. Return value don't throw InvalidSyncPassIdentifierScopeException
     * @return int The syncPassIdentifier
     */
    public function getSyncPassIdentifier(bool $ignoreScopeError = false): int
    {
        $queryBuilder = $this->getQueryBuilder();

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        list($maxPass, $minPass) = $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('hubspot_sync_pass'),
                $queryBuilder->expr()->min('hubspot_sync_pass')
            )
            ->from(static::TABLE_NAME)
            ->execute()
            ->fetch(\PDO::FETCH_NUM);

        if ($maxPass === $minPass) {
            return $maxPass + 1;
        }

        if ($minPass === $maxPass - 1 || $ignoreScopeError) {
            return $maxPass;
        }

        throw new InvalidSyncPassIdentifierScopeException(
            'Sync pass identifier out of scope. Max was ' . $maxPass . ' and min ' . $minPass . '.',
            1602173860
        );
    }

    /**
     * Fixes sync pass identifier scope issues
     *
     * Sets all hubspot_sync_pass to $syncPassIdentifier - 1 where hubspot_sync_pass is less than max
     */
    public function fixSyncPassIdentifierScope()
    {
        $syncPassIdentifier = $this->getSyncPassIdentifier(true);

        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->update(static::TABLE_NAME)
            ->set('hubspot_sync_pass', $syncPassIdentifier - 1)
            ->where($queryBuilder->expr()->neq('hubspot_sync_pass', $syncPassIdentifier));

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        $queryBuilder->execute();
    }

    /**
     * Get the latest synchronization timestamp
     *
     * @return int Unix timestamp
     */
    public function getLatestSynchronizationTimestamp()
    {
        $queryBuilder = $this->getQueryBuilder();

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        return (int)$queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('hubspot_sync_timestamp')
            )
            ->from(static::TABLE_NAME)
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get oldest non-zero millisecond timestamp for a record created in hubspot
     *
     * @return int Millisecond timestamp
     */
    public function getOldestHubspotCreatedTimestamp(): int
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->min('hubspot_created_timestamp')
            )
            ->from(static::TABLE_NAME)
            ->where($queryBuilder->expr()->neq('hubspot_created_timestamp', 0));

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        return (int)$queryBuilder
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get most recent millisecond timestamp for a record created in hubspot
     *
     * @return int Millisecond timestamp
     */
    public function getLatestHubspotCreatedTimestamp(): int
    {
        $queryBuilder = $this->getQueryBuilder();

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        return (int)$queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('hubspot_created_timestamp')
            )
            ->from(static::TABLE_NAME)
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * @return int
     */
    public function getDefaultPageId(): int
    {
        return $this->defaultPageId;
    }

    /**
     * @param int $defaultPageId
     */
    public function setDefaultPageId(int $defaultPageId)
    {
        $this->defaultPageId = $defaultPageId;
    }

    /**
     * Returns an instance of the DataHandler
     *
     * @return DataHandler
     */
    protected function getDataHandler(): DataHandler
    {
        return GeneralUtility::makeInstance(DataHandler::class);
    }
}
