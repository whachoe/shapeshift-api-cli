<?php
namespace Payment;

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

    public function getWalletAmount()
    {
        $balance = 0;
        $output = parent::getWalletAmount();
        try {
            $data = json_decode($output, true);
            if (isset($data['result']) && isset($data['result']['balance'])) {
                $balance = $data['result']['balance'];
            }
        } catch (\Exception $e) {
            echo "XMR: Error getting wallet amount";
            exit();
        }

        return $balance;
    }

    public function getWalletAmountFriendly()
    {
        return $this->getWalletAmount() / self::MONERO_BASE_CONVERSION;
    }

    /**
     * Used to convert Shifter/Exchange output values into the value our Wallet understands
     * @param $amount
     * @return mixed
     */
    public static function toBase($amount)
    {
        return $amount * self::MONERO_BASE_CONVERSION;
    }
}