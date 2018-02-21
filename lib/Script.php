<?php
    use Aws\S3\S3Client;
    use Aws\CommandPool;

    require '../vendor/autoload.php';
    require "..".DIRECTORY_SEPARATOR."config.php";

    ini_set("memory_limit","8192M");

    // Instantiate the client.
    $s3 = new S3Client($S3Credential);

    $commands = [];

    // Use the high-level iterators (returns ALL of your objects).
    $objects = $s3->getIterator('ListObjects', [
        'Bucket' => $bucket
    ]);

    echo "Keys retrieved!\n";
    foreach ($objects as $objectInt => $object) :
        if ($object['StorageClass'] === 'REDUCED_REDUNDANCY') :
            echo $object['Key'] . "\n";

            $batch = 

            $commands[] = $s3->getCommand('CopyObject', [
                'Bucket'     => 'qobuz-eu-west-1-bi',
                'Key'        => 'testStorageClass/'.$object['Key'],
                'CopySource' => $bucket.'/'.$object['Key'],
                'StorageClass' => 'STANDARD',
                'TaggingDirective' => 'COPY',
            ]);
        endif;
    endforeach;

    echo "\n" . count($commands);

    $pool = new CommandPool($s3, $commands, ['concurrency' => 1000000]);
    unset($commands);
    $promise = $pool->promise();
    $promise->wait();



