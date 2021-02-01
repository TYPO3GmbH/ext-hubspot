<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockBuilder;
use T3G\Hubspot\Configuration\BackendConfigurationManager;
use T3G\Hubspot\Repository\FrontendUserRepository;
use T3G\Hubspot\Repository\HubspotContactRepository;
use T3G\Hubspot\Service\ContactSynchronizationService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test for Service/ContactSynchronizationService
 */
class ContactSynchronizationServiceTest extends UnitTestCase
{
    protected const EXAMPLE_FRONTEND_USER_DATA = [
        'uid' => 1,
        'tstamp' => 1602791890,
        'first_name' => 'Adam',
        'zip' => '1234',
        'hubspot_id' => 3234574,
        'hubspot_sync_timestamp' => 1602791891,
        'hubspot_sync_pass' => 10,
        'hubspot_created_timestamp' => 1602786169553,
    ];

    protected const EXAMPLE_HUBSPOT_CONTACT_DATA = [
        'vid' => 3234574,
        'canonical-vid' => 3234574,
        'merged-vids' => [
        ],
        'portal-id' => 62515,
        'is-contact' => true,
        'properties' => [
            'zip' => [
                'value' => '02139',
                'versions' => [
                    0 => [
                        'value' => '02139',
                        'source-type' => 'API',
                        'source-id' => null,
                        'source-label' => null,
                        'timestamp' => 1484026585538,
                        'selected' => false,
                    ],
                ],
            ],
            'firstname' => [
                'value' => 'Codey',
                'versions' => [
                    0 => [
                        'value' => 'Codey',
                        'source-type' => 'BATCH_UPDATE',
                        'source-id' => null,
                        'source-label' => null,
                        'timestamp' => 1484858430193,
                        'selected' => false,
                    ],
                    1 => [
                        'value' => 'HubSpot',
                        'source-type' => 'API',
                        'source-id' => null,
                        'source-label' => null,
                        'timestamp' => 1484857783087,
                        'selected' => false,
                    ],
                    2 => [
                        'value' => 'Adrian',
                        'source-type' => 'API',
                        'source-id' => null,
                        'source-label' => null,
                        'timestamp' => 1484026585536,
                        'selected' => false,
                    ],
                ],
            ],
        ],
        'identity-profiles' => [
            0 => [
                'vid' => 3234574,
                'saved-at-timestamp' => 1484026585613,
                'deleted-changed-timestamp' => 0,
                'identities' => [
                    0 => [
                        'type' => 'EMAIL',
                        'value' => 'testingapis@hubspot.com',
                        'timestamp' => 1484026585538,
                        'is-primary' => true,
                    ],
                    1 => [
                        'type' => 'LEAD_GUID',
                        'value' => '4b11f8af-50d9-4665-9c43-bb2fc46e3a80',
                        'timestamp' => 1484026585610,
                    ],
                ],
            ],
        ],
    ];

    /**
     * @var ContactSynchronizationService
     */
    protected $subject;

    /**
     * @var MockBuilder|ContactSynchronizationService
     */
    protected $subjectMockBuilder;

    protected $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        $signalSlotDispatcherMock = $this->createMock(Dispatcher::class);

        $this->subject = new ContactSynchronizationService(
            null,
            null,
            $signalSlotDispatcherMock
        );

        $this->subjectMockBuilder = $this->getMockBuilder(ContactSynchronizationService::class)
            ->setConstructorArgs([
                null,
                null,
                $signalSlotDispatcherMock
            ]);

        $mockConfigurationManager = $this->getAccessibleMock(
            BackendConfigurationManager::class,
            ['getTypoScriptSetup', 'setCurrentPageId'],
            [],
            '',
            false
        );

        $mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();

        $mockConfigurationManager->_set('typoScriptService', $mockTypoScriptService);

        GeneralUtility::setSingletonInstance(
            BackendConfigurationManager::class,
            $mockConfigurationManager
        );

        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager
            ->method('get')
            ->willReturn($this->returnValue($mockConfigurationManager));

        GeneralUtility::setSingletonInstance(ObjectManager::class, $mockObjectManager);
    }

    /**
     * Sets the configuration within $this->subject
     *
     * @param array $configuration
     */
    protected function setConfiguration(array $configuration)
    {
        $this->setConfigurationOnObject($configuration, $this->subject);
    }

    /**
     * Sets the configuration within supplied object
     *
     * @param array $configuration
     * @param ContactSynchronizationService $object
     */
    protected function setConfigurationOnObject(array $configuration, ContactSynchronizationService $object)
    {
        if (!isset($configuration['persistence.']['synchronize.']['storagePid'])) {
            $configuration['persistence.']['synchronize.']['storagePid'] = 0;
        }

        if (!isset($configuration['synchronize.']['limit'])) {
            $configuration['synchronize.']['limit'] = 10;
        }

        $object->setDefaultConfiguration($configuration);
        $object->configureForPageId(1);
    }

    /**
     * @test
     */
    public function compareAndUpdateFrontendUserAndHubspotContactCorrectlyCreatesNewHubspotContact()
    {
        $frontendUserData = self::EXAMPLE_FRONTEND_USER_DATA;
        $frontendUserData['hubspot_id'] = 0;

        $subjectMock = $this->subjectMockBuilder
            ->setMethods(['addFrontendUserToHubspot'])
            ->getMock();

        $this->setConfigurationOnObject(
            [
                'settings.' => [
                    'synchronize.' => [
                        'createNewInHubspot' => 1,
                    ],
                ],
            ],
            $subjectMock
        );

        $subjectMock
            ->expects($this->once())
            ->method('addFrontendUserToHubspot')
            ->with($this->equalTo($frontendUserData));

        $subjectMock->compareAndUpdateFrontendUserAndHubspotContact($frontendUserData);
    }

    /**
     * @test
     */
    public function compareAndUpdateFrontendUserAndHubspotContactCorrectlyUpdatesProperties()
    {
        $frontendUserData1 = self::EXAMPLE_FRONTEND_USER_DATA;
        $frontendUserData2 = self::EXAMPLE_FRONTEND_USER_DATA;
        $frontendUserData2['tstamp'] = time(); // Very recent

        $frontendUserUpdateArguments1 = [
            1,
            [
                'zip' => '02139'
            ]
        ];
        $frontendUserUpdateArguments2 = [
            1,
           [
                'zip' => '02139'
           ]
        ];

        $hubspotContactData1 = self::EXAMPLE_HUBSPOT_CONTACT_DATA;
        $hubspotContactData2 = self::EXAMPLE_HUBSPOT_CONTACT_DATA;
        $hubspotContactData2['properties']['zip']['versions'][0]['timestamp'] = (time() + 10) * 1000; // Even more recent

        $hubspotUpdateArguments1 = [
            3234574,
            [
                'firstname' => 'Adam',
                'zip' => '1234'
            ]
        ];
        $hubspotUpdateArguments2 = [
            3234574,
            [
                'firstname' => 'Adam'
            ]
        ];

        $hubspotContactRepositoryMock = $this->createMock(HubspotContactRepository::class);

        $hubspotContactRepositoryMock
            ->method('findByIdentifier')
            ->willReturnOnConsecutiveCalls(
                $hubspotContactData1,
                $hubspotContactData2
            );

        $hubspotContactRepositoryMock
            ->expects($this->exactly(2))
            ->method('update')
            ->withConsecutive(
                $hubspotUpdateArguments1,
                $hubspotUpdateArguments2
            );

        $frontendUserRepositoryMock = $this->createMock(FrontendUserRepository::class);

        $frontendUserRepositoryMock
            ->expects($this->once())
            ->method('update')
            ->withConsecutive(
                // $frontendUserUpdateArguments2, isn't called because it's empty
                $frontendUserUpdateArguments2
            );

        $this->subjectMockBuilder->setConstructorArgs([
            $hubspotContactRepositoryMock,
            $frontendUserRepositoryMock,
            $this->createMock(Dispatcher::class)
        ]);

        $this->subjectMockBuilder->setMethods(null);

        $subjectMock = $this->subjectMockBuilder->getMock();

        $this->setConfigurationOnObject([
            'settings.' => [
                'synchronize.' => [
                    'toHubspot.' => [
                        'firstname.' => [
                            'field' => 'first_name'
                        ],
                        'zip.' => [
                            'field' => 'zip'
                        ],
                    ],
                    'toFrontendUser.' => [
                        'first_name.' => [
                            'field' => 'firstname'
                        ],
                        'zip.' => [
                            'field' => 'zip'
                        ],
                    ]
                ],
            ],
        ], $subjectMock);

        $subjectMock->compareAndUpdateFrontendUserAndHubspotContact($frontendUserData1);
        $subjectMock->compareAndUpdateFrontendUserAndHubspotContact($frontendUserData2);
    }

    /**
     * @test
     */
    public function mapFrontendUserToHubspotContactPropertiesSetsProperty()
    {
        $this->setConfiguration([
            'settings.' => [
                'synchronize.' => [
                    'toHubspot.' => [
                        'firstname.' => [
                            'field' => 'first_name'
                        ],
                    ],
                    'toFrontendUser.' => [
                        'first_name.' => [
                            'field' => 'firstname'
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->subject->mapFrontendUserToHubspotContactProperties(self::EXAMPLE_FRONTEND_USER_DATA);

        $this->assertArrayHasKey(
            'firstname',
            $result
        );

        $this->assertEquals(
            'Adam',
            $result['firstname']
        );
    }

    /**
     * @test
     */
    public function mapHubspotContactToFrontendUserPropertiesSetsProperty()
    {
        $this->setConfiguration([
            'settings.' => [
                'synchronize.' => [
                    'toFrontendUser.' => [
                        'first_name.' => [
                            'field' => 'firstname'
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->subject->mapHubspotContactToFrontendUserProperties(self::EXAMPLE_HUBSPOT_CONTACT_DATA);

        $this->assertArrayHasKey(
            'first_name',
            $result
        );

        $this->assertEquals(
            'Codey',
            $result['first_name']
        );
    }

    /**
     * @test
     */
    public function mapHubspotContactToFrontendUserPropertiesSetsEmail()
    {
        $this->setConfiguration([
            'settings.' => [
                'synchronize.' => [
                    'toFrontendUser.' => [
                        'email.' => [
                            'field' => 'email'
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->subject->mapHubspotContactToFrontendUserProperties(self::EXAMPLE_HUBSPOT_CONTACT_DATA);

        $this->assertArrayHasKey(
            'email',
            $result
        );

        $this->assertEquals(
            'testingapis@hubspot.com',
            $result['email']
        );
    }
}
