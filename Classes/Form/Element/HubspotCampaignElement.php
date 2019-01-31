<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Lang\LanguageService;

class HubspotCampaignElement extends AbstractFormElement
{
    /**
     * All nodes get an instance of the NodeFactory and the main data array
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
    }

    /**
     * Main render method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $helpText = $this->getLanguageService()->sL('LLL:EXT:hubspot/Resources/Private/Language/Tca.xlf:hubspot_campaign_fullink_helptext');
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $hubspot_utmcampaign = $row['hubspot_utmcampaign'];
        $hubspot_utmmedium = $row['hubspot_utmmedium'];
        $hubspot_utmsource = $row['hubspot_utmsource'];

        $html = '';
        if (!empty($hubspot_utmcampaign) && !empty($hubspot_utmmedium) && !empty($hubspot_utmsource)) {
            $html .= '<table class="table">' .
                     '<tr>' .
                     '<th class="text-right" style="white-space: nowrap; width: 1%;"><strong>UTM Campaign:&nbsp;</strong></th><td>' . htmlspecialchars($hubspot_utmcampaign) . '</td>' .
                     '</tr>' .
                     '<tr>' .
                     '<th class="text-right"><strong>UTM Medium:&nbsp;</strong></th><td>' . htmlspecialchars($hubspot_utmmedium) . '</td>' .
                     '</tr>' .
                     '<tr>' .
                     '<th class="text-right"><strong>UTM Source:&nbsp;</strong></th><td>' . htmlspecialchars($hubspot_utmsource) . '</td>' .
                     '</tr>' .
                     '</table>';
        }
        $html .= '<input type="text" class="form-control" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />' .
                 '<span id="helpBlock" class="help-block">' . $helpText . '</span>';

        $html = '<div class="form-control-wrap">' . $html . '</div>';
        $resultArray['html'] = $html;

        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
