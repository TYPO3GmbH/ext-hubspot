<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Configuration;

use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager as ExtbaseBackendConfigurationManager;

class BackendConfigurationManager extends ExtbaseBackendConfigurationManager
{
    /**
     * @return int
     */
    public function getCurrentPageId(): int
    {
        return $this->currentPageId;
    }

    /**
     * @param int $currentPageId
     */
    public function setCurrentPageId(int $currentPageId)
    {
        $this->currentPageId = $currentPageId;
    }
}
