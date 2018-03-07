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

    // Only use for progress estimation in logs
    $estimatedNumberOfObjects = 230;

    $start = null;
    $end = null;