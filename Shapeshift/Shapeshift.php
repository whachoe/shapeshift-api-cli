<?php
namespace Shapeshift;

use Payment\Payment;

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
            $command = "curl -s -X POST -H \"Content-Type: application/json\" -d '{\"withdrawal\":\"{$to['address']}\", \"pair\":\"$pair\", \"returnAddress\":\"{$from['address']}\"}' {$this->baseUrl}/shift";
            $output = `$command`;
            try {
                echo "Shapeshift answer: $output\n";
                $data = json_decode($output, true);
            } catch (\Exception $e) {
                echo "Something went wrong while calling Shapeshift: {$e->getMessage()}\n";
                return false;
            }

            if (isset($data['error'])) {
                echo "Shapeshift error: {$data['error']}. Exiting\n";
                return false;
            }

            // Double check deposit type: Should match our from-wallet
            if (strtolower($data['depositType']) != $from['currency']) {
                echo "Wrong deposittype: Shapeshift: {$data['depositType']}. We: {$from['currency']}";
                return false;
            }

            $paymentProcessor = Payment::factory($from);
            $paymentProcessor->amount = $amountToShift;
            if ($paymentProcessor->parseShapeshiftResponse($data)) {
                return $paymentProcessor->send();
            }
        } else {
            echo "Amount to shift is too low: $amountToShift";
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
        $response = `$command`;

        $data = $this->catchError($response);
        if ($data)
            return isset($data['success']);

        return false;
    }

    private function catchError($response) {
        try {
            $data = json_decode($response, true);
        } catch (\Exception $e) {
            echo "Error getting MarketInfo: {$e->getMessage()}";
            return false;
        }

        if (isset($data['error'])) {
            echo "Shapeshift error: {$data['error']}. Exiting\n";
            return false;
        }

        return $data;
    }
}