<?php
/************************************************************************
 * Extension Manager/Repository config file for ext "hubspot".
 ************************************************************************/
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Hubspot Integration',
    'description' => 'Hubspot integration',
    'category' => 'extension',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.2.0-8.99.99'
        ),
        'conflicts' => array(
        ),
    ),
    'autoload' => array(
        'psr-4' => array(
            'T3G\\Hubspot\\' => 'Classes'
        ),
    ),
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Susanne Moog',
    'author_email' => 'susanne.moog@typo3.com',
    'author_company' => 'TYPO3 GmbH',
    'version' => '0.0.1',
);
