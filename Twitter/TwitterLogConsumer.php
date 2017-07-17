<?php
include_once "vendor/autoload.php";
include_once "config.php";
include_once "lib.php";

use Pheanstalk\Pheanstalk;

class TwitterLogConsumer
{
    var $currencies;

    public function __construct()
    {
        global $wallets;

        $this->client = new Pheanstalk('127.0.0.1');
        $this->currencies = array_keys($wallets);
        $this->currencies[] = 'USD';

        array_walk($this->currencies, "strtoupper");
    }

    public function listen()
    {
        $this->client->watch('vickiqueue');

        while ($job = $this->client->reserve()) { // Do this forever... so it's always listening.
            $message = json_decode($job->getData(), true); // Decode the message

            $this->process($message);

            $this->client->delete($job); // Delete anyway. You could burry it, meaning it gets re-tried later.
        }
    }

    public function process($msg)
    {
        if ($msg['text']) {
            $text = urldecode($msg['text']);
            $input = '';
            $output = '';

            if (strpos($text, "long") > 0) {
                $matches = [];
                if (preg_match('/([A-Z]{6})/', $text, $matches)) {
                    $output = substr($matches[1], 0, 3);
                    $input = substr($matches[1], 3, 3);
                }
            } elseif (strpos($text, 'short') > 0) {
                $matches = [];
                if (preg_match('/([A-Z]{6})/', $text, $matches)) {
                    $input = substr($matches[1], 0, 3);
                    $output = substr($matches[1], 3, 3);
                }
            }

            if (in_array($input, $this->currencies) && in_array($output, $this->currencies)) {
                $command = "php do_a_shift.php --input={$input} --output=$output";
                echo date("c")."\tTwitter consumer running: " . $command . "\n";
                `$command`;
            }
        }
    }
}

$consumer = new TwitterLogConsumer();
$consumer->listen();