<?php
//  +------------------------------------------------------------------------+
//  | netjukebox, Copyright © 2001-2015 Willem Bartels                       |
//  |                                                                        |
//  | http://www.netjukebox.nl                                               |
//  | http://forum.netjukebox.nl                                             |
//  |                                                                        |
//  | This program is free software: you can redistribute it and/or modify   |
//  | it under the terms of the GNU General Public License as published by   |
//  | the Free Software Foundation, either version 3 of the License, or      |
//  | (at your option) any later version.                                    |
//  |                                                                        |
//  | This program is distributed in the hope that it will be useful,        |
//  | but WITHOUT ANY WARRANTY; without even the implied warranty of         |
//  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          |
//  | GNU General Public License for more details.                           |
//  |                                                                        |
//  | You should have received a copy of the GNU General Public License      |
//  | along with this program.  If not, see <http://www.gnu.org/licenses/>.  |
//  +------------------------------------------------------------------------+




//  +------------------------------------------------------------------------+
//  | MySQLi configuration                                                   |
//  +------------------------------------------------------------------------+
$cfg['mysqli_host']                 = '127.0.0.1';
$cfg['mysqli_db']                   = 'netjukebox';
$cfg['mysqli_user']                 = 'root';
$cfg['mysqli_password']             = '';
$cfg['mysqli_port']                 = '3306';
$cfg['mysqli_socket']               = '';
$cfg['mysqli_auto_create_db']       = true;




//  +------------------------------------------------------------------------+
//  | Media directory                                                        |
//  +------------------------------------------------------------------------+
//  | Use a UNIX style directory scheme with a trailing slash.               |
//  |                                                                        |
//  | Linux/Unix/OSX: '/var/mpd/music/';                                     |
//  | Windows:        'D:/Music/';                                           |
//  +------------------------------------------------------------------------+
$cfg['media_dir']                   = '/data/Music/';




//  +------------------------------------------------------------------------+
//  | Binary directory                                                       |
//  +------------------------------------------------------------------------+
//  | Use the native directory scheme with a trailing slash or backslash.    |
//  | ESCAPE THE LAST BACKSLASH WITH A BACKSLASH OR USE DOUBLE QUOTES!       |
//  |                                                                        |
//  | Linux/Unix:     '/usr/bin/';                                           |
//  | OSX (MacPorts): '/opt/local/bin/';                                     |
//  | Windows:        'D:\Codec\\';                                          |
//  |                                                                        |
//  | BE AWARE THAT START CAN SUPPRESS ERROR MESSAGES ON SOME SETUPS!        |
//  | The process priority can be set with start or nice depending on the    |
//  | operating system:                                                      |
//  |                                                                        |
//  | Linux/Unix/OSX: 'nice -n 19 ...';                                      |
//  | Windows:        'start /b /low ...';                                   |
//  +------------------------------------------------------------------------+
$cfg['bin_dir']                     = 'nice -n 19 /usr/bin/';




//  +------------------------------------------------------------------------+
//  | External storage (portable media player)                               |
//  +------------------------------------------------------------------------+
//  | Use a UNIX style directory scheme with a trailing slash.               |
//  |                                                                        |
//  | Linux/Unix:     '/mnt/MUSIC/';                                         |
//  | OSX:            '/Volumes/MP3 PLAYER/MUSIC/';                          |
//  | Windows:        'G:/MUSIC/';                                           |
//  |                                                                        |
//  | External storage features are only visible in netjukebox when the      |
//  | web server has access to the path set in $cfg['external_storage']      |
//  +------------------------------------------------------------------------+
$cfg['external_storage']            = '/data/Mobile/Music/';




//  +------------------------------------------------------------------------+
//  | Media extensions                                                       |
//  +------------------------------------------------------------------------+
// Audio
$cfg['media_extension'][]           = 'aac';
$cfg['media_extension'][]           = 'm4a';
$cfg['media_extension'][]           = 'm4b';
$cfg['media_extension'][]           = 'mp2';
$cfg['media_extension'][]           = 'mp3';
$cfg['media_extension'][]           = 'mpc';
$cfg['media_extension'][]           = 'ogg';
$cfg['media_extension'][]           = 'oga';
$cfg['media_extension'][]           = 'wma';
// Losless audio
$cfg['media_extension'][]           = 'ape';
$cfg['media_extension'][]           = 'flac';
$cfg['media_extension'][]           = 'wv';
// Video
$cfg['media_extension'][]           = 'asf';
$cfg['media_extension'][]           = 'avi';
$cfg['media_extension'][]           = 'm4v';
$cfg['media_extension'][]           = 'mp4';
$cfg['media_extension'][]           = 'mkv';
$cfg['media_extension'][]           = 'mpeg';
$cfg['media_extension'][]           = 'mpg';
$cfg['media_extension'][]           = 'nsv';
$cfg['media_extension'][]           = 'ra';
$cfg['media_extension'][]           = 'ram';
$cfg['media_extension'][]           = 'swf';
$cfg['media_extension'][]           = 'ts';
$cfg['media_extension'][]           = 'wmv';




//  +------------------------------------------------------------------------+
//  | Decode audio (for stream, download & record)                           |
//  +------------------------------------------------------------------------+
$cfg['decode_stdout']['aac']        = $cfg['bin_dir'] . 'faad -d -o - %source';
$cfg['decode_stdout']['ape']        = $cfg['bin_dir'] . 'mac %source - -d';
$cfg['decode_stdout']['flac']       = $cfg['bin_dir'] . 'flac --decode --totally-silent --stdout %source';
$cfg['decode_stdout']['m4a']        = $cfg['bin_dir'] . 'faad -d -o - %source';
$cfg['decode_stdout']['mp3']        = $cfg['bin_dir'] . 'lame --decode --silent %source -';
$cfg['decode_stdout']['mpc']        = $cfg['bin_dir'] . 'mpcdec %source -';									
$cfg['decode_stdout']['ogg']        = $cfg['bin_dir'] . 'oggdec --dither 3 --downmix --stdout %source';
$cfg['decode_stdout']['wma']        = $cfg['bin_dir'] . 'wmadec -w -q %source';
$cfg['decode_stdout']['wv']         = $cfg['bin_dir'] . 'wvunpack -q %source -';




//  +------------------------------------------------------------------------+
//  | Encode audio (for stream & download)                                   |
//  +------------------------------------------------------------------------+
//  | Tag writing is done by the getID3() library, attached picture is       |
//  | currently only supported with the id3v2.3 tag                          |
//  +------------------------------------------------------------------------+
$cfg['transcode_treshold']          = 150;

$cfg['encode_name'][]               = 'MP3 @ Low';
$cfg['encode_mime_type'][]          = 'audio/mpeg';
$cfg['encode_extension'][]          = 'mp3';
$cfg['encode_stdout'][]             = $cfg['bin_dir'] . 'lame --abr 64 --quiet --noreplaygain - -';
$cfg['encode_file'][]               = $cfg['bin_dir'] . 'lame --abr 64 --quiet --replaygain-accurate - %destination';
$cfg['encode_bitrate'][]            = 64000;
$cfg['encode_vbr'][] 	            = true;
$cfg['tag_format'][]                = 'id3v2.3';
$cfg['tag_encoding'][]              = 'UTF-8';
$cfg['tag_padding'][]               = 25600;

$cfg['encode_name'][]               = 'MP3 @ Portable';
$cfg['encode_mime_type'][]          = 'audio/mpeg';
$cfg['encode_extension'][]          = 'mp3';
$cfg['encode_stdout'][]             = $cfg['bin_dir'] . 'lame -V5 --quiet --noreplaygain - -';
$cfg['encode_file'][]               = $cfg['bin_dir'] . 'lame -V5 --quiet --replaygain-accurate - %destination';
$cfg['encode_bitrate'][]            = 128000;
$cfg['encode_vbr'][] 	            = true;
$cfg['tag_format'][]                = 'id3v2.3';
$cfg['tag_encoding'][]              = 'UTF-8';
$cfg['tag_padding'][]               = 25600;

$cfg['encode_name'][]               = 'MP3 @ HiFi';
$cfg['encode_mime_type'][]          = 'audio/mpeg';
$cfg['encode_extension'][]          = 'mp3';
$cfg['encode_stdout'][]             = $cfg['bin_dir'] . 'lame -V2 --quiet --noreplaygain - -';
$cfg['encode_file'][]               = $cfg['bin_dir'] . 'lame -V2 --quiet --replaygain-accurate - %destination';
$cfg['encode_bitrate'][]            = 190000;
$cfg['encode_vbr'][] 	            = true;
$cfg['tag_format'][]                = 'id3v2.3';
$cfg['tag_encoding'][]              = 'UTF-8';
$cfg['tag_padding'][]               = 25600;




//  +------------------------------------------------------------------------+
//  | Download album with 7-Zip or zip                                       |
//  +------------------------------------------------------------------------+
//  | http://www.7-zip.org                                                   |
//  |                                                                        |
//  | 7za uses the LANG envirement variabale. Normaly this is only set when  |
//  | PHP is running in CLI mode.                                            |
//  +------------------------------------------------------------------------+
//  | http://www.info-zip.org/                                               |
//  |                                                                        |
//  | For zip the LANG envirement variabale is not needed. And can be set to |
//  | an empty string. On Windows you can replace the Linux cat command with |
//  | type (untested).                                                       |
//  +------------------------------------------------------------------------+
$cfg['download_album_mime_type']	= 'application/zip';
$cfg['download_album_env_lang']     = 'en_US.UTF-8';
$cfg['download_album_extension']    = 'zip';
$cfg['download_album_cmd']          = $cfg['bin_dir'] . '7za a -tzip -mx0 -- %destination @%list';

/*
$cfg['download_album_mime_type']	= 'application/zip';
$cfg['download_album_env_lang']     = '';
$cfg['download_album_extension']    = 'zip';
$cfg['download_album_cmd']          = 'cat %list | ' . $cfg['bin_dir'] . 'zip -0 --junk-paths %destination -@';
*/




//  +------------------------------------------------------------------------+
//  | Record (with CDRDAO)                                                   |
//  +------------------------------------------------------------------------+
//  | Use "cdrdao scanbus" from the cli to see a list with cdrom devices.    |
//  | Set the disired device with --device x,x,x in $cfg['record']           |
//  |                                                                        |
//  | http://sourceforge.net/projects/cdrdao/                                |
//  +------------------------------------------------------------------------+
$cfg['record']                      = $cfg['bin_dir'] . 'cdrdao write --device 1,1,0 --driver generic-mmc --eject --force -n %tocfile';
$cfg['record_cdtext']               = false;




//  +------------------------------------------------------------------------+
//  | Cache                                                                  |
//  +------------------------------------------------------------------------+
//  | Decoding to wav and creating zip files are relatively fast.            |
//  | When expire these files there will be more space for slower to         |
//  | transcode (mp3/ogg) files in the cache. It is advisable to set the     |
//  | expire time to at least the expected download or record time.          |
//  | When setting the expire value to 0 these files will not expire.        |
//  | The cache will maximum use 95% of the total available space.           |
//  +------------------------------------------------------------------------+
$cfg['cache_expire_wav']            = 3600;
$cfg['cache_expire_zip']            = 14400; // 3600 * 4




//  +------------------------------------------------------------------------+
//  | Album features                                                         |
//  +------------------------------------------------------------------------+
$cfg['album_download']              = true;
$cfg['album_share_stream']          = true;
$cfg['album_share_download']        = true;
$cfg['album_copy']                  = true;
$cfg['album_update_image']          = true;
$cfg['album_edit_genre']            = true;




//  +------------------------------------------------------------------------+
//  | Image                                                                  |
//  +------------------------------------------------------------------------+
//  | $cfg['image_read_embedded'] = true;                                    |
//  | Read embeded APIC or PICTURE image from first media file if no other   |
//  | image is found.                                                        |
//  |                                                                        |
//  | $cfg['image_share'] = true;                                            |
//  | Share image for another forum or website.                              |
//  | See the webinterface for the BB-Code, HTML-Code or URL only code.      |
//  |                                                                        |
//  | $cfg['image_share_mode'] = 'mode';                                     |
//  | new: New added album.                                                  |
//  | played: Recently played or streamed album.                             |
//  +------------------------------------------------------------------------+
$cfg['image_read_embedded']         = true;
$cfg['image_share']                 = true;
$cfg['image_share_mode']            = 'played';
$cfg['image_front']                 = 'cd_front'; // .jpg and .png
$cfg['image_back']                  = 'cd_back';  // .jpg and .png
$cfg['image_front_cover_treshold']  = 90000;      // 300 * 300




//  +------------------------------------------------------------------------+
//  | No album artist                                                        |
//  +------------------------------------------------------------------------+
$cfg['no_album_artist'][]           = 'compilation';
$cfg['no_album_artist'][]           = 'remix';
$cfg['no_album_artist'][]           = 'sampler';
$cfg['no_album_artist'][]           = 'singles';
$cfg['no_album_artist'][]           = 'various';




//  +------------------------------------------------------------------------+
//  | Internet search                                                        |
//  +------------------------------------------------------------------------+
$cfg['search_name'][]               = 'AllMusic';
$cfg['search_url_artist'][]         = 'http://www.allmusic.com/search/artist/%artist';
$cfg['search_url_album'][]          = 'http://www.allmusic.com/search/album/%album';
$cfg['search_url_combined'][]       = '';
$cfg['search_method'][]             = 'get';
$cfg['search_charset'][]            = 'UTF-8';

$cfg['search_name'][]               = 'Google';
$cfg['search_url_artist'][]         = 'https://www.google.com/search?q=%artist';
$cfg['search_url_album'][]          = 'https://www.google.com/search?q=%album';
$cfg['search_url_combined'][]       = 'https://www.google.com/search?q=%artist+%album';
$cfg['search_method'][]             = 'get';
$cfg['search_charset'][]            = 'UTF-8';

$cfg['search_name'][]               = 'Last.fm';
$cfg['search_url_artist'][]         = 'http://www.last.fm/search?m=artists&q=%artist';
$cfg['search_url_album'][]          = 'http://www.last.fm/search?m=albums&q=%album';
$cfg['search_url_combined'][]       = '';
$cfg['search_method'][]             = 'get';
$cfg['search_charset'][]            = 'UTF-8';

$cfg['search_name'][]               = 'MuziekWeb';
$cfg['search_url_artist'][]         = 'https://www.muziekweb.nl/MuziekWeb/Cat/Popular/Search.php?Artist=%artist';
$cfg['search_url_album'][]          = 'https://www.muziekweb.nl/MuziekWeb/Cat/Popular/Search.php?Album=%album';
$cfg['search_url_combined'][]       = 'https://www.muziekweb.nl/MuziekWeb/Cat/Popular/Search.php?Artist=%artist&Album=%album';
$cfg['search_method'][]             = 'get';
$cfg['search_charset'][]            = 'UTF-8';

$cfg['search_name'][]               = 'Rate your music';
$cfg['search_url_artist'][]         = 'https://rateyourmusic.com/search?type=a&searchterm=%artist';
$cfg['search_url_album'][]          = 'https://rateyourmusic.com/search?type=l&searchterm=%album';
$cfg['search_url_combined'][]       = '';
$cfg['search_method'][]             = 'get';
$cfg['search_charset'][]            = 'UTF-8';

$cfg['search_name'][]               = 'Sputnikmusic';
$cfg['search_url_artist'][]         = 'http://www.sputnikmusic.com/search_results.php?search_in=Bands&search_text=%artist';
$cfg['search_url_album'][]          = 'http://www.sputnikmusic.com/search_results.php?search_in=Albums&search_text=%album';
$cfg['search_url_combined'][]       = '';
$cfg['search_method'][]             = 'get';
$cfg['search_charset'][]            = 'UTF-8';




//  +------------------------------------------------------------------------+
//  | Image services                                                         |
//  +------------------------------------------------------------------------+
//  | For the Amazon web services a AWSAccessKeyId, SecretAccessKey and      |
//  | AssociateTag are required.                                             |            
//  | Get these from: http://aws.amazon.com                                  |
//  |                                                                        |
//  | For the Last.fm web services a "API key" is needed. Get this key free  |
//  | from:  http://www.last.fm/api/account                                  |
//  +------------------------------------------------------------------------+
$cfg['image_service_user_agent']	= 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:31.0) Gecko/20100101 Firefox/31.0';  // When empty use client user agent

/*
$cfg['image_AWSAccessKeyId']	    = '';
$cfg['image_AWSSecretAccessKey']    = '';
$cfg['image_AWSAssociateTag']       = 'free-usage-tier';

$cfg['image_service_name'][]        = 'Amazon';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'https://ecs.amazonaws.com/onca/xml?Service=AWSECommerceService&AWSAccessKeyId=%awsaccesskeyid&AssociateTag=%associatetag&Operation=ItemSearch&ResponseGroup=Images&SearchIndex=Music&Type=Lite&Artist=%artist&Title=%album&Timestamp=%timestamp';
$cfg['image_service_process'][]     = 'amazon';
$cfg['image_service_urldecode'][]   = null;

$cfg['image_service_name'][]        = 'Amazon (uk)';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'https://ecs.amazonaws.co.uk/onca/xml?Service=AWSECommerceService&AWSAccessKeyId=%awsaccesskeyid&AssociateTag=%associatetag&Operation=ItemSearch&ResponseGroup=Images&SearchIndex=Music&Type=Lite&Artist=%artist&Title=%album&Timestamp=%timestamp';
$cfg['image_service_process'][]     = 'amazon';
$cfg['image_service_urldecode'][]   = null;

$cfg['image_service_name'][]        = 'Amazon (de)';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'https://ecs.amazonaws.de/onca/xml?Service=AWSECommerceService&AWSAccessKeyId=%awsaccesskeyid&AssociateTag=%associatetag&Operation=ItemSearch&ResponseGroup=Images&SearchIndex=Music&Type=Lite&Artist=%artist&Title=%album&Timestamp=%timestamp';
$cfg['image_service_process'][]     = 'amazon';
$cfg['image_service_urldecode'][]   = null;
*/

$cfg['image_service_name'][]        = 'Slothradio';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'http://covers.slothradio.com/?adv=1&artist=%artist&album=%album&genre=p&imgsize=x&locale=us&sort=salesrank';
$cfg['image_service_process'][]     = '#<!-- RESULT ITEM START -->.+?><img src="(http://.+?)" width="([0-9]+?)" height="([0-9]+?)"#s';
$cfg['image_service_urldecode'][]   = false;

$cfg['image_service_name'][]        = 'Slothradio (uk)';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'http://covers.slothradio.com/?adv=1&artist=%artist&album=%album&genre=p&imgsize=x&locale=uk&sort=salesrank';
$cfg['image_service_process'][]     = '#<!-- RESULT ITEM START -->.+?><img src="(http://.+?)" width="([0-9]+?)" height="([0-9]+?)"#s';
$cfg['image_service_urldecode'][]   = false;

$cfg['image_service_name'][]        = 'Slothradio (de)';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'http://covers.slothradio.com/?adv=1&artist=%artist&album=%album&genre=p&imgsize=x&locale=de&sort=salesrank';
$cfg['image_service_process'][]     = '#<!-- RESULT ITEM START -->.+?><img src="(http://.+?)" width="([0-9]+?)" height="([0-9]+?)"#s';
$cfg['image_service_urldecode'][]   = false;

$cfg['image_service_name'][]        = 'Google';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'https://www.google.com/search?tbm=isch&q=%artist+%album';
$cfg['image_service_process'][]     = '#/imgres\?imgurl=(http://.+?)&amp;.+?&amp;h=([0-9]+?)&amp;w=([0-9]+?)&amp;#s';
$cfg['image_service_urldecode'][]   = true;

/*
$cfg['image_lastfm_api_key']        = '';

$cfg['image_service_name'][]        = 'Last.fm';
$cfg['image_service_charset'][]     = 'UTF-8';
$cfg['image_service_url'][]         = 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=%api_key&artist=%artist&album=%album';
$cfg['image_service_process'][]     = 'lastfm';
$cfg['image_service_urldecode'][]   = null;
*/




//  +------------------------------------------------------------------------+
//  | Internet ip tools                                                      |
//  +------------------------------------------------------------------------+
$cfg['ip_tools']                    = 'http://www.infosniper.net/index.php?ip_address=%ip&map_source=1&overview_map=1&lang=1&map_type=1&zoom_level=5';
// $cfg['ip_tools']                 = 'http://whois.domaintools.com/%ip';
// $cfg['ip_tools']                 = 'http://ip-lookup.net/index.php?ip=%ip';




//  +------------------------------------------------------------------------+
//  | Auto suggest limit (search results)                                    |
//  | Page limit (max albums per page for new)                               |
//  +------------------------------------------------------------------------+
$cfg['autosuggest_limit']           = 25;
$cfg['page_limit']                  = 100;




//  +------------------------------------------------------------------------+
//  | Date                                                                   |
//  +------------------------------------------------------------------------+
//  | The date_format syntax is identical to the PHP date() function.        |
//  +------------------------------------------------------------------------+
$cfg['date_format']               = 'r'; 
$cfg['date_added']                = 'F Y';




//  +------------------------------------------------------------------------+
//  | Default characterset                                                   |
//  +------------------------------------------------------------------------+
//  | When leaving empty it will use the ISO-8859-1 characterset for Windows |
//  | and UTF-8 for all other operating systems.                             |
//  +------------------------------------------------------------------------+
$cfg['default_charset']             = '';




//  +------------------------------------------------------------------------+
//  | Allow deleting duplicate and error files.                              |
//  +------------------------------------------------------------------------+
$cfg['delete_file']                = false;




//  +------------------------------------------------------------------------+
//  | File system escape characters                                          |
//  +------------------------------------------------------------------------+
//  | DON'T DELETE THESE SETTINGS! Even if your operating system fully       |
//  | supported these caracters. Don't use forwardslash or backslash in the  |
//  | filenames                                                              |
//  +------------------------------------------------------------------------+
$cfg['escape_char']['?']            = '^';   // question mark
$cfg['escape_char'][':']            = ';';   // colon
$cfg['escape_char']['"']            = "''";  // double quote
$cfg['escape_char']['*']            = '%2A'; // asterisk
$cfg['escape_char']['<']            = '%3C'; // less than
$cfg['escape_char']['>']            = '%3E'; // greater than
$cfg['escape_char']['|']            = '%7C'; // pipe

// Client detection based on useragent
$cfg['client_char_limit']['#Macintosh|Mac OS X#i']  = array(':');
$cfg['client_char_limit']['#Windows|OS/2#i']        = array('"', '*', ':', '<', '>', '?', '|');
// Server detection based on PHP_OS
$cfg['server_char_limit']['#^Darwin#i']             = array(':');
$cfg['server_char_limit']['#^WIN#i']                = array('"', '*', ':', '<', '>', '?', '|');
// Album copy directory
$cfg['album_copy_char_limit']                       = array('"', '*', ':', '<', '>', '?', '|');




//  +------------------------------------------------------------------------+
//  | Authenticate                                                           |
//  +------------------------------------------------------------------------+
$cfg['anonymous_user']              = 'anonymous';
$cfg['anonymous_autologin']         = false;
$cfg['login_delay']                 = 2000;
$cfg['session_lifetime']            = 604800; // 3600 * 24 * 7
$cfg['share_stream_lifetime']       = 604800;
$cfg['share_download_lifetime']     = 604800;





//  +------------------------------------------------------------------------+
//  | Message                                                                |
//  +------------------------------------------------------------------------+
//  | [br]                                                                   |
//  | [b]bold[/b]                                                            |
//  | [i]italic[/i]                                                          |
//  | [img]small_back.png[/img]                                              |
//  | [url]http://www.example.com[/url]                                      |
//  | [url=http://www.example.com]example[/url]                              |
//  | [email]info@example.com[/email]                                        |
//  | [list][*]first[*]second[/list]                                         |
//  +------------------------------------------------------------------------+
$cfg['admin_about_message']         = '';
$cfg['admin_login_message']         = '';




//  +------------------------------------------------------------------------+
//  | Offline message                                                        |
//  +------------------------------------------------------------------------+
$cfg['offline']                     = false;
$cfg['offline_message']             = '[b]This site is temporarily unavailable.[/b][br]We apologize for the inconvenience.';




//  +------------------------------------------------------------------------+
//  | Debug                                                                  |
//  +------------------------------------------------------------------------+
$cfg['debug']                       = false;
$cfg['php_info']                    = false;
