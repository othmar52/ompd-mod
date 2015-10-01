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
//  | Initialize                                                             |
//  +------------------------------------------------------------------------+
define('NJB_START_TIME', microtime(true));

define('NJB_VERSION', '6.08.8');
define('NJB_DATABASE_VERSION', 45);
define('NJB_IMAGE_SIZE', 200);
define('NJB_IMAGE_QUALITY', 85); // < 100
define('NJB_WINDOWS', (stripos(PHP_OS, 'WIN') === 0));
define('NJB_SCRIPT', basename($_SERVER['SCRIPT_NAME']));
define('NJB_HTTPS', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != ''));

define('NJB_HTTPQ', 0);
define('NJB_VLC', 1);
define('NJB_MPD', 2);

define('NJB_COUNTER_PLAY', 0);
define('NJB_COUNTER_STREAM', 1);
define('NJB_COUNTER_DOWNLOAD', 2);
define('NJB_COUNTER_COVER', 3);
define('NJB_COUNTER_RECORD', 4);




//  +------------------------------------------------------------------------+
//  | Check PHP version                                                      |
//  +------------------------------------------------------------------------+
if (version_compare(PHP_VERSION, '5.3.0', '<'))
	exit('<!doctype html><html><head><title></title></head><body><h1>netjukebox requires PHP 5.3.0 or higher</h1>Now PHP ' . htmlspecialchars(PHP_VERSION) . ' is running</body></html>');

ini_set('request_order', 'GP');
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
	ini_set('magic_quotes_runtime', '0');
	if (get_magic_quotes_gpc())
		exit('<!doctype html><html><head><title></title></head><body><h1>netjukebox requires to disable get_magic_quotes_gpc</h1>Set magic_quotes_gpc = Off in the php.ini</body></html>');
}




//  +------------------------------------------------------------------------+
//  | Get home directory                                                     |
//  +------------------------------------------------------------------------+
$temp = realpath(__DIR__ . '/..');
define('NJB_HOME_DIR', str_replace('\\', '/', $temp) . '/');




//  +------------------------------------------------------------------------+
//  | Load config file & set config defaults                                 |
//  +------------------------------------------------------------------------+
$cfg = array();
require_once(NJB_HOME_DIR . 'include/config.inc.php');

$cfg['menu']				= 'media';
$cfg['skin']				= 'Clean';
$cfg['img']					= 'skin/Clean/img/';
$cfg['username']			= '';
$cfg['sign']				= '';
$cfg['sign_validated']		= false;
$cfg['access_media']		= false;
$cfg['access_popular']		= false;
$cfg['access_favorite']		= false;
$cfg['access_cover']		= false;
$cfg['access_stream']		= false;
$cfg['access_download']		= false;
$cfg['access_playlist']		= false;
$cfg['access_play']			= false;
$cfg['access_add']			= false;
$cfg['access_record']		= false;
$cfg['access_statistics']	= false;
$cfg['access_admin']		= false;
$cfg['access_search']		= 0;




//  +------------------------------------------------------------------------+
//  | Check for default stylesheets (skin)                                   |
//  +------------------------------------------------------------------------+
if (file_exists(NJB_HOME_DIR . 'skin/' . $cfg['skin'] . '/style.css') == false && PHP_SAPI != 'cli')
	exit('<!doctype html><html><head><title></title></head><body><h1>Missing stylesheets</h1><p>netjukebox is missing the default stylesheets <em>skin/' . htmlspecialchars($cfg['skin']) . '/style.css</em></p></body></html>');




//  +------------------------------------------------------------------------+
//  | Default charset                                                        |
//  +------------------------------------------------------------------------+
if (NJB_WINDOWS)	define('NJB_DEFAULT_CHARSET', ($cfg['default_charset'] == '') ? 'ISO-8859-1' : $cfg['default_charset']);
else				define('NJB_DEFAULT_CHARSET', ($cfg['default_charset'] == '') ? 'UTF-8' : $cfg['default_charset']);
ini_set('default_charset', NJB_DEFAULT_CHARSET);




//  +------------------------------------------------------------------------+
//  | Get home url                                                           |
//  +------------------------------------------------------------------------+
if (PHP_SAPI != 'cli') {
	$temp = rawurlencode(dirname($_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']));
	$temp = str_replace('%2F', '/', $temp);
	$temp = str_replace('%3A', ':', $temp);
	define('NJB_HOME_URL', (NJB_HTTPS ? 'https://' : 'http://') . $temp . '/');
}
else 
	define('NJB_HOME_URL', '');




//  +------------------------------------------------------------------------+
//  | Offline                                                                |
//  +------------------------------------------------------------------------+
if ($cfg['offline'])
	message(__FILE__, __LINE__, 'warning', $cfg['offline_message']);




//  +------------------------------------------------------------------------+
//  | Check for required extensions                                          |
//  +------------------------------------------------------------------------+
if (function_exists('imagecreatetruecolor') == false)
	message(__FILE__, __LINE__, 'error', '[b]GD2 not loaded[/b][list][*]Compile PHP with GD2 support.[*]Or use a loadable module in the php.ini[/list]');
if (function_exists('mysqli_connect') == false)
	message(__FILE__, __LINE__, 'error', '[b]MYSQLI not loaded[/b][list][*]Compile PHP with MYSQLI support.[*]Or use a loadable module in the php.ini[/list]');
if (function_exists('iconv') == false)
	message(__FILE__, __LINE__, 'error', '[b]ICONV not loaded[/b][list][*]Compile PHP with ICONV support.[*]Or use a loadable module in the php.ini[/list]');




//  +------------------------------------------------------------------------+
//  | Require once                                                           |
//  +------------------------------------------------------------------------+
require_once(NJB_HOME_DIR . 'include/library.inc.php');

// To prevent mysql error snowball effect.
if (NJB_SCRIPT != 'message.php')
	require_once(NJB_HOME_DIR . 'include/mysqli.inc.php');


	

//  +------------------------------------------------------------------------+
//  | Authenticate                                                           |
//  +------------------------------------------------------------------------+
function authenticate($access, $cache = false, $validate_sign = false, $disable_counter = false) {
	global $cfg, $db;
	
	if ($cache == false && headers_sent() == false)	{
		header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
	}
	
	$sid			= @$_COOKIE['netjukebox_sid'];
	$authenticate	= @$_REQUEST['authenticate'];
	
	$query			= mysqli_query($db, 'SELECT logged_in, user_id, idle_time, lock_ip, ip, user_agent, sign, seed, skin,
						random_blacklist, thumbnail, thumbnail_size, stream_id, download_id, player_id
						FROM session
						WHERE sid = BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
	$session		= mysqli_fetch_assoc($query);
	
	setSkin($session['skin']);
	
	// Validate login
	if ($authenticate == 'validate') {
		$username	= @$_POST['username'];
		$hash1		= @$_POST['hash1'];
		$hash2		= @$_POST['hash2'];
		$sign		= @$_POST['sign'];
		
		if ($session['ip'] == '')
			message(__FILE__, __LINE__, 'error', '[b]Login failed[/b][br]netjukebox requires cookies to login.[br]Enable cookies in your browser and try again.[br][url=index.php][img]small_login.png[/img]login[/url]');
			
		if ($session['lock_ip'] && $session['ip'] != $_SERVER['REMOTE_ADDR'])
			message(__FILE__, __LINE__, 'error', '[b]Login failed[/b][br]Unexpected IP address[br][url=index.php][img]small_login.png[/img]login[/url]');
				
		$query		= mysqli_query($db, 'SELECT ' . (string) round(microtime(true) * 1000) . ' - pre_login_time AS login_delay FROM session WHERE ip = "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '" ORDER BY pre_login_time DESC LIMIT 1');
		$ip			= mysqli_fetch_assoc($query);
		
		$query		= mysqli_query($db, 'SELECT password, seed, user_id FROM user WHERE username = "' . mysqli_real_escape_string($db, $username) . '"');
		$user		= mysqli_fetch_assoc($query);
		$user_id	= $user['user_id'];

		if (// validate password
			$user['password'] == hmacsha1($hash1, $user['seed']) &&
			// sha1 collision protection
			preg_match('#^[0-9a-f]{40}$#', $hash1) &&
			// new password validation as far as possible
			preg_match('#^[0-9a-f]{40}$#', $hash2) &&
			(($username == $cfg['anonymous_user'] && $hash2 == hmacsha1(hmacsha1($cfg['anonymous_user'], $session['seed']), $session['seed'])) ||
			($username != $cfg['anonymous_user'] && $hash2 != hmacsha1(hmacsha1('', $session['seed']), $session['seed']))) &&
			// brute force & hack attack protection
			$ip['login_delay'] > $cfg['login_delay'] &&
			$session['user_agent'] == substr($_SERVER['HTTP_USER_AGENT'], 0, 255) &&
			$session['sign'] == $sign &&
			(($cfg['anonymous_autologin'] == true && $username == $cfg['anonymous_user']) || $cfg['anonymous_autologin'] == false)
		)
		{
			mysqli_query($db, 'UPDATE user SET
				password		= "' . mysqli_real_escape_string($db, $hash2) . '",
				seed			= "' . mysqli_real_escape_string($db, $session['seed']) . '"
				WHERE username	= "' . mysqli_real_escape_string($db, $username) . '"');
			
			$sign	= randomSeed();
			$sid	= randomSid();
			
			mysqli_query($db, 'UPDATE session SET
				logged_in		= 1,
				user_id			= ' . (int) $user_id . ',
				login_time		= ' . (int) time() . ',
				idle_time		= ' . (int) time() . ',
				ip				= "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '",
				sid				= "' . mysqli_real_escape_string($db, $sid) . '",
				sign			= "' . mysqli_real_escape_string($db, $sign) . '",
				hit_counter		= hit_counter + ' . ($disable_counter ? 0 : 1) . ',
				visit_counter	= visit_counter + ' . (time() > $session['idle_time'] + 3600 ? 1 : 0) . '
				WHERE sid		= BINARY "' . mysqli_real_escape_string($db, @$_COOKIE['netjukebox_sid']) . '"');
			
			setcookie('netjukebox_sid', $sid, time() + 31536000, null, null, NJB_HTTPS, true);
		}
		else
			logoutSession();
	}
	else {
		// Validate current session
		$user_id = $session['user_id'];
		
		if ($session['logged_in'] &&
			$session['idle_time'] + $cfg['session_lifetime'] > time() &&
			($session['lock_ip'] == false || $session['ip'] == $_SERVER['REMOTE_ADDR']) &&
			$session['user_agent']	== substr($_SERVER['HTTP_USER_AGENT'], 0, 255)) {
			
			mysqli_query($db, 'UPDATE session SET
				hit_counter		= hit_counter + ' . ($disable_counter ? 0 : 1) . ',
				visit_counter	= visit_counter + ' . (time() > $session['idle_time'] + 3600 ? 1 : 0) . ',
				idle_time		= ' . (int) time() . ',
				ip				= "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '"
				WHERE sid		= BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
		}
		elseif ($access == 'access_always')
			return true;
		else
			logoutSession();
	}
	
	
	// username & user privalages
	$query = mysqli_query($db, 'SELECT
		username,
		access_media,
		access_popular,
		access_favorite,
		access_cover,
		access_stream,
		access_download,
		access_playlist,
		access_play,
		access_add,
		access_record,
		access_statistics,
		access_admin,
		access_search
		FROM user
		WHERE user_id = ' . (int) $user_id);
	$user = mysqli_fetch_assoc($query);
	$cfg = array_merge($cfg, $user);
	
	
	// Logout
	if ($authenticate == 'logout' && $cfg['username'] != $cfg['anonymous_user']) {
		$query = mysqli_query($db, 'SELECT user_id FROM session
			WHERE logged_in
			AND user_id		= ' . (int) $user_id . '
			AND idle_time	> ' . (int) (time() - $cfg['session_lifetime']) );
		
		if (mysqli_affected_rows($db) > 1)	logoutMenu();
		else								logoutSession();	
	}
	elseif ($authenticate == 'logoutAllSessions' && $cfg['username'] != $cfg['anonymous_user']) {
		mysqli_query($db, 'UPDATE session
			SET logged_in	= 0
			WHERE user_id	= ' . (int) $user_id);
		logoutSession();
	}
	elseif ($authenticate == 'logoutSession' || $authenticate == 'logout')
		logoutSession();
	
	
	// Validate privilege
	$access_validated = false;
	if (is_array($access)) {
		foreach ($access as $value)
			if (isset($cfg[$value]) && $cfg[$value])	$access_validated = true;
	}
	elseif (isset($cfg[$access]) && $cfg[$access])		$access_validated = true;
	elseif ($access == 'access_logged_in')				$access_validated = true;
	elseif ($access == 'access_always')					$access_validated = true;
	if ($access_validated == false)
		message(__FILE__, __LINE__, 'warning', '[b]You have no privilege to access this page[/b][br][url=index.php?authenticate=logout][img]small_login.png[/img]Login as another user[/url]');
	
	// Validate signature
	if	($cfg['sign_validated'] == false &&
		($validate_sign ||
		$authenticate == 'logoutAllSessions' ||
		$authenticate == 'logoutSession')) {
		
		$cfg['sign'] = randomSeed();
		mysqli_query($db, 'UPDATE session
			SET	sign		= "' . mysqli_real_escape_string($db, $cfg['sign']) . '"
			WHERE sid		= BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
		if ($session['sign'] == @$_REQUEST['sign'])
			$cfg['sign_validated'] = true;
		else
			message(__FILE__, __LINE__, 'error', '[b]Digital signature has expired[/b]');
	}
	else
		$cfg['sign'] = $session['sign'];
	
	$cfg['user_id']				= $user_id;
	$cfg['sid']					= $sid;
	$cfg['session_seed']		= $session['seed'];
	$cfg['random_blacklist']	= $session['random_blacklist'];
	$cfg['thumbnail']			= $session['thumbnail'];
	$cfg['thumbnail_size']		= $session['thumbnail_size'];
	$cfg['stream_id']			= (isset($cfg['encode_extension'][$session['stream_id']])) ? $session['stream_id'] : -1;
	$cfg['download_id']			= (isset($cfg['encode_extension'][$session['download_id']])) ? $session['download_id'] : -1;
	$cfg['player_id']			= $session['player_id'];
}




//  +------------------------------------------------------------------------+
//  | Authenticate stream                                                    |
//  +------------------------------------------------------------------------+
function authenticateStream($cache = false) {
	global $cfg, $db;
	
	if ($cache == false) {
		header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
	}
	
	$action		= @$_GET['action'];
	$short_sid	= @$_GET['short_sid'];
	$hash		= @$_GET['hash'];
	$data		= $_GET;
	
	unset($data['short_sid'], $data['hash'], $data['ext'], $data['timestamp'], $data['menu'], $data['ajax']);
	ksort($data);
	$data = implode('', $data);
	
	$query = mysqli_query($db, 'SELECT user_id, idle_time, lock_ip, ip, sid, player_id, random_blacklist FROM session
		WHERE LEFT(sid, 8) = BINARY "' . mysqli_real_escape_string($db, $short_sid) . '"
		AND logged_in');
	
	while ($session = mysqli_fetch_assoc($query)) {
		if ($session['idle_time'] + $cfg['session_lifetime'] > time() &&
			($session['lock_ip'] == false || $session['ip'] == $_SERVER['REMOTE_ADDR']) &&
			$hash == hmacsha1($cfg['server_seed'] . $session['sid'], $data)) {

			$query2	= mysqli_query($db, 'SELECT access_stream, access_download, access_cover, access_admin FROM user WHERE user_id = ' . (int) $session['user_id']);
			$user 	= mysqli_fetch_assoc($query2);
			if ($action == 'm3u'			&& $user['access_stream'] ||
				$action == 'm3uPlaylist'	&& $user['access_stream'] ||
				$action == 'stream'			&& $user['access_stream'] ||
				$action == 'downloadTrack'	&& $user['access_download'] ||
				$action == 'downloadAlbum'	&& $user['access_download'] ||
				$action == 'downloadCover'	&& $user['access_cover'] ||
				$action == 'transcodeTrack'	&& $user['access_admin']) {
				$cfg['user_id']				= $session['user_id'];
				$cfg['sid']					= $session['sid'];
				$cfg['player_id']			= $session['player_id'];
				$cfg['random_blacklist']	= $session['random_blacklist'];
				
				return true;
			}
		}
	} 
	if (in_array($action, array('m3u', 'm3uPlaylist')))										message(__FILE__, __LINE__, 'error', '[b]Stream error[/b][br]Authentication failed');
	elseif (in_array($action, array('downloadTrack', 'downloadAlbum', 'downloadCover')))	message(__FILE__, __LINE__, 'error', '[b]Download error[/b][br]Authentication failed');
	
	header('HTTP/1.1 403 Forbidden');
	exit();
}




//  +------------------------------------------------------------------------+
//  | Logout menu                                                            |
//  +------------------------------------------------------------------------+
function logoutMenu() {
	global $cfg;
	require_once(NJB_HOME_DIR . 'include/header.inc.php');
?>
<form action="index.php" id="logoutform">
	<label><input type="radio" name="authenticate" value="logoutSession" checked class="space">Logout this session</label>
	<label><input type="radio" name="authenticate" value="logoutAllSessions" class="space">Logout all sessions</label>
	<input type="hidden" name="sign" value="<?php echo $cfg['sign']; ?>">
	<button type="submit" value="logout">Logout</button>
</form>
<?php
	require_once(NJB_HOME_DIR . 'include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Logout session                                                         |
//  +------------------------------------------------------------------------+
function logoutSession() {
	global $cfg, $db;
	
	$cfg['username']		= ''; // Footer
	$cfg['access_media']	= false; // Header opensearch
	
	$sid = @$_COOKIE['netjukebox_sid'];
	
	// Update current session
	mysqli_query($db, 'UPDATE session SET
		logged_in			= 0,
		ip					= "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '",
		user_agent			= "' . mysqli_real_escape_string($db, $_SERVER['HTTP_USER_AGENT']) . '"
		WHERE sid			= BINARY "' . mysqli_real_escape_string($db, $sid) . '"');




//  +------------------------------------------------------------------------+
//  | Login                                                                  |
//  +------------------------------------------------------------------------+
	$query		= mysqli_query($db, 'SELECT username FROM user WHERE username = "' . mysqli_real_escape_string($db, $cfg['anonymous_user']) . '"');
	$user		= mysqli_fetch_assoc($query);
	$anonymous	= $user['username'];
	
	$action = @$_GET['action'];
	if (NJB_SCRIPT == 'index.php' && substr($action, 0, 4) == 'view') {
		$url = 'index.php?';
		$get = $_GET;
		foreach ($get as $key => $value) {
			$url .= rawurlencode($key) . '=' . rawurlencode($value) . '&amp;';
		}
		$url = substr($url, 0, -5);
	}
	else
		$url = 'index.php';
		
	if ($cfg['admin_login_message'] == '')
		$cfg['admin_login_message'] = 'By continuing to use our site, you agree to the placement of cookies on your device.';
	
	require_once(NJB_HOME_DIR . 'include/header.inc.php');
?>
<form action="<?php echo $url; ?>" method="post" id="loginform" onsubmit="loginStage1(this.username.value); return false;">
	<input type="hidden" name="authenticate" value="validate">
	<input type="hidden" name="hash1" value="">
	<input type="hidden" name="hash2" value="">
	<input type="hidden" name="sign" value="">
<label>
	<span class="description">Username</span>
	<input class="input" type="text" name="username" value="<?php echo html($anonymous); ?>" maxlength="255" onkeyup="anonymousPassword();">
</label>
<label>
	<span class="description">Password</span>
	<input class="input" type="password" id="password" name="password">
</label>
<!--checkbox-->
	<div class="description">&nbsp;</div>
	<label><span class="input"><input type="checkbox" name="lock_ip" value="1" <?php echo (preg_match('#android|blackberry|ipad|ipod|mobi|palmos|phone|symbian|tablet|touchpad|webos#i', $_SERVER['HTTP_USER_AGENT'])) ? '' : 'checked '; ?>class="space">Lock to ip address</span></label>
<!-- submit -->
	<div class="description">&nbsp;</div>
	<button type="submit" value="login">Login</button>
<!-- footer -->
	<div class="footer"><?php echo bbcode($cfg['admin_login_message']); ?></div>
</form>


<script type="text/javascript">
function initialize() {
	if (typeof XMLHttpRequest != 'undefined') {
		loginform.username.focus();
		loginform.username.select();
		anonymousPassword();
<?php if ($cfg['anonymous_autologin']) echo "\t\t" . 'loginStage1(loginform.username.value)' . "\n"; ?>
	}
}


function anonymousPassword() {
	if (<?php echo ($anonymous) ? 'true' : 'false'; ?> && loginform.username.value == '<?php echo addslashes(html($anonymous)); ?>') {
		loginform.password.value = '';
		loginform.password.className = 'input readonly';
		// loginform.password.disabled = true;
	}
	else {
		loginform.password.className = 'input';
		// loginform.password.disabled = false;
	}
}


function loginStage1(username) {
	var request = 'action=loginStage1';
	request += '&username=' + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(username);
	request += '&lock_ip=' + ((loginform.lock_ip.checked == true) ? '1' : '0');
	request += '&ip=<?php echo rawurlencode($_SERVER['REMOTE_ADDR']); ?>';
	
	loginform.username.value = '';
	loginform.username.value = username;
	loginform.username.className = 'input readonly';
	loginform.password.className = 'input readonly';
	ajaxRequest('json.php', loginStage2, request);
}


function loginStage2(data) {
	// data.user_seed, data.session_seed, data.sign;	
	var password = loginform.password.value;
	loginform.password.value = '';
	if (<?php echo ($anonymous) ? 'true' : 'false'; ?> && loginform.username.value == '<?php echo addslashes(html($anonymous)); ?>')
		password = '<?php echo addslashes(html($anonymous)); ?>'
	
	loginform.hash1.value = hmacsha1(password, data.user_seed);
	loginform.hash2.value = hmacsha1(hmacsha1(password, data.session_seed), data.session_seed);
	loginform.sign.value = data.sign;
	password = '';
	setTimeout('loginform.submit();', <?php echo $cfg['login_delay']; ?>);
}
</script>
<?php
	require_once(NJB_HOME_DIR . 'include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Set skin                                                               |
//  +------------------------------------------------------------------------+
function setSkin($skin) {
	global $cfg, $db;
	
	if ($skin != '' && file_exists(NJB_HOME_DIR . 'skin/' . $skin . '/style.css')) {
		$cfg['skin']	= $skin;
		$cfg['img']		= 'skin/' . rawurlencode($skin) . '/img/';
		return true;
	}
	
	// Get session default skin
	$sid		= @$_COOKIE['netjukebox_sid'];
	$query		= mysqli_query($db, 'DESCRIBE session skin');
	$session 	= mysqli_fetch_assoc($query);
	if (file_exists(NJB_HOME_DIR . 'skin/' . $session['Default'] . '/style.css'))	{
		if ($skin == '') {
			mysqli_query($db, 'UPDATE session
				SET skin	= "' . mysqli_real_escape_string($db, $session['Default']) . '"
				WHERE sid	= BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
		}
		$cfg['skin']	= $session['Default'];
		$cfg['img']		= 'skin/' . rawurlencode($session['Default']) . '/img/';
		return true;
	}
	
	// Leave netjukebox skin set on top of this page and set it as default skin.
	mysqli_query($db, 'ALTER TABLE session CHANGE skin skin VARCHAR(255) NOT NULL DEFAULT "' . mysqli_real_escape_string($db, $cfg['skin']) . '"');
	return true;
}




//  +------------------------------------------------------------------------+
//  | Message: ok / warning / error                                          |
//  +------------------------------------------------------------------------+
function message($file, $line, $type, $message)	{
	global $cfg;
	if (php_sapi_name() == 'cli') {
		// Command line error message
		require_once(NJB_HOME_DIR . 'include/library.inc.php');
		echo "\n";
		echo strtoupper($type) . "\n";
		echo str_repeat('-', 79) . "\n";
		echo bbcode2txt($message);
		if ($cfg['debug']) {
			echo "\n";
			echo str_repeat('-', 79) . "\n";
			echo 'File: ' . $file . "\n";
			echo 'Line: ' . $line;
		}
		exit();
	}
	elseif (NJB_SCRIPT != 'message.php') {
		if (in_array(@$_GET['menu'], array('favorite', 'playlist', 'config')))
			$cfg['menu'] = $_GET['menu'];
		
		$url = NJB_HOME_URL;
		$url .= 'message.php';
		$url .= '?message=' . rawurlencode($message);
		$url .= '&type=' . rawurlencode($type);
		$url .= '&menu=' . rawurlencode($cfg['menu']);
		$url .= '&skin=' . rawurlencode($cfg['skin']);
		$url .= '&username=' . rawurlencode($cfg['username']);
		if ($cfg['debug']) {
			$url .= '&file=' . rawurlencode($file);
			$url .= '&line=' . rawurlencode($line);
		}
		$url .= '&sign=' . rawurlencode($cfg['sign']);
		$url .= '&timestamp=' . dechex(time());
		
		if (@$_GET['ajax'] == '1') {
			header('HTTP/1.1 500 Internal Server Error');
			echo safe_json_encode($url);
			exit();
		}
		elseif (headers_sent() == false) {
			header('Location: ' . $url);
			exit();
		}
		else
			exit('<script type="text/javascript">window.location.href="' . $url . '";</script>');
	}
}
