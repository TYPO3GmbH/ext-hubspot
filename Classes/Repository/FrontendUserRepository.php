<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Repository;

use T3G\Hubspot\Repository\Exception\InvalidSyncPassIdentifierScopeException;
use T3G\Hubspot\Repository\Traits\LimitResultTrait;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

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

        if ((int)$maxPass === (int)$minPass) {
            return $maxPass + 1;
        }

        if ((int)$minPass === (int)$maxPass - 1) {
            return $maxPass;
        }

        throw new InvalidSyncPassIdentifierScopeException(
            'Sync pass identifier out of scope. Max was ' . (int)$maxPass . ' and min ' . (int)$minPass . '.',
            1602173860
        );
    }
}
