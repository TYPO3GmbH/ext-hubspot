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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line controller for synchronizing Hubspot contacts
 */
class SynchronizeContactsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setDescription('Synchronize HubSpot contacts.')
            ->setHelp('This command synchronizes HubSpot contact records with TYPO3 frontend users.')
            ->addOption('limit', 'l', InputArgument::OPTIONAL, 'Max records to synchronize', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('test');
    }


}
