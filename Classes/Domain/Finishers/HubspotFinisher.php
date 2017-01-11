<?php
declare (strict_types=1);
namespace T3G\Hubspot\Domain\Finishers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use T3G\Hubspot\Service\Form\HubspotApiService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * Scope: frontend
 */
class HubspotFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'hubspotTableProperty' => 'hubspotTable',
        'hubspotPropertyProperty' => 'hubspotProperty',
    ];

    /**
     * Collect all form elements with hubspot properties
     * and submit it.
     *
     * @return void
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();

        $hubspotTableProperty = $this->parseOption('hubspotTableProperty');
        $hubspotPropertyProperty = $this->parseOption('hubspotPropertyProperty');
        $hubspotData = [];

        $elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();
        foreach ($elements as $element) {
            if (
                !$element instanceof FormElementInterface
                || empty($element->getProperties()[$hubspotPropertyProperty])
            ) {
                continue;
            }

            $hubspotData[] = [
                'identifier' => $element->getIdentifier(),
                'value' => $formRuntime[$element->getIdentifier()],
                'hubspotTable' => $element->getProperties()[$hubspotTableProperty],
                'hubspotProperty' => $element->getProperties()[$hubspotPropertyProperty],
            ];
        }

        if (!empty($hubspotData)) {
            $this->submitData($hubspotData);
        }
    }

    /**
     * Send the form values to hubspot.
     *
     * @param array $data
     * @return void
     */
    protected function submitData(array $data)
    {
        $hubspotApiService = GeneralUtility::makeInstance(HubspotApiService::class);
        $hubspotApiService->sendToHubspot($data);
    }
}
