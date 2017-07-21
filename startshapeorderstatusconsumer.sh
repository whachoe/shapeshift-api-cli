#!/bin/bash
cd /home/cjpa/shapeshift-api-cli
#php Shifters/Shapeshift/ShapeshiftOrderstatusConsumer.php >> /home/cjpa/shapeshift-api-cli/logs/consumer_orderstatus.log
php Shifters/Changelly/ChangellyOrderstatusConsumer.php >> /home/cjpa/shapeshift-api-cli/logs/consumer_orderstatus.log
