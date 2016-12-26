<?php
declare(strict_types = 1);


namespace T3G\Hubspot\Repository;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentElementRepository
{

    public function getContentElementsWithHubspotForm()
    {
        $table = 'tt_content';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        return $queryBuilder
            ->select('ce.pid', 'header', 'hubspot_guid', 'ce.uid', 'title')
            ->from($table, 'ce')
            ->join('ce', 'pages', 'p', 'p.uid = ce.pid')
            ->where('hubspot_guid <> \'\'')
            ->execute()
            ->fetchAll();
    }
}
