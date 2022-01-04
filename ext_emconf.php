<?php

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Hubspot Integration',
    'description' => 'Hubspot integration',
    'category' => 'extension',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-11.5.99'
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'T3G\\Hubspot\\' => 'Classes'
        ],
    ],
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Susanne Moog',
    'author_email' => 'susanne.moog@typo3.com',
    'author_company' => 'TYPO3 GmbH',
    'version' => '2.1.1',
];
