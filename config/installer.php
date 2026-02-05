<?php


return [
    /*
    |--------------------------------------------------------------------------
    | Server/PHP Requirements
    |--------------------------------------------------------------------------
    |
    */
    'core' => [
        'minPhpVersion' => '8.1', // used in detached.php as well
    ],

    'requirements' => [
        'php' => [
            'json',
            'ctype',
            'filter',
            'hash',
            'mbstring',
            'openssl',
            'session',
            'tokenizer',
            'fileinfo',
            'date',
            'pcre',
            'spl',
            'dom',
            'libxml',
            'xml',
            'phar',
            'xmlwriter',
            'curl',
            'pdo_mysql'
        ],

        'apache' => [
            'mod_rewrite',
        ],

        'recommended' => [
            'php' => [
                'zip',
            ],
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | Folders Permissions
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel folders permissions, if your application
    | requires more permissions just add them to the array list bellow.
    |
    */
    'permissions' => [
        'storage/app/'       => '755',
        'storage/framework/' => '755',
        'storage/logs/'      => '755',
        'bootstrap/cache/'   => '755',
    ],
];
