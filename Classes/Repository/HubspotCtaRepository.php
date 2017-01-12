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
        $table = 'tx_hubspot_domain_model_cta';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $cta = $queryBuilder
            ->select('hubspot_cta_code')
            ->from($table, 'cta')
            ->join('cta', 'tx_hubspot_domain_model_cta_mm', 'mm', 'mm.uid_foreign = cta.uid')
            ->where($queryBuilder->expr()->eq('mm.uid_local', $this->cObj->cObjGetSingle($conf['content_uid'], $conf['content_uid.'])))
            ->execute()
            ->fetchAll();
        return $cta[0]['hubspot_cta_code'] ?? '';
    }
}
