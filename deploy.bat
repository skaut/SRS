@ECHO off

ECHO Stahuji kody z GITu
ECHO --------------------
call git pull origin master

ECHO Aktualizuji composer
ECHO --------------------
call composer install

ECHO Synchronizuji na FTP
ECHO --------------------
ECHO.
call php deployment.phar deployment.ini


ECHO.
ECHO HOTOVO !!!