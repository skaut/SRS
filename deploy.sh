#!/bin/sh

echo "Stahuji kody z GITu"
echo "--------------------"
git pull origin master

echo "Aktualizuji composer"
echo "--------------------"
composer install

echo "Synchronizuji na FTP"
echo "--------------------"
php deployment.phar deployment.ini


echo "HOTOVO !!!"