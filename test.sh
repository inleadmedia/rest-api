#!/bin/sh
echo y | php app/console doctrine:mongodb:fixtures:load
export BPI_TEST_ENDPOINT_URI="http://bpi-ws.ci.inlead.dk/"
./vendor/bin/phpunit -c app/phpunit.xml src/Bpi/ApiBundle/Tests/
