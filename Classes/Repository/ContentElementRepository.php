<?php
declare(strict_types = 1);

namespace T3G\Hubspot\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the selection of ContentElements
 *
 * @package T3G\Hubspot\Repository
 */
class ContentElementRepository
{

    /**
     * Select all content elements (non-deleted) on pages (non-deleted) that
     * contain a hubspot form.
     *
     * @return array
     */
    public function getContentElementsWithHubspotForm() : array
    {
        $table = 'tt_content';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder
            ->selectLiteral(
                'ce.pid', 'header', 'hubspot_guid', 'ce.uid', 'title', 'p.hidden as pageHidden', 'ce.hidden as hidden', 'p.starttime as pageStarttime', 'p.endtime as pageEndtime',
                'ce.starttime as starttime', 'ce.endtime as endtime'
            )
            ->from($table, 'ce')
            ->join('ce', 'pages', 'p', 'p.uid = ce.pid AND p.deleted = 0')
            ->where('hubspot_guid <> \'\'')
            ->execute()
            ->fetchAll();
    }
}
