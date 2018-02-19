<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Requirement checker',
    'description' => '',
    'category' => 'be',
    'author' => 'Georg Ringer',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.0.0-9.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'GeorgRinger\\RequirementChecker\\' => 'Classes'
        ]
    ],
];
