@ECHO off

ECHO Stahuji kody z GITu
ECHO --------------------
call git pull origin master
ECHO.

ECHO Aktualizuji composer
ECHO --------------------
call composer self-update
call composer update --no-dev
ECHO.

ECHO Synchronizuji na FTP
ECHO --------------------
call php deployment.phar deployment.ini
ECHO.


ECHO HOTOVO !!!