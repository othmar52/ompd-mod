<?php
//  +------------------------------------------------------------------------+
//  | O!MPD, Copyright © 2015 Artur Sierzant		                         |
//  | http://www.ompd.pl                                             		 |
//  |                                                                        |
//  |                                                                        |
//  | netjukebox, Copyright © 2001-2012 Willem Bartels                       |
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
//require_once('include/library.inc.php');
//require_once('include/config.inc.php');
header('Content-type: application/json');

$action	= get('action');

if		($action == 'play')				play();
elseif	($action == 'pause')			pause();
elseif	($action == 'stop')				stop();
elseif	($action == 'prev')				prev_();
elseif	($action == 'next')				next_();
elseif	($action == 'playSelect')		playSelect();
elseif	($action == 'addSelect')		addSelect();
elseif	($action == 'insertSelect')		insertSelect();
elseif	($action == 'seekImageMap')		seekImageMap();
elseif	($action == 'playIndex')		playIndex();
elseif	($action == 'deleteIndex')		deleteIndex();
elseif	($action == 'deleteIndexAjax')	deleteIndexAjax();
elseif	($action == 'deletePlayed')		deletePlayed();
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
		if (get('menu') == 'playlist') {
			echo (httpq('getlistlength')) ? '1' : '0';
		}
	}
	elseif ($cfg['player_type'] == NJB_VLC)
		vlc('pl_play');
	elseif ($cfg['player_type'] == NJB_MPD) {
		//mpd('stop');
		mpd('play');
		if (get('menu') == 'playlist') {
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
		if (get('menu') == 'playlist') {
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
		if (get('menu') == 'playlist') {
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
		if (get('menu') == 'playlist')
			echo '0';
	}
	elseif ($cfg['player_type'] == NJB_VLC) 
		vlc('pl_stop');
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('stop');
		if (get('menu') == 'playlist')
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
	
	if ($cfg['player_type'] == NJB_HTTPQ)		httpq('prev');
	elseif ($cfg['player_type'] == NJB_VLC)		vlc('pl_previous');
	elseif ($cfg['player_type'] == NJB_MPD)		mpd('previous');
}




//  +------------------------------------------------------------------------+
//  | Next                                                                   |
//  +------------------------------------------------------------------------+
function next_() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ)		httpq('next');
	elseif ($cfg['player_type'] == NJB_VLC)		vlc('pl_next');
	elseif ($cfg['player_type'] == NJB_MPD)		mpd('next');
}




//  +------------------------------------------------------------------------+
//  | Play select                                                            |
//  +------------------------------------------------------------------------+
function playSelect() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('stop');
		if ($cfg['play_queue'] == false)
			httpq('delete');
		addTracks('play');
	}
	elseif ($cfg['player_type'] == NJB_VLC) {
		vlc('pl_empty');
		addTracks('play');
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('stop');
		if ($cfg['play_queue'] == false)
			mpd('clear');
		addTracks('play');
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
		if ($cfg['add_autoplay'] && httpq('getlistlength') == 0)	addTracks('play');
		else														addTracks('add');
	}
	elseif ($cfg['player_type'] == NJB_VLC) {
		addTracks('add');
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		if ($cfg['add_autoplay'] && $status['playlistlength'] == 0)	addTracks('play');
		else														addTracks('add');		
	}
	
	return 'add_OK';
}


//  +------------------------------------------------------------------------+
//  | Insert select                                                             |
//  +------------------------------------------------------------------------+
function insertSelect() {
	global $cfg, $db;
	authenticate('access_add');
	require_once('include/play.inc.php');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
	}
	elseif ($cfg['player_type'] == NJB_VLC) {	
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$insPos = $status['song'] + 1;
		$playAfterInsert= get('playAfterInsert');
		if ($status['playlistlength'] == 0)	addTracks('play');
		else								addTracks('addid',$insPos, $playAfterInsert);		
	}
	
	return 'add_OK';
}


//  +------------------------------------------------------------------------+
//  | Add tracks                                                             |
//  +------------------------------------------------------------------------+
function addTracks($mode = 'play', $insPos = '', $playAfterInsert) {
	global $cfg, $db;
	
	$track_id		= get('track_id');
	$album_id		= get('album_id');
	$favorite_id	= get('favorite_id');
	$random			= get('random');
	
	if ($track_id) {
		$query = mysql_query('SELECT relative_file FROM track WHERE track_id = "' . mysql_real_escape_string($track_id) . '"');
	}
	elseif ($album_id) {
		$query = mysql_query('SELECT relative_file FROM track WHERE album_id = "' . mysql_real_escape_string($album_id) . '" ORDER BY relative_file');
	}
	elseif ($favorite_id) {
		$query = mysql_query('SELECT stream FROM favorite WHERE favorite_id = ' . (int) $favorite_id . ' AND stream = 1');
		if (mysql_fetch_row($query))
			playStream($favorite_id);
		
		$query	= mysql_query('SELECT relative_file
			FROM track, favoriteitem
			WHERE favoriteitem.track_id = track.track_id 
			AND favorite_id = "' . mysql_real_escape_string($favorite_id) . '"
			ORDER BY position');
	}
	elseif ($random == 'database') {
		$query = mysql_query('SELECT relative_file
			FROM track, random
			WHERE random.sid	= "' . mysql_real_escape_string(cookie('netjukebox_sid')) . '" AND
			random.track_id		= track.track_id
			ORDER BY position');
	}
	elseif ($random == 'new') {
		$blacklist = explode(',', $cfg['random_blacklist']);
		$blacklist = '"' . implode('","', $blacklist) . '"';
		$query = mysql_query('SELECT relative_file
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
	
	if ($cfg['play_queue'] == false)
		$index = 0;
	elseif ($cfg['player_type'] == NJB_HTTPQ) {
		$index = httpq('getlistlength');
	}
	elseif ($cfg['player_type'] == NJB_VLC) {
		$index = 0;
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$index = $status['playlistlength'];
		$insPos = $status['song'];
	}
	
	$n = $index;
	$first = true;
	while ($track = mysql_fetch_assoc($query)) {
		if ($cfg['player_type'] == NJB_HTTPQ) {
			$file = $cfg['media_share'] . $track['relative_file'];
			$file = str_replace('/', '\\', $file);
			httpq('playfile', 'file=' . rawurlencode($file));
			if ($first && $mode == 'play') {
				httpq('setplaylistpos', 'index=' . $index);
				httpq('play');
			}
		}
		elseif ($cfg['player_type'] == NJB_VLC) {
			$file = $cfg['media_share'] . $track['relative_file'];
			$file = addslashes($file);
			$file = iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $file);
			vlc('in_enqueue&input=' . rawurlencode($file));
			if ($first && $mode == 'play')
				vlc('pl_play');
		}
		elseif ($cfg['player_type'] == NJB_MPD) {
			$file = $track['relative_file'];
			$file = iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $file);
			mpd('addid "' . $file . '" ' . $insPos);
			if ($playAfterInsert) {mpd('play ' . $insPos);}
			if ($first && $mode == 'play')
				mpd('play ' . $index);
		}
		$n++;
		$first = false;
	}
	
	if ($cfg['play_queue'] && $mode == 'play' && $n > $cfg['play_queue_limit']) {		
		if ($cfg['player_type'] == NJB_HTTPQ) {
			for ($i = 0; $i < $n - $cfg['play_queue_limit']; $i++) {
				httpq('deletepos', 'index=0');
			}
		}
		elseif ($cfg['player_type'] == NJB_MPD) {
			$status = mpd('status');
			if (version_compare($cfg['mpd_version'], '0.16.0', '<')) {
				for ($i = 0; $i < $n- $cfg['play_queue_limit']; $i++) {
					mpd('delete 0');
				}
			}
			else {
				mpd('delete 0:' . ($n - $cfg['play_queue_limit']));
			}
		}
	}
	if ($album_id)
		updateCounter($album_id, NJB_COUNTER_PLAY);
	
	return 'add_OK';
}




//  +------------------------------------------------------------------------+
//  | Play Stream                                                            |
//  +------------------------------------------------------------------------+
function playStream($favorite_id) {
	global $db, $cfg;
	
	$first = true;
	$query = mysql_query('SELECT stream_url FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' AND stream_url != "" ORDER BY position');
	while ($favoriteitem = mysql_fetch_assoc($query)) {
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
	exit();
}




//  +------------------------------------------------------------------------+
//  | Seek image map                                                         |
//  +------------------------------------------------------------------------+
function seekImageMap() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	$dx	= get('dx');
	$x	= get('x');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$file	= httpq('getplaylistfile');
		
		$relative_file = str_replace('\\', '/', $file);
		$relative_file = substr($relative_file, strlen($cfg['media_share']));
		
		$query 	= mysql_query('SELECT miliseconds FROM track WHERE relative_file = "' . mysql_real_escape_string($relative_file) . '"');
		$track 	= mysql_fetch_assoc($query);
		
		$miliseconds = round($track['miliseconds'] * $x / ($dx-1));
		httpq('jumptotime', 'ms=' . $miliseconds);
		
		if (get('menu') == 'playlist') {
			$data = array();
			$data['miliseconds']	= (int) $miliseconds;
			$data['max']			= (int) $track['miliseconds'];
			echo safe_json_encode($data);			
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$currentsong	= mpd('currentsong');
			
		$query = mysql_query('SELECT miliseconds FROM track WHERE relative_file = "' . mysql_real_escape_string($currentsong['file']) . '"');
		$track = mysql_fetch_assoc($query);
		
		$miliseconds = round($track['miliseconds'] * $x / ($dx-1));
		mpd('seek ' . $currentsong['Pos'] .  ' ' . (round($miliseconds / 1000))); //seek in seconds
		
		if (get('menu') == 'playlist') {
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
	
	$index = (int) get('index');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('stop');
		httpq('setplaylistpos', 'index=' . $index);
		httpq('play');
		if (get('menu') == 'playlist') {
			echo $index;
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		mpd('stop');
		mpd('play ' . $index);
		if (get('menu') == 'playlist') {
			echo $index;
		}
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Command not supported for this player[/b]');
}



//  +------------------------------------------------------------------------+
//  | Delete index (Ajax)                                                    |
//  +------------------------------------------------------------------------+
function deleteIndexAjax() {
	global $cfg, $db;
	authenticate('access_play');
	require_once('include/play.inc.php');
	
	$index = (int) get('index');
	echo $index;
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('deletepos', 'index=' . $index);
		if (get('menu') == 'playlist') {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {	
		mpd('delete ' . $index);
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
	
	$index = (int) get('index');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		httpq('deletepos', 'index=' . $index);
		if (get('menu') == 'playlist') {
			header('HTTP/1.1 500 Internal Server Error');
			echo NJB_HOME_URL . 'playlist.php';
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		
		mpd('delete ' . $index);
		if (get('menu') == 'playlist') {
			$data = array();
			$data['index'] = (string) $index;
			echo safe_json_encode($data);
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
		if (get('menu') == 'playlist' && $listpos > 0) {
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
		if (get('menu') == 'playlist' && $status['song'] > 0) {
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
	
	$dx         = (int) get('dx');
	$x			= (int) get('x');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$volume		= round(255 * $x / ($dx-1));
		if ($volume < round(255 * 0.05)) $volume = 0; // set volume to zero
		if ($volume > round(255 * 0.95)) $volume = 255; // set volume to max
		httpq('setvolume', 'level=' . $volume);
		
		mysql_query('UPDATE player
					SET mute_volume	= ' . (int) $volume . '
					WHERE player_id	= ' . (int) $cfg['player_id']);
		
		if (get('menu') == 'playlist')
			echo $volume;
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$data = array();
		$volume		= round(100 * $x / ($dx-1));
		if ($volume < round(100 * 0.05)) $volume = 0; // set volume to zero
		if ($volume > round(100 * 0.95)) $volume = 100; // set volume to max
		mpd('setvol ' . $volume);
		
		mysql_query('UPDATE player
					SET mute_volume	= ' . (int) $volume . '
					WHERE player_id	= ' . (int) $cfg['player_id']);
		
		//if (get('menu') == 'playlist')
			//echo json_encode($volume);
				$data['volume'] = $volume;
				$data['player_id'] = $cfg['player_id'];
				echo safe_json_encode($data);
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
			$query = mysql_query('SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$player = mysql_fetch_assoc($query);
			
			httpq('setvolume', 'level=' . $player['mute_volume']);
			mysql_query('UPDATE player
				SET mute_volume	= 0
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = $player['mute_volume'];
		}
		else {
			httpq('setvolume', 'level=0');
			mysql_query('UPDATE player
				SET mute_volume	= ' . (int) $volume . '
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = -$volume;
		}
		
		if (get('menu') == 'playlist')
			echo $volume;
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$volume	= $status['volume'];
		
		if ($volume == 0) {
			$query = mysql_query('SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$player = mysql_fetch_assoc($query);
			
			mpd('setvol ' . $player['mute_volume']);
			mysql_query('UPDATE player
				SET mute_volume	= 0
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = $player['mute_volume'];
		}
		else {
			mpd('setvol 0');
			mysql_query('UPDATE player
				SET mute_volume	= ' . (int) $volume . '
				WHERE player_id	= ' . (int) $cfg['player_id']);
			$volume = -$volume;
		}
		if (get('menu') == 'playlist')
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
		if (get('menu') == 'playlist')
			echo $invert;
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$invert = (int) ($status['random'] xor 1);
		
		mpd('random ' . $invert);
		if (get('menu') == 'playlist')
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
		
		if (get('menu') == 'playlist')
			echo $invert;
	}	
	elseif ($cfg['player_type'] == NJB_MPD) {
		$status = mpd('status');
		$invert = (int) ($status['repeat'] xor 1);
		
		mpd('repeat ' . $invert);
		if (get('menu') == 'playlist')
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
		if ($gain['replay_gain_mode'] == 'off')	{
			$mode = 'album';
		}
		if ($gain['replay_gain_mode'] == 'album') {
			$mode = 'auto';
		}
		if ($gain['replay_gain_mode'] == 'auto') {
			$mode = 'track';
		}
		if ($gain['replay_gain_mode'] == 'track') {
			$mode = 'off';
		}
		
		mpd('replay_gain_mode ' . $mode);
		if (get('menu') == 'playlist')
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
	
	$track_id = get('track_id');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		// volume
		$volume	= (int) httpq('getvolume');
		
		// get mute volume
		if ($volume == 0) {
			$query	= mysql_query('SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$temp	= mysql_fetch_assoc($query);
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
			$data['gain'] = (string) $gain['replay_gain_mode'];
		}
		
		// get mute volume
		if ($data['volume'] == 0) {
			$query	= mysql_query('SELECT mute_volume FROM player WHERE player_id = ' . (int) $cfg['player_id']);
			$temp	= mysql_fetch_assoc($query);
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
	
	$track_id = get('track_id');
	
	$query = mysql_query('SELECT track.artist, album.artist AS album_artist, title, featuring, miliseconds, relative_file, album, album.image_id, album.album_id, track.genre, track.audio_bitrate, track.audio_dataformat, track.audio_bits_per_sample, track.audio_sample_rate, album.genre_id, track.audio_profile, track.track_artist, album.year as year, track.number, track.comment, track.track_id, track.year as trackYear, track.dr, album.album_dr
		FROM track, album 
		WHERE track.album_id = album.album_id
		AND track_id = "' . mysql_real_escape_string($track_id) . '"');
	$track = mysql_fetch_assoc($query);
	
	$query = mysql_query('SELECT image_front FROM bitmap WHERE image_id="' . mysql_real_escape_string($track['image_id']) . '"');
	$bitmap = mysql_fetch_assoc($query);
	
	
	$title = $track['title'];
		
	/* $query_ = mysql_query('SELECT title FROM track
		WHERE DIFFERENCE(SOUNDEX(title), SOUNDEX("' . (mysql_real_escape_like($title)) . '")) > 0');
	$query_ = mysql_query('SELECT SOUNDEX(title) FROM track');
	 */
	

	/* $title = strtolower($title);
	$separator = $cfg['separator'];
	$count = count($separator);
	$i=0;
	
	for ($i=0; $i<$count; $i++) {
		$pos = strpos($title,strtolower($separator[$i]));
		if ($pos !== false) {
			$title = trim(substr($title, 0 , $pos));
			//break;
		}
	}  */
	
	
	
	
	$title = findCoreTrackTitle($title);
	$title = mysql_real_escape_like($title);
	
	$separator = $cfg['separator'];
	$count = count($separator);
	
	$query_string = '';
	$i=0;
	for ($i=0; $i<$count; $i++) {
		$query_string = $query_string . ' OR LOWER(title) LIKE "' . $title . $separator[$i] . '%"'; 
	}
	
	$filter_query = 'WHERE (LOWER(title) = "' . ($title) . '" ' . $query_string . ')';
	
	$query = mysql_query('SELECT title FROM track ' . $filter_query);
	
	if (strlen($title) > 0) {
		$num_rows = mysql_num_rows($query);
		if ($num_rows > 1) {
			$other_track_version = true;
		}
	}
	else {
		$other_track_version = false;
	}
	
	$exploded = multiexplode($cfg['artist_separator'],$track['track_artist']);
	
	$inFavorite = false;
	if (isset($cfg['favorite_id'])) {
		$query = mysql_query("SELECT track_id FROM favoriteitem WHERE track_id = '" . $track_id . "' AND favorite_id = '" . $cfg['favorite_id'] . "' LIMIT 1");
		if (mysql_num_rows($query) > 0) $inFavorite = true;
	}
	
	$data = array();
	$data['album_artist'] = (string) ($track['album_artist'] == "Various Artists") ? rawurlencode($track['track_artist']) : rawurlencode($track['album_artist']);
	$data['track_artist']	= $exploded;
	$data['track_artist_url']	= $exploded;
	$data['track_artist_url_all']	= (string) rawurlencode($track['track_artist']);
	$data['title']		= (string) $track['title'];
	$data['album']		= (string) $track['album'];
	//$data['album']		= (string) $title;
	$data['by']			= (string) $by;
	$data['image_id']	= (string) $track['image_id'];
	$data['album_id']	= (string) $track['album_id'];
	$data['year']	= ((is_null($track['year'])) ? (string) $track['trackYear'] : (string) $track['year']);
	$data['genre']	= (string) $track['genre'];
	$data['audio_dataformat']	= (string) strtoupper($track['audio_dataformat']);
	$data['audio_bits_per_sample']	= (string) $track['audio_bits_per_sample'];
	$data['audio_sample_rate']	= (string) $track['audio_sample_rate'];
	$data['genre_id']	= (string) $track['genre_id'];
	if ($track['audio_profile'] == 'Lossless compression')
		$data['audio_profile']	= (string) (floor($track['audio_bitrate']/1000)) . ' kbps';
	else
		$data['audio_profile']	= (string) $track['audio_profile'];
	
	$data['number']	= (string) $track['number'] . '. ';
	$data['miliseconds']	= (string) $track['miliseconds'];
	$data['other_track_version']	= (boolean) $other_track_version;
	$data['comment']	= (string) $track['comment'];
	$data['track_id']	= (string) $track['track_id'];
	$data['relative_file']	= (string) $track['relative_file'];
	$data['inFavorite'] = (boolean) $inFavorite;
	$data['dr']	= (string) $track['dr'];
	$data['album_dr']	= (string) $track['album_dr'];
	$data['title_core'] = $title;
	echo safe_json_encode($data);
}


?>