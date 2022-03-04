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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MappedTableRepository extends AbstractDatabaseRepository
{
    use LimitResultTrait;

    protected const RELATION_TABLE = 'tx_hubspot_mapping';

    /**
     * @var string Hubspot object type string.
     */
    protected $objectType;

    /**
     * @var string The configuration's TypoScript key.
     */
    protected $typoScriptKey;

    /**
     * @var string Table name for the TYPO3 side.
     */
    protected $tableName;

    /**
     * CustomDatabaseRepository constructor.
     *
     * @param string $objectType Hubspot object type string
     * @param string $typoScriptKey The configuration's TypoScript key
     * @param string $tableName Table name for the TYPO3 side
     */
    public function __construct(string $objectType, string $typoScriptKey, string $tableName)
    {
        if ($objectType === '') {
            throw new \InvalidArgumentException(
                'No Hubspot object type supplied.',
                1610288376
            );
        }

        if (!isset($GLOBALS['TCA'][$tableName])) {
            throw new \InvalidArgumentException(
                'Table "' . $tableName . '" does not exist.',
                1610288542
            );
        }

        $this->objectType = $objectType;
        $this->typoScriptKey = $typoScriptKey;
        $this->tableName = $tableName;
    }

    /**
     * Finds records that have not yet been synchronized.
     *
     * @return array
     */
    public function findNotYetSynchronized(): array
    {
        $queryBuilder = $this->getSelectQueryBuilder();

        return $queryBuilder
            ->andWhere($queryBuilder->expr()->isNull('m.uid_foreign'))
            ->execute()
            ->fetchAll(FetchMode::ASSOCIATIVE);
    }

    public function findReadyForSyncPass(): array
    {
        $queryBuilder = $this->getSelectQueryBuilder();

        return $queryBuilder
            ->andWhere($queryBuilder->expr()->isNotNull('m.uid_foreign'))
            ->execute()
            ->fetchAll(FetchMode::ASSOCIATIVE);
    }

    /**
     * Add a TYPO3-to-Hubspot record relation mapping.
     *
     * @param int $objectId The unique ID of the Hubspot custom object record.
     * @param int $uid The corresponding local TYPO3 record UID.
     */
    public function add(int $objectId, int $uid)
    {
        $queryBuilder = $this->getMappedTableQueryBuilder();

        $queryBuilder
            ->insert(self::RELATION_TABLE)
            ->values([
                'object_type' => $this->objectType,
                'typoscript_key' => $this->typoScriptKey,
                'hubspot_id' => $objectId,
                'uid_foreign' => $uid,
                'table_foreign' => $this->tableName,
                'hubspot_created_timestamp' => time() * 1000,
                'hubspot_sync_timestamp' => time(),
                'hubspot_sync_pass' => 1,
            ])
            ->execute();
    }

    /**
     * Update a record.
     *
     * @param int $uid UID of the record to update
     * @param array $row The row properties to be updated. UID will be removed.
     * @param bool $setSyncPassIdentifier
     * @throws DataHandlerErrorException
     */
    public function update(int $uid, array $row, bool $setSyncPassIdentifier = true)
    {
        unset($row['uid']);

        $data = [
            $this->tableName => [
                $uid => $row
            ]
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        if (count($dataHandler->errorLog) > 0) {
            throw new DataHandlerErrorException(
                'The DataHandler reported error: ' . implode(', ', $dataHandler->errorLog),
                1645192871163
            );
        }

        $queryBuilder = $this->getRelationTableQueryBuilder();

        if ($setSyncPassIdentifier) {
            $queryBuilder->set('hubspot_sync_pass', $this->getSyncPassIdentifier());
        }

        $queryBuilder
            ->update(self::RELATION_TABLE)
            ->set('hubspot_sync_timestamp', time())
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'object_type',
                    $queryBuilder->createNamedParameter($this->objectType)
                ),
                $queryBuilder->expr()->eq(
                    'typoscript_key',
                    $queryBuilder->createNamedParameter($this->typoScriptKey)
                ),
                $queryBuilder->expr()->eq(
                    'table_foreign',
                    $queryBuilder->createNamedParameter($this->tableName)
                )
            )
            ->execute();
    }

    /**
     * Calculates the syncPassIdentifier to use when updating a record. This value identifies whether a record
     * has been updated in the current sync pass.
     *
     * @param bool $ignoreScopeError Internal. Return value, don't throw InvalidSyncPassIdentifierScopeException
     * @return int The syncPassIdentifier
     */
    public function getSyncPassIdentifier(bool $ignoreScopeError = false): int
    {
        $queryBuilder = $this->getRelationTableQueryBuilder();

        if ($this->hasSearchPids()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('pid', $this->getSearchPids()));
        }

        list($maxPass, $minPass) = $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max('hubspot_sync_pass'),
                $queryBuilder->expr()->min('hubspot_sync_pass')
            )
            ->from(self::RELATION_TABLE)
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
     * Silently sets the sync pass value (i.e. without updating tstamp)
     *
     * @param int $uid
     * @return bool
     */
    public function setSyncPassSilently(int $uid): bool
    {
        $queryBuilder = $this->getRelationTableQueryBuilder();

        return (bool)$queryBuilder
            ->update(static::RELATION_TABLE)
            ->set('hubspot_sync_pass', $this->getSyncPassIdentifier())
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'object_type',
                    $queryBuilder->createNamedParameter($this->objectType)
                ),
                $queryBuilder->expr()->eq(
                    'typoscript_key',
                    $queryBuilder->createNamedParameter($this->typoScriptKey)
                ),
                $queryBuilder->expr()->eq(
                    'table_foreign',
                    $queryBuilder->createNamedParameter($this->tableName)
                )
            )
            ->execute();
    }

    /**
     * Get a QueryBuilder instance for the mapped table.
     *
     * @return QueryBuilder
     */
    protected function getMappedTableQueryBuilder(): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);
    }

    /**
     * Get a QueryBuilder instance for the mapped table.
     *
     * @return QueryBuilder
     */
    protected function getRelationTableQueryBuilder(): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::RELATION_TABLE);
    }

    /**
     * Get a query builder instance with table alias 't' and tx_hubspot_mapping alias 'm' with
     * restrictions set for object type and table name.
     *
     * @return QueryBuilder
     */
    protected function getSelectQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getMappedTableQueryBuilder();

        if ($this->getLimit() > 0) {
            $queryBuilder->setMaxResults($this->getLimit());
        }

        return $queryBuilder
            ->select('*')
            ->from($this->tableName, 't')
            ->leftJoin(
                't',
                self::RELATION_TABLE,
                'm',
                (string)$queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        't.uid',
                        $queryBuilder->quoteIdentifier('m.uid_foreign')
                    ),
                    $queryBuilder->expr()->eq(
                        'm.object_type',
                        $queryBuilder->createNamedParameter($this->objectType)
                    ),
                    $queryBuilder->expr()->eq(
                        'm.typoscript_key',
                        $queryBuilder->createNamedParameter($this->typoScriptKey)
                    ),
                    $queryBuilder->expr()->eq(
                        'm.table_foreign',
                        $queryBuilder->createNamedParameter($this->tableName)
                    )
                )
            );
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
     * @param string $table
     * @param int $uid
     * @return int
     */
    public static function getHubspotId(string $table, int $uid, string $objectType): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::RELATION_TABLE);

        return (int)$queryBuilder
            ->select('hubspot_id')
            ->from(self::RELATION_TABLE)
            ->andWhere(
                $queryBuilder->expr()->eq('table_foreign', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('object_type', $queryBuilder->createNamedParameter($objectType))
            )
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();
    }

    /**
     * Remove all mappings for custom object schema of type $objectType.
     *
     * @param string $objectType
     */
    public static function removeSchemaMappings(string $objectType)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::RELATION_TABLE);

        $queryBuilder
            ->delete(self::RELATION_TABLE)
            ->where($queryBuilder->expr()->eq('object_type', $queryBuilder->createNamedParameter($objectType)))
            ->execute();
    }

    /**
     * @return string
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * @return string
     */
    public function getTypoScriptKey(): string
    {
        return $this->typoScriptKey;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
