+------------------------------------------------------------------------+
| netjukebox 6 requirements                                              | 
+------------------------------------------------------------------------+
PHP 5.3.0 or later with extension: GD2, ICONV and MYSQL/MYSQLI (PDFLIB for PDF support)
MySQL 4.1.0 or later


+------------------------------------------------------------------------+
| Additional netjukebox LAN playback/playlist requirements               | 
+------------------------------------------------------------------------+
Music Player Daemon (MPD)
VideoLAN (VLC) with HTTP remote control interface
Winamp with the httpQ 3.1 plugin


+------------------------------------------------------------------------+
| Installation                                                           |
+------------------------------------------------------------------------+
The installation instruction can be found on:
http://www.netjukebox.nl/install.php


+------------------------------------------------------------------------+
| Configuration                                                          |
+------------------------------------------------------------------------+
All configuration parameters can be set in:
include/config.inc.php


+------------------------------------------------------------------------+
| MySQL and MySQLi                                                       |
+------------------------------------------------------------------------+
netjukebox natively works with the php MySQLi engine.
If you want to use the older MySQL engine convert the scripts with:
tools/mysqli2mysql.php


+------------------------------------------------------------------------+
| Command line update                                                    |
+------------------------------------------------------------------------+
netjukebox update can be run from the command line.
This way it is possible to schedule an update with a Windows Scheduled Task or Linux Cron Job.
Examples:
C:\PHP\php.exe "D:\Internet\netjukebox\update.php" update
php /var/www/update.php update


+------------------------------------------------------------------------+
| Very large files                                                       | 
+------------------------------------------------------------------------+
netjukebox 5.25 and up supports files larger than 2GB on 64-bit PHP installation.