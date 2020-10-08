<?php

return [
    'hubspot:contactsync' => [
        'class' => \T3G\Hubspot\Command\SynchronizeContactsCommand::class,
        'schedulable' => true,
    ],
];
