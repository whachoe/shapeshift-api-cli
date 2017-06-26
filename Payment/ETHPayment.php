<?php
namespace Payment;

class ETHPayment extends Payment
{
    const WEI = 1000000000000000000;

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
            throw new \Exception("ETH send: Missing parameters");
        }

        // Make sure the amount is in HEX
        $amountstring = strval($this->amount);
        if (!strpos('0x')) {
            $this->amount = self::toHex($this->amount);
        }

        $str_replace_from = [':address', ':amount', ':password', ':fromAddress'];
        $str_replace_to = [$this->toAddress, $this->amount, $this->walletConfig['password'], $this->fromAddress];
        $command = str_replace($str_replace_from, $str_replace_to, $this->transferCommand);

        return $this->executeSend($command);
    }

    public static function toHex($wei)
    {
        return '0x'.dechex($wei);
    }

    public function getWalletAmount()
    {
        $balance = "0";
        $output = parent::getWalletAmount();
        try {
            $data = json_decode($output, true);
            if (isset($data['result'])) {
                $balance = hexdec($data['result']);
            }
        } catch (\Exception $e) {
            echo "ETH: Error getting wallet amount";
            exit();
        }

        return $balance;
    }

    public function getWalletAmountFriendly()
    {
        $hex = $this->getWalletAmount();
        return hexdec($hex) / self::WEI;
    }

    /**
     * Used to convert Shifter/Exchange output values into the value our Wallet understands
     * @param $amount
     * @return mixed
     */
    public static function toBase($amount)
    {
        return $amount * self::WEI;
    }
}