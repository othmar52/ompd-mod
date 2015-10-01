<?php
/* TODO: currently there is no check of any update status
 * TODO: remove dead items after update
 * TODO: instead of heaving multiple update-cli.php scripts merge the together with a procedural logic
 * TODO: move memory_limit - value to config
 */
if(PHP_SAPI !== 'cli') {
	header('HTTP/1.0 403 Forbidden');
	echo "Sorry, execution is not allowed via http...";
	die();
}


ini_set('max_execution_time', 0);
ini_set('memory_limit', '4096M');

require_once('include/initialize.inc.php');
require_once('include/cache.inc.php');


$cfg['cli_update'] = true;

// check if mpd_db_file exists
if(is_file($cfg['mpd_db_file']) == FALSE || is_readable($cfg['mpd_db_file']) === FALSE) {
	echo "databasefile not readable";
	exit;
}

// get all existing album-ids to increase performance by avoiding thousands of mysqlqueries
$allAlbumIds = getAllAlbumIds();

// get all existing genres to increase performance by avoiding thousands of mysqlqueries
$allGenreIds = getAllGenreIds();




$dbfile = explode("\n", file_get_contents($cfg['mpd_db_file']));


$currentDirectory = "";
$currentSong = "";
$currentPlaylist = "";
$currentSection = "";
$dirs = array();
$songs = array();
$playlists = array();
$dircount = 0;
$filescount = 0;
$linecount = 0;


$level = -1;
$opendirs = array();


$mtime = 0;
$time = 0;
$artist = '';
$title = '';
$track = '';
$album = '';
$date = '';
$genre = '';

$mtimeDirectory = 0;





foreach($dbfile as $line) {
	$linecount++;
	if(trim($line) === "") {
		continue;	// skip empty lines
	}
	
	$attr = explode (": ", $line, 2);
	if(count($attr === 1)) {
		switch($attr[0]) {
			case 'info_begin': break;
			case 'info_end': break;
			case 'playlist_end':
				$playlists[] = $currentDirectory . DIRECTORY_SEPARATOR . $currentPlaylist;
				$currentPlaylist = "";
				$currentSection = "";
				break;
			case 'song_end':
				$filescount++;
				updateSong(
					$currentSong,
					$currentDirectory . DIRECTORY_SEPARATOR,
					$mtimeDirectory,
					$mtime,
					$time,
					$artist,
					$title,
					$track,
					$album,
					$date,
					$genre
				);
				echo "#" . $filescount . " " . $currentDirectory . DIRECTORY_SEPARATOR . $currentSong . "\n";
				$songs[] = $currentDirectory . DIRECTORY_SEPARATOR . $currentSong;
				$currentSong = "";
				$currentSection = "";
				
				// reset song attributes
				$mtime = 0;
				$time = 0;
				$artist = '';
				$title = '';
				$track = '';
				$album = '';
				$date = '';
				$genre = '';

				break;
			default: break;
		}
		#continue;
	}
	switch($attr[0]) {
		case 'directory':
			$currentSection = "directory";
			break;
		case 'begin':
			$level++;
			$opendirs = explode(DIRECTORY_SEPARATOR, $attr[1]);
			$currentSection = "directory";
			$currentDirectory = $attr[1];
			break;
		case 'song_begin':
			$currentSection = "song";
			$currentSong = $attr[1];
			break;
		case 'playlist_begin':
			$currentSection = "playlist";
			$currentPlaylist = $attr[1];
			break;
		case 'end':
			$level--;
			$dirs[$currentDirectory] = TRUE;
			array_pop($opendirs);
			$currentDirectory = join(DIRECTORY_SEPARATOR, $opendirs);
			$currentSection = "";
			
			break;
			
		case 'mtime' :
			if($currentSection == "directory") {
				$mtimeDirectory = $attr[1];
			} else {
				$mtime = $attr[1];
			}
			break;
		case 'Time'  : $time = $attr[1];  break;
		case 'Artist': $artist = $attr[1];break;
		case 'Title' : $title = $attr[1]; break;
		case 'Track' : $track = $attr[1]; break;
		case 'Album' : $album = $attr[1]; break;
		case 'Genre' : $genre = $attr[1]; break;
		case 'Date'  : $date = $attr[1];  break;
	}
}
echo "\ndircount: " . count($dirs);
echo "\nsongs: " . count($songs);
echo "\nplaylists: " . count($playlists);
echo "\nexiting...\n";
exit;


function updateSong($currentSong,
					$currentDirectory,
					$mtimeDirectory,
					$mtime,
					$time,
					$artist,
					$title,
					$track,
					$album,
					$date,
					$genre) {
	global $cfg, $db, $allAlbumIds, $allGenreIds;
	
	
	
	// phase 
	if(isset($allAlbumIds[$cfg['media_dir'] . $currentDirectory]) === TRUE) {
		$album_id = $allAlbumIds[$cfg['media_dir'] . $currentDirectory];
	} else {
		// create a new album_id
		$album_id = base_convert(uniqid(), 16, 36);
		$album_add_time = $mtimeDirectory;
		mysql_query("
			INSERT INTO album_id(
				album_id,
				path,
				album_add_time,
				updated
			) VALUES ('" .
				mysql_real_escape_string($album_id) . "','" . 
				mysql_real_escape_string($cfg['media_dir'] . $currentDirectory) . "','" . 
				$album_add_time . "',
				'1'
			)"
		);
		$allAlbumIds[$cfg['media_dir'] . $currentDirectory] = $album_id;
		
		// initial insert with album-tags based on (first) track
		mysql_query("
			INSERT INTO album(
				artist,
				artist_alphabetic,
				album,
				year,
				album_id,
				album_add_time,
				genre_id,
				discs,
				updated
			) VALUES (
				'" . mysql_real_escape_string($artist) . "',
				'" . mysql_real_escape_string($artist) . "',
				'" . mysql_real_escape_string($album) . "',
				'" . mysql_real_escape_string($date) . "',
				'" . mysql_real_escape_string($album_id) . "',
				'".(int)$album_add_time."',
				'1',
				'1',
				'1'
			)"
		);
	}
	
	// TODO: assign multiple genres to track and album based on splitchars
	$genre = genreUnifier($genre);
	$genreAz09 = az09($genre);
		
	// get Genre id
	$genre_id = (isset($allGenreIds[$genreAz09]) === TRUE)
		? $allGenreIds[$genreAz09]
		: insertGenre($genre);
		
	$allGenreIds[$genreAz09] = $genre_id;
	
	
	// check if we do already have a matching track
	$res = mysql_query('SELECT track_id FROM track
		WHERE album_id		= "' . mysql_real_escape_string($album_id) . '"
		AND relative_file	= BINARY "' . mysql_real_escape_string($currentDirectory . $currentSong) . '"
		LIMIT 1'
	);
	if (mysql_num_rows($res) == 0) {
		mysql_query('
			INSERT INTO track (
				artist,
				title,
				relative_file,
				relative_file_hash,
				number,
				album_id,
				updated,
				track_id,
				filemtime,
				genre,
				year,
				disc,
				miliseconds,
				track_artist
			)
			VALUES (
				"' . mysql_real_escape_string($artist) . '",
				"' . mysql_real_escape_string($title) . '",
				"' . mysql_real_escape_string($currentDirectory . $currentSong) . '",
				"' . pathhash($currentDirectory . $currentSong) . '",
				' . ((is_numeric($track)) ? (int) $track : 'NULL'). ',
				"' . mysql_real_escape_string($album_id) . '",
				1,
				\'' . $album_id . '_' . fileId($cfg['media_dir'] . $currentDirectory . $currentSong) .'\',
				'. (int)$mtime . ',
				'. (int)$genre_id .',
				'. (int)$date .',
				1,
				'. $time*1000 .',
				\'' . mysql_real_escape_string($artist) . '\'
			)'
		);
	} else {
		$row = mysql_fetch_assoc($res);
		$track_id = $row["track_id"];
		mysql_query('UPDATE track SET
			artist				= "' . mysql_real_escape_string($artist) . '",
			title				= "' . mysql_real_escape_string($title) . '",
			number				= ' . ((is_numeric($track)) ? (int) $track : 'NULL') . ',
			album_id			= "' . mysql_real_escape_string($album_id) . '",
			updated				= 1
			WHERE track_id		= "' . mysql_real_escape_string($track_id) . '"
			LIMIT 1'
		);
	}
	
}





//  +------------------------------------------------------------------------+
//  | File identification                                                    |
//  +------------------------------------------------------------------------+
function fileId($file) {
	#error_log('fileId()' . $file);
	$filesize = filesize($file);
	if ($filesize > 5120) {
		$filehandle	= @fopen($file, 'rb') or message(__FILE__, __LINE__, 'error', '[b]Failed to open fileA:[/b][br]' . $file . '[list][*]Check file permission[/list]');
		fseek($filehandle, round(0.5 * $filesize - 2560 - 1));
		$data = fread($filehandle, 5120);
		$data .= $filesize;
		fclose($filehandle);
	}
	else
		$data = @file_get_contents($file) or message(__FILE__, __LINE__, 'error', '[b]Failed to open fileB:[/b][br]' . $file . '[list][*]Check file permission[/list]');
	
	$crc32 = dechex(crc32($data));
	return str_pad($crc32, 8, '0', STR_PAD_LEFT);
}

// get all existing album-ids to increase performance
function getAllAlbumIds() {
	global $db;
	$allAlbums = array();
	$res = mysql_query('SELECT path, album_id FROM album_id');
	while($rec = mysql_fetch_assoc($res)) {
		$allAlbums[$rec['path']] = $rec['album_id'];
	}
	return $allAlbums;
}

// get all existing genres to increase performance
function getAllGenreIds() {
	global $db;
	$allGenres = array();
	$res = mysql_query('SELECT genre, genre_id FROM genre');
	while($rec = mysql_fetch_assoc($res)) {
		$allGenres[$rec['genre']] = $rec['genre_id'];
	}
	return $allGenres;
}

function insertGenre($genreString) {
	global $db;
	$res = mysql_query(
		'INSERT INTO genre (genre)
		VALUES ("' . mysql_real_escape_string($genreString) . '")'
	);
	$genre_id = mysql_insert_id($db);
	return $genre_id;
}



function genreUnifier($genreString) {
	// TODO: convert to tree
	// https://en.wikipedia.org/wiki/List_of_electronic_music_genres
	// https://en.wikipedia.org/wiki/List_of_rock_genres
	// https://en.wikipedia.org/wiki/Heavy_metal_subgenres
	
	if(trim($genreString) == '') {
		return 'Unknown';
	}
	if(preg_match("/^HASH\((.*)\)$/i", $genreString)) {
		return 'HASH(0xxxxxxxx)';
	}
	$unified = array(
		'Drum & Bass' => array(
			'127',
			'db',
			'dnb',
			'dandb',
			'drumbass',
			'drumnbass',
			'drumandbass',
			'drumampbass',
			'drumandbassjungle',
			'drumnbassjungle',
			'rumbass',
			
			'jungle',
			'junglednb',
			'jungledrumbass',
			'jungledrumnbass',
			'jungledrumandbass',
			
			'liquid',
			'liquiddnb',
			'liquiddrumbass',
			'liquiddrumnbass',
			'liquiddrumandbass',
			
			'jumpup',
			'jumpupdnb',
			'jumpupdrumbass',
			'jumpupdrumnbass',
			'jumpupdrumandbass',
			
			'vocaldnb',
			'vocaldrumbass',
			'vocaldrumnbass',
			'vocaldrumandbass',
		),
		'Hip Hop' => array(
			'hiphip',
			
		),
		'Rap' => array(
			'rap',
			'hiphoprap',
			'raphiphop'
		),
		'R&B' => array(
			'rb',
			'rnb',
			'hiphoprb',
			'hiphoprnb',
			'rnbhiphip'
		),
		'Unknown' => array(
			'unknown',
			'genre',
			'unknowngenre',
			'unbekannt',
			'unbekanntesgenre',
			
			
		)
	);
	$az09 = az09($genreString);
	
	foreach($unified as $beauty => $matches) {
		foreach ($matches as $match) {
			if($az09 === $match) {
				return $beauty;
			}
		}
	}
	return ucwords(strtolower($genreString));
}

