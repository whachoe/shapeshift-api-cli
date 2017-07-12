<?php
namespace Shifters\Shapeshift;

use Payment\Payment;
use Pheanstalk\Pheanstalk;

class Shapeshift {
    private $baseUrl="https://shapeshift.io";


    public function getRate($pair)
    {
        $response = file_get_contents("{$this->baseUrl}/rate/$pair");

        return $this->catchError($response);
    }

    public function getMarketInfo($pair)
    {
        $response = file_get_contents("{$this->baseUrl}/marketinfo/$pair");

        return $this->catchError($response);
    }

    public function getLimit($pair)
    {
        $response = file_get_contents("{$this->baseUrl}/limit/$pair");

        return $this->catchError($response);
    }

    public function getStatusOfDeposit($address)
    {
        $response = file_get_contents("{$this->baseUrl}/txStat/{$address}");
        return $this->catchError($response);
    }

    /**
     * Checks with Shapeshift if both input and output are available
     *
     * @param $input
     * @param $output
     * @return bool
     */
    public function checkAvailability($input, $output)
    {
        $response = file_get_contents("{$this->baseUrl}/getcoins");
        $data = $this->catchError($response);
        if ($data)
            return ($data[strtoupper($input)]['status'] == 'available' && $data[strtoupper($output)]['status'] == 'available');
    }

    /**
     * url: shapeshift.io/validateAddress/[address]/[coinSymbol]
     * method: GET
     *
     * [address] the address that the user wishes to validate
     * [coinSymbol] the currency symbol of the coin
     *
     * Success Output:
     *
     *
     * {
     *   isvalid: [true / false],
     *   error: [(if isvalid is false, there will be an error message)]
     * }
     *
     *
     * isValid will either be true or false. If isvalid returns false, an error parameter will be present and will contain a descriptive error message.
     *
     */
    public function validateAddress($address, $currency)
    {
        $response = file_get_contents("{$this->baseUrl}/validateAddress/$address/$currency");
        $data = $this->catchError($response);

        if (isset($data['error'])) {
            echo "Invalid address: {$data['error']}";
            return false;
        }

        return $data['isvalid'];
    }

    public function orderInfo($orderId)
    {
        $response = file_get_contents("{$this->baseUrl}/orderInfo/$orderId");
        $data = $this->catchError($response);

        return $data;
    }

    /**
     * Call shapeshift and send funds
     *
     * @param $from
     * @param $to
     * @param $pair
     * @param $amountToShift
     * @return bool
     */
    function doShift($from, $to, $pair, $amountToShift, $minerFee=0)
    {
        if ($amountToShift > 0.0) {
            // First talk to shapeshift
            $payload = "{\"withdrawal\":\"{$to['address']}\", \"pair\":\"$pair\", \"returnAddress\":\"{$from['address']}\"}";
            $command = "curl -s -X POST -H \"Content-Type: application/json\" -d '$payload' {$this->baseUrl}/shift";
            logger("Shapeshift call: ".$command);
            $output = `$command`;
            logger("Shapeshift answer: $output");

            try {
                $data = json_decode($output, true);
            } catch (\Exception $e) {
                logger("Something went wrong while calling Shapeshift: {$e->getMessage()}");
                return false;
            }

            if (isset($data['error'])) {
                logger("Shapeshift error: {$data['error']}. Exiting");
                return false;
            }

            // Double check deposit type: Should match our from-wallet
            if (strtolower($data['depositType']) != $from['currency']) {
                logger("Wrong deposittype: Shapeshift: {$data['depositType']}. We: {$from['currency']}");
                return false;
            }

            $paymentProcessor = Payment::factory($from);
            $paymentProcessor->amount = $amountToShift;
            if ($paymentProcessor->parseShapeshiftResponse($data)) {
                sleep(2); // sleep a bit to be safe we can send the transaction

                // Schedule job to check order-status
                $queueclient = new Pheanstalk('127.0.0.1');
                $queueclient->putInTube('shapeshift_orderstatus', $output, Pheanstalk::DEFAULT_PRIORITY, 900); // wait 15 minutes before checking

                // Do the payment
                return $paymentProcessor->send();
            } else {
                logger("Shapeshift: Error in parsing shapeshift message");
                return false;
            }
        } else {
            logger("Amount to shift is too low: ".strval($amountToShift));
            return false;
        }

        return false;
    }

    /**
     * url: shapeshift.io/cancelpending
     * method: POST
     * data type: JSON
     * data required: address  = The deposit address associated with the pending transaction
     *
     * Example data : {address : "1HB5XMLmzFVj8ALj6mfBsbifRoD4miY36v"}
     *
     * Success Output:
     *
     * {  success  : " Pending Transaction cancelled "  }
     *
     * Error Output:
     *
     * {  error  : {errorMessage}  }
     */
    public function cancelPending($address)
    {
        $command = "curl -s -X POST -H \"Content-Type: application/json\" -d '{\"address\":\"{$address}\"}' {$this->baseUrl}/cancelpending";
        logger("Shapeshift cancelPending: $command");
        $response = `$command`;
        logger("Shapeshift cancelPending response: $response");

        $data = $this->catchError($response);
        if ($data)
            return isset($data['success']);

        return false;
    }

    public function checkAmount($paymentProcessor, $pair) {
        // Get wallet amount for input
        $walletAmount = $paymentProcessor->getWalletAmount();

        // First getting some info from shapeshift
        $marketInfo = $this->getMarketInfo($pair);

        if (!$marketInfo) {
            logger("No marketinfo found. Exiting.");
            exit(1);
        }

        $rate = $marketInfo['rate'];
        $limit = $paymentProcessor->toBase($marketInfo['limit']);
        $min = $paymentProcessor->toBase($marketInfo['minimum']);
        $minerFee = $paymentProcessor->toBase($marketInfo['minerFee']);

        if (!$rate) {
            logger("No rate for $pair found. Exiting");
            exit(1);
        }

        if (! "$limit" || ! "$min") {
            logger("No valid limit ($limit) or minimum ($min) found. Exiting.");
            exit(1);
        }

        // Make sure we have at least minimum to work with
        if ($walletAmount < $min) {
            logger("Not enough in wallet. Min: $min");
            exit();
        }

        $amountToShift = min($walletAmount*90/100, $limit);

        return $amountToShift;
    }

    private function catchError($response) {
        try {
            $data = json_decode($response, true);
        } catch (\Exception $e) {
            logger("Error parsing Shapeshift response: {$e->getMessage()}");
            return false;
        }

        if (isset($data['error'])) {
            logger("Shapeshift error: {$data['error']}. Exiting\n");
            return false;
        }

        return $data;
    }
}