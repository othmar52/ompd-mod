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
//  | json.php                                                               |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
header('Content-type: application/json');

$action = @$_REQUEST['action'];

if		($action == 'suggestAlbumArtist')	suggestAlbumArtist();
elseif	($action == 'suggestTrackArtist')	suggestTrackArtist();
elseif	($action == 'suggestTrackTitle')	suggestTrackTitle();
elseif	($action == 'loginStage1')			loginStage1();
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Suggest album artist                                                   |
//  +------------------------------------------------------------------------+
function suggestAlbumArtist() {
	global $cfg, $db;
	authenticate('access_media', false, false, true);
	$artist = @$_GET['artist'];
	
	if ($artist == '')
		exit('[""]');
		
	$query = mysqli_query($db, 'SELECT artist_alphabetic FROM album
		WHERE artist_alphabetic LIKE "%' . mysqli_real_escape_like($db, $artist) . '%"
		OR artist LIKE "%' . mysqli_real_escape_like($db, $artist) . '%"
		OR artist SOUNDS LIKE "' . mysqli_real_escape_string($db, $artist) . '"
		GROUP BY artist_alphabetic ORDER BY artist_alphabetic LIMIT ' . (int) $cfg['autosuggest_limit']);
	
	$data = array();
	while ($album = mysqli_fetch_assoc($query))
		$data[] = (string) $album['artist_alphabetic'];
	
	echo safe_json_encode($data);
}




//  +------------------------------------------------------------------------+
//  | Suggest track artist                                                   |
//  +------------------------------------------------------------------------+
function suggestTrackArtist() {
	global $cfg, $db;
	authenticate('access_media', false, false, true);
	$artist = @$_GET['artist'];
	
	if ($artist == '')
		exit('[""]');
		
	$query = mysqli_query($db, 'SELECT artist FROM track
		WHERE artist LIKE "%' . mysqli_real_escape_like($db, $artist) . '%"
		OR artist SOUNDS LIKE "' . mysqli_real_escape_string($db, $artist) . '"
		GROUP BY artist ORDER BY artist LIMIT ' . (int) $cfg['autosuggest_limit']);
	
	$data = array();
	while ($album = mysqli_fetch_assoc($query))
		$data[] = (string) $album['artist'];
	
	echo safe_json_encode($data);
}




//  +------------------------------------------------------------------------+
//  | Suggest track title                                                    |
//  +------------------------------------------------------------------------+
function suggestTrackTitle() {
	global $cfg, $db;
	authenticate('access_media', false, false, true);
	$title = @$_GET['title'];
	
	if ($title == '')
			exit('[""]');
		
	$query = mysqli_query($db, 'SELECT title FROM track
		WHERE title LIKE "%' . mysqli_real_escape_like($db, $title) . '%"
		OR title SOUNDS LIKE "' . mysqli_real_escape_string($db, $title) . '"
		GROUP BY title ORDER BY title LIMIT ' . (int) $cfg['autosuggest_limit']);
	
	$data = array();
	while ($track = mysqli_fetch_assoc($query))
		$data[] = (string) $track['title'];
		
	echo safe_json_encode($data);
}




//  +------------------------------------------------------------------------+
//  | Login stage 1                                                          |
//  +------------------------------------------------------------------------+
function loginStage1() {
	global $cfg, $db;
	header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
		
	$username		= @$_POST['username'];
	$lock_ip		= @$_POST['lock_ip'] ? 1 : 0;
	$ip				= @$_POST['ip'];
	$sid			= @$_COOKIE['netjukebox_sid'];
	$sign			= randomSeed();
	$session_seed	= randomSeed();
	
	if ($lock_ip && $ip != $_SERVER['REMOTE_ADDR'])
		message(__FILE__, __LINE__, 'error', '[b]Login failed[/b][br]Unexpected IP address[br][url=index.php][img]small_login.png[/img]login[/url]');
	
	// Update current session
	mysqli_query($db, 'UPDATE session SET
		logged_in			= 0,
		ip					= "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '",
		user_agent			= "' . mysqli_real_escape_string($db, $_SERVER['HTTP_USER_AGENT']) . '",
		sign				= "' . mysqli_real_escape_string($db, $sign) . '",
		seed				= "' . mysqli_real_escape_string($db, $session_seed) . '",
		pre_login_time		= ' . (string) round(microtime(true) * 1000) . ',
		lock_ip				= ' . (int) $lock_ip . '
		WHERE sid			= BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
	if (mysqli_affected_rows($db) == 0) {
		// Create new session
		$sid = randomSid();
		
		mysqli_query($db, 'INSERT INTO session (logged_in, create_time, ip, user_agent, sid, sign, seed, pre_login_time, lock_ip) VALUES (
			0,
			' . (int) time() . ',
			"' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '",
			"' . mysqli_real_escape_string($db, $_SERVER['HTTP_USER_AGENT']) . '",
			"' . mysqli_real_escape_string($db, $sid) . '",
			"' . mysqli_real_escape_string($db, $sign) . '",
			"' . mysqli_real_escape_string($db, $session_seed) . '",
			' . (string) round(microtime(true) * 1000) . ',
			' . (int) $lock_ip . ')');
			
		setcookie('netjukebox_sid', $sid, time() + 31536000, null, null, NJB_HTTPS, true);
	}
	
	$query		= mysqli_query($db, 'SELECT seed FROM user WHERE username = "' . mysqli_real_escape_string($db, $username) . '"');
	$user 		= mysqli_fetch_assoc($query);
	
	// Always calculate fake seed to prevent script execution time differences
	$fake_seed	= substr(hmacsha1($cfg['server_seed'], $username . 'NeZlFgqDoh9hc-BkczryQFIcpoBng3I_vXaWtOKS'), 0, 30);
	$fake_seed	.= substr(hmacsha1($cfg['server_seed'], $username . 'g-FE6H0MJ1n0lNo2D7XLachV8WE-xmEcwsXNZqlQ'), 0, 30);
	$fake_seed	= hex_to_base64url($fake_seed);
		
	$data = array();
	$data['user_seed']		= (string) ($user['seed'] == '') ? $fake_seed : $user['seed'];
	$data['session_seed']	= (string) $session_seed;
	$data['sign']			= (string) $sign;
	echo safe_json_encode($data);
}
