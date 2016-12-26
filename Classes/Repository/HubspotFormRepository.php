<?php
declare(strict_types = 1);


namespace T3G\Hubspot\Repository;


class HubspotFormRepository
{

    protected $client;

    public function __construct()
    {
        $this->client = new \SevenShores\Hubspot\Factory();
    }

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

    public function getFormForPreview(string $guid)
    {
        return $this->client->forms()->getById($guid)->toArray();
    }
}
