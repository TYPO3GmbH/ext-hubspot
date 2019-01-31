<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Hooks\Typolink;

use TYPO3\CMS\Frontend\ContentObject\TypolinkModifyLinkConfigForPageLinksHookInterface;

class ModifyParameterForPageLinksHook implements TypolinkModifyLinkConfigForPageLinksHookInterface
{
    /**
     * Modifies the typolink page link configuration array.
     *
     * @param array $linkConfiguration The link configuration (for options see TSRef -> typolink)
     * @param array $linkDetails Additional information for the link
     * @param array $pageRow The complete page row for the page to link to
     * @return array The modified $linkConfiguration
     */
    public function modifyPageLinkConfiguration(array $linkConfiguration, array $linkDetails, array $pageRow): array
    {
        $params = [];
        if (!empty($pageRow['hubspot_utmmedium'])) {
            $params['utm_medium'] = $pageRow['hubspot_utmmedium'];
        }
        if (!empty($pageRow['hubspot_utmsource'])) {
            $params['utm_source'] = $pageRow['hubspot_utmsource'];
        }
        if (!empty($pageRow['hubspot_utmcampaign'])) {
            $params['utm_campaign'] = $pageRow['hubspot_utmcampaign'];
        }

        $linkConfiguration['additionalParams'] .= '&' . http_build_query($params);
        return $linkConfiguration;
    }
}
