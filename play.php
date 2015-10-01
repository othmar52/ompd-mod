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
//  | play.php                                                               |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
header('Content-type: application/json');

$action	= @$_GET['action'];

if		($action == 'play')				play();
elseif	($action == 'pause')			pause();
elseif	($action == 'stop')				stop();
elseif	($action == 'prev')				prev_();
elseif	($action == 'next')				next_();
elseif	($action == 'playSelect')		playSelect();
elseif	($action == 'addSelect')		addSelect();
elseif	($action == 'seekImageMap')		seekImageMap();
elseif	($action == 'playIndex')		playIndex();
elseif	($action == 'deleteIndex')		deleteIndex();
elseif	($action == 'deletePlayed')		deletePlayed();
elseif	($action == 'deletePlaylist')	deletePlaylist();
elseif	($action == 'volumeImageMap')	volumeImageMap();
elseif	($action == 'toggleMute')		toggleMute();
elseif	($action == 'toggleShuffle')	toggleShuffle();
elseif	($action == 'toggleRepeat') 	toggleRepeat();
elseif	($action == 'loopGain')			loopGain();
elseif	($action == 'playlistStatus')	playlistStatus();
elseif	($action == 'playlistTrack')	playlistTrack();
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Play                                                                   |
//  +------------------------------------------------------------------------+
function play() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('play');
		if (@$_GET['menu'] == 'playlist') {
			echo (httpq('getlistlength')) ? '1' : '0';
		}
	}
	elseif ($cfg['player_type'] == NJB_VLC)
		vlc('pl_play');
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('stop');
		mpd('play');
		if (@$_GET['menu'] == 'playlist') {
			$status = mpd('status');
			echo ($status['playlistlength']) ? '1' : '0';
		}
	}
}




//  +------------------------------------------------------------------------+
//  | Pause                                                                  |
//  +------------------------------------------------------------------------+
function pause() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$isplaying = httpq('isplaying');
		httpq('pause');
		if (@$_GET['menu'] == 'playlist') {
			if ($isplaying == 0)	echo '0'; // stop
			if ($isplaying == 3)	echo '1'; // play
			if ($isplaying == 1)	echo '3'; // pause
		}
	}
	elseif ($cfg['player_type'] == NJB_VLC)
		vlc('pl_pause');
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		mpd('pause');
		if (@$_GET['menu'] == 'playlist') {
			if ($status['state'] == 'stop')		echo '0'; // stop
			if ($status['state'] == 'pause')	echo '1'; // play
			if ($status['state'] == 'play')		echo '3'; // pause
		}
	}
}




//  +------------------------------------------------------------------------+
//  | Stop                                                                   |
//  +------------------------------------------------------------------------+
function stop() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('stop');
		if (@$_GET['menu'] == 'playlist')
			echo '0';
	}
	elseif ($cfg['player_type'] == NJB_VLC) 
		vlc('pl_stop');
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('stop');
		if (@$_GET['menu'] == 'playlist')
			echo '0';
	}
}




//  +------------------------------------------------------------------------+
//  | Prev                                                                   |
//  +------------------------------------------------------------------------+
function prev_() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('prev');
		if (@$_GET['menu'] == 'playlist')
			echo (int) httpq('getlistpos');
	}
	elseif ($cfg['player_type'] == NJB_VLC)
		vlc('pl_previous');
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('previous');
		if (@$_GET['menu'] == 'playlist') {
			$status = mpd('status');
			echo isset($status['song']) ? (int) $status['song'] : 0;
		}
	}	
}




//  +------------------------------------------------------------------------+
//  | Next                                                                   |
//  +------------------------------------------------------------------------+
function next_() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('next');
		if (@$_GET['menu'] == 'playlist')
			echo (int) httpq('getlistpos');
	}
	elseif ($cfg['player_type'] == NJB_VLC)
		vlc('pl_next');
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('next');
		if (@$_GET['menu'] == 'playlist') {
			$status = mpd('status');
			echo isset($status['song']) ? (int) $status['song'] : 0;
		}
	}	
}




//  +------------------------------------------------------------------------+
//  | Play select                                                            |
//  +------------------------------------------------------------------------+
function playSelect() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	$stack = (@$_GET['track_id']) ? true : false;
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		if ($stack) {
			httpq('stop');
			addTracks('stack');
		}
		else {
			httpq('stop');
			httpq('delete');
			addTracks('play');
		}
	}
	elseif ($cfg['player_type'] == NJB_VLC) {
		vlc('pl_empty');
		addTracks('play');
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		if ($stack) {
			mpd('stop');
			addTracks('stack');
		}
		else {
			mpd('stop');
			mpd('clear');
			addTracks('play');
		}
	}	
}




//  +------------------------------------------------------------------------+
//  | Add select                                                             |
//  +------------------------------------------------------------------------+
function addSelect() {
	global $cfg, $db;
	authenticate('access_add');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		if (httpq('getlistlength') == 0)	addTracks('play');
		else								addTracks('add');
	}
	elseif ($cfg['player_type'] == NJB_VLC) {
		addTracks('add');
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		if ($status['playlistlength'] == 0)	addTracks('play');
		else								addTracks('add');		
	}
}




//  +------------------------------------------------------------------------+
//  | Add tracks                                                             |
//  +------------------------------------------------------------------------+
function addTracks($mode = 'play') {
	global $cfg, $db;
	
	$track_id		= @$_GET['track_id'];
	$album_id		= @$_GET['album_id'];
	$favorite_id	= @$_GET['favorite_id'];
	$random			= @$_GET['random'];
	
	if ($track_id) {
		$query = mysqli_query($db, 'SELECT relative_file FROM track WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	}
	elseif ($album_id) {
		$query = mysqli_query($db, 'SELECT relative_file FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '" ORDER BY relative_file');
	}
	elseif ($favorite_id) {
		$query = mysqli_query($db, 'SELECT stream FROM favorite WHERE favorite_id = ' . (int) $favorite_id . ' AND stream = 1');
		if (mysqli_fetch_row($query)) {
			playStream($favorite_id);
			exit();
		}
		
		$query	= mysqli_query($db, 'SELECT relative_file
			FROM track, favoriteitem
			WHERE favoriteitem.track_id = track.track_id 
			AND favorite_id = "' . mysqli_real_escape_string($db, $favorite_id) . '"
			ORDER BY position');
	}
	elseif ($random == 'database') {
		$query = mysqli_query($db, 'SELECT relative_file
			FROM track, random
			WHERE random.sid	= "' . mysqli_real_escape_string($db, @$_COOKIE['netjukebox_sid']) . '" AND
			random.track_id		= track.track_id
			ORDER BY position');
	}
	elseif ($random == 'generate') {
		$blacklist = explode(',', $cfg['random_blacklist']);
		$blacklist = '"' . implode('","', $blacklist) . '"';
		$query = mysqli_query($db, 'SELECT relative_file
			FROM track, album
			WHERE (genre_id = "" OR genre_id NOT IN (' . $blacklist . ')) AND
			audio_dataformat != "" AND
			video_dataformat = "" AND
			track.album_id = album.album_id
			ORDER BY RAND()
			LIMIT 30');
	}
	else {
		message(__FILE__, __LINE__, 'error', '[b]Unsupported query string[/b][br]' . $_SERVER['QUERY_STRING']);
	}
	
	$index = 0;
	if ($mode == 'stack' && $cfg['player_type'] == NJB_MPD) {
		$status		= mpd('status');
		$index		= $status['playlistlength'];
	}
	elseif ($mode == 'stack' && $cfg['player_type'] == NJB_HTTPQ) {
		$index		= httpq('getlistlength');
	}
	
	$first = true;
	while ($track = mysqli_fetch_assoc($query)) {
		if ($cfg['player_type'] == NJB_HTTPQ) {
			$file = $cfg['media_share'] . $track['relative_file'];
			$file = str_replace('/', '\\', $file);
			httpq('playfile', 'file=' . rawurlencode($file));
			if ($mode != 'add' && $first) {
				httpq('setplaylistpos', 'index=' . $index);
				httpq('play');
			}
		}
		elseif ($cfg['player_type'] == NJB_VLC) {
			$file = $cfg['media_share'] . $track['relative_file'];
			$file = addslashes($file);
			$file = iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $file);
			vlc('in_enqueue&input=' . rawurlencode($file));
			if ($mode != 'add' && $first)
				vlc('pl_play');
		}
		elseif ($cfg['player_type'] == NJB_MPD) {
			$file = $track['relative_file'];
			$file = str_replace('"', '\"', $file);
			$file = iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $file);
			mpd('add "' . $file . '"');
			if ($mode != 'add' && $first) {
				mpd('play ' . $index);
			}
		}
		$first = false;
	}
	if ($album_id)
		updateCounter($album_id, NJB_COUNTER_PLAY);
}




//  +------------------------------------------------------------------------+
//  | Play Stream                                                            |
//  +------------------------------------------------------------------------+
function playStream($favorite_id) {
	global $db, $cfg;
	
	$first = true;
	$query = mysqli_query($db, 'SELECT stream_url FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' AND stream_url != "" ORDER BY position');
	while ($favoriteitem = mysqli_fetch_assoc($query)) {
		if ($cfg['player_type'] == NJB_HTTPQ) {
			httpq('playfile', 'file=' . rawurlencode($favoriteitem['stream_url']));
			if ($first)
				httpq('play');
		}
		elseif ($cfg['player_type'] == NJB_VLC) {
			$file = addslashes($file);
			$file = iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $file);
			vlc('in_enqueue&input=' . rawurlencode($favoriteitem['stream_url']));
			if ($first)
				vlc('pl_play');
		}
		elseif ($cfg['player_type'] == NJB_MPD) {
			$file = iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $file);
			mpd('add ' . $favoriteitem['stream_url']);
			if ($first)
				mpd('play');
		}
		$first = false;
	}
}




//  +------------------------------------------------------------------------+
//  | Seek image map                                                         |
//  +------------------------------------------------------------------------+
function seekImageMap() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	$dx	= @$_GET['dx'];
	$x	= @$_GET['x'];
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$file	= httpq('getplaylistfile');
		
		$relative_file = str_replace('\\', '/', $file);
		$relative_file = substr($relative_file, strlen($cfg['media_share']));
		
		$query 	= mysqli_query($db, 'SELECT miliseconds FROM track WHERE relative_file = "' . mysqli_real_escape_string($db, $relative_file) . '"');
		$track 	= mysqli_fetch_assoc($query);
		
		$miliseconds = round($track['miliseconds'] * $x / ($dx-1));
		httpq('jumptotime', 'ms=' . $miliseconds);
		
		if (@$_GET['menu'] == 'playlist') {
			$data = array();
			$data['miliseconds']	= (int) $miliseconds;
			$data['max']			= (int) $track['miliseconds'];
			echo safe_json_encode($data);			
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$currentsong	= mpd('currentsong');
			
		$query = mysqli_query($db, 'SELECT miliseconds FROM track WHERE relative_file = "' . mysqli_real_escape_string($db, $currentsong['file']) . '"');
		$track = mysqli_fetch_assoc($query);
		
		$miliseconds = round($track['miliseconds'] * $x / ($dx-1));
		mpd('seek ' . $currentsong['Pos'] .  ' ' . (round($miliseconds / 1000))); //seek in seconds
		
		if (@$_GET['menu'] == 'playlist') {
			$data = array();
			$data['miliseconds']	= (int) $miliseconds;
			$data['max']			= (int) $track['miliseconds'];
			echo safe_json_encode($data);
		}
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +------------------------------------------------------------------------+
//  | Play index                                                             |
//  +------------------------------------------------------------------------+
function playIndex() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	$index = (int) @$_GET['index'];
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('stop');
		httpq('setplaylistpos', 'index=' . $index);
		httpq('play');
		if (@$_GET['menu'] == 'playlist') {
			echo $index;
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('stop');
		mpd('play ' . $index);
		if (@$_GET['menu'] == 'playlist') {
			echo $index;
		}
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +------------------------------------------------------------------------+
//  | Delete index                                                           |
//  +------------------------------------------------------------------------+
function deleteIndex() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	$index = (int) @$_GET['index'];
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('deletepos', 'index=' . $index);
		if (@$_GET['menu'] == 'playlist') {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('delete ' . $index);
		if (@$_GET['menu'] == 'playlist') {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +------------------------------------------------------------------------+
//  | Delete played                                                          |
//  +------------------------------------------------------------------------+
function deletePlayed() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$listpos = httpq('getlistpos');
		for ($i = 0; $i < $listpos; $i++) {
			httpq('deletepos', 'index=0');
		}
		if (@$_GET['menu'] == 'playlist' && $listpos > 0) {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
	elseif ($cfg['player_type'] == NJB_VLC) {
		vlc('pl_empty'); // Not supported yet, clear whole playlist instead!
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		if (version_compare($cfg['mpd_version'], '0.16.0', '<')) {
			for ($i = 0; $i < $status['song']; $i++) {
				mpd('delete 0');
			}
		}
		else {
			mpd('delete 0:' . $status['song']);
		}
		if (@$_GET['menu'] == 'playlist' && $status['song'] > 0) {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
}




//  +------------------------------------------------------------------------+
//  | Delete playlist                                                        |
//  +------------------------------------------------------------------------+
function deletePlaylist() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$listlength = httpq('getlistlength');
		httpq('delete');
		
		if (@$_GET['menu'] == 'playlist' && $listlength > 0) {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
	elseif ($cfg['player_type'] == NJB_VLC) {
		vlc('pl_empty');
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		mpd('clear');
		
		if (@$_GET['menu'] == 'playlist' && $status['playlistlength'] > 0) {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
}




//  +------------------------------------------------------------------------+
//  | Volume image map                                                       |
//  +------------------------------------------------------------------------+
function volumeImageMap() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	$dx         = (int) @$_GET['dx'];
	$x			= (int) @$_GET['x'];
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$volume		= round(255 * $x / ($dx-1));
		if ($volume < round(255 * 0.05)) $volume = 0; // set volume to zero
		if ($volume > round(255 * 0.95)) $volume = 255; // set volume to max
		httpq('setvolume', 'level=' . $volume);
		
		mysqli_query($db, 'UPDATE player
					SET mute_volume	= ' . (int) $volume . '
					WHERE player_id	= ' . (int) $cfg['player_id']);
		
		if (@$_GET['menu'] == 'playlist')
			echo $volume;
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$volume		= round(100 * $x / ($dx-1));
		if ($volume < round(100 * 0.05)) $volume = 0; // set volume to zero
		if ($volume > round(100 * 0.95)) $volume = 100; // set volume to max
		mpd('setvol ' . $volume);
		
		mysqli_query($db, 'UPDATE player
					SET mute_volume	= ' . (int) $volume . '
					WHERE player_id	= ' . (int) $cfg['player_id']);
		
		if (@$_GET['menu'] == 'playlist')
			echo $volume;
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +------------------------------------------------------------------------+
//  | Toggle mute                                                            |
//  +------------------------------------------------------------------------+
function toggleMute() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$volume	= httpq('getvolume');
		
		if ($volume == 0) {
			$query = mysqli_query($db, 'SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$player = mysqli_fetch_assoc($query);
			
			httpq('setvolume', 'level=' . $player['mute_volume']);
			mysqli_query($db, 'UPDATE player
				SET mute_volume	= 0
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = $player['mute_volume'];
		}
		else {
			httpq('setvolume', 'level=0');
			mysqli_query($db, 'UPDATE player
				SET mute_volume	= ' . (int) $volume . '
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = -$volume;
		}
		
		if (@$_GET['menu'] == 'playlist')
			echo $volume;
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$volume	= $status['volume'];
		
		if ($volume == 0) {
			$query = mysqli_query($db, 'SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$player = mysqli_fetch_assoc($query);
			
			mpd('setvol ' . $player['mute_volume']);
			mysqli_query($db, 'UPDATE player
				SET mute_volume	= 0
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = $player['mute_volume'];
		}
		else {
			mpd('setvol 0');
			mysqli_query($db, 'UPDATE player
				SET mute_volume	= ' . (int) $volume . '
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = -$volume;
		}
		if (@$_GET['menu'] == 'playlist')
			echo $volume;
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +------------------------------------------------------------------------+
//  | Toggle shuffle                                                         |
//  +------------------------------------------------------------------------+
function toggleShuffle() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$invert = (int) (httpq('shuffle_status') xor 1);
		
		httpq('shuffle', 'enable=' . $invert);
		if (@$_GET['menu'] == 'playlist')
			echo $invert;
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$invert = (int) ($status['random'] xor 1);
		
		mpd('random ' . $invert);
		if (@$_GET['menu'] == 'playlist')
			echo $invert;
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +------------------------------------------------------------------------+
//  | Toggle repeat                                                          |
//  +------------------------------------------------------------------------+
function toggleRepeat() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$invert = (int) (httpq('repeat_status') xor 1);
		httpq('repeat', 'enable=' . $invert);
		
		if (@$_GET['menu'] == 'playlist')
			echo $invert;
	}	
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$invert = (int) ($status['repeat'] xor 1);
		
		mpd('repeat ' . $invert);
		if (@$_GET['menu'] == 'playlist')
			echo $invert;
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +------------------------------------------------------------------------+
//  | Loop gain                                                              |
//  +------------------------------------------------------------------------+
function loopGain() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_MPD) {
		$gain = mpd('replay_gain_status');
		$status = mpd('status'); 
		if ($gain['replay_gain_mode'] == 'off')	{
			$mode	= 'album';
			$gain	= 'album';
			$sec	= 0;
		}
		if ($gain['replay_gain_mode'] == 'album') {
			$mode	= 'track';
			$gain	= 'track';
			$sec	= 0;
		}
		if ($gain['replay_gain_mode'] == 'track' && isset($status['xfade']) == false) {
			$mode	= 'fade';
			$gain	= 'track';
			$sec	= 15;
		}
		if ($gain['replay_gain_mode'] == 'track' && isset($status['xfade']) == true) {
			$mode	= 'off';
			$gain	= 'off';
			$sec	= 0;
		}
		mpd('replay_gain_mode ' . $gain);
		mpd('crossfade ' . $sec);
		if (@$_GET['menu'] == 'playlist')
			echo '"' . $mode . '"';
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}




//  +---------------------------------------------------------------------------+
//  | Playlist status                                                           |
//  +---------------------------------------------------------------------------+
function playlistStatus() {
	global $cfg, $db;
	authenticate('access_playlist', false, false, true);
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		// volume
		$volume	= (int) httpq('getvolume');
		
		// get mute volume
		if ($volume == 0) {
			$query	= mysqli_query($db, 'SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$temp	= mysqli_fetch_assoc($query);
			$volume = -$temp['mute_volume'];
		}
		
		$data = array();
		$data['hash']			= (string) httpq('gethash');
		$data['miliseconds']	= (int) httpq('getoutputtime', 'frmt=0');
		$data['listpos']		= (int) httpq('getlistpos');
		$data['isplaying']		= (int) httpq('isplaying');
		$data['repeat']			= (int) httpq('repeat_status');
		$data['shuffle']		= (int) httpq('shuffle_status');
		$data['volume']			= (int) $volume;
		$data['gain']			= -1;
		echo safe_json_encode($data);
	}
	if ($cfg['player_type'] == NJB_MPD) {
		$playlist	= mpd('playlist');
		$status 	= mpd('status');
		
		$data = array();
		$data['hash']			= md5(implode('<seperation>', $playlist));
		$data['listpos']		= isset($status['song']) ? (int) $status['song'] : 0;
		$data['volume']			= (int) $status['volume'];
		$data['repeat']			= (int) $status['repeat'];
		$data['shuffle']		= (int) $status['random'];
		
		$data['isplaying'] = 0;
		if ($status['state'] == 'stop')		$data['isplaying'] = 0;
		if ($status['state'] == 'play')		$data['isplaying'] = 1;
		if ($status['state'] == 'pause')	$data['isplaying'] = 3;
		
		$data['miliseconds'] = ($status['state'] == 'stop') ? 0 : (int) round($status['elapsed'] * 1000);
		
		$data['gain'] = -1;
		if (version_compare($cfg['mpd_version'], '0.16.0', '>=')) {
			$gain = mpd('replay_gain_status');
			$data['gain'] = ($gain['replay_gain_mode'] == 'track' && isset($status['xfade'])) ? 'fade' : (string) $gain['replay_gain_mode'];
		}
		
		// get mute volume
		if ($data['volume'] == 0) {
			$query	= mysqli_query($db, 'SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$temp	= mysqli_fetch_assoc($query);
			$data['volume'] = -$temp['mute_volume'];
		}
		echo safe_json_encode($data);	
	}
}




//  +---------------------------------------------------------------------------+
//  | Playlist track                                                            |
//  +---------------------------------------------------------------------------+
function playlistTrack() {
	global $cfg, $db;
	authenticate('access_playlist', false, false, true);
	
	$track_id = @$_GET['track_id'];
	
	$query = mysqli_query($db, 'SELECT track.artist, album.artist AS album_artist, title, featuring, miliseconds, relative_file, album, album.image_id, album.album_id
		FROM track, album 
		WHERE track.album_id = album.album_id
		AND track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	$track = mysqli_fetch_assoc($query);
	
	$by = ($track['artist'] != $track['album_artist'] && !in_array(strtolower($track['album_artist']), $cfg['no_album_artist'])) ? $track['album_artist'] : '';
		
	$data = array();
	$data['artist']		= (string) $track['artist'];
	$data['title']		= (string) $track['title'];
	$data['album']		= (string) $track['album'];
	$data['by']			= (string) $by;
	$data['image_id']	= (string) $track['image_id'];
	$data['album_id']	= (string) $track['album_id'];
	echo safe_json_encode($data);
}
