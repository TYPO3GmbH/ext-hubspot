<?php
declare(strict_types = 1);


namespace T3G\Hubspot\Service;


use T3G\Hubspot\Repository\ContentElementRepository;
use T3G\Hubspot\Repository\HubspotFormRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UsedFormsService
{
    protected $allForms;

    /**
     * @var \T3G\Hubspot\Repository\ContentElementRepository
     */
    protected $contentElementRepository;

    /**
     * @var \T3G\Hubspot\Repository\HubspotFormRepository
     */
    protected $hubspotFormRepository;

    public function __construct()
    {
        $this->contentElementRepository = GeneralUtility::makeInstance(ContentElementRepository::class);
        $this->hubspotFormRepository = GeneralUtility::makeInstance(HubspotFormRepository::class);
        $this->allForms = $this->hubspotFormRepository->getAllFormsWithGuidAsKey();
    }

    public function getFormsInUseWithDetails()
    {
        $contentElementsWithHubspotForm = $this->contentElementRepository->getContentElementsWithHubspotForm();
        $enrichedElements = [];
        foreach ($contentElementsWithHubspotForm as $contentElement) {
            $guid = $contentElement['hubspot_guid'];
            $formDetails = $this->allForms[$guid] ?? [];
            $contentElement['form_name'] = $formDetails['name'];
            $enrichedElements[] = $contentElement;
        }
        return $enrichedElements;
    }
}
