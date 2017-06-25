<?php

require 'Payment.php';

class XMRPayment extends Payment
{
    const MONERO_BASE_CONVERSION = 1000000000000.0;
    public $paymentID;

    public function parseShapeshiftResponse($response)
    {
        if (is_string($response)) {
            try {
                $data = json_decode($response, true);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            $data = $response;
        }

        $this->toAddress = $data['sAddress'];
        $this->paymentID = $data['deposit'];
        return true;
    }

    public function send()
    {
        if (!$this->toAddress || !$this->paymentID || !$this->amount) {
            throw new Exception("XMR send: Missing parameters");
        }

        // Convert amount if it's still in decimal notation
        if (strpos($this->amount, '.')) {
            $this->amount = intval($this->amount * self::MONERO_BASE_CONVERSION);
        }

        $str_replace_from = [':address', ':amount', ':password', ':fromAddress', ':user', ':paymentId'];
        $str_replace_to = [$this->toAddress, $this->amount, $this->walletConfig['password'], $this->fromAddress, $this->walletConfig['user'], $this->paymentID];
        $command = str_replace($str_replace_from, $str_replace_to, $this->transferCommand);

        return $this->executeSend($command);
    }
}