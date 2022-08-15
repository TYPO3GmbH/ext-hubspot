<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'hubspot:sync' => [
        'class' => \T3G\Hubspot\Command\SynchronizeCommand::class,
        'schedulable' => true,
    ],
];
