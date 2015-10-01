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
//  | Execution time                                                         |
//  +------------------------------------------------------------------------+
function executionTime() {
	$miliseconds = round((microtime(true) - NJB_START_TIME) * 1000);
	$seconds = round($miliseconds / 1000);
	
	if ($miliseconds < 1000)	return $miliseconds . ' ms';
	if ($seconds == 1)			return '1 second';
	if ($seconds < 60)			return $seconds . ' seconds';
								return formattedTime($miliseconds);
}




//  +------------------------------------------------------------------------+
//  | Formatted time                                                         |
//  +------------------------------------------------------------------------+
function formattedTime($time, $milisecond = true) {
	$seconds 	= ($milisecond) ? round($time / 1000) : $time;
	$hours		= floor($seconds / 3600);
	$minutes 	= floor($seconds / 60) % 60;
	$seconds 	= $seconds % 60;
	
	if ($hours > 0)	return $hours . ':' . sprintf('%02d:%02d', $minutes, $seconds);
					return $minutes . sprintf(':%02d', $seconds);
}




//  +------------------------------------------------------------------------+
//  | Formatted days                                                         |
//  +------------------------------------------------------------------------+
function formattedDays($seconds) {
	$days = $seconds / 3600 / 24;
	if ($days <= 1)	return  number_format($days, 1, '.', '') . ' day';
					return  number_format($days, 1, '.', '') . ' days';
}




//  +------------------------------------------------------------------------+
//  | Formatted size                                                         |
//  +------------------------------------------------------------------------+
function formattedSize($filesize) {
	$weight = array('bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
	
	for ($i = 0; $filesize >= 1024; $i++)
		$filesize /= 1024;
	
	if ($i == 0)		return (int) $filesize . ' ' . $weight[$i];
						return number_format($filesize, 2, '.', '') . ' ' . $weight[$i];
}




//  +------------------------------------------------------------------------+
//  | Formatted bitrate                                                      |
//  +------------------------------------------------------------------------+
function formattedBirate($bitrate) {
	$weight = array('bps', 'kbps', 'Mbps', 'Gbps', 'Tbps', 'Pbps', 'Ebps', 'Zbps', 'Ybps');
	
	for ($i = 0; $bitrate >= 1000; $i++)
		$bitrate /= 1000;
	
	return round($bitrate) . ' ' . $weight[$i];
}




//  +------------------------------------------------------------------------+
//  | Formatted frequency                                                    |
//  +------------------------------------------------------------------------+
function formattedFrequency($frequency) {
	$weight = array('Hz', 'kHz', 'MHz', 'GHz', 'THz', 'PHz', 'EHz', 'ZHz', 'YHz');
	
	for ($i = 0; $frequency >= 1000; $i++)
		$frequency /= 1000;
	
	return number_format($frequency, 1) . ' ' . $weight[$i];
}




//  +------------------------------------------------------------------------+
//  | Formatted date                                                         |
//  +------------------------------------------------------------------------+
function formattedDate($year = null, $month = null, $day = null) {
	$date = '';
	if (isset($day))	$date .= str_pad($day, 2, 0, STR_PAD_LEFT) . '&nbsp;';
	if (isset($month))	$date .= formattedMonth($month) . '&nbsp;';
	if (isset($year))	$date .= $year;
	
	return $date;
}




//  +------------------------------------------------------------------------+
//  | Formatted month                                                        |
//  +------------------------------------------------------------------------+
function formattedMonth($number) {
	$month = array(1 =>	'January', 'February', 'March', 'April', 'May', 'June',
					'July', 'August', 'September', 'October', 'November', 'December');
	
	return $month[$number];
}




//  +------------------------------------------------------------------------+
//  | HTML                                                                   |
//  +------------------------------------------------------------------------+
function html($string) {
	return htmlspecialchars($string, ENT_COMPAT, NJB_DEFAULT_CHARSET);
}




//  +------------------------------------------------------------------------+
//  | Safe JSON encode                                                       |
//  +------------------------------------------------------------------------+
function safe_json_encode($data) {
	if (NJB_DEFAULT_CHARSET == 'UTF-8' && version_compare(PHP_VERSION, '5.4.0', '>='))
		return json_encode($data, JSON_UNESCAPED_UNICODE); 
	elseif (NJB_DEFAULT_CHARSET == 'UTF-8')
		return json_encode($data);
	else
		return json_encode(recursive_iconv_to_utf8($data));
}




//  +------------------------------------------------------------------------+
//  | Recursive iconv to utf8                                                |
//  +------------------------------------------------------------------------+
function recursive_iconv_to_utf8($data) {
	if (is_string($data)) return iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $data);
	if (!is_array($data)) return $data;
	
	$data = array_map('recursive_iconv_to_utf8', $data);
	
	return $data;
}




//  +------------------------------------------------------------------------+
//  | Escape CMD arg                                                         |
//  +------------------------------------------------------------------------+
function escapeCmdArg($arg) {
	if (NJB_WINDOWS) {
		// No need to escape " because this symbol isn't used by Windows
		return '"' . str_replace('/', '\\', $arg) . '"';
	}
	else {
		// Didn't use escapeshellarg() because of problems with UTF8
		// Thanks Marc Maurice: http://positon.org/php-escapeshellarg-function-utf8-and-locales
		return "'" . str_replace("'", "'\\''", $arg) . "'";
	}
}




//  +------------------------------------------------------------------------+
//  | Encode escape character                                                |
//  +------------------------------------------------------------------------+
function encodeEscapeChar($filename) {
	global $cfg;
	
	foreach ($cfg['escape_char'] as $key => $value)
		$filename = str_replace($key, $value, $filename); // Example: ? to %3F
	
	return $filename;
}




//  +------------------------------------------------------------------------+
//  | Decode escape character                                                |
//  +------------------------------------------------------------------------+
function decodeEscapeChar($filename) {
	global $cfg;
	
	foreach ($cfg['escape_char'] as $key => $value)
		$filename = str_replace($value, $key, $filename); // Example: %3F to ?
	
	return $filename;
}




//  +------------------------------------------------------------------------+
//  | Download filename                                                      |
//  +------------------------------------------------------------------------+
function downloadFilename($filename, $client_compatible = true, $server_compatible = false) {
	global $cfg;
	
	// Decode filename
	$filename = decodeEscapeChar($filename); // Example: %3F to ?
	
	// Encode for client compatibility
	if ($client_compatible)	{
		foreach ($cfg['client_char_limit'] as $regex => $value)	{
			if (preg_match($regex, $_SERVER['HTTP_USER_AGENT'])) {
				foreach ($cfg['client_char_limit'][$regex] as $key => $value)
					$filename = str_replace($value, $cfg['escape_char'][$value], $filename); // Example: ? to %3F
				break;
			}
		}
	}
	
	// Encode for server compatibility
	if ($server_compatible)	{
		foreach ($cfg['server_char_limit'] as $regex => $value)	{
			if (preg_match($regex, PHP_OS)) {
				foreach ($cfg['server_char_limit'][$regex] as $key => $value)
					$filename = str_replace($value, $cfg['escape_char'][$value], $filename); // Example: ? to %3F
				break;
			}
		}
	}
	
	return $filename;
}




//  +------------------------------------------------------------------------+
//  | Copy filename                                                          |
//  +------------------------------------------------------------------------+
function copyFilename($filename) {
	global $cfg;
	
	// Decode filename
	$filename = decodeEscapeChar($filename); // Example: %3F to ?
	
	// Encode for compatibility
	foreach ($cfg['album_copy_char_limit'] as $key => $value)
		$filename = str_replace($value, $cfg['escape_char'][$value], $filename); // Example: ? to %3F

	return $filename;
}




//  +------------------------------------------------------------------------+
//  | BBcode                                                                 |
//  +------------------------------------------------------------------------+
function bbcode($string) {
	global $cfg;
	$bbcode = array(
		'#\[br\]#s',
		'#\[b\](.*?)\[\/b\]#s',
		'#\[i\](.*?)\[\/i\]#s',
		'#\[img\]([a-z_]+\.png)\[\/img\]#s',
		'#\[url=([a-z]+\.php(?:\?.*)?)\](.*?)\[\/url\]#s',
		'#\[url\]((?:http|https|ftp)://.*?)\[\/url\]#s',
		'#\[url=((?:http|https|ftp)://.*?)\](.*?)\[\/url\]#s',
		'#\[email\]([a-z0-9._%-]+@[a-z0-9.-]+\.[a-z]{2,4})\[\/email\]#si');
	$replace = array(
		'<br>',
		'<strong>$1</strong>',
		'<em>$1</em>',
		'<img src="' . $cfg['img'] . '$1" alt="" class="small space">',
		'<a href="$1">$2</a>',
		'<a href="$1">$1</a>',
		'<a href="$1">$2</a>',
		'<a href="mailto:$1">$1</a>');
	
	$string = html($string);
	$string = preg_replace($bbcode, $replace, $string);
	$string = preg_replace_callback('#\[list\](.*?)\[\/list\]#s', 'bblist', $string);
	
	return $string;
}




//  +------------------------------------------------------------------------+
//  | BBcode list                                                            |
//  +------------------------------------------------------------------------+	
function bblist($maches) {
	$list = '';
	$list_array = explode('[*]', $maches[1]);
	foreach ($list_array as $key => $value)	{
		if ($key == 0) $list .= $value;
		else $list .= '<li>' . $value . '</li>';
	}
	
	return '<ul class="bbcode">' . $list . '</ul>';
}




//  +------------------------------------------------------------------------+
//  | BBcode to txt                                                          |
//  +------------------------------------------------------------------------+
function bbcode2txt($string) {
	$bbcode = array(
		"#\r\n|\n|\r#",
		'#\[br\]#s',
		'#\[b\](.*?)\[\/b\]#s',
		'#\[i\](.*?)\[\/i\]#s',
		'#\[list\](.*?)\[\/list\]#s',
		'#\[\*\]#s',
		'#\[img\]([a-z_]+\.png)\[\/img\]#s',
		'#\[url=([a-z]+\.php(?:\?.*)?)\](.*?)\[\/url\]#s',
		'#\[url\]((?:http|https|ftp)://.*?)\[\/url\]#s',
		'#\[url=((?:http|https|ftp)://.*?)\](.*?)\[\/url\]#s',
		'#\[email\]([a-z0-9._%-]+@[a-z0-9.-]+\.[a-z]{2,4})\[\/email\]#si');
	
	$replace = array(
		'',
		"\n",
		'$1',
		'$1',
		'$1',
		"\n* ",
		'',
		'$2 <$1>',
		'<$1>',
		'$2 <$1>',
		'$1');
	
	return preg_replace($bbcode, $replace, $string);
}




//  +------------------------------------------------------------------------+
//  | BBcode reference title                                                 |
//  +------------------------------------------------------------------------+
function bbcodeReferenceTitle() {
	$list = '[br]<br>';
	$list .= '[b]bold[/b]<br>';
	$list .= '[i]italic[/i]<br>';
	$list .= '[img]small_back.png[/img]<br>';
	$list .= '[url]http://www.example.com[/url]<br>';
	$list .= '[url=http://www.example.com]example[/url]<br>';
	$list .= '[email]info@example.com[/email]<br>';
	$list .= '[list][*]first[*]second[/list]';
	
	return 'title="' . html($list) . '"';
}




//  +------------------------------------------------------------------------+
//  | Image title                                                            |
//  +------------------------------------------------------------------------+
function imageTitle($image_id) {
	$image = '<img src="image.php?image_id=' . rawurlencode($image_id) . '" alt="" width="50" height="50">';
	
	return 'title="' . html($image) . '"';
}




//  +------------------------------------------------------------------------+
//  | Access info title                                                      |
//  +------------------------------------------------------------------------+
function accessInfoTitle($access) {
	switch ($access) {
		case 'media':		$info = 'View media';						break;
		case 'popular':		$info = 'View popular albums';				break;
		case 'favorite':	$info = 'View favorites';					break;
		case 'cover':		$info = 'Download pdf cover';				break;
		case 'stream':		$info = 'Stream media';						break;
		case 'download':	$info = 'Download media';					break;
		case 'playlist':	$info = 'View playlist';					break;
		case 'play':		$info = 'Play media';						break;
		case 'add':			$info = 'Add media to playlist';			break;
		case 'record':		$info = 'Record album to compact disc';		break;
		case 'statistics':	$info = 'View media statistics';			break;
		case 'admin':		$info = 'Administrator';					break;
	}
	
	return 'title="' . html($info) .'"';
}




//  +------------------------------------------------------------------------+
//  | Navigator Player Profile                                               |
//  +------------------------------------------------------------------------+
function navPlayerProfile() {
	global $cfg, $db, $nav;
	require_once(NJB_HOME_DIR . 'include/play.inc.php');
	
	$query = mysqli_query($db, 'SELECT player_name, player_id FROM player ORDER BY player_name');
	if (mysqli_num_rows($query) == 1)
		return;
		
	if (@$_GET['navigator'] == 'selectPlayerProfile') {
		if ($cfg['menu'] == 'media') {	
			$nav			= array();
			$nav['name'][]	= 'Media';
			$nav['url'][]	= 'index.php';
			$nav['class'][]	= 'nav';
		}
		if ($cfg['menu'] == 'favorite') {	
			$nav			= array();
			$nav['name'][]	= 'Favorites';
			$nav['url'][]	= 'favorite.php';
			$nav['class'][]	= 'nav';
		}
		elseif ($cfg['menu'] == 'playlist') {
			$nav			= array();
			$nav['name'][]	= 'Playlist';
			$nav['url'][]	= 'playlist.php';
			$nav['class'][]	= 'nav';
		}
		while ($player = mysqli_fetch_assoc($query)) {
			if ($player['player_id'] == $cfg['player_id']) {
				$nav['name'][]	= $player['player_name'];
				$nav['url'][]	= '';
				$nav['class'][]	= 'nav';
			}
			else {
				$nav['name'][]	= $player['player_name'];
			 	$nav['url'][]	= 'config.php?action=setPlayerProfile&amp;player_id=' . $player['player_id'] . '&amp;sign=' . $cfg['sign'] . '&amp;menu=' . $cfg['menu'];
			 	$nav['class'][]	= 'suggest';
			}
		}
	}
	else {
		$nav['name'][]	= $cfg['player_name'];
		$nav['url'][]	= (($cfg['menu'] == 'media') ? 'index' :  $cfg['menu']) . '.php?navigator=selectPlayerProfile';
		$nav['class'][]	= 'nav';
	}
}




//  +------------------------------------------------------------------------+
//  | Random sid                                                             |
//  +------------------------------------------------------------------------+
function randomSid() {
	$uniqid	= uniqid();
	$rand = randomHex(60 - strlen($uniqid));
	return strrev(hex_to_base64url($uniqid . $rand));
}




//  +------------------------------------------------------------------------+
//  | Random file sid                                                        |
//  +------------------------------------------------------------------------+
function randomFileSid() {
	$uniqid	= uniqid();
	$rand = randomHex(40 - strlen($uniqid));
	return $rand . strrev($uniqid);
}




//  +------------------------------------------------------------------------+
//  | Random seed                                                            |
//  +------------------------------------------------------------------------+
function randomSeed() {
	$rand = randomHex(60);
	return hex_to_base64url($rand);
}




//  +------------------------------------------------------------------------+
//  | Random hex                                                             |
//  +------------------------------------------------------------------------+
function randomHex($lenght) {
	$bytes = ceil($lenght / 2);
	if  (function_exists('random_bytes') && $rand = @random_bytes($bytes)); // PHP >= 7
	elseif (@is_readable('/dev/arandom') && $rand = @file_get_contents('/dev/arandom', null, null, null, $bytes));
	elseif (@is_readable('/dev/urandom') && $rand = @file_get_contents('/dev/urandom', null, null, null, $bytes));
	elseif (function_exists('openssl_random_pseudo_bytes') && $rand = @openssl_random_pseudo_bytes($bytes)); // PHP 5 >= 5.3.0
	else {
		$rand = '';
		for ($i = 0; $i < $lenght; $i++)
			$rand .= dechex(mt_rand(0, 15));
		return $rand;
	}
	return substr(bin2hex($rand), -$lenght);
}




//  +------------------------------------------------------------------------+
//  | HEX to Base64url                                                       |
//  +------------------------------------------------------------------------+
function hex_to_base64url($hex) {
	$base64 = base64_encode(pack('H*', $hex));
	return rtrim(strtr($base64, '+/', '-_'), '='); // http://www.ietf.org/rfc/rfc4648.txt
}




//  +------------------------------------------------------------------------+
//  | HMAC MD5                                                               |
//  +------------------------------------------------------------------------+
function hmacmd5($key, $data, $raw = false) {
	if (function_exists('hash_hmac'))
		return hash_hmac('md5', $data, $key, $raw);
	
	$blocksize = 64;
	if (strlen($key) > $blocksize)
		$key = md5($key, true);
	
	$key	= str_pad($key, $blocksize, chr(0x00));
	$ipad	= str_repeat(chr(0x36), $blocksize);
	$opad	= str_repeat(chr(0x5c), $blocksize);
	
	return md5(($key^$opad) . md5(($key^$ipad) . $data, true), $raw);
}




//  +------------------------------------------------------------------------+
//  | HMAC SHA-1                                                             |
//  +------------------------------------------------------------------------+
function hmacsha1($key, $data, $raw = false) {
	if (function_exists('hash_hmac'))
		return hash_hmac('sha1', $data, $key, $raw);
	
	$blocksize = 64;
	if (strlen($key) > $blocksize)
		$key = sha1($key, true);
	
	$key	= str_pad($key, $blocksize, chr(0x00));
	$ipad	= str_repeat(chr(0x36), $blocksize);
	$opad	= str_repeat(chr(0x5c), $blocksize);
	
	return sha1(($key^$opad) . sha1(($key^$ipad) . $data, true), $raw);
}




//  +------------------------------------------------------------------------+
//  | Filemtime compare                                                      |
//  +------------------------------------------------------------------------+
function filemtimeCompare($filemtime1, $filemtime2) {
	if ($filemtime1 == $filemtime2) return true;
	if (NJB_WINDOWS && $filemtime1 == $filemtime2 + 3600) return true;
	if (NJB_WINDOWS && $filemtime1 == $filemtime2 - 3600) return true;
	
	return false;
}




//  +------------------------------------------------------------------------+
//  | Source file                                                            |
//  +------------------------------------------------------------------------+
function sourceFile($extension, $bitrate, $id) {
	global $cfg;
	if ($id == -1 ||
		array_key_exists($extension, $cfg['decode_stdout']) == false ||
		$extension == $cfg['encode_extension'][$id] &&
		$bitrate <= round($cfg['encode_bitrate'][$id] * $cfg['transcode_treshold'] / 100))
		return true;
	else
		return false;
}




//  +------------------------------------------------------------------------+
//  | Validate skin                                                          |
//  +------------------------------------------------------------------------+
function validateSkin($skin) {
	$dir = NJB_HOME_DIR . 'skin/' . $skin . '/';
	
	if (file_exists($dir . 'style.css') &&
		file_exists($dir . 'template.footer.php') &&
		file_exists($dir . 'template.header.php') &&
		$dir == str_replace('\\', '/', realpath($dir)) . '/')
		return true;
	else
		return false;
}




//  +------------------------------------------------------------------------+
//  | Update counter: play / add / stream / download / cover                 |
//  +------------------------------------------------------------------------+
function updateCounter($album_id, $flag){
	global $cfg, $db;
	// flag 0 = play/add
	// flag 1 = stream
	// flag 2 = download
	// flag 3 = cover
	// flag 4 = record
	
	$query = mysqli_query($db, 'SELECT time FROM counter
		WHERE album_id	= "' . mysqli_real_escape_string($db, $album_id) . '"
		AND sid			= BINARY "' . mysqli_real_escape_string($db, $cfg['sid']) . '"
		AND flag		= ' . (int) $flag . '
		ORDER BY time DESC LIMIT 1');
	$counter = mysqli_fetch_assoc($query);
	$counter_time = $counter['time'];
	
	if ($counter_time + 60 - time() < 0) {
		mysqli_query($db, 'INSERT INTO counter (sid, album_id, user_id, flag, time) VALUES (
			"' . mysqli_real_escape_string($db, $cfg['sid']) . '",
			"' . mysqli_real_escape_string($db, $album_id) . '",
			' . (int) $cfg['user_id'] . ',
			' . (int) $flag . ',
			' . (int) time() . ')');
	}
	else {
		mysqli_query($db, 'UPDATE counter
			SET time = 			' . (int) time() . '
			WHERE album_id = 	"' . mysqli_real_escape_string($db, $album_id) . '"
			AND sid =			BINARY "' . mysqli_real_escape_string($db, $cfg['sid']) . '"
			AND flag =			' . (int) $flag . ',
			AND time =			' . (int) $counter_time);
	}
}




//  +------------------------------------------------------------------------+
//  | Create hiden dir                                                       |
//  +------------------------------------------------------------------------+
function createHiddenDir($dir) {
	$file = $dir . 'index.php';
	$content = '<!doctype html><html><head><title></title></head><body></body></html>';
	
	if (is_dir($dir) == false && @mkdir($dir, 0777) == false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to create directory:[/b][br]' . $dir);
	
	if (@filesize($file) != strlen($content) && file_put_contents($file, $content) === false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to create file:[/b][br]' . $file);
}




//  +------------------------------------------------------------------------+
//  | Recursive rmdir                                                        |
//  +------------------------------------------------------------------------+
function rrmdir($dir) {
	if (is_dir($dir)) {
		$entries = scandir($dir);
		foreach ($entries as $entry) {
			if ($entry != '.' && $entry != '..') {
				if (is_dir($dir . $entry . '/'))	@rrmdir($dir . $entry . '/');
				else								@unlink($dir . $entry) or message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $dir . $entry);
			}
		}
		rmdir($dir) or message(__FILE__, __LINE__, 'error', '[b]Failed to delete directory:[/b][br]' . $dir);
	}
}
