<?php
declare(strict_types = 1);


namespace T3G\Hubspot\Tests\Service\Form;


use T3G\Hubspot\Service\Form\ConverterService;

class ConverterServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function converterTestOneDimensionalContact()
    {
        $formData = [
            0 => [
                'identifier' => 'foo',
                'value' => 'bar',
                'hubspotTable' => 'contact',
                'hubspotProperty' => 'firstname',
            ],
            1 => [
                'identifier' => 'foo',
                'value' => 'bar',
                'hubspotTable' => 'contact',
                'hubspotProperty' => 'lastname',
            ],
        ];
        $expected = [
            'contact' => [
                [
                    [
                        'property' => 'firstname',
                        'value' => 'bar'
                    ],
                    [
                        'property' => 'lastname',
                        'value' => 'bar'
                    ]
                ]
            ]
        ];
        $converterService = new ConverterService();
        $result = $converterService->convertToHubspotFormat($formData);

        self::assertSame($expected, $result);
    }

    /**
     * @test
     * @return void
     */
    public function converterTestMultiDimensionalContact()
    {
        $formData = [
            0 => [
                'identifier' => 'foo',
                'value' => 'bar',
                'hubspotTable' => 'contact',
                'hubspotProperty' => '1.firstname',
            ],
            1 => [
                'identifier' => 'foo',
                'value' => 'bar',
                'hubspotTable' => 'contact',
                'hubspotProperty' => '1.lastname',
            ],
            2 => [
                'identifier' => 'foo',
                'value' => 'bar2',
                'hubspotTable' => 'contact',
                'hubspotProperty' => '2.firstname',
            ],
            3 => [
                'identifier' => 'foo',
                'value' => 'bar2',
                'hubspotTable' => 'contact',
                'hubspotProperty' => '2.lastname',
            ],
        ];
        $expected = [
            'contact' => [
                1 => [
                    [
                        'property' => 'firstname',
                        'value' => 'bar'
                    ],
                    [
                        'property' => 'lastname',
                        'value' => 'bar'
                    ]
                ],
                2 => [
                    [
                        'property' => 'firstname',
                        'value' => 'bar2'
                    ],
                    [
                        'property' => 'lastname',
                        'value' => 'bar2'
                    ]
                ]
            ]
        ];
        $converterService = new ConverterService();
        $result = $converterService->convertToHubspotFormat($formData);

        self::assertSame($expected, $result);
    }
}
