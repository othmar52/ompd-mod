<?php
//  +------------------------------------------------------------------------+
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
//  | Cache update file                                                      |
//  +------------------------------------------------------------------------+
function cacheUpdateFile($id, $profile, $file, $tag_hash = '', $zip_hash = '') {
	global $cfg, $db;
	
	$file 			= str_replace('\\', '/', $file);
	$home 			= NJB_HOME_DIR . 'cache/';
	$relative_file	= substr($file, strlen(NJB_HOME_DIR));
	
	if ($home != substr($file, 0, strlen($home)))
		message(__FILE__, __LINE__, 'error', '[b]Cache file must start with:[/b][br]' . $home);
	
	clearstatcache();
	
	mysql_query('UPDATE cache SET
		idle_time			= ' . (int) time() . ',
		filesize			= ' . (int) filesize($file) . ',
		filemtime			= ' . (int) filemtime($file) . ',
		tag_hash			= "' . mysql_real_escape_string($tag_hash) . '",
		zip_hash			= "' . mysql_real_escape_string($zip_hash) . '",
		relative_file		= "' . mysql_real_escape_string($relative_file) . '"
		WHERE id			= "' . mysql_real_escape_string($id) . '"
		AND profile			= ' . (int) $profile);
	if (mysql_affected_rows($db) == 0)
		mysql_query('INSERT INTO cache (id, profile, create_time, idle_time, filesize, filemtime, tag_hash, zip_hash, relative_file) VALUES (
			"' . mysql_real_escape_string($id) . '",
			' . (int) $profile . ',
			' . (int) time() . ',
			' . (int) time() . ',
			' . (int) filesize($file) . ',
			' . (int) filemtime($file) . ',
			"' . mysql_real_escape_string($tag_hash) . '",
			"' . mysql_real_escape_string($zip_hash) . '",
			"' . mysql_real_escape_string($relative_file) . '")');
}




//  +------------------------------------------------------------------------+
//  | Cache update tag                                                       |
//  +------------------------------------------------------------------------+
function cacheUpdateTag($track_id, $profile, $file) {
	global $cfg, $db;
	
	$query  = mysql_query('SELECT image, bitmap.flag
		FROM bitmap, track
		WHERE bitmap.album_id = track.album_id
		AND track_id = "' . mysql_real_escape_string($track_id) . '"');
	$bitmap = mysql_fetch_assoc($query);
	
	$query = mysql_query('SELECT
		LOWER(SUBSTRING_INDEX(relative_file, ".", -1)) AS extension,
		track.artist, title, album, year, disc, discs, number, audio_lossless
		FROM track, album
		WHERE track_id = "' . mysql_real_escape_string($track_id) . '" 
		AND track.album_id = album.album_id');
	$track = mysql_fetch_assoc($query);
	
	$query = mysql_query('SELECT tag_hash
		FROM cache
		WHERE id		= "' . mysql_real_escape_string($track_id) . '"
		AND profile		= ' . (int) $profile);
	$cache = mysql_fetch_assoc($query);
		
	// populate data array
	$tagData['title'][0]		= iconv(NJB_DEFAULT_CHARSET, $cfg['tag_encoding'][$profile], $track['title']);
	$tagData['artist'][0]		= iconv(NJB_DEFAULT_CHARSET, $cfg['tag_encoding'][$profile], $track['artist']);
	$tagData['album'][0]		= iconv(NJB_DEFAULT_CHARSET, $cfg['tag_encoding'][$profile], $track['album']);
	$tagData['year'][0]			= $track['year'];
	$tagData['comment'][0]		= ($track['audio_lossless']) ? 'Lossless audio source' : 'Transcoded from ' . $track['extension'] . ' source';
	
	if ($cfg['tag_format'][$profile] == 'id3v2.3' && $track['number'])
		$tagData['tracknumber'][0] = $track['number'];
	
	if ($cfg['tag_format'][$profile] == 'id3v2.3' && $track['discs'] > 1)
		$tagData['part_of_a_set'][0] = $track['disc'] . '/' . $track['discs'];
	
	if ($cfg['tag_format'][$profile] == 'id3v2.3' && $bitmap['flag'] == 3) {
		$tagData['attached_picture'][0]['data']          = $bitmap['image'];
		$tagData['attached_picture'][0]['picturetypeid'] = 0x03; // 0x03 => 'Cover (front)'
		$tagData['attached_picture'][0]['description']   = '';
		$tagData['attached_picture'][0]['mime']          = 'image/jpeg';
	}
	
	unset($bitmap, $track);

	$hash = $cfg['tag_format'][$profile];
	$hash .= $cfg['tag_encoding'][$profile];
	$hash .= $cfg['tag_padding'][$profile];
	$hash .= print_r($tagData, true);
	$hash = md5($hash);
	
	if ($hash != $cache['tag_hash']) {
		require_once(NJB_HOME_DIR . 'getid3/getid3/getid3.php');
		require_once(NJB_HOME_DIR . 'getid3/getid3/write.php');
		
		// Initialize getID3 engine
		$getID3 = new getID3;
		$getID3->setOption(array('encoding'=>$cfg['tag_encoding'][$profile]));
				
		// Initialize getID3 tag-writing module
		$tagwriter = new getid3_writetags;
		
		$tagwriter->filename	= $file;
		$tagwriter->tagformats	= array($cfg['tag_format'][$profile]); // array('id3v2.3');
		
		// set various options (optional)
		$tagwriter->tag_encoding		= $cfg['tag_encoding'][$profile];
		$tagwriter->overwrite_tags		= true;
		$tagwriter->remove_other_tags	= true;
		$tagwriter->id3v2_paddedlength	= $cfg['tag_padding'][$profile];
	
		$tagwriter->tag_data = $tagData;
		
		if ($tagwriter->WriteTags() == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to write tags[/b][br]Error: ' . (implode('[br]', $tagwriter->errors)) . '[br]File: ' . $file);

		cacheUpdateFile($track_id, $profile, $file, $hash);
	}
}




//  +------------------------------------------------------------------------+
//  | Cache get file                                                         |
//  +------------------------------------------------------------------------+
function cacheGetFile($id, $profile) {
	global $cfg, $db;
	
	$zip_hash = '';
	if (strpos($id, '_') === false) {
		$hash_data = '';
		$query = mysql_query('SELECT relative_file
			FROM track
			WHERE album_id	= "' . mysql_real_escape_string($id) . '"
			ORDER BY relative_file');
		while($track = mysql_fetch_assoc($query)) {
			$pathinfo	= pathinfo($track['relative_file']);
			$hash_data	.= downloadFilename($pathinfo['filename'], true, true);
		}
		$zip_hash = md5($hash_data);	
	}
	
	$query = mysql_query('SELECT create_time, filesize, filemtime, relative_file
		FROM cache
		WHERE id		= "' . mysql_real_escape_string($id) . '"
		AND zip_hash	= "' . mysql_real_escape_string($zip_hash) . '"
		AND profile		= ' . (int) $profile);
	$cache = mysql_fetch_assoc($query);
	$relative_file = $cache['relative_file'];
	$file = NJB_HOME_DIR . $cache['relative_file'];
	
	if (is_file($file) && filesize($file) == $cache['filesize'] && filemtimeCompare(filemtime($file), $cache['filemtime'])) {
		// File exist and has not changed
		if (strpos($id, '_') !== false && $profile >= 0) {
			// Update cache filename, except for zip and wave files
			$query = mysql_query('SELECT relative_file
				FROM track
				WHERE track_id	= "' . mysql_real_escape_string($id) . '"');
			$track = mysql_fetch_assoc($query);
			
			$cache_pathinfo	= pathinfo($cache['relative_file']);
			$track_pathinfo	= pathinfo($track['relative_file']);
			
			if ($cache_pathinfo['filename'] != $track_pathinfo['filename']) {
				$relative_file = $cache_pathinfo['dirname'] . '/' . $track_pathinfo['filename'] . '.' . $cache_pathinfo['extension'];
				rename($file, NJB_HOME_DIR . $relative_file) or message(__FILE__, __LINE__, 'error', '[b]Failed to rename file[/b][br]From: ' . $file . '[br]To: ' . NJB_HOME_DIR . relative_file);
				$file = NJB_HOME_DIR . $relative_file;
			}
		}
		mysql_query('UPDATE cache
			SET idle_time	= ' . (int) time() . ',
			relative_file	= "' . mysql_real_escape_string($relative_file) . '"
			WHERE id		= "' . mysql_real_escape_string($id) . '"
			AND  profile	= ' . (int) $profile);
		return $file;
	}
	elseif (is_file($file)) {
		// File exist and has changed
		if (@unlink($file) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
		
		mysql_query('DELETE
			FROM cache
			WHERE id 		= "' . mysql_real_escape_string($id) . '"
			AND  profile	= ' . (int) $profile);
		return false;
	}
	else {
		// File does not exist
		return false;
	}
}




//  +------------------------------------------------------------------------+
//  | Cache get dir                                                          |
//  +------------------------------------------------------------------------+
function cacheGetDir($id, $profile) {
	global $cfg, $db;
	
	if (strpos($id, '_'))
		$id = substr($id, 0, strpos($id, '_'));
	
	$query = mysql_query('SELECT relative_file
		FROM cache
		WHERE profile 					= ' . (int) $profile . '
		AND SUBSTRING_INDEX(id, "_", 1)	= "' . mysql_real_escape_string($id) . '"');
	$cache = mysql_fetch_assoc($query);
	
	$relative_dir	= substr($cache['relative_file'], 0, strrpos($cache['relative_file'], '/')) . '/';
	$dir			= NJB_HOME_DIR . $relative_dir;
	
	if ($cache['relative_file'] != '' && is_dir($dir)) {
		return $dir;
	}
	elseif ($cache['relative_file'] != '') {
		cacheCreateRoot();
		if (@mkdir($dir, 0777) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to create directory:[/b][br]' . $dir);
		return $dir;
	}
	else {
		$random = randomHex();
		$relative_dir = 'cache/' . substr($random, 0, 1) . '/' . substr($random, 1, 1) . '/' . $random . '/';
		$dir = NJB_HOME_DIR . $relative_dir;
		
		cacheCreateRoot();
		if (@mkdir($dir, 0777) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to create directory:[/b][br]' . $dir);
		
		mysql_query('INSERT INTO cache (id, profile, create_time, idle_time, relative_file) VALUES (
			"' . mysql_real_escape_string($id) . '_pinpoint",
			' . (int) $profile . ',
			' . (int) time() . ',
			' . (int) time() . ',
			"' . mysql_real_escape_string($relative_dir) . 'dummy.pinpoint")');
		return $dir;
	}
}




//  +------------------------------------------------------------------------+
//  | Cache Create Root                                                      |
//  +------------------------------------------------------------------------+
function cacheCreateRoot() {
	global $cfg;
	
	createHiddenDir(NJB_HOME_DIR . 'cache/');
	
	for ($i = 0; $i < 16; $i++)	{
		createHiddenDir(NJB_HOME_DIR . 'cache/' . dechex($i) . '/');
		for ($j = 0; $j < 16; $j++)
			createHiddenDir(NJB_HOME_DIR . 'cache/' . dechex($i) . '/' . dechex($j) . '/');
	}
}




//  +------------------------------------------------------------------------+
//  | Cache delete album                                                     |
//  +------------------------------------------------------------------------+
function cacheDeleteAlbum($album_id) {
	global $cfg, $db;
	
	$query = mysql_query('SELECT album_id
		FROM album
		WHERE album_id = "' . mysql_real_escape_string($album_id) . '"');
	$album = mysql_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	$query = mysql_query('SELECT relative_file
		FROM cache
		WHERE SUBSTRING_INDEX(id, "_", 1) = "' . mysql_real_escape_string($album_id) . '"');
	
	while ($cache = mysql_fetch_assoc($query))	{
		$file = NJB_HOME_DIR . $cache['relative_file'];
		
		if (is_file($file) && @unlink($file) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
		
		mysql_query('DELETE FROM cache
			WHERE relative_file = "' . mysql_real_escape_string($cache['relative_file']) . '"');
	}

 }




//  +------------------------------------------------------------------------+
//  | Cache cleanup                                                          |
//  +------------------------------------------------------------------------+
function cacheCleanup() {
	global $cfg, $db;
	
	// Delete unavailable files from cache
	$query = mysql_query('SELECT cache.relative_file
		FROM cache LEFT JOIN track
		ON cache.id = track.track_id
		WHERE track.track_id IS NULL
		AND LOWER(SUBSTRING_INDEX(cache.relative_file, ".", -1)) != "' . mysql_real_escape_string($cfg['download_album_extension']) . '"');
	
	while ($cache = mysql_fetch_assoc($query)) {
		$file = NJB_HOME_DIR . $cache['relative_file'];
		
		if (is_file($file) && @unlink($file) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
		
		mysql_query('DELETE FROM cache
			WHERE relative_file = "' . mysql_real_escape_string($cache['relative_file']) . '"');
	}
		
	// Delete unavailable zip files from cache	
	$query = mysql_query('SELECT cache.relative_file
		FROM cache LEFT JOIN album
		ON cache.id = album.album_id
		WHERE album.album_id IS NULL
		AND LOWER(SUBSTRING_INDEX(cache.relative_file, ".", -1)) = "' . mysql_real_escape_string($cfg['download_album_extension']) . '"');
	
	while ($cache = mysql_fetch_assoc($query))	{
		$file = NJB_HOME_DIR . $cache['relative_file'];
		
		if (is_file($file) && @unlink($file) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
		
		mysql_query('DELETE FROM cache
			WHERE relative_file = "' . mysql_real_escape_string($cache['relative_file']) . '"');
	}
		
	// Delete wav files after x hour idle time set by $cfg['cache_expire_wav']
	$query = mysql_query('SELECT relative_file
		FROM cache
		WHERE profile = -2
		AND LOWER(SUBSTRING_INDEX(cache.relative_file, ".", -1)) = "wav"
		AND idle_time < ' . (int) (time() - $cfg['cache_expire_wav']));
	
	while ($cfg['cache_expire_wav'] && $cache = mysql_fetch_assoc($query)) {
		$file = NJB_HOME_DIR . $cache['relative_file'];
		
		if (is_file($file) && @unlink($file) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
		
		mysql_query('DELETE FROM cache
			WHERE relative_file = "' . mysql_real_escape_string($cache['relative_file']) . '"');
	}
		
	// Delete zip files after x hour idle time set by $cfg['cache_expire_zip']
	$query = mysql_query('SELECT relative_file, id
		FROM cache
		WHERE LOWER(SUBSTRING_INDEX(relative_file, ".", -1)) = "' . mysql_real_escape_string($cfg['download_album_extension']) . '"
		AND idle_time < ' . (int) (time() - $cfg['cache_expire_zip']));
	
	while ($cfg['cache_expire_zip'] && $cache = mysql_fetch_assoc($query))	{
		$query2 = mysql_query('SELECT album_id
			FROM share_download
			WHERE album_id = "' . mysql_real_escape_string($cache['id']) . '"
			AND expire_time > ' . (int) time() );
		
		if (mysql_fetch_assoc($query2) == false) {
			$file = NJB_HOME_DIR . $cache['relative_file'];
			
			if (is_file($file) && @unlink($file) == false)
				message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
			
			mysql_query('DELETE FROM cache
				WHERE relative_file = "' . mysql_real_escape_string($cache['relative_file']) . '"');
		}
	}
		
	// Delete files from the cache when more than 95% of the total available space is used (ordered by idle time)
	$cache_total_space = disk_total_space(NJB_HOME_DIR . 'cache/');
	$cache_free_space = disk_free_space(NJB_HOME_DIR . 'cache/');
	$cache_used_space = $cache_total_space - $cache_free_space;
	
	$query = mysql_query('SELECT relative_file, filesize
		FROM cache
		WHERE  cache.relative_file != "dummy.pinpoint"
		ORDER BY idle_time');
	
	while ($cache_used_space > $cache_total_space * .95 && $cache = mysql_fetch_assoc($query)) {
		$file = NJB_HOME_DIR . $cache['relative_file'];
		
		if (is_file($file) && @unlink($file) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
		
		mysql_query('DELETE FROM cache
			WHERE relative_file = "' . mysql_real_escape_string($cache['relative_file']) . '"');
		
		$cache_free_space = disk_free_space(NJB_HOME_DIR . 'cache/');
		$cache_used_space = $cache_total_space - $cache_free_space;
	}
}




//  +------------------------------------------------------------------------+
//  | Cache validate                                                         |
//  +------------------------------------------------------------------------+
function cacheValidate() {
	global $cfg, $db;
	cacheCreateRoot();
	cacheCleanup();
	
	mysql_query('UPDATE cache SET updated = 0');
	recursiveValidate(NJB_HOME_DIR . 'cache/');
	mysql_query('DELETE FROM cache WHERE NOT updated');
}




//  +------------------------------------------------------------------------+
//  | Recursive validate                                                     |
//  +------------------------------------------------------------------------+
function recursiveValidate($dir) {
	global $cfg, $db;
	
	$entries = @scandir($dir) or message(__FILE__, __LINE__, 'error', '[b]Failed to open directory:[/b][br]' . $dir);
	foreach ($entries as $entry) {
		if (!in_array($entry, array('.', '..', 'index.php'))) {
			if (is_dir($dir . $entry . '/')) {
				recursiveValidate($dir . $entry . '/');
				if (strlen($entry) != 1)
					@rmdir($dir . $entry . '/');
			}
			else {
				$file = $dir . $entry;
				$relative_file = substr($file, strlen(NJB_HOME_DIR));
				$query = mysql_query('SELECT filesize, filemtime FROM cache
					WHERE relative_file = "' . mysql_real_escape_string($relative_file) . '"');
				$cache = mysql_fetch_assoc($query);
				
				if (filesize($file) == $cache['filesize'] && filemtimeCompare(filemtime($file), $cache['filemtime'])) {
					mysql_query('UPDATE cache
						SET updated			= 1
						WHERE relative_file	= "' . mysql_real_escape_string($relative_file) . '"');
				}
				else {
					@unlink($file);
					// File can be in use by another process.
					// So don't give an error message when the file can't be deleted.
				}
			
			}
		}
	}
}
?>
