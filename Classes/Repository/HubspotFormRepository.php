<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Repository;

use SevenShores\Hubspot\Factory;

/**
 * Repository for fetching data from hubspot via API
 *
 */
class HubspotFormRepository
{
    /**
     * Hubspot API client.
     *
     * @var Factory
     */
    protected $factory;

    /**
     * HubspotFormRepository constructor.
     */
    public function __construct()
    {
        $this->factory = Factory::create(getenv('APP_HUBSPOT_SECRET'));
    }

    /**
     * Get form elements from hubspot for selection in content element dropdown
     *
     * @param array &$configuration
     */
    public function getFormsForItemsProcFunc(array &$configuration): void
    {
        foreach ($this->factory->forms()->all()->toArray() as $form) {
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
    public function getFormForPreview(string $guid): array
    {
        return $this->factory->forms()->getById($guid)->toArray();
    }

    /**
     * Fetches all forms from hubspot and adds the guid as key.
     *
     * @return array
     */
    public function getAllFormsWithGuidAsKey(): array
    {
        $allForms = [];
        foreach ($this->factory->forms()->all()->toArray() as $form) {
            $allForms[$form['guid']] = $form;
        }
        return $allForms;
    }
}
