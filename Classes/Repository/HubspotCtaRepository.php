<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HubspotCtaRepository
{

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * @param string $content
     * @param array $conf
     * @return string
     */
    public function getHubspotCtaForContentElement(string $content, array $conf): string
    {
        $table = 'tx_hubspot_cta';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $cta = $queryBuilder
            ->select('hubspot_cta_code')
            ->from($table, 'cta')
            ->where($queryBuilder->expr()->eq('uid', $this->cObj->cObjGetSingle($conf['hubspot_cta'], $conf['hubspot_cta.'])))
            ->execute()
            ->fetchAll();
        return $cta[0]['hubspot_cta_code'] ?? '';
    }
}
