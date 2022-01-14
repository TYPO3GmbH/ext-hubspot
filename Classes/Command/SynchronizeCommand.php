<?php

declare(strict_types=1);

namespace T3G\Hubspot\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use T3G\Hubspot\Service\ContactSynchronizationService;
use T3G\Hubspot\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SynchronizeCommand extends Command
{
    const SYNCHRONIZATION_TYPES = [
        'contacts',
        'customobjects',
    ];

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setDescription('Synchronize HubSpot contacts.')
            ->setHelp('This command synchronizes HubSpot contact records with TYPO3 frontend users.')
            ->addArgument(
                'types',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'The synchronization types to run. If none, all types are used.',
                self::SYNCHRONIZATION_TYPES
            )
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
        $this->input = $input;
        $this->output = $output;

        CompatibilityUtility::initializeBackendAuthentication();

        foreach ($input->getArgument('types') as $type) {
            $type = strtolower($type);
            $methodName = 'synchronize' . ucfirst($type);

            if (!method_exists($this, $methodName)) {
                throw new InvalidArgumentException(
                    'Unknown synchronization type: "' . $type . "'",
                    1642171905258
                );
            }

            $this->$methodName();
        }
    }

    /**
     * Synchronize TYPO3 Frontend Users with Hubspot Contacts.
     */
    protected function synchronizeContacts()
    {
        $configuration = [
            'persistence.' => [
                'synchronize.' => [
                    'defaultPid' => $this->input->getOption('default-pid')
                ]
            ],
            'settings.' => [
                'synchronize.' => [
                    'limitToPids' => implode(',', $this->input->getOption('limit-to-pids')),
                    'limit' => $this->input->getOption('limit')
                ]
            ]
        ];

        $synchronizationService = GeneralUtility::makeInstance(ContactSynchronizationService::class);

        $synchronizationService->setDefaultConfiguration($configuration);

        $synchronizationService->synchronize();
    }

    /**
     * Synchronize any record to Hubspot Custom Objects.
     */
    protected function synchronizeCustomObjects()
    {
        $configuration = [
            'persistence.' => [
                'synchronizeCustomObjects.' => [
                    'defaultPid' => $this->input->getOption('default-pid')
                ]
            ],
            'settings.' => [
                'synchronizeCustomObjects.' => [
                    'limitToPids' => implode(',', $this->input->getOption('limit-to-pids')),
                    'limit' => $this->input->getOption('limit')
                ]
            ]
        ];
    }
}
