<?php
//  +------------------------------------------------------------------------+
//  | O!MPD, Copyright © 2015 Artur Sierzant		                         |
//  | http://www.ompd.pl           		                                     |
//  |                                                                        |
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
//  | additional.php                                                         |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
//header('Content-type: application/json');

$action	= get('action');

if		($action == 'updateAddPlay')				updateAddPlay();
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | updateAddPlay                                                          |
//  +------------------------------------------------------------------------+
function updateAddPlay() {
	global $cfg, $db;
	//authenticate('access_playlist', false, false, true);
	
	sleep(1);
	$album_id = get('album_id');
	
	$query = mysql_query('SELECT COUNT(c.album_id) as counter, c.time FROM (SELECT time, album_id FROM counter WHERE album_id = "' . mysql_real_escape_string($album_id) . '" ORDER BY time DESC) c ORDER BY c.time');
	$played = mysql_fetch_assoc($query);
	
	$query = mysql_query('SELECT artist, artist_alphabetic, album, image_id, album.album_id, COUNT(*) AS counter
			FROM counter, album
			WHERE counter.album_id = album.album_id
			GROUP BY album.album_id
			ORDER BY counter DESC, time DESC
			LIMIT 1');
	$max_played = mysql_fetch_assoc($query);
	
	$popularity = round($played['counter'] / $max_played['counter'] * 100);
	$data = array();
	
	$data['played']			= (string) $played['counter'] . ' ' . (($played['counter'] == 1) ? ' time' : ' times');
	$data['last_played']	= date("Y-m-d H:i",$played['time']);
	$data['popularity']		= (int) $popularity;
	//$data['bar_popularity']		= (string) floor($popularity * 1.8);
	echo safe_json_encode($data);
}


?>