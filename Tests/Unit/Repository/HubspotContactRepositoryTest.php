<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Tests\Unit\Repository;

use T3G\Hubspot\Domain\Repository\Hubspot\ContactRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HubspotContactRepositoryTest extends UnitTestCase
{
    /**
     * @var ContactRepository
     */
    public $subject = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ContactRepository();
    }

    /**
     * @test
     */
    public function convertAssociativeArrayToHubspotPropertiesReturnsCorrectData()
    {
        $input = [
            'property1' => 'value1',
            'property2' => 'value2',
        ];

        $expected = [
            [
                'property' => 'property1',
                'value' => 'value1'
            ],            [
                'property' => 'property2',
                'value' => 'value2'
            ],
        ];

        $this->assertEquals(
            $expected,
            $this->subject->convertAssociativeArrayToHubspotProperties($input)
        );
    }
}
