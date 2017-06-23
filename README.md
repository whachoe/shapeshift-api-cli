# Shapeshift API CLI

This thing is a helper do to CLI-calls to the Shapeshift.io API. 

# Setup 

There's a whole lot to set up to get this stuff working. For each cryptocoin, we need a cli-wallet.      

## ETH
### Get parity 
`bash <(curl https://get.parity.io -Lk)`

### Start parity 
`parity --port=30334 --jsonrpc-apis="web3,eth,net,personal,parity,traces,rpc"`

### New account
`curl --data '{"method":"personal_newAccount","params":["your_pw"],"id":1,"jsonrpc":"2.0"}' -H "Content-Type: application/json" -X POST localhost:8545`

## BTC
`apt-get install python-qt4 python-pip`
`sudo pip2 install https://download.electrum.org/2.8.3/Electrum-2.8.3.tar.gz`

### New account
`electrum create`

## LTC
`apt-get install python-slowaes`
`pip2 install https://electrum-ltc.org/download/Electrum-LTC-2.6.4.2.tar.gz`

