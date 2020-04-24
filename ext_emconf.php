<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Piwik - web analytics, new API',
    'description' => 'Adds Piwik JS-Code (http://piwik.org/) to your pages',
    'category' => 'fe',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Kay Strobach',
    'author_email' => 'kay.strobach@typo3.org',
    'author_company' => 'www.kay-strobach.de',
    'version' => '4.0.0-dev',
    'constraints' => [
        'depends' => ['typo3' => '9.0.0-9.99.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
];
