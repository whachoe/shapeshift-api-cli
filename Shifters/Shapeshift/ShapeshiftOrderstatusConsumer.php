<?php
namespace Shifters\Shapeshift;

include_once "vendor/autoload.php";
include_once "config.php";
include_once "lib.php";

use Pheanstalk\Pheanstalk;

class ShapeshiftOrderstatusConsumer {
    public function __construct()
    {
        $this->client = new Pheanstalk('127.0.0.1');
    }

    public function listen()
    {
        $this->client->watch('shapeshift_orderstatus');

        // Do this forever... so it's always listening.
        while ($job = $this->client->reserve()) {
            $message = json_decode($job->getData(), true);

            if ($this->process($message))
                $this->client->delete($job);
            else {
                // Retry 3 times, after 3 retries: bury the job. A separate process will pick up those jobs for further inspection by a human
                if (isset($message['queuetimes']) && $message['queuetimes'] == 3) {
                    logger(json_encode($message), LOGFILE_FAILED_ORDERS);
                    $this->client->bury($job);
                } else {
                    $message['queuetimes'] = isset($message['queuetimes']) ? $message['queuetimes'] + 1 : 1;
                    $this->client->putInTube('shapeshift_orderstatus', json_encode($message), Pheanstalk::DEFAULT_PRIORITY, 900); // wait 15 minutes before checking
                    $this->client->delete($job);
                }
            }
        }
    }

    public function process($msg)
    {
        // Check orderstatus
        $shifter = new Shapeshift();
        $statusObj = $shifter->orderInfo($msg['orderId']);

        if (!$statusObj || isset($statusObj['error']))
            return false;

        return $statusObj['status'] == "complete";
    }
}

$consumer = new ShapeshiftOrderstatusConsumer();
$consumer->listen();