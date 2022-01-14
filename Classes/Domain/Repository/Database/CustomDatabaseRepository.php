<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Domain\Repository\Database;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomDatabaseRepository extends AbstractDatabaseRepository
{
    protected const RELATION_TABLE = 'tx_hubspot_object_foreigntable_mm';

    /**
     * @var string Hubspot object type string
     */
    protected $objectType;

    /**
     * @var string Table name for the TYPO3 side
     */
    protected $tableName;

    /**
     * CustomDatabaseRepository constructor.
     *
     * @param string $objectType Hubspot object type string
     * @param string $tableName Table name for the TYPO3 side
     */
    public function __construct(string $objectType, string $tableName)
    {
        if ($objectType === '') {
            throw new \InvalidArgumentException(
                'No Hubspot object type supplied.',
                1610288376
            );
        }

        if (!in_array($tableName, array_keys($GLOBALS['TCA']))) {
            throw new \InvalidArgumentException(
                'Table "' . $tableName . '" does not exist.',
                1610288542
            );
        }

        $this->objectType = $objectType;
        $this->tableName = $tableName;
    }

    /**
     * Get a QueryBuilder instance
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);
    }

    /**
     * Get a query builder instance with table alias 't' and tx_hubspot_object_foreigntable_mm alias 'r' with
     * restrictions set for object type and table name.
     *
     * @return QueryBuilder
     */
    protected function getSelectQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder
            ->select('*')
            ->from($this->tableName, 't')
            ->join(
                't',
                self::RELATION_TABLE,
                'r',
                $queryBuilder->expr()->eq(
                    't.uid',
                    $queryBuilder->quoteIdentifier('r.uid_foreign')
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'r.object_type',
                    $this->objectType
                ),
                $queryBuilder->expr()->eq(
                    'r.table_foreign',
                    $this->tableName
                )
            );
    }
}
