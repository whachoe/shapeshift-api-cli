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

        $str_replace_from = [':address', ':amount', ':password', ':fromAddress'];
        $str_replace_to = [$this->toAddress, $this->amount, $this->walletConfig['password'], $this->fromAddress];
        $command = str_replace($str_replace_from, $str_replace_to, $this->transferCommand);

        return $this->executeSend($command);
    }

    public static function toBase($ethAmount)
    {
        return $ethAmount * self::WEI;
    }

    public static function toHex($wei)
    {
        return dechex($wei);
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
            echo "BTC: Error getting wallet amount";
            exit();
        }

        return $balance;
    }

    public function getWalletAmountFriendly()
    {
        return $this->getWalletAmount() / self::WEI;
    }
}