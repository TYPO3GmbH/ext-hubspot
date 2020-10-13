<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Repository;

use T3G\Hubspot\Repository\Exception\DataHandlerErrorException;
use T3G\Hubspot\Repository\Exception\InvalidSyncPassIdentifierScopeException;
use T3G\Hubspot\Repository\Traits\LimitResultTrait;
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

        if ($this->getLimit() > 0) {
            $queryBuilder->setMaxResults($this->getLimit());
        }

        return $queryBuilder->execute()->fetchAll();
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

    public function create(array $row, bool $setSyncPassIdentifier = true)
    {
        unset($row['uid']);

        $row['hubspot_sync_timestamp'] = time();

        if ($setSyncPassIdentifier) {
            $row['hubspot_sync_pass'] = $this->getSyncPassIdentifier();
        }

        $data = [
            static::TABLE_NAME => [
                uniqid('NEW') => $row
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
            ->setValue('hubspot_sync_pass', $this->getSyncPassIdentifier())
            ->where($queryBuilder->expr()->eq('uid', $frontendUserUid))
            ->execute();
    }

    /**
     * Calculates the syncPassIdentifier to use when updating a FrontendUser. This value identifies whether a record
     * has been
     *
     * @return int The syncPassIdentifier
     */
    public function getSyncPassIdentifier(): int
    {
        $queryBuilder = $this->getQueryBuilder();

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

        if ($minPass === $maxPass - 1) {
            return $maxPass;
        }

        throw new InvalidSyncPassIdentifierScopeException(
            'Sync pass identifier out of scope. Max was ' . $maxPass . ' and min ' . $minPass . '.',
            1602173860
        );
    }

    /**
     * Get the latest synchronization timestamp
     *
     * @return int Unix timestamp
     */
    public function getLatestSynchronizationTimestamp()
    {
        $queryBuilder = $this->getQueryBuilder();

        return (int)$queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('hubspot_sync_timestamp')
            )
            ->from(static::TABLE_NAME)
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get the latest recorded record created timestamp
     *
     * @return int Unix timestamp
     */
    public function getLatestHubspotCreatedTimestamp()
    {
        $queryBuilder = $this->getQueryBuilder();

        return (int)$queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('hubspot_created_timestamp')
            )
            ->from(static::TABLE_NAME)
            ->execute()
            ->fetchColumn(0);
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
