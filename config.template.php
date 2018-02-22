<?php

    $S3Credential = [
        'version'     => 'latest',
        'region'      => 'eu-west-1',
        'credentials' => [
            'key'    => '',
            'secret' => '',
        ],
    ];

    $bucket = '';

    $searchStorageClass = 'REDUCED_REDUNDANCY';
    $putStorageClass = 'STANDARD';

    $estimatedNumberOfObjects = 230;