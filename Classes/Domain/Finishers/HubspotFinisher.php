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

            $identifier = $element->getIdentifier();
            $hubspotData[$identifier] = [
                'identifier' => $identifier,
                'value' => $formRuntime[$identifier],
                'hubspotTable' => $element->getProperties()[$hubspotTableProperty],
                'hubspotProperty' => $element->getProperties()[$hubspotPropertyProperty],
            ];
        }

        /**
         * This is a condition aimed only for any new freelancer.
         * If the company field is empty it shall be filled with the first and last name of the
         * freelancer to have a valid company name.
         */
        if ((int)$hubspotData['freelancer']['value'] === 1 && empty($hubspotData['company']['value'])) {
            $hubspotData['company']['value'] = $hubspotData['first-name']['value'] . ' ' . $hubspotData['last-name']['value'];
        }

        /**
         * If a starting date for a certificate is given, add the expiration date (+ 3 years) by default.
         * This way we prevent the freelancer from entering any bogus data. 
         */
        foreach (['tcce', 'tcci', 'tccd', 'tccc'] as $certificate) {
            $fieldName = $certificate . '_begin';
            $untilFieldName = $certificate . '_until';
            if ((int)$hubspotData['freelancer']['value'] === 1 && !empty($hubspotData[$fieldName]['value'])) {
                /** @var \DateTime $start */
                $start = $hubspotData[$fieldName]['value'];
                /** @var \DateTime $end */
                $end = clone $start;
                $end->modify('+ 3 years');
                $hubspotData[$fieldName]['value'] = $start->format('d/m/Y');
                $hubspotData[$untilFieldName] = [
                    'identifier' => $untilFieldName,
                    'value' => $end->format('d/m/Y'),
                    'hubspotTable' => 'company.1.contact.1.' . $untilFieldName,
                    'hubspotProperty' => 'contact'
                ];
            }
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
