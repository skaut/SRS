#!/bin/sh

echo "Stahuji kody z GITu"
echo "--------------------"
git pull origin master
echo ""

echo "Aktualizuji composer"
echo "--------------------"
composer self-update
composer update --no-dev
echo ""

echo "Synchronizuji na FTP"
echo "--------------------"
php deployment.phar deployment.ini
echo ""

echo "HOTOVO !!!"