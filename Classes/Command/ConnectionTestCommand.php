<?php

declare(strict_types=1);

namespace T3G\Hubspot\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use T3G\Hubspot\Domain\Repository\Hubspot\ContactRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConnectionTestCommand extends Command
{
    protected function configure()
    {
        $this
            ->setDescription('Test Hubspot Connection.')
            ->setHelp('Tests the API connection to Hubspot.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contactRepository = GeneralUtility::makeInstance(ContactRepository::class);

        $output->writeln(var_export($contactRepository->statistics(), true));
    }
}
