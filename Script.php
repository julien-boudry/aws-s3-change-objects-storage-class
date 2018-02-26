<?php
    use Aws\Exception\AwsException;
    use Aws\S3\S3Client;
    use Aws\CommandPool;
    use Aws\CommandInterface;
    use Aws\ResultInterface;
    use GuzzleHttp\Promise\PromiseInterface;

    require 'vendor/autoload.php';
    require "config.php";

    ini_set("memory_limit","2048M");
    exec('ulimit -H -n 100000'); // try to enforce maximum number concurrent of stream
    exec('ulimit -S -n 3200'); // try to enforce maximum number concurrent of stream

    // Instantiate the client.
    $s3 = new S3Client($S3Credential);

    // Use the high-level iterators (returns ALL of your objects).
    $commandGenerator = function (S3Client $s3, string $bucket) use ($searchStorageClass, $putStorageClass) {
        $objects = $s3->getIterator('ListObjects', [
            'Bucket' => $bucket
        ]);

        foreach ($objects as $objectInt => $object) :
            if ($object['StorageClass'] === $searchStorageClass) :
                echo $object['Key'] . "\n";

                yield $s3->getCommand('CopyObject', [
                    'Bucket'     => $bucket,
                    'Key'        => $object['Key'],
                    'CopySource' => $bucket.'/'.$object['Key'],
                    'StorageClass' => $putStorageClass,
                    'TaggingDirective' => 'COPY',
                ]);
            endif;
        endforeach;
    };

    $commands = $commandGenerator($s3, $bucket);

    echo "Starting\n";

    $success = 0;
    $fail = 0;

    $pool = new CommandPool($s3, $commands, [
        'concurrency' => 1000000,
        'before' => function (CommandInterface $cmd, $iterKey) {
            echo "About to send: ".$cmd->toArray()['Key'] . "\n";
        },
        'fulfilled' => function (
            ResultInterface $result,
            $iterKey,
            PromiseInterface $aggregatePromise
        ) use(&$success,&$fail,$estimatedNumberOfObjects) {
            echo "Completed ".++$success."/".$estimatedNumberOfObjects." (".round($success/$estimatedNumberOfObjects*100,1)."%) : ".$result->get("ObjectURL")."\n";

        },
        // Invoke this function for each failed transfer.
        'rejected' => function (
            AwsException $reason,
            $iterKey,
            PromiseInterface $aggregatePromise
        ) {
            echo "Failed ".++$fail."/".$estimatedNumberOfObjects.": {$reason}\n";
        },
    ]);
    $promise = $pool->promise();
    $promise->wait();



