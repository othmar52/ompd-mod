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
//  | update.php                                                             |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
require_once('include/cache.inc.php');

$cfg['menu'] = 'config';

$action = @$_REQUEST['action'];
$flag	= (int) @$_REQUEST['flag'];

if		(PHP_SAPI == 'cli')					cliUpdate();
elseif	($action == 'update')				update();
elseif	($action == 'fileStructureJson')	fileStructureJson();
elseif	($action == 'imageJson')			imageJson();
elseif	($action == 'fileInfoJson')			fileInfoJson();
elseif	($action == 'cleanupJson')			cleanupJson();
elseif	($action == 'imageUpdate')			imageUpdate($flag);
elseif	($action == 'saveImage')			saveImage($flag);
elseif	($action == 'selectImageUpload')	selectImageUpload($flag);
elseif	($action == 'imageUpload')			imageUpload($flag);
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Update                                                                 |
//  +------------------------------------------------------------------------+
function update() {
	global $cfg, $db;
	authenticate('access_admin');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Update';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td class="update_text">Update</td>
	<td>Progress</td>
	<td class="space"></td>
</tr>
<tr class="odd">
	<td></td>
	<td>Structure:</td>
	<td><span id="structure"></span></td>
	<td></td>
</tr>
<tr class="even">
	<td></td>
	<td>Image:</td>
	<td><span id="image"></span></td>
	<td></td>
</tr>
<tr class="odd">
	<td></td>
	<td>File info:</td>
	<td><span id="fileinfo"></span></td>
	<td></td>
</tr>
<tr class="even">
	<td></td>
	<td>Cleanup:</td>
	<td><span id="cleanup"></span></td>
	<td></td>
</tr>
</table>
<script type="text/javascript">
	document.getElementById('structure').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
	ajaxRequest('update.php?action=fileStructureJson',imageInfo);
	
	
	function imageInfo(no_image) {
		if (no_image == -1) {
			document.getElementById('structure').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
			document.getElementById('image').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
			ajaxRequest('update.php?action=imageJson',imageInfo);
		}
		else if (no_image > 0) {
			document.getElementById('image').innerHTML='<a href="update.php?action=imageUpdate&amp;flag=0"><img src="<?php echo $cfg['img']; ?>small_image.png" alt="" class="small space">Update ' + no_image + ((no_image == 1) ? ' image' : ' images') + ' from internet</a>';
			fileInfo(-1);
		}
		else {
			document.getElementById('image').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
			fileInfo(-1);
		}
	}
	
	
	function fileInfo(error) {
		if (error == -1) {
			document.getElementById('fileinfo').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
			ajaxRequest('update.php?action=fileInfoJson',fileInfo);
		}
		else if (error > 0) {
			document.getElementById('fileinfo').innerHTML='<a href="statistics.php?action=fileError"><img src="<?php echo $cfg['img']; ?>small_error.png" alt="" class="small space">' + error + ((error == 1) ? ' error' : ' errors') + '</a>';
			cleanup();
		}
		else {
			document.getElementById('fileinfo').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
			cleanup();
		}
	}
	
	
	function cleanup() {		
		document.getElementById('cleanup').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
		ajaxRequest('update.php?action=cleanupJson',ready);
	}
	
	
	function ready() {
		document.getElementById('cleanup').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
	}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | File structure JSON                                                    |
//  +------------------------------------------------------------------------+
function fileStructureJson() { 
	global $cfg, $db;
	authenticate('access_admin');
	ini_set('max_execution_time', 0);
	
	require_once('getid3/getid3/getid3.php');
	
	$cfg['new_escape_char_hash']	= hmacmd5(print_r($cfg['escape_char'], true), file_get_contents(NJB_HOME_DIR . 'update.php'));
	$cfg['force_filename_update']	= ($cfg['new_escape_char_hash'] != $cfg['escape_char_hash']) ? true : false;
	
	mysqli_query($db, 'UPDATE album SET updated = 0');
	mysqli_query($db, 'UPDATE track SET updated = 0');
	
	recursiveScan($cfg['media_dir']);
	
	mysqli_query($db, 'DELETE FROM album WHERE NOT updated');
	mysqli_query($db, 'DELETE FROM track WHERE NOT updated');
	mysqli_query($db, 'UPDATE server SET value = "' . mysqli_real_escape_string($db, $cfg['new_escape_char_hash']) . '" WHERE name = "escape_char_hash" LIMIT 1');
	
	// Initialize imageScan();
	mysqli_query($db, 'UPDATE track SET updated = 0');
	mysqli_query($db, 'UPDATE bitmap SET updated = 0');
	
	echo safe_json_encode(-1);
}



//  +------------------------------------------------------------------------+
//  | Image JSON                                                             |
//  +------------------------------------------------------------------------+
function imageJson() { 
	global $cfg, $db;
	authenticate('access_admin');
	ini_set('max_execution_time', 0);
	
	require_once('getid3/getid3/getid3.php');
		
	if ($cfg['image_size'] != NJB_IMAGE_SIZE || $cfg['image_quality'] != NJB_IMAGE_QUALITY) {
		mysqli_query($db, 'UPDATE bitmap SET filemtime = 0');
		mysqli_query($db, 'UPDATE server SET value = "' . mysqli_real_escape_string($db, NJB_IMAGE_SIZE) . '" WHERE name = "image_size" LIMIT 1');
		mysqli_query($db, 'UPDATE server SET value = "' . mysqli_real_escape_string($db, NJB_IMAGE_QUALITY) . '" WHERE name = "image_quality" LIMIT 1');
	}
	
	imageScan();
	
	mysqli_query($db, 'DELETE FROM bitmap WHERE NOT updated');
	
	// Initialize fileInfo();
	mysqli_query($db, 'UPDATE track SET updated = 0');
	
	$no_image = mysqli_num_rows(mysqli_query($db, 'SELECT album_id FROM bitmap WHERE flag = 0'));
	echo safe_json_encode($no_image);
}




//  +------------------------------------------------------------------------+
//  | File info JSON                                                         |
//  +------------------------------------------------------------------------+
function fileInfoJson() { 
	global $cfg, $db;
	authenticate('access_admin');
	ini_set('max_execution_time', 0);
	
	require_once('getid3/getid3/getid3.php');
	
	fileInfo();
	
	$error = mysqli_num_rows(mysqli_query($db, 'SELECT error FROM track WHERE error != ""'));
	echo safe_json_encode($error);
}




//  +------------------------------------------------------------------------+
//  | Cleanup JSON                                                           |
//  +------------------------------------------------------------------------+
function cleanupJson() { 
	global $cfg, $db;
	authenticate('access_admin');
	ini_set('max_execution_time', 0);
	
	require_once('include/play.inc.php'); // Needed for mpdUpdate()
	
	databaseCleanup();
	mpdUpdate();
	
	echo '""';
}




//  +------------------------------------------------------------------------+
//  | Command line interface (CLI) update                                    |
//  +------------------------------------------------------------------------+
function cliUpdate() {
	global $cfg, $db;
	
	require_once('getid3/getid3/getid3.php');
	require_once('include/play.inc.php'); // Needed for mpdUpdate()

	echo 'netjukebox ' . NJB_VERSION . ', Copyright (C) 2001-2015 Willem Bartels' . "\n";
	echo 'This program comes with ABSOLUTELY NO WARRANTY.' . "\n";
	echo 'This is free software, and you are welcome to redistribute it' . "\n";
	echo 'under certain conditions.' . "\n\n";
	
	echo 'Command Line Interface (CLI) Update' . "\n\n";
	
	if		(in_array('update', $_SERVER['argv']))		{}
	else {
		echo 'Usage: php update.php <command>' . "\n\n";
		echo '<command>' . "\n";
		echo ' update' . "\n\n";
		echo 'Example:' . "\n";
		echo ' php update.php update silent' . "\n\n";
		exit();
	}
	
	
	echo 'Processing structure...' . "\n";
	$cfg['new_escape_char_hash']	= hmacmd5(print_r($cfg['escape_char'], true), file_get_contents(NJB_HOME_DIR . 'update.php'));
	$cfg['force_filename_update']	= ($cfg['new_escape_char_hash'] != $cfg['escape_char_hash']) ? true : false;
	
	mysqli_query($db, 'UPDATE album SET updated = 0');
	mysqli_query($db, 'UPDATE track SET updated = 0');
	recursiveScan($cfg['media_dir']);
	mysqli_query($db, 'DELETE FROM album WHERE NOT updated');
	mysqli_query($db, 'DELETE FROM track WHERE NOT updated');
	mysqli_query($db, 'UPDATE server SET value = "' . mysqli_real_escape_string($db, $cfg['new_escape_char_hash']) . '" WHERE name = "escape_char_hash" LIMIT 1');
	
	
	echo 'Processing bitmap...' . "\n";
	if ($cfg['image_size'] != NJB_IMAGE_SIZE || $cfg['image_quality'] != NJB_IMAGE_QUALITY) {
		mysqli_query($db, 'UPDATE bitmap SET filemtime = 0');
		mysqli_query($db, 'UPDATE server SET value = "' . mysqli_real_escape_string($db, NJB_IMAGE_SIZE) . '" WHERE name = "image_size" LIMIT 1');
		mysqli_query($db, 'UPDATE server SET value = "' . mysqli_real_escape_string($db, NJB_IMAGE_QUALITY) . '" WHERE name = "image_quality" LIMIT 1');
	}
	
	// Initialize imageScan();
	mysqli_query($db, 'UPDATE track SET updated = 0');
	mysqli_query($db, 'UPDATE bitmap SET updated = 0');
	imageScan();
	mysqli_query($db, 'DELETE FROM bitmap WHERE NOT updated');
	
	
	echo 'Processing file info...' . "\n";
	// Initialize fileInfo();
	mysqli_query($db, 'UPDATE track SET updated = 0');
	fileInfo();
	
		
	echo 'Cleanup...' . "\n";
	databaseCleanup();
	
	echo 'Music Player Daemon update...' . "\n";
	mpdUpdate();
	
	echo 'Script execution time: ' . executionTime() . "\n";
}




//  +------------------------------------------------------------------------+
//  | Recursive scan                                                         |
//  +------------------------------------------------------------------------+
function recursiveScan($dir) {
	global $cfg;
	$album_id	= '';
	$file		= array();
	$filename	= array();
	
	$entries = @scandir($dir) or message(__FILE__, __LINE__, 'error', '[b]Failed to open directory:[/b][br]' . $dir . '[list][*]Check media_dir value in the config.inc.php file[*]Check file permission[/list]');
	foreach ($entries as $entry) {
		if ($entry[0] != '.' && !in_array($entry, array('lost+found', 'Temporary Items', 'Network Trash Folder', 'System Volume Information', 'RECYCLER', '$RECYCLE.BIN', 'iTunes'))) {
			if (is_dir($dir . $entry . '/'))
				recursiveScan($dir . $entry . '/');
			else {
				$extension = substr(strrchr($entry, '.'), 1);
				$extension = strtolower($extension);
				if (in_array($extension, $cfg['media_extension'])) {
					$file[] 	= $dir . $entry;
					$filename[] = substr($entry, 0, -strlen($extension) - 1);
				}
				elseif ($extension == 'id')
					$album_id = substr($entry, 0, -3);
			}
		}
	}
	if (count($file) > 0)
		fileStructure($dir, $file, $filename, $album_id);
}




//  +------------------------------------------------------------------------+
//  | File structure                                                         |
//  +------------------------------------------------------------------------+
function fileStructure($dir, $file, $filename, $album_id) {
	global $cfg, $db;
	
	$album_add_time = 0;
	if ($album_id == '') {
		$album_id = base_convert(uniqid(), 16, 36);
		$album_add_time = time();
		if (file_put_contents($dir . $album_id . '.id', '') === false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to write file:[/b][br]' . $dir . $album_id . '.id[list][*]Check file/directory permission.[/list]');
	}
	elseif (preg_match('#^[a-z0-9]{10,11}$#', $album_id) == false)
		message(__FILE__, __LINE__, 'error', '[b]This is not a valid id:[/b][br]' . $dir . $album_id . '.id[list][*]Remove this id and update again.[/list]');
	else
		$album_add_time = filemtime($dir . $album_id . '.id');

	// Also needed for track update!
	$discs 			= 1;
	$disc_digits	= 0;
	$track_digits	= 0;
		
	if (preg_match('#^(0{0,1}1)(0{1,3}1)\s+-\s+.+#', $filename[0], $match) && preg_match('#^(\d{' . strlen($match[1] . $match[2]) . '})\s+-\s+.+#', $filename[count($filename)-1])) {
		// Multi disc
		$disc_digits	= strlen($match[1]);
		$track_digits	= strlen($match[2]);
		preg_match('#^(\d{' . $disc_digits . '})\d{' . $track_digits . '}\s+-\s+#', $filename[count($filename)-1], $match);
		$discs = $match[1];
	}
	elseif (preg_match('#^(\d{2,4})\s+-\s+.+#', $filename[0], $match)) {
		// Single disc
		$track_digits	= strlen($match[1]);
	}
	
	$temp   			= explode('/', $dir);
	$n					= count($temp);
	
	$artist_alphabetic 	= decodeEscapeChar($temp[$n - 3]);
	$album				= decodeEscapeChar($temp[$n - 2]);
	
	$year				= null;
	$month				= null;
			
	if (preg_match('#^(\d{4})\s+-\s+(.+)#', $album, $match)) {
	    $year	= $match[1];
		$album	= $match[2];
	}
	elseif (preg_match('#^(\d{4})(0[1-9]|1[012])\s+-\s+(.+)#', $album, $match)) {
		$year	= $match[1];
		$month	= $match[2];
		$album	= $match[3];
	}
	
		
	$preposition = array('de', 'het', '\'t', 'een', 'eene', '\'n',						// Dutch
		'a', 'an', 'the',																// English
		'le', 'la', 'l\'', 'les', 'un', 'une',											// French
		'der', 'die', 'das', 'ein', 'eine',												// German
		'hinn', 'hin', 'hi', 'hinir', 'hinar',											// Icelandic
		'il', 'la', 'lo', 'i', 'gli', 'gl\'', 'le', 'l\'', 'un', 'uno', 'una', 'un\'',	// Italian
		'den', 'det', 'de', 'dei', 'ein', 'ei', 'eit', 'en', 'et',						// Norwegian
		'o', 'a', 'os', 'as', 'um', 'uma', 'uns', 'umas',								// Portuguese
		'el', 'la', 'lo', 'los', 'las', 'uno', 'una', 'unos', 'unas',					// Spanish
		'den', 'det', 'de', 'en', 'ett');												// Swedish
	if (substr_count($artist_alphabetic, ', ') == 1 && preg_match('#^(.+),\s+(.+)#', $artist_alphabetic, $match) && (strpos($artist_alphabetic, ' & ') === false || in_array(strtolower($match[2]), $preposition))) {
		if (in_array(strtolower($match[2]), $preposition))	$artist = $match[2] . ' ' . strtolower($match[1]);
		else												$artist = $match[2] . ' ' . $match[1];
		$artist_alphabetic = $match[1] . ', ' . $match[2]; // Remove multiple spaces after ,
	}
	else 
		$artist = $artist_alphabetic;
	
	
	mysqli_query($db, 'UPDATE album SET
		artist_alphabetic	= "' . mysqli_real_escape_string($db, $artist_alphabetic) . '",
		artist				= "' . mysqli_real_escape_string($db, $artist) . '",
		album				= "' . mysqli_real_escape_string($db, $album) . '",
		year				= ' . ((is_null($year)) ? 'NULL' : (int) $year) . ',
		month				= ' . ((is_null($month)) ? 'NULL' : (int) $month) . ',
		discs				= ' . (int) $discs . ',
		updated				= 1
		WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"
		LIMIT 1');
	if (mysqli_affected_rows($db) == 0)
		mysqli_query($db, 'INSERT INTO album (artist_alphabetic, artist, album, year, month,  album_add_time, discs, album_id, updated)
			VALUES (
			"' . mysqli_real_escape_string($db, $artist_alphabetic) . '",
			"' . mysqli_real_escape_string($db, $artist) . '",
			"' . mysqli_real_escape_string($db, $album) . '",
			' . ((is_null($year)) ? 'NULL' : (int) $year) . ',
			' . ((is_null($month)) ? 'NULL' : (int) $month) . ',
			' . (int) $album_add_time . ',
			' . (int) $discs . ',
			"' . mysqli_real_escape_string($db, $album_id) . '",
			1)');
	
	
	// Track update
	$disc		= 1;
	$number		= null;
	
	for ($i = 0; $i < count($filename); $i++) {
		$relative_file = substr($file[$i], strlen($cfg['media_dir']));
		
		mysqli_query($db, 'UPDATE track SET
			updated				= 1
			WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"
			AND relative_file	= BINARY "' . mysqli_real_escape_string($db, $relative_file) . '"
			LIMIT 1');
		if ($cfg['force_filename_update'] || mysqli_affected_rows($db) == 0)
			{
			$temp = decodeEscapeChar($filename[$i]);
	
			if (preg_match('#^(\d{' . $disc_digits . '})(\d{' . $track_digits . '})\s+-\s+(.+)#', $temp, $match)) {
				if ($disc_digits > 0) {
					// Multiple disc
					$disc		= $match[1];
					$number		= $match[2];
				}
				else {
					// Single disc
					$number		= $match[2];
				}
				$temp = $match[3]; // Strip disc and track number
			}
			if (preg_match('#^(.+?)\s+-\s+(.+?)(?:\s+Ft\.\s+(.+))?$#i', $temp, $match)) {
				$track_artist	= $match[1];
				$title			= $match[2];
				$featuring		= (isset($match[3])) ? $match[3] : '';
			}
			elseif (preg_match('#^(.+?)(?:\s+Ft\.\s+(.+))?$#i', $temp, $match)) {
				$track_artist	= $artist;
				$title			= $match[1];
				$featuring		= (isset($match[2])) ? $match[2] : '';
			}
			else {
				$track_artist	= '*** UNSUPPORTED FILENAME FORMAT ***';
				$title			= '(' . $filename[$i] . ')';
				$featuring		= '';
			}
			if (mysqli_affected_rows($db) == 0)
				mysqli_query($db, 'INSERT INTO track (artist, featuring, title, relative_file, disc, number, album_id, updated)
					VALUES ("' . mysqli_real_escape_string($db, $track_artist) . '",
					"' . mysqli_real_escape_string($db, $featuring) . '",
					"' . mysqli_real_escape_string($db, $title) . '",
					"' . mysqli_real_escape_string($db, $relative_file) . '",
					' . (int) $disc . ',
					' . ((is_null($number)) ? 'NULL' : (int) $number) . ',
					"' . mysqli_real_escape_string($db, $album_id) . '",
					1)');
			else
				mysqli_query($db, 'UPDATE track SET
					artist				= "' . mysqli_real_escape_string($db, $track_artist) . '",
					featuring			= "' . mysqli_real_escape_string($db, $featuring) . '",
					title				= "' . mysqli_real_escape_string($db, $title) . '",
					relative_file		= "' . mysqli_real_escape_string($db, $relative_file) . '",
					disc				= ' . (int) $disc . ',
					number				= ' . ((is_null($number)) ? 'NULL' : (int) $number) . ',
					album_id			= "' . mysqli_real_escape_string($db, $album_id) . '",
					updated				= 1
					WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"
					AND relative_file	= BINARY "' . mysqli_real_escape_string($db, $relative_file) . '"
					LIMIT 1');
		}
	}
}




//  +------------------------------------------------------------------------+
//  | Image scan                                                             |
//  +------------------------------------------------------------------------+
function imageScan() {
	global $cfg, $db;
	
	$query = mysqli_query($db, 'SELECT relative_file, album_id FROM track WHERE NOT updated GROUP BY album_id ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query)) {
		$album_id		= $track['album_id'];
		$relative_dir	= substr($track['relative_file'], 0, strrpos($track['relative_file'], '/')) . '/';
		$dir			= $cfg['media_dir'] . $relative_dir;
		
		if (PHP_SAPI != 'cli' && (time() - $_SERVER['REQUEST_TIME']) >= 25) {
			// Reload to prevent timeout!
			echo safe_json_encode(-1);
			exit();
		}
		
		// Image update
		$image = NJB_HOME_DIR . 'image/no_image.png';
		$flag = 0; // No image
		
		if		(is_file($dir . $cfg['image_front'] . '.jpg')) { $image = $dir . $cfg['image_front'] . '.jpg'; $flag = 3; /* Stored image */ }
		elseif	(is_file($dir . $cfg['image_front'] . '.png')) { $image = $dir . $cfg['image_front'] . '.png'; $flag = 3; /* Stored image */ }
		elseif	($cfg['image_read_embedded']) {
			// Initialize getID3
			$getID3 = new getID3;
			// public: Settings
			$getID3->encoding					= $cfg['default_charset'];	// CASE SENSITIVE! - i.e. (must be supported by iconv()) Examples:  ISO-8859-1  UTF-8  UTF-16  UTF-16BE
			$getID3->encoding_id3v1				= 'ISO-8859-1';				// Should always be 'ISO-8859-1', but some tags may be written in other encodings such as 'EUC-CN'
			// public: Optional tag checks - disable for speed.
			$getID3->option_tag_id3v1			= false;		// Read and process ID3v1 tags
			$getID3->option_tag_id3v2			= true;			// Read and process ID3v2 tags
			$getID3->option_tag_lyrics3			= false;		// Read and process Lyrics3 tags
			$getID3->option_tag_apetag			= true;			// Read and process APE tags
			$getID3->option_tags_process		= true;			// Copy tags to root key 'tags' and encode to $this->encoding
			$getID3->option_tags_html			= false;		// Copy tags to root key 'tags_html' properly translated from various encodings to HTML entities
			// public: Optional tag/comment calucations
			$getID3->option_extra_info			= false;		// Calculate additional info such as bitrate, channelmode etc
			// public: Optional handling of embedded attachments (e.g. images)
			$getID3->option_save_attachments	= true;			// defaults to true (ATTACHMENTS_INLINE) for backward compatibility
			// public: Optional calculations
			$getID3->option_md5_data			= false;		// Get MD5 sum of data part - slow
			$getID3->option_md5_data_source		= false;		// Use MD5 of source file if availble - only FLAC and OptimFROG
			$getID3->option_sha1_data			= false;		// Get SHA1 sum of data part - slow
			$getID3->option_max_2gb_check		= null;			// Check whether file is larger than 2GB and thus not supported by 32-bit PHP
			
			$getID3->analyze($cfg['media_dir'] . $track['relative_file']);
			
			if (isset($getID3->info['error']) == false &&
				isset($getID3->info['comments']['picture'][0]['image_mime']) &&
				isset($getID3->info['comments']['picture'][0]['data']) &&
				($getID3->info['comments']['picture'][0]['image_mime'] == 'image/jpeg' || $getID3->info['comments']['picture'][0]['image_mime'] == 'image/png')) {
					if ($getID3->info['comments']['picture'][0]['image_mime'] == 'image/jpeg')	$image = $dir . $cfg['image_front'] . '.jpg';
					if ($getID3->info['comments']['picture'][0]['image_mime'] == 'image/png')	$image = $dir . $cfg['image_front'] . '.png';
					if (file_put_contents($image, $getID3->info['comments']['picture'][0]['data']) === false)
						message(__FILE__, __LINE__, 'error', '[b]Failed to wtite image to:[/b][br]' . $image);
					$flag = 3; // Stored image
			}
			
			// Close getID3				
			unset($getID3);
		}
		// $relative_dir = substr($dir, strlen($cfg['media_dir']));
		if		(is_file($dir . $cfg['image_front'] . '.jpg')) 	$image_front = $relative_dir . $cfg['image_front'] . '.jpg';
		elseif	(is_file($dir . $cfg['image_front'] . '.png'))	$image_front = $relative_dir . $cfg['image_front'] . '.png';
		else													$image_front = '';
		if		(is_file($dir . $cfg['image_back'] . '.jpg'))	$image_back = $relative_dir . $cfg['image_back'] . '.jpg';
		elseif	(is_file($dir . $cfg['image_back'] . '.png'))	$image_back = $relative_dir . $cfg['image_back'] . '.png';
		else													$image_back = '';
		
		$filesize	= filesize($image);
		$filemtime	= filemtime($image);
		
		$query2	= mysqli_query($db, 'SELECT filesize, filemtime, image_id, flag FROM bitmap WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
		$bitmap	= mysqli_fetch_assoc($query2);
		
		if ($bitmap['filesize'] == $filesize && filemtimeCompare($bitmap['filemtime'], $filemtime)) {
			mysqli_query($db, 'UPDATE bitmap SET
				image_front			= "' . mysqli_real_escape_string($db, $image_front) . '",
				image_back			= "' . mysqli_real_escape_string($db, $image_back) . '",
				updated				= 1
				WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"
				LIMIT 1');
			$image_id = $bitmap['image_id'];
		}
		else {
			$imagesize = @getimagesize($image) or message(__FILE__, __LINE__, 'error', '[b]Failed to read image information from:[/b][br]' . $image);
			$image_id = (($flag == 3) ? $album_id : 'no_image');
			$image_id .= '_' . base_convert(NJB_IMAGE_SIZE * 100 + NJB_IMAGE_QUALITY, 10, 36) . base_convert($filemtime, 10, 36) . base_convert($filesize, 10, 36);
			
			if ($bitmap['image_id'])
				mysqli_query($db, 'UPDATE bitmap SET
					image				= "' . mysqli_real_escape_string($db, resampleImage($image)) . '",
					filesize			= ' . (int) $filesize . ',
					filemtime			= ' . (int) $filemtime . ',
					flag				= ' . (int) $flag . ',
					image_front			= "' . mysqli_real_escape_string($db, $image_front) . '",
					image_back			= "' . mysqli_real_escape_string($db, $image_back) . '",
					image_front_width	= ' . ($flag == 3 ? $imagesize[0] : 0) . ',
					image_front_height	= ' . ($flag == 3 ? $imagesize[1] : 0) . ',
					image_id			= "' . mysqli_real_escape_string($db, $image_id) . '",
					updated				= 1
					WHERE album_id	= "' . mysqli_real_escape_string($db, $album_id) . '"
					LIMIT 1');
			else
				mysqli_query($db, 'INSERT INTO bitmap (image, filesize, filemtime, flag, image_front, image_back, image_front_width, image_front_height, image_id, album_id, updated)
					VALUES ("' . mysqli_real_escape_string($db, resampleImage($image)) . '",
					' . (int) $filesize . ',
					' . (int) $filemtime . ',
					' . (int) $flag . ',
					"' . mysqli_real_escape_string($db, $image_front) . '",
					"' . mysqli_real_escape_string($db, $image_back) . '",
					' . ($flag == 3 ? $imagesize[0] : 0) . ',
					' . ($flag == 3 ? $imagesize[1] : 0) . ',
					"' . mysqli_real_escape_string($db, $image_id) . '",
					"' . mysqli_real_escape_string($db, $album_id) . '",
					1)');
		}
		
		mysqli_query($db, 'UPDATE album SET
			image_id			= "' . mysqli_real_escape_string($db, $image_id) . '"
			WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"
			LIMIT 1');
			
		mysqli_query($db, 'UPDATE track SET
			updated				= 1
			WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"');	
	}
}




//  +------------------------------------------------------------------------+
//  | File info                                                              |
//  +------------------------------------------------------------------------+
function fileInfo() {
	global $cfg, $db;
	
	// Initialize getID3
	$getID3 = new getID3;
	// public: Settings
	$getID3->encoding        			= $cfg['default_charset'];	// CASE SENSITIVE! - i.e. (must be supported by iconv()) Examples:  ISO-8859-1  UTF-8  UTF-16  UTF-16BE
	$getID3->encoding_id3v1  			= 'ISO-8859-1';				// Should always be 'ISO-8859-1', but some tags may be written in other encodings such as 'EUC-CN'
	// public: Optional tag checks - disable for speed.
	$getID3->option_tag_id3v1			= false;		// Read and process ID3v1 tags
	$getID3->option_tag_id3v2			= false;		// Read and process ID3v2 tags
	$getID3->option_tag_lyrics3			= false;		// Read and process Lyrics3 tags
	$getID3->option_tag_apetag			= false;		// Read and process APE tags
	$getID3->option_tags_process		= false;		// Copy tags to root key 'tags' and encode to $this->encoding
	$getID3->option_tags_html			= false;		// Copy tags to root key 'tags_html' properly translated from various encodings to HTML entities
	// public: Optional tag/comment calucations
	$getID3->option_extra_info			= true;			// Calculate additional info such as bitrate, channelmode etc
	// public: Optional handling of embedded attachments (e.g. images)
	$getID3->option_save_attachments	= false;			// defaults to true (ATTACHMENTS_INLINE) for backward compatibility
	// public: Optional calculations
	$getID3->option_md5_data			= false;		// Get MD5 sum of data part - slow
	$getID3->option_md5_data_source		= false;		// Use MD5 of source file if availble - only FLAC and OptimFROG
	$getID3->option_sha1_data			= false;		// Get SHA1 sum of data part - slow
	$getID3->option_max_2gb_check		= null;			// Check whether file is larger than 2 Gb and thus not supported by PHP
		
	// Force update all tracks on new getID3() or netjukebox update.php version. 
	$new_getid3_hash = hmacmd5($getID3->version(), file_get_contents(NJB_HOME_DIR . 'update.php'));
	if ($new_getid3_hash != $cfg['getid3_hash']) {
		mysqli_query($db, 'UPDATE track SET filemtime = 0');
		mysqli_query($db, 'UPDATE server SET value = "' . mysqli_real_escape_string($db, $new_getid3_hash) . '" WHERE name = "getid3_hash" LIMIT 1');
	}
	
	$query = mysqli_query($db, 'SELECT relative_file, filesize, filemtime, album_id FROM track WHERE NOT updated ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query)) {
		$file = $cfg['media_dir'] . $track['relative_file'];
		
		if (PHP_SAPI != 'cli' && (time() - $_SERVER['REQUEST_TIME']) >= 25) {
			// Reload to prevent timeout!
			echo safe_json_encode(-1);
			exit();
		}
		
		if (is_file($file) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to read file:[/b][br]' . $file . '[list][*]Update again[*]Check file permission[/list]');
		
		$filemtime = filemtime($file);
		$filesize = filesize($file);
		
		if ($filesize != $track['filesize'] || filemtimeCompare($filemtime, $track['filemtime']) == false) {						
			$getID3->analyze($file);
			
			$mime_type					= (isset($getID3->info['mime_type'])) ? $getID3->info['mime_type'] : 'application/octet-stream';
			$miliseconds				= (isset($getID3->info['playtime_seconds'])) ? round($getID3->info['playtime_seconds'] * 1000) : 0;
			$audio_bitrate				= 0;
			$audio_bits_per_sample		= 0;
			$audio_sample_rate			= 0;
			$audio_channels				= 0;
			$audio_lossless				= 0;
			$audio_compression_ratio	= 0;
			$audio_dataformat			= '';
			$audio_encoder 				= '';
			$audio_bitrate_mode			= '';
			$audio_profile				= '';
			$video_dataformat			= '';
			$video_codec				= '';
			$video_resolution_x			= 0;
			$video_resolution_y			= 0;
			$video_framerate			= 0;
			$track_id					= $track['album_id'] . '_' . fileId($file);
			$error						= (isset($getID3->info['error'])) ? implode('<br>', $getID3->info['error']) : '';
					
			if (isset($getID3->info['audio']['dataformat'])) {
				$audio_dataformat = $getID3->info['audio']['dataformat'];
				$audio_encoder = (isset($getID3->info['audio']['encoder'])) ? $getID3->info['audio']['encoder'] : 'Unknown encoder';
				
				if (isset($getID3->info['mpc']['header']['profile']))			$audio_profile = $getID3->info['mpc']['header']['profile'];
				if (isset($getID3->info['aac']['header']['profile_text']))		$audio_profile = $getID3->info['aac']['header']['profile_text'];
				
				if (empty($getID3->info['audio']['lossless']) == false) {
					$audio_lossless = 1;
					$audio_profile = 'Lossless compression';
				}
				
				if (isset($getID3->info['audio']['compression_ratio']))			$audio_compression_ratio = $getID3->info['audio']['compression_ratio'];
				if (isset($getID3->info['audio']['bitrate_mode']))				$audio_bitrate_mode = $getID3->info['audio']['bitrate_mode'];
				if (isset($getID3->info['audio']['bitrate']))					$audio_bitrate = $getID3->info['audio']['bitrate'];
				if (!$audio_profile)											$audio_profile = $audio_bitrate_mode . ' ' . round($audio_bitrate / 1000, 1) . '  kbps';
			
				$audio_bits_per_sample	= (isset($getID3->info['audio']['bits_per_sample'])) ? $getID3->info['audio']['bits_per_sample'] : 16;
				$audio_sample_rate		= (isset($getID3->info['audio']['sample_rate'])) ? $getID3->info['audio']['sample_rate'] : 44100;
				$audio_channels			= (isset($getID3->info['audio']['channels'])) ? $getID3->info['audio']['channels'] : 2;
				$audio_bitrate			= round($audio_bitrate); // integer in database					
			}
			if (isset($getID3->info['video']['dataformat'])) {
				$video_dataformat = $getID3->info['video']['dataformat'];
				$video_codec = (isset($getID3->info['video']['codec'])) ? $getID3->info['video']['codec'] : 'Unknown codec';
				
				if (isset($getID3->info['video']['resolution_x']))		$video_resolution_x	= $getID3->info['video']['resolution_x'];
				if (isset($getID3->info['video']['resolution_y']))		$video_resolution_y	= $getID3->info['video']['resolution_y'];
				if (isset($getID3->info['video']['frame_rate']))		$video_framerate	= $getID3->info['video']['frame_rate'] . ' fps';
			}
	
			mysqli_query($db, 'UPDATE track SET
				mime_type					= "' . mysqli_real_escape_string($db, $mime_type) . '",
				filesize					= ' . (int) $filesize . ',
				filemtime					= ' . (int) $filemtime . ',
				miliseconds					= ' . (int) $miliseconds . ',
				audio_bitrate				= ' . (int) $audio_bitrate . ',
				audio_bits_per_sample		= ' . (int) $audio_bits_per_sample . ',
				audio_sample_rate			= ' . (int) $audio_sample_rate . ',
				audio_channels				= ' . (int) $audio_channels . ',
				audio_lossless				= ' . (int) $audio_lossless . ',
				audio_compression_ratio		= ' . (float) $audio_compression_ratio . ',			
				audio_dataformat			= "' . mysqli_real_escape_string($db, $audio_dataformat) . '",
				audio_encoder 				= "' . mysqli_real_escape_string($db, $audio_encoder) . '",
				audio_profile				= "' . mysqli_real_escape_string($db, $audio_profile) . '",
				video_dataformat			= "' . mysqli_real_escape_string($db, $video_dataformat) . '",
				video_codec					= "' . mysqli_real_escape_string($db, $video_codec) . '",
				video_resolution_x			= ' . (int) $video_resolution_x . ',
				video_resolution_y			= ' . (int) $video_resolution_y . ',
				video_framerate				= ' . (int) $video_framerate . ',
				error						= "' . mysqli_real_escape_string($db, $error) . '",
				track_id					= "' . mysqli_real_escape_string($db, $track_id) . '",
				updated						= 1
				WHERE relative_file 		= BINARY "' . mysqli_real_escape_string($db, $track['relative_file']) . '"');
		}
	}
	// Close getID3				
	unset($getID3);
}




//  +------------------------------------------------------------------------+
//  | File identification                                                    |
//  +------------------------------------------------------------------------+
function fileId($file) {
	$filesize = filesize($file);
	
	if ($filesize > 5120) {
		$filehandle	= @fopen($file, 'rb') or message(__FILE__, __LINE__, 'error', '[b]Failed to open file:[/b][br]' . $file . '[list][*]Check file permission[/list]');
		fseek($filehandle, round(0.5 * $filesize - 2560 - 1));
		$data = fread($filehandle, 5120);
		$data .= $filesize;
		fclose($filehandle);
	}
	else
		$data = @file_get_contents($file) or message(__FILE__, __LINE__, 'error', '[b]Failed to open file:[/b][br]' . $file . '[list][*]Check file permission[/list]');
	
	$crc32 = dechex(crc32($data));
	return str_pad($crc32, 8, '0', STR_PAD_LEFT);
}




//  +------------------------------------------------------------------------+
//  | Database cleanup                                                       |
//  +------------------------------------------------------------------------+
function databaseCleanup() {
	global $cfg, $db;
	// Clean up database
	mysqli_query($db, 'DELETE FROM session WHERE idle_time = 0 AND create_time < ' . (int) (time() - 600));
	mysqli_query($db, 'DELETE FROM random WHERE create_time < ' . (int) (time() - 3600));
	mysqli_query($db, 'DELETE FROM share_download WHERE expire_time < ' . (int) time());
	mysqli_query($db, 'DELETE FROM share_stream WHERE expire_time < ' . (int) time());
	mysqli_query($db, 'DELETE share_download
		FROM share_download LEFT JOIN album
		ON share_download.album_id = album.album_id
		WHERE album.album_id IS NULL');
	mysqli_query($db, 'DELETE share_stream
		FROM share_stream LEFT JOIN album
		ON share_stream.album_id = album.album_id
		WHERE album.album_id IS NULL');
	mysqli_query($db, 'DELETE counter
		FROM counter LEFT JOIN album
		ON counter.album_id = album.album_id
		WHERE album.album_id IS NULL');
	mysqli_query($db, 'DELETE counter
		FROM counter LEFT JOIN user
		ON counter.user_id = user.user_id
		WHERE user.user_id IS NULL');
	
	// Delete unavailable files from cache
	cacheCleanup();
	
	// Optimize tables
	$list	= array();
	$query	= mysqli_query($db, 'SHOW TABLES');
	while ($table = mysqli_fetch_row($query))
		$list[] = $table[0];
	$list = implode(', ', $list);
	mysqli_query($db, 'OPTIMIZE TABLE ' . $list);
}




//  +------------------------------------------------------------------------+
//  | Image update                                                           |
//  +------------------------------------------------------------------------+
function imageUpdate($flag) {
	global $cfg, $db;
	authenticate('access_admin');
	
	$size				= @$_GET['size'];
	$artistSearch		= @$_POST['artist'];
	$albumSearch		= @$_POST['album'];
	$image_service_id	= (int) @$_POST['image_service_id'];
	
	if (in_array($size, array('50', '100', '200'))) {
		mysqli_query($db, 'UPDATE session
			SET thumbnail_size	= ' . (int) $size . '
			WHERE sid			= BINARY "' . mysqli_real_escape_string($db, $cfg['sid']) . '"');
	}
	else
		$size = $cfg['thumbnail_size'];
		
	if (isset($cfg['image_service_name'][$image_service_id]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]image_service_id');
	
	// flag 0 = No image
	// flag 1 = Skipped
	// flag 2 = Skipped not updated in this run
	// flag 3 = Stored image
	// flag 9 = Update one image by album_id, Needed for redirect to saveImage() (store as flag 1 or 3 in database)
	
	if ($flag == 2) {
		mysqli_query($db, 'UPDATE bitmap SET flag = 2 WHERE flag = 1');
		$flag = 1;
	}
	if ($flag == 1) {
		$query = mysqli_query($db, 'SELECT album.artist, album.album, album.album_id
			FROM album, bitmap
			WHERE bitmap.flag = 2
			AND bitmap.album_id = album.album_id
			ORDER BY album.artist_alphabetic, album.album');
	}
	elseif ($flag == 0) {
		$query = mysqli_query($db, 'SELECT album.artist, album.album, album.album_id
			FROM album, bitmap
			WHERE bitmap.flag = 0
			AND bitmap.album_id = album.album_id
			ORDER BY album.artist_alphabetic, album.album');
	}
	elseif ($flag == 9 && $cfg['album_update_image']) {
		$album_id = @$_REQUEST['album_id'];
		$query = mysqli_query($db, 'SELECT album.artist, album.artist_alphabetic, album.album, album.image_id, album.album_id,
			bitmap.flag, bitmap.image_front_width, bitmap.image_front_height
			FROM album, bitmap
			WHERE album.album_id = "' . mysqli_real_escape_string($db, $album_id) . '"
			AND bitmap.album_id = album.album_id');
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Error internet image update[/b][br]Unsupported flag set');
	
	
	$album = mysqli_fetch_assoc($query);
	if ($album == '') {
		header('Location: ' . NJB_HOME_URL . 'config.php');
		exit();
	}
		
	if ($artistSearch == '' && $albumSearch == '') {
		// Remove (...) [...] {...} from the end
		$artistSearch	= preg_replace('#^(.+?)(?:\s*\(.+\)|\s*\[.+\]|\s*{.+})?$#', '$1', $album['artist']);
		$albumSearch	= preg_replace('#^(.+?)(?:\s*\(.+\)|\s*\[.+\]|\s*{.+})?$#', '$1', $album['album']);
	}
	
	$responce_url			= array();
	$responce_pixels		= array();
	$responce_resolution	= array();
	$responce_squire		= array();
	
	$url = $cfg['image_service_url'][$image_service_id];
	$url = str_replace('%artist', rawurlencode(iconv(NJB_DEFAULT_CHARSET, $cfg['image_service_charset'][$image_service_id], $artistSearch)), $url);
	$url = str_replace('%album', rawurlencode(iconv(NJB_DEFAULT_CHARSET, $cfg['image_service_charset'][$image_service_id], $albumSearch)), $url);
	
	if ($cfg['image_service_process'][$image_service_id] == 'amazon') {
		// Amazon web services
		if (function_exists('hash_hmac') == false)
		 	message(__FILE__, __LINE__, 'error', '[b]Missing hash_hmac function[/b][br]For the Amazone Web Service the hash_hmac function is required.');

		$url = str_replace('%awsaccesskeyid', rawurlencode($cfg['image_AWSAccessKeyId']), $url);
		$url = str_replace('%associatetag', rawurlencode($cfg['image_AWSAssociateTag'] ), $url);
		$url = str_replace('%timestamp', rawurlencode(gmdate('Y-m-d\TH:i:s\Z')), $url);
		
		$url_array = parse_url($url);
		
		// Sort on query key
		$query = $url_array['query'];
		$query = explode('&', $query);
		sort($query);
		$query = implode('&', $query);
		
		$signature = 'GET' . "\n";
		$signature .= $url_array['host'] . "\n";
		$signature .= $url_array['path'] . "\n";
		$signature .= $query;
		$signature = rawurlencode(base64_encode(hash_hmac('sha256', $signature, $cfg['image_AWSSecretAccessKey'], true)));
		
		// $url = $url_array['scheme'] . '://' . $url_array['host'] . $url_array['path'] . '?' . $query;
		$url .= '&Signature=' . $signature;
		$xml = @simplexml_load_file($url) or message(__FILE__, __LINE__, 'error', '[b]Failed to open XML file:[/b][br]' . $url);
				
		foreach ($xml->Items->Item as $item) {
			if (isset($item->LargeImage->URL) && isset($item->LargeImage->Width) && isset($item->LargeImage->Height)) {
				$width					= $item->LargeImage->Width;
				$height					= $item->LargeImage->Height;
				$responce_url[]			= $item->LargeImage->URL;
				$responce_pixels[]		= $width * $height;
				$responce_resolution[]	= $width . ' x ' . $height;
				$responce_squire[]		= ($width/$height > 0.95 && $width/$height < 1.05) ? true : false;
				
			}
		}
	}
	elseif ($cfg['image_service_process'][$image_service_id] == 'lastfm') {
		// Last.fm web services
		$url = str_replace('%api_key', rawurlencode($cfg['image_lastfm_api_key']), $url);
		$xml = @simplexml_load_file($url) or message(__FILE__, __LINE__, 'error', '[b]Failed to open XML file:[/b][br]' . $url);
			
		foreach ($xml->album->image as $image) {
			$imagesize = @getimagesize($image);
			if ($imagesize !== false && isset($imagesize[0]) && isset($imagesize[1])) {
				$width					= $imagesize[0];
				$height					= $imagesize[1];
				$responce_url[]			= $image;
				$responce_pixels[]		= $width * $height;
				$responce_resolution[]	= $width . ' x ' . $height;
				$responce_squire[]		= ($width/$height > 0.95 && $width/$height < 1.05) ? true : false;
			}
		}
	}	
	else {
		// Regular expression
		$cfg['image_service_user_agent'] = (isset($cfg['image_service_user_agent']) && $cfg['image_service_user_agent']) ? $cfg['image_service_user_agent'] : $_SERVER['HTTP_USER_AGENT'];
		
		$options = array('http' => array('user_agent' => $cfg['image_service_user_agent']));
		$context = stream_context_create($options);
		$content = @file_get_contents($url, false, $context) or message(__FILE__, __LINE__, 'error', '[b]Failed to open url:[/b][br]' . $url);
		
		if (preg_match_all($cfg['image_service_process'][$image_service_id], $content, $match)) {
			foreach ($match[1] as $key => $image) {
				if ($cfg['image_service_urldecode'][$image_service_id])
					$image = rawurldecode($image);
				$extension = substr(strrchr($image, '.'), 1);
				$extension = strtolower($extension);
				if (!in_array($extension, array('gif', 'bmp'))) {
					if (isset($match[2][$key]) && isset($match[3][$key])) {
						$width 					= $match[2][$key];
						$height 				= $match[3][$key];
						$responce_url[]			= $image;
						$responce_pixels[]		= $width * $height;
						$responce_resolution[]	= $width . ' x ' . $height;
						$responce_squire[]		= ($width/$height > 0.95 && $width/$height < 1.05) ? true : false;
					}
					else {
						$imagesize = @getimagesize($image);
						if ($imagesize !== false && isset($imagesize[0]) && isset($imagesize[1])) {
							$width 					= $imagesize[0];
							$height 				= $imagesize[1];
							$responce_url[]			= $image;
							$responce_pixels[]		= $width * $height;
							$responce_resolution[]	= $width . ' x ' . $height;
							$responce_squire[]		= ($width/$height > 0.95 && $width/$height < 1.05) ? true : false;
						}
					}
				}
			}
		}
	}
	
	// squire images first:
	array_multisort($responce_squire, SORT_DESC, $responce_pixels, SORT_DESC, $responce_url, $responce_resolution);
	
	$width = (@$_COOKIE['netjukebox_width']) ? (int) $_COOKIE['netjukebox_width'] : 1024;
	$colombs = floor(($width - 40) / ($size + 10));
	$max_images = count($responce_squire) + 2; // n + "no image available" + "upload"
	$max_images = ($max_images > 10) ? 10 : $max_images;
	
	if (isset($album['flag']) && $album['flag'] == 3)
		$max_images += 1; // Current image
		
	if ($flag == 9) {
		$cfg['menu'] = 'media';
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Media';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= $album['artist_alphabetic'];
		$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
		$nav['name'][]	= $album['album'];
		$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . rawurlencode($album_id);
		$nav['name'][]	= 'Update image';
	}
	else {
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Configuration';
		$nav['url'][]	= 'config.php';
		$nav['name'][]	= 'Update image';
	}
	
	require_once('include/header.inc.php');
?>
<form action="update.php" method="post" id="imageform">
		<input type="hidden" name="action" value="imageUpdate">
		<input type="hidden" name="flag" value="<?php echo $flag; ?>">
		<input type="hidden" name="album_id" value="<?php if (isset($album_id)) echo $album_id; ?>">
<table class="border">
<tr class="header">
	<td colspan="<?php echo $colombs + 2; ?>">
	<!-- begin table header -->
	<table style="width: 100%;">
	<tr class="header">
		<td class="space"></td>
		<td><?php echo html($album['artist']) . ' - ' . html($album['album']); ?></td>
		<td class="text-align-right">
			<a href="update.php?action=imageUpdate<?php if (isset($album_id)) echo '&amp;album_id=' . $album_id; ?>&amp;flag=<?php echo $flag; ?>&amp;size=50"><img src="<?php echo $cfg['img']; ?>small_header_image50_<?php echo ($size == '50') ? 'on' : 'off'; ?>.png" alt="" class="small"></a><!--
			--><a href="update.php?action=imageUpdate<?php if (isset($album_id)) echo '&amp;album_id=' . $album_id; ?>&amp;flag=<?php echo $flag; ?>&amp;size=100"><img src="<?php echo $cfg['img']; ?>small_header_image100_<?php echo ($size == '100') ? 'on' : 'off'; ?>.png" alt="" class="small"></a><!--
			--><a href="update.php?action=imageUpdate<?php if (isset($album_id)) echo '&amp;album_id=' . $album_id; ?>&amp;flag=<?php echo $flag; ?>&amp;size=200"><img src="<?php echo $cfg['img']; ?>small_header_image200_<?php echo ($size == '200') ? 'on' : 'off'; ?>.png" alt="" class="small"></a>
		</td>
	</tr>
	</table>
	<!-- end table header -->
	</td>
</tr>
<tr class="odd smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<?php
	for ($i = 0; $i < ceil($max_images / $colombs); $i++) {
		$class = ($i & 1) ? 'even' : 'odd';
?>
<tr class="<?php echo $class; ?>">
	<td class="smallspace">&nbsp;</td>
<?php
		for ($j = 1; $j <= $colombs; $j++) { ?>
	<td>
	<span id="image<?php echo $i * $colombs + $j; ?>"><img src="image/dummy.png" alt="" width="<?php echo $size; ?>" height="<?php echo $size; ?>" class="thumbnail"></span>
	</td>
<?php
		} ?>
	<td class="smallspace">&nbsp;</td>
</tr>
<?php
	} ?>
<tr class="<?php echo $class; ?> smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<tr class="footer">
	<td colspan="<?php echo $colombs + 2; ?>">
	<!-- begin table footer -->
	<table>
	<tr class="footer smallspace"><td colspan="6"></td></tr>
	<tr class="footer">
		<td class="space"></td>
		<td>Artist:</td>
		<td class="space"></td>
		<td><input type="text" name="artist" value="<?php echo html($artistSearch); ?>" class="edit"></td>
		<td class="textspace"></td>
		<td>		
		<select name="image_service_id">
<?php
	foreach ($cfg['image_service_name'] as $key => $value)
		echo "\t\t" . '<option value="' . $key . '"' . (($image_service_id == $key) ? ' selected' : ''). '>' . html($value) . '</option>' . "\n"; ?>
		</select>		
		</td>
	</tr>
	<tr class="footer smallspace"><td colspan="6"></td></tr>
	<tr class="footer">
		<td></td>		
		<td>Album:</td>
		<td></td>
		<td><input type="text" name="album" value="<?php echo html($albumSearch); ?>" class="edit"></td>
		<td></td>
		<td><a href="javascript:imageform.submit();" class="smallbutton">search</a></td>
	</tr>
	<tr class="footer smallspace"><td colspan="6"></td></tr>
	</table>
	<!-- end table footer -->
	</td>
</tr>
</table>
</form>
<script type="text/javascript">
<?php
	$i = 0;
	if (isset($album['flag']) && $album['flag'] == 3) {
		// Show current image
		$i++;
		$mouseover = ' title="Current image: ' . $album['image_front_width'] . ' x ' . $album['image_front_height'] . '"';
		$url = '<a href="index.php?action=view3&amp;album_id=' . rawurlencode($album_id) . '"' . $mouseover . '><img src="image.php?image_id=' . $album['image_id'] . '" alt="" width="' . $size . '" height="' . $size . '"><\/a>';
		echo 'document.getElementById(\'image' . $i . '\').innerHTML=\'' . $url . '\';' . "\n";
	}
	
	foreach ($responce_url as $key => $image) {
		$i++;
		$url = '<a href="update.php?action=saveImage&flag=' . $flag . '&amp;album_id=' . $album['album_id'] . '&amp;image=' . rawurlencode($image) . '&amp;sign=' . $cfg['sign'] . '" title="' . html($responce_resolution[$key]) . '"><img src="image.php?image=' . rawurlencode($image) . '" alt="" width="' . $size . '" height="' . $size . '" class="thumbnail"><\/a>';
		echo 'document.getElementById(\'image' . $i . '\').innerHTML=\'' . $url . '\';' . "\n";
	}
	
	$i++;
	$url = '<a href="update.php?action=saveImage&amp;flag=' . $flag . '&amp;album_id=' . $album['album_id'] . '&amp;image=noImage&amp;sign=' . $cfg['sign'] . '" title="No image"><img src="image/no_image.png" alt="" width="' . $size . '" height="' . $size . '" class="thumbnail"><\/a>';
	echo 'document.getElementById(\'image' . $i . '\').innerHTML=\'' . $url . '\';' . "\n";
	
	$i++;
	$url = '<a href="update.php?action=selectImageUpload&amp;flag=' . $flag . '&amp;album_id=' . $album['album_id'] . '" title="Upload"><img src="skin/' . rawurlencode($cfg['skin']) . '/img/large_upload.png" alt="" width="' . $size . '" height="' . $size . '" class="thumbnail"><\/a>';
	echo 'document.getElementById(\'image' . $i . '\').innerHTML=\'' . $url . '\';' . "\n"; ?>
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Save image                                                             |
//  +------------------------------------------------------------------------+
function saveImage($flag_flow) {
	global $cfg, $db;
	authenticate('access_admin', false, true);
	
	$source = @$_GET['image'];
	$album_id = @$_GET['album_id'];
	
	$query		= mysqli_query($db, 'SELECT relative_file FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$track		= mysqli_fetch_assoc($query);
	$image_dir	= $cfg['media_dir'] . $track['relative_file'];
	$image_dir	= substr($image_dir, 0, strrpos($image_dir, '/') + 1);
	
	if ($track == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	if ($source == 'noImage') {
		$image = NJB_HOME_DIR . 'image/no_image.png';
		if (is_file($image_dir . $cfg['image_front'] . '.jpg') && @unlink($image_dir . $cfg['image_front'] . '.jpg') == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $image_dir . $cfg['image_front'] . '.jpg');
		if (is_file($image_dir . $cfg['image_front'] . '.png') && @unlink($image_dir . $cfg['image_front'] . '.png') == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $image_dir . $cfg['image_front'] . '.png');
		
		$flag = 1; // Skipped (or Delete)		
	}
	else {
		$imagesize = @getimagesize($source);
		if ($imagesize !== false && isset($imagesize[2]) && $imagesize[2] == IMAGETYPE_JPEG) {
			$image = $image_dir . $cfg['image_front'] . '.jpg';
			$delete = $image_dir . $cfg['image_front'] . '.png';
		}
		elseif ($imagesize !== false && isset($imagesize[2]) && $imagesize[2] == IMAGETYPE_PNG) {
			$image = $image_dir . $cfg['image_front'] . '.png';
			$delete = $image_dir . $cfg['image_front'] . '.jpg';
		}
		else
			message(__FILE__, __LINE__, 'error', '[b]Save image error[/b][br]Unsupported file.');

		if (copy($source, $image) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to copy[/b][br]from: ' . $source . '[br]to: ' . $image);
		if (is_file($delete) && @unlink($delete) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $delete);
		
		$flag = 3; // Stored image
	}
	
	$filemtime	= filemtime($image);
	$filesize	= filesize($image);
	$imagesize	= @getimagesize($image) or message(__FILE__, __LINE__, 'error', '[b]Failed to read image information from:[/b][br]' . $image);
	$image_id	= (($flag == 3) ? $album_id : 'no_image');
	$image_id	.= '_' . base_convert(NJB_IMAGE_SIZE * 100 + NJB_IMAGE_QUALITY, 10, 36) . base_convert($filemtime, 10, 36) . base_convert($filesize, 10, 36); 
	 
	$relative_image = substr($image, strlen($cfg['media_dir']));
	mysqli_query($db, 'UPDATE bitmap SET
		image				= "' . mysqli_real_escape_string($db, resampleImage($image)) . '",
		filesize			= ' . (int) $filesize . ',
		filemtime			= ' . (int) $filemtime . ',
		flag				= ' . (int) $flag . ',
		image_front			= "' . ($flag == 3 ? mysqli_real_escape_string($db, $relative_image) : '') . '",
		image_front_width	= ' . ($flag == 3 ? $imagesize[0] : 0) . ',
		image_front_height	= ' . ($flag == 3 ? $imagesize[1] : 0) . ',
		image_id			= "' . mysqli_real_escape_string($db, $image_id) . '"
		WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"');
		
	mysqli_query($db, 'UPDATE album SET
		image_id			= "' . mysqli_real_escape_string($db, $image_id) . '"
		WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"');
	
	if ($flag_flow == 9) {
		header('Location: ' . NJB_HOME_URL . 'index.php?action=view3&album_id=' . $album_id);
		exit();
	}
	else
		imageUpdate($flag_flow);
}




//  +------------------------------------------------------------------------+
//  | Select image upload                                                    |
//  +------------------------------------------------------------------------+
function selectImageUpload($flag) {
	global $cfg, $db;
	authenticate('access_admin');
	
	$album_id = @$_GET['album_id'];
	
	$query = mysqli_query($db, 'SELECT artist, artist_alphabetic, album, album_id
		FROM album
		WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	if ($flag == 0 || $flag == 1) {
		$cancel = 'update.php?action=imageUpdate&amp;flag=' . rawurlencode($flag);
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Configuration';
		$nav['url'][]	= 'config.php';
		$nav['name'][]	= 'Update image';
		$nav['url'][]	= 'update.php?action=imageUpdate&amp;flag=' . rawurlencode($flag);
		$nav['name'][]	= 'Upload';
	}
	elseif ($flag == 9 && $cfg['album_update_image']) {
		$cfg['menu'] = 'media';
		$cancel = 'index.php?action=view3&amp;album_id=' . rawurlencode($album_id);
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Media';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= $album['artist_alphabetic'];
		$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
		$nav['name'][]	= $album['album'];
		$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . rawurlencode($album_id);
		$nav['name'][]	= 'Update image';
		$nav['url'][]	= 'update.php?action=imageUpdate&amp;flag=9&amp;album_id=' . rawurlencode($album_id);
		$nav['name'][]	= 'Upload';
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Error internet image update[/b][br]Unsupported flag set');
	
	require_once('include/header.inc.php');
?>
<form action="update.php" method="post" enctype="multipart/form-data" id="uploadform">
		<input type="hidden" name="action" value="imageUpload">
		<input type="hidden" name="flag" value="<?php echo $flag; ?>">
		<input type="hidden" name="album_id" value="<?php echo html($album_id); ?>">
		<input type="hidden" name="sign" value="<?php echo html($cfg['sign']); ?>">
<table class="border bottom_space">
<tr class="header">
	<td></td>
	<td colspan="3">Upload</td>
	<td></td>
</tr>
<tr class="odd">
	<td class="space"></td>
	<td>Front cover:</td>
	<td class="textspace"></td>
	<td><input type="file" name="image_front"></td>
	<td class="space"></td>
</tr>
<tr class="even">
	<td class="space"></td>
	<td>Back cover:</td>
	<td class="textspace"></td>
	<td><input type="file" name="image_back"></td>
	<td class="space"></td>
</tr>
</table>
<a href="javascript:uploadform.submit();" class="button space">upload</a><!--
--><a href="<?php echo $cancel; ?>" class="button">cancel</a>
</form>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Image upload                                                           |
//  +------------------------------------------------------------------------+
function imageUpload($flag_flow) {
	global $cfg, $db;
	authenticate('access_admin', false, true);
	
	if (ini_get('file_uploads') == false)
		message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]File uploads disabled in the php.ini.');
	
	if ($_FILES['image_front']['error'] == UPLOAD_ERR_NO_FILE && $_FILES['image_back']['error'] == UPLOAD_ERR_NO_FILE)
		message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]There is no file uploaded');
	
	if ($_FILES['image_front']['error'] != UPLOAD_ERR_OK && $_FILES['image_front']['error'] != UPLOAD_ERR_NO_FILE) {
		if ($_FILES['image_front']['error'] == UPLOAD_ERR_INI_SIZE)			message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]The file is larger than the value set in php.ini for upload_max_file');
		elseif ($_FILES['image_front']['error'] == UPLOAD_ERR_PARTIAL)		message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]The file is not fully uploaded');
		elseif ($_FILES['image_front']['error'] == UPLOAD_ERR_NO_TMP_DIR)	message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]PHP, the directory for the temporary file not found');
		elseif ($_FILES['image_front']['error'] == UPLOAD_ERR_CANT_WRITE)	message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]PHP could not write the temporary file');
		else																message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]Error code: ' . $_FILES['image_front']['error']);
	}
	
	if ($_FILES['image_back']['error'] != UPLOAD_ERR_OK && $_FILES['image_back']['error'] != UPLOAD_ERR_NO_FILE) {
		if ($_FILES['image_back']['error'] == UPLOAD_ERR_INI_SIZE)			message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]The file is larger than the value set in php.ini for upload_max_file');
		elseif ($_FILES['image_back']['error'] == UPLOAD_ERR_PARTIAL)		message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]The file is not fully uploaded');
		elseif ($_FILES['image_back']['error'] == UPLOAD_ERR_NO_TMP_DIR)	message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]PHP, the directory for the temporary file not found');
		elseif ($_FILES['image_back']['error'] == UPLOAD_ERR_CANT_WRITE)	message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]PHP could not write the temporary file');
		else																message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]Error code: ' . $_FILES['image_back']['error']);
	}
	
	$album_id	= @$_POST['album_id'];
	$query		= mysqli_query($db, 'SELECT relative_file FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$track		= mysqli_fetch_assoc($query);
	$image_dir	= $cfg['media_dir'] . $track['relative_file'];
	$image_dir	= substr($image_dir, 0, strrpos($image_dir, '/') + 1);
	
	if ($track == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	if ($_FILES['image_front']['error'] == UPLOAD_ERR_OK)
		{
		$imagesize = @getimagesize($_FILES['image_front']['tmp_name']);
		if ($imagesize !== false && isset($imagesize[2]) && $imagesize[2] == IMAGETYPE_JPEG) {
			$image = $image_dir . $cfg['image_front'] . '.jpg';
			$delete = $image_dir . $cfg['image_front'] . '.png';
		}
		elseif ($imagesize !== false && isset($imagesize[2]) && $imagesize[2] == IMAGETYPE_PNG) {
			$image = $image_dir . $cfg['image_front'] . '.png';
			$delete = $image_dir . $cfg['image_front'] . '.jpg';
		}
		else
			message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]Unsupported file.');
		
		if (copy($_FILES['image_front']['tmp_name'], $image) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to copy[/b][br]from: ' . $_FILES['image_front']['tmp_name'] . '[br]to: ' . $image);
		if (is_file($delete) && @unlink($delete) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $delete);
		
		$flag		= 3; // stored
		$filemtime	= filemtime($image);
		$filesize	= filesize($image);
		$image_id	= $album_id . '_' . base_convert(NJB_IMAGE_SIZE * 100 + NJB_IMAGE_QUALITY, 10, 36) . base_convert($filemtime, 10, 36) . base_convert($filesize, 10, 36);
				
		$relative_image = substr($image, strlen($cfg['media_dir']));
		mysqli_query($db, 'UPDATE bitmap SET
			image				= "' . mysqli_real_escape_string($db, resampleImage($image)) . '",
			filesize			= ' . (int) $filesize . ',
			filemtime			= ' . (int) $filemtime . ',
			flag				= ' . (int) $flag . ',
			image_front			= "' . mysqli_real_escape_string($db, $relative_image) . '",
			image_front_width	= ' . (int) $imagesize[0] . ',
			image_front_height	= ' . (int) $imagesize[1] . ',
			image_id			= "' . mysqli_real_escape_string($db, $image_id) . '"
			WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"');
		
		mysqli_query($db, 'UPDATE album SET
			image_id			= "' . mysqli_real_escape_string($db, $image_id) . '"
			WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"');		
	}
	
	if ($_FILES['image_back']['error'] == UPLOAD_ERR_OK) {
		$imagesize = @getimagesize($_FILES['image_back']['tmp_name']);
		if ($imagesize !== false && isset($imagesize[2]) && $imagesize[2] == IMAGETYPE_JPEG) {
			$image = $image_dir . $cfg['image_back'] . '.jpg';
			$delete = $image_dir . $cfg['image_back'] . '.png';
		}
		elseif ($imagesize !== false && isset($imagesize[2]) && $imagesize[2] == IMAGETYPE_PNG) {
			$image = $image_dir . $cfg['image_back'] . '.png';
			$delete = $image_dir . $cfg['image_back'] . '.jpg';
		}
		else message(__FILE__, __LINE__, 'error', '[b]Upload error[/b][br]Unsupported file.');
		
		if (copy($_FILES['image_back']['tmp_name'], $image) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to copy[/b][br]from: ' . $_FILES['image_back']['tmp_name'] . '[br]to: ' . $image);
		if (is_file($delete) && @unlink($delete) == false)
			message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $delete);
		
		$relative_image = substr($image, strlen($cfg['media_dir']));
		mysqli_query($db, 'UPDATE bitmap SET
			image_back			= "' . mysqli_real_escape_string($db, $relative_image) . '"
			WHERE album_id		= "' . mysqli_real_escape_string($db, $album_id) . '"');
	}
	
	if ($flag_flow == 9) {
		header('Location: ' . NJB_HOME_URL . 'index.php?action=view3&album_id=' . $album_id);
		exit();
	}
	else
		imageUpdate($flag_flow);
}




//  +------------------------------------------------------------------------+
//  | Resample image                                                         |
//  +------------------------------------------------------------------------+
Function resampleImage($image, $size = NJB_IMAGE_SIZE) {
	$extension = strtolower(substr(strrchr($image, '.'), 1));
		
	if		($extension == 'jpg')	$src_image = @imageCreateFromJpeg($image)	or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
	elseif	($extension == 'png')	$src_image = @imageCreateFromPng($image)	or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
	else																		message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]Unsupported extension.');
	
	if ($extension == 'jpg' && imageSX($src_image) == $size && imageSY($src_image) == $size) {
		$data = @file_get_contents($image) or message(__FILE__, __LINE__, 'error', '[b]Failed to open file:[/b][br]' . $image);
	}
	elseif (imageSY($src_image) / imageSX($src_image) <= 1) {
		// Crops from left and right to get a squire image.
		$sourceWidth		= imageSY($src_image);
		$sourceHeight		= imageSY($src_image);
		$sourceX			= round((imageSX($src_image) - imageSY($src_image)) / 2);
		$sourceY			= 0;
	}
	else {
		// Crops from top and bottom to get a squire image.
		$sourceWidth		= imageSX($src_image);
		$sourceHeight		= imageSX($src_image);
		$sourceX			= 0;
		$sourceY			= round((imageSY($src_image) - imageSX($src_image)) / 2);
	}
	if (isset($sourceWidth)) {
		$dst_image = ImageCreateTrueColor($size, $size);
		imageCopyResampled($dst_image, $src_image, 0, 0, $sourceX, $sourceY, $size, $size, $sourceWidth, $sourceHeight);
		ob_start();
		imageJpeg($dst_image, NULL, NJB_IMAGE_QUALITY); 
		$data = ob_get_contents();
		ob_end_clean();
		imageDestroy($dst_image);
	}
	
	imageDestroy($src_image);
	return $data;
}