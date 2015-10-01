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


require_once('include/initialize.inc.php');

global $cfg, $db;
global $base_size, $spaces, $scroll_bar_correction;

$size = $_POST["tileSize"];

	/* 
	$query = mysql_query('SELECT SUM(discs) AS discs FROM album');
	$album = mysql_fetch_assoc($query);
	$discs = floor($album['discs'] * 0.6); 
	*/
	//$time4months = time() - (60 * 60 * 24 * 7 * 12);
	//$time4months = 1413812833;
	//$date = date('m/d/Y', time());
	//$time4months = strtotime($date) - (60 * 60 * 24 * 7 * 12);
	/* 
	$query = mysql_query("SELECT UPDATE_TIME
		FROM   information_schema.tables
		WHERE  TABLE_SCHEMA = '" . $cfg['mysql_db'] . "'
				AND TABLE_NAME = 'suggested' LIMIT 1");
	$suggested = mysql_fetch_assoc($query);
	$modTime = strtotime($suggested['UPDATE_TIME']);
	
	//refresh table if last refresh was earlier then 24h ago
	if (($modTime + (60*60*24)) < time()) {
		$time4months = $modTime  - (60 * 60 * 24 * 7 * 12);
		
		$query = mysql_query ('TRUNCATE suggested');
		$query = mysql_query('INSERT INTO suggested 
						SELECT *
						FROM (
							SELECT album.artist, album.artist_alphabetic, album.album, album.image_id, album.album_id, q.last_time, q.counter
							FROM album
							LEFT JOIN (
								SELECT counter.album_id, max(counter.time) as last_time , count( counter.album_id ) AS counter
								FROM counter
								GROUP BY counter.album_id						
							)q ON album.album_id = q.album_id						
						)a
						WHERE a.last_time < ' . $time4months . ' or a.last_time IS NULL
						');
	}					
	//$query = mysql_query('call suggested'); 
	//mysql_query('UPDATE');
	$query = mysql_query("SELECT * FROM suggested 
	WHERE album_id NOT IN 
		(SELECT album_id FROM counter WHERE time > " . $modTime  . ")
	ORDER BY RAND() LIMIT 10"); 
	*/
	//$date = date('m/d/Y', time());
	$query = mysql_query("SELECT UPDATE_TIME
		FROM   information_schema.tables
		WHERE  TABLE_SCHEMA = '" . $cfg['mysql_db'] . "'
				AND TABLE_NAME = 'counter' LIMIT 1");
	$suggested = mysql_fetch_assoc($query);
	$modTime = strtotime($suggested['UPDATE_TIME']);
	
	$time4months = $modTime - (60 * 60 * 24 * 7 * 12);
	$query = mysql_query("SELECT * FROM album 
	WHERE album_id NOT IN 
		(SELECT album_id FROM counter WHERE time > " . $time4months  . ")
	ORDER BY RAND() LIMIT 10");
	
	while ($album = mysql_fetch_assoc($query)) {
		draw_tile($size,$album);
	
	}
	
?>
	
