<?php
namespace Payment;

class LTCPayment extends Payment
{
    private $minerFee;

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

        $this->toAddress = $data['deposit'];
    }

    public function send()
    {
        if (!$this->toAddress || !$this->amount) {
            throw new Exception("LTC send: Missing parameters");
        }

        $str_replace_from = [':address', ':amount'];
        $str_replace_to = [$this->toAddress, $this->amount];
        $command = str_replace($str_replace_from, $str_replace_to, $this->transferCommand);

        return $this->executeSend($command);
    }

    public function getWalletAmount()
    {
        $balance = 0;
        $output = parent::getWalletAmount();
        try {
            $data = json_decode($output, true);
            if ($data['confirmed']) {
                $balance = $data['confirmed'];
            }
        } catch (\Exception $e) {
            echo "LTC: Error getting wallet amount";
            exit();
        }

        return $balance;
    }

    public function getWalletAmountFriendly()
    {
        return $this->getWalletAmount();
    }

    /**
     * Used to convert Shifter/Exchange output values into the value our Wallet understands
     * @param $amount
     * @return mixed
     */
    public static function toBase($amount)
    {
        return $amount;
    }
}