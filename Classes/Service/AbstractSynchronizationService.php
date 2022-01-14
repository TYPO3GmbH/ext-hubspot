<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service;

use Pixelant\Interest\ObjectManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use T3G\Hubspot\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractSynchronizationService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array Default values for TypoScript configuration
     */
    protected $defaultConfiguration = [];

    /**
     * @var array Configuration array
     */
    protected $configuration = [];

    /**
     * @var int To track active configuration PID
     */
    protected $activeConfigurationPageId = 0;

    /**
     * Set default TypoScript values
     *
     * @param array $defaultConfiguration
     */
    public function setDefaultConfiguration(array $defaultConfiguration)
    {
        $this->defaultConfiguration = $defaultConfiguration;
    }

    /**
     * Fetches TypoScript configuration from page and sets $this->configuration to what's in module.tx_hubspot
     *
     * Also configures repository defaults
     *
     * @param int $pageId
     */
    public function configureForPageId(int $pageId)
    {
        if ($this->activeConfigurationPageId === $pageId) {
            return;
        }

        /** @var BackendConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(BackendConfigurationManager::class);

        $configurationManager->setCurrentPageId($pageId);

        $configuration = $configurationManager->getTypoScriptSetup()['module.']['tx_hubspot.'] ?? [];

        $this->configuration = array_merge_recursive($this->defaultConfiguration, $configuration);

        $this->activeConfigurationPageId = $pageId;

        $this->configureRepositoryDefaults();
    }
}
