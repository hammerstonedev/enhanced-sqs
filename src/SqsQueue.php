<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\EnhancedSqs;

use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Arr;

class SqsQueue extends \Illuminate\Queue\SqsQueue
{
    /**
     * @var int The maximum number of seconds a job can be delayed for.
     */
    public $maxDelaySeconds = 60 * 15;

    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (!is_null($response['Messages']) && count($response['Messages']) > 0) {
            $message = $response['Messages'][0];

            if ($this->hasRemainingDelay($message)) {
                return $this->handleRemainingDelay($queue, $message);
            }

            // Default behavior from the parent class.
            return new SqsJob(
                $this->container, $this->sqs, $message, $this->connectionName, $queue
            );
        }
    }

    protected function hasRemainingDelay($message)
    {
        $body = json_decode($message['Body'], true);

        return Arr::get($body, 'delayUntil', 0) > time();
    }

    protected function handleRemainingDelay($queue, $message)
    {
        $payload = json_decode($message['Body'], true);
        $delaySeconds = Arr::get($payload, 'delayUntil') - time();

        $messageId = $this->sqs->sendMessage([
            'QueueUrl' => $this->getQueue($queue),
            'MessageBody' => $message['Body'],
            'DelaySeconds' => min($delaySeconds, $this->maxDelaySeconds),
        ])->get('MessageId');

        if ($messageId) {
            $this->sqs->deleteMessage([
                'QueueUrl' => $this->getQueue($queue),
                'ReceiptHandle' => $message['ReceiptHandle'],
            ]);
        }
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        $delaySeconds = $this->secondsUntil($delay);

        // If it's under the limit, just defer to the parent.
        if ($delaySeconds <= $this->maxDelaySeconds) {
            return parent::later($delay, $job, $data, $queue);
        }

        // Copied directly from the parent class.
        $payload = $this->createPayload($job, $queue ?: $this->default, $data);

        $payload = json_decode($payload, true);

        // Use a timestamp instead of seconds because we don't know how
        // long it will sit in the queue waiting to be processed.
        $payload['delayUntil'] = time() + $this->secondsUntil($delay);

        // Repeat the json_encode flags of the parent class.
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Delay for as long as we possibly can, but as short as we need.
        $delaySeconds = min($delaySeconds, $this->maxDelaySeconds);

        return $this->enqueueUsing(
            $job,
            $payload,
            $queue,
            $delaySeconds,
            function ($payload, $queue, $delay) {
                return $this->sqs->sendMessage([
                    'QueueUrl' => $this->getQueue($queue),
                    'MessageBody' => $payload,
                    'DelaySeconds' => $delay,
                ])->get('MessageId');
            }
        );
    }
}
