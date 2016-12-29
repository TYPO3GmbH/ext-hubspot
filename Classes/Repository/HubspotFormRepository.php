<?php
declare(strict_types = 1);

namespace T3G\Hubspot\Repository;

use SevenShores\Hubspot\Factory;

/**
 * Repository for fetching data from hubspot via API
 *
 * @package T3G\Hubspot\Repository
 */
class HubspotFormRepository
{

    /**
     * Hubspot API client.
     *
     * @var Factory
     */
    protected $client;

    /**
     * HubspotFormRepository constructor.
     */
    public function __construct()
    {
        $this->client = new Factory();
    }

    /**
     * Get form elements from hubspot for selection in content element dropdown
     *
     * @param array &$configuration
     */
    public function getFormsForItemsProcFunc(array &$configuration)
    {
        $response = $this->client->forms()->all();
        $forms = $response->toArray();
        foreach ($forms as $form) {
            $fieldName = $form['name'];
            $value = $form['guid'];
            $configuration['items'][] = [$fieldName, $value];
        }
    }

    /**
     * Get form by guid from hubspot.
     *
     * @param string $guid
     * @return array
     */
    public function getFormForPreview(string $guid) : array
    {
        return $this->client->forms()->getById($guid)->toArray();
    }

    /**
     * Fetches all forms from hubspot and adds the guid as key.
     *
     * @return array
     */
    public function getAllFormsWithGuidAsKey() : array
    {
        $allForms = [];
        $forms = $this->client->forms()->all()->toArray();
        foreach ($forms as $form) {
            $allForms[$form['guid']] = $form;
        }
        return $allForms;
    }
}
