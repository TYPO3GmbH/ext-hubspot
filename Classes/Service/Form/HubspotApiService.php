<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Service\Form;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class HubspotApiService
 */
class HubspotApiService
{

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $url = '';

    /**
     * HubspotApiService constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->url = getenv('HUBSPOT_MIDDLEWARE_BASEURL') . '/api/genericFormData?humweeekey=' . getenv('HUBSPOT_FORM_FRAMEWORK_HUMWEEE_KEY');
    }

    /**
     * @param array $data
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function sendToHubspot(array $data)
    {
        $converterService = new ConverterService();
        $hubspotData = $converterService->convertToHubspotFormat($data);
        $options = [
            'json' => $hubspotData
        ];
        try {
            $this->client->post($this->url, $options);
        } catch (ClientException $exception) {
            // @todo we need a logger here!
            // this happens if e.g. a form is sent from stage.typo3.com
            if (strpos($exception->getMessage(), '401 Unauthorized') === false){
                throw $exception;
            }
        }
    }
}
