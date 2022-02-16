<?php

declare(strict_types=1);

namespace T3G\Hubspot\Service;

use Pixelant\Interest\ObjectManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var OutputInterface|null
     */
    protected $output = null;

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

    /**
     * Set an output for outputting information and errors.
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Log info. Output if verbose or very verbose.
     *
     * @param string $message
     * @param array $context
     */
    protected function logInfo(string $message, array $context = [])
    {
        $this->logger->info($message, $context);

        if ($this->output !== null && $this->output->isVeryVerbose()) {
            $this->output->writeln($message . ' ' . var_export($context, true));
        }

        if ($this->output !== null && $this->output->isVerbose()) {
            $this->output->writeln($message);
        }
    }

    /**
     * Log info. Output if verbose or very verbose.
     *
     * @param string $message
     * @param array $context
     */
    protected function logWarning(string $message, array $context = [])
    {
        $this->logger->warning($message, $context);

        if ($this->output !== null && $this->output->isVeryVerbose()) {
            $this->output->writeln($message . ' ' . var_export($context, true));
        }

        if ($this->output !== null && $this->output->isVerbose()) {
            $this->output->writeln($message);
        }
    }

    /**
     * Log error. Output if verbose or very verbose.
     *
     * @param string $message
     * @param array $context
     */
    protected function logError(string $message, array $context = [])
    {
        $this->logger->error($message, $context);

        if ($this->output !== null && $this->output->isVeryVerbose()) {
            $this->output->writeln($message . ' ' . var_export($context, true));
        }

        if ($this->output !== null) {
            $this->output->writeln($message);
        }
    }
}
