<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command line controller for synchronizing Hubspot contacts
 */
class SynchronizeContactsCommand extends Command
{
    /**
     * @var ContactSynchronizationService
     */
    protected $synchronizationService = null;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->synchronizationService = GeneralUtility::makeInstance(ContactSynchronizationService::class);
    }

    protected function configure()
    {
        $this
            ->setDescription('Synchronize HubSpot contacts.')
            ->setHelp('This command synchronizes HubSpot contact records with TYPO3 frontend users.')
            ->addOption(
                'default-pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'Default PID for storage and TypoScript settings'
            )
            ->addOption(
                'limit-to-pids',
                's',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Array of PIDs to search within. Default is to ignore PID.',
                []
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Max records to synchronize',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        CompatibilityUtility::initializeBackendAuthentication();

        $configuration = [
            'persistence.' => [
                'synchronize.' => [
                    'defaultPid' => $input->getOption('default-pid')
                ]
            ],
            'settings.' => [
                'synchronize.' => [
                    'limitToPids' => implode(',', $input->getOption('limit-to-pids')),
                    'limit' => $input->getOption('limit')
                ]
            ]
        ];

        $this->synchronizationService->setDefaultConfiguration($configuration);

        $this->synchronizationService->synchronize();
    }
}
