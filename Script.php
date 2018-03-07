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
    exec('ulimit -S -n 100000'); // try to enforce maximum number concurrent of stream

    // Instantiate the client.
    $s3 = new S3Client($S3Credential);

    $start_time = null;
    $success = 0;
    $alreadyDone = 0;
    $fail = 0;

    // Use the high-level iterators (returns ALL of your objects).
    $commandGenerator = function (S3Client $s3, string $bucket) use (&$searchStorageClass, &$putStorageClass, &$start_time, &$alreadyDone, &$start, &$end) {
        $objects = $s3->getIterator('ListObjects', [
            'Bucket' => $bucket
        ]);

        $i = 0;
        foreach ($objects as $objectInt => $object) :

            if ($start !== null || $end !== null) :
                if (++$i < $start) :
                    ++$alreadyDone;
                    continue;
                elseif ($end !== null && $i > $end) :
                    return;
                endif;
            endif;

            if ($object['StorageClass'] === $searchStorageClass) :
                if ($start_time === null) :
                    $start_time = time();
                endif;

                yield $s3->getCommand('CopyObject', [
                    'Bucket'     => $bucket,
                    'Key'        => $object['Key'],
                    'CopySource' => $bucket.'/'.$object['Key'],
                    'StorageClass' => $putStorageClass,
                    'TaggingDirective' => 'COPY',
                ]);
            else :
                ++$alreadyDone;
            endif;
        endforeach;
    };

    $commands = $commandGenerator($s3, $bucket);

    echo "Starting\n";

    $pool = new CommandPool($s3, $commands, [
        'concurrency' => 1000000,
        'fulfilled' => function (
            ResultInterface $result,
            $iterKey,
            PromiseInterface $aggregatePromise
        ) use(&$success,&$fail,&$estimatedNumberOfObjects,&$start_time, &$alreadyDone) {

            if ((++$success % 1000) === 0) :
                echo " Time: " . ($perf = time() - $start_time) ."s - ".round($success/($perf/60),0)."q/m | Completed ".($done = $success + $alreadyDone)."/".$estimatedNumberOfObjects." (".round($done/$estimatedNumberOfObjects*100,1)."%) : ".$result->get("ObjectURL")."\n";
            endif;

        },
        // Invoke this function for each failed transfer.
        'rejected' => function (
            AwsException $reason,
            $iterKey,
            PromiseInterface $aggregatePromise
        ) use(&$success,&$fail,&$estimatedNumberOfObjects) {
            echo "Failed ".++$fail."/".$estimatedNumberOfObjects.": {$reason}\n";
        },
    ]);
    $promise = $pool->promise();
    $promise->wait();



