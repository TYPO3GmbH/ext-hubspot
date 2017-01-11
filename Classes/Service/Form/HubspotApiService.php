<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Service\Form;

use GuzzleHttp\Client;

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
        $this->url = getenv('HUBSPOT_MIDDLEWARE_BASEURL') . '?whatever&humweeekey=' . getenv('HUBSPOT_MIDDLEWARE_APIKEY');
    }

    /**
     * @param array $data
     */
    public function sendToHubspot(array $data)
    {
        $converterService = new ConverterService();
        $hubspotData = $converterService->convertToHubspotFormat($data);
        $options = [
            'json' => $hubspotData
        ];
        $this->client->post($this->url, $options);
    }
}
