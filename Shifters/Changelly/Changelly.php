<?php

namespace Shifters\Changelly;

use Payment\Payment;
use Datto\JsonRpc\Http\Client as JsonRpcClient;
use Pheanstalk\Pheanstalk;

/**
 * Class Changelly
 * @package Shifters\Changelly
 * todo: Add order status tracking
 */
class Changelly
{
    private $baseUrl = 'https://api.changelly.com';
    private $jsonrpcClient;

    public function __construct($apiKey, $secret)
    {
        $options = array(
            'http' => array(
                'timeout' => 5
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
        $this->jsonrpcClient = new JsonRpcClient($this->baseUrl, null, $options, $secret);
        $this->jsonrpcClient->setHeader('api-key', $apiKey);
    }

    /**
     * Checks with Changelly if both input and output are available
     *
     * @param $input
     * @param $output
     * @return bool
     */
    public function checkAvailability($input, $output)
    {
        $this->jsonrpcClient->query($this->getId(), 'getCurrencies');

        $response = $this->jsonrpcClient->send();

        $data = $this->catchError($response);
        if ($data)
            return (in_array(strtolower($input), $data['result']) && in_array(strtolower($output), $data['result']));

        return false;
    }

    public function checkAmount(Payment $paymentProcessor, $pair)
    {
        $p = explode('_', $pair);
        $min = $this->getMinAmount($p[0], $p[1]);

        // Get wallet amount for input
        $walletAmount = $paymentProcessor->getWalletAmountFriendly();

        // Make sure we have at least minimum to work with
        if ($walletAmount < $min) {
            logger("Not enough in wallet. Min: $min Cur: $walletAmount");
            exit();
        }

        $amountToShift = $walletAmount*0.8; // leave a bit of money for insurance and costs

        return $paymentProcessor->toBase($amountToShift);
    }

    public function doShift($from, $to, $pair, $amountToShift, $minerFee=0)
    {
        if ($amountToShift > 0.0) {
            $data_to_send = [
                "from"      => $from['currency'],
                "to"        => $to['currency'],
                "address"   => $to['address'],
                // "extraId": null
            ];
            logger("Changelly call: ".var_export($data_to_send, true));

            $this->jsonrpcClient->query($this->getId(), 'generateAddress', $data_to_send);
            $response = $this->jsonrpcClient->send();
            logger("Changelly response: ".var_export($response, true));

            if (!isset($response['result'])) {
                logger("Changelly: Error parsing changelly message: ".var_export($response, true));
                return false;
            }

            $paymentProcessor = Payment::factory($from);
            $paymentProcessor->amount = $amountToShift;

            if ($paymentProcessor->parseChangellyResponse($response)) {
                // Schedule job to check order-status
                $queueclient = new Pheanstalk('127.0.0.1');
                $queueclient->putInTube('changelly_orderstatus', json_encode($response), Pheanstalk::DEFAULT_PRIORITY, 900); // wait 15 minutes before checking

                // Do the payment
                return $paymentProcessor->send();
            } else {
                logger("Changelly: Error in parsing changelly response");
                return false;
            }
        } else {
            logger("Amount to shift is too low: ".strval($amountToShift));
            return false;
        }

        return false;
    }

    public function getExchangeAmount($input, $output, $amount)
    {
        $this->jsonrpcClient->query($this->getId(), 'getExchangeAmount', ['from' => strtolower($input), 'to' => strtolower($output), 'amount' => $amount]);
        $response = $this->jsonrpcClient->send();
        $data = $this->catchError($response);

        return $data['result'];
    }

    private function getMinAmount($input, $output)
    {
        $this->jsonrpcClient->query($this->getId(), 'getMinAmount', ['from' => strtolower($input), 'to' => strtolower($output)]);

        $response = $this->jsonrpcClient->send();

        $data = $this->catchError($response);

        return $data['result'];
    }

    public function getTransactions($currency=null, $address=null, $extraId=null)
    {
        $dataToSend = [
            'limit' => 10,
            'offset' => 0,
        ];
        if ($currency)
            $dataToSend['currency'] = $currency;
        if ($address)
            $dataToSend['address'] = $address;
        if ($extraId)
            $dataToSend['extraId'] = $extraId;

        $this->jsonrpcClient->query($this->getId(), 'getTransactions', $dataToSend);
        $response = $this->jsonrpcClient->send();

        $data = $this->catchError($response);

        return $data['result'];
    }

    public function getStatus($transactionId)
    {
        $this->jsonrpcClient->query($this->getId(), 'getStatus', ['id' => $transactionId]);
        $response = $this->jsonrpcClient->send();

        $data = $this->catchError($response);

        return $data['result'];
    }

    private function getId()
    {
        $string = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
        $newstring = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx";
        for ($i = 0; $i < strlen($string); $i++) {
            $c = $string[$i];
            if ($c == 'x' || $c == 'y') {
                $r = (mt_rand() / mt_getrandmax()) * 16 | 0;
                $v = dechex(($c == 'x' ? $r : ($r & 0x3 | 0x8)));
            } else {
                $v = $c;
            }
            $newstring[$i] = $v;
        }

        return $newstring;
    }

    private function catchError($response)
    {
        if (isset($response['error'])) {
            logger("Changelly error: ".var_export($response, true). "Exiting\n");
            return false;
        }

        return $response;
    }
}
