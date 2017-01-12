<?php
declare (strict_types = 1);

namespace T3G\Hubspot\Tests\Service\Form;

use T3G\Hubspot\Service\Form\ConverterService;

class ConverterServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function converterTestSampleEltsStructure()
    {
        $formData = [
            0 =>
                [
                    'identifier' => 'text-1',
                    'value' => 'firstname1',
                    'hubspotTable' => 'contact',
                    'hubspotProperty' => 'company.1.contact.1.firstname',
                ],
            1 =>
                [
                    'identifier' => 'text-2',
                    'value' => 'lastname1',
                    'hubspotTable' => 'contact',
                    'hubspotProperty' => 'company.1.contact.1.lastname',
                ],
            2 =>
                [
                    'identifier' => 'text-3',
                    'value' => 'mail1@example.com',
                    'hubspotTable' => 'contact',
                    'hubspotProperty' => 'company.1.contact.1.email',
                ],
            3 =>
                [
                    'identifier' => 'text-4',
                    'value' => 'companyname1',
                    'hubspotTable' => 'company',
                    'hubspotProperty' => 'company.1.name',
                ],
            4 =>
                [
                    'identifier' => 'text-5',
                    'value' => 'companywebsite1.com',
                    'hubspotTable' => 'company',
                    'hubspotProperty' => 'company.1.domain',
                ],
            5 =>
                [
                    'identifier' => 'text-6',
                    'value' => 'firstname2',
                    'hubspotTable' => 'contact',
                    'hubspotProperty' => 'company.2.contact.1.firstname',
                ],
            6 =>
                [
                    'identifier' => 'text-7',
                    'value' => 'lastname2',
                    'hubspotTable' => 'contact',
                    'hubspotProperty' => 'company.2.contact.1.lastname',
                ],
            7 =>
                [
                    'identifier' => 'text-8',
                    'value' => 'mail2@example.com',
                    'hubspotTable' => 'contact',
                    'hubspotProperty' => 'company.2.contact.1.email',
                ],
            8 =>
                [
                    'identifier' => 'text-9',
                    'value' => 'companyname2',
                    'hubspotTable' => 'company',
                    'hubspotProperty' => 'company.2.name',
                ],
            9 =>
                [
                    'identifier' => 'text-10',
                    'value' => 'companywebsite2.com',
                    'hubspotTable' => 'company',
                    'hubspotProperty' => 'company.2.domain',
                ],
            10 =>
                [
                    'identifier' => 'hidden-1',
                    'value' => 'a173d7f9-b372-4c4b-86d4-ce28e2de4b55',
                    'hubspotTable' => 'deal',
                    'hubspotProperty' => 'stageId',
                ],
            11 =>
                [
                    'identifier' => 'hidden-2',
                    'value' => '7c78571d-7e79-4b1a-b2ce-64b96608d717',
                    'hubspotTable' => 'deal',
                    'hubspotProperty' => 'pipelineId',
                ],
            12 =>
                [
                    'identifier' => 'hidden-3',
                    'value' => '2000',
                    'hubspotTable' => 'deal',
                    'hubspotProperty' => 'amount',
                ],
            13 =>
                [
                    'identifier' => 'hidden-4',
                    'value' => 'ELTS',
                    'hubspotTable' => 'deal',
                    'hubspotProperty' => 'product_type',
                ],
            14 =>
                [
                    'identifier' => 'hidden-5',
                    'value' => 'ELTS 6.2',
                    'hubspotTable' => 'deal',
                    'hubspotProperty' => 'dealname',
                ],
        ];
        $expected = [
            'company' => [
                1 => [
                    'contact' => [
                        1 => [
                            [
                                'property' => 'firstname',
                                'value' => 'firstname1',
                            ],
                            [
                                'property' => 'lastname',
                                'value' => 'lastname1',
                            ],
                            [
                                'property' => 'email',
                                'value' => 'mail1@example.com',
                            ],
                        ],
                    ],
                    [
                        'property' => 'name',
                        'value' => 'companyname1',
                    ],
                    [
                        'property' => 'domain',
                        'value' => 'companywebsite1.com',
                    ],
                ],
                2 => [
                    [
                        'property' => 'name',
                        'value' => 'companyname2',
                    ],
                    [
                        'property' => 'domain',
                        'value' => 'companywebsite2.com',
                    ],
                    'contact' => [
                        1 => [
                            [
                                'property' => 'firstname',
                                'value' => 'firstname2',
                            ],
                            [
                                'property' => 'lastname',
                                'value' => 'lastname2',
                            ],
                            [
                                'property' => 'email',
                                'value' => 'mail2@example.com',
                            ],
                        ],
                    ],
                ],
            ],
            'deal' => [
                [
                    'property' => 'stageId',
                    'value' => 'a173d7f9-b372-4c4b-86d4-ce28e2de4b55',
                ],
                [
                    'property' => 'pipelineId',
                    'value' => '7c78571d-7e79-4b1a-b2ce-64b96608d717',
                ],
                [
                    'property' => 'amount',
                    'value' => '2000',
                ],
                [
                    'property' => 'product_type',
                    'value' => 'ELTS',
                ],
                [
                    'property' => 'dealname',
                    'value' => 'ELTS 6.2',
                ],
            ],
        ];
        $converterService = new ConverterService();
        $result = $converterService->convertToHubspotFormat($formData);

        self::assertEquals($expected, $result);
    }

}
