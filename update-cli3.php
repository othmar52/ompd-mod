<?php

// Phase 3
// extract detailed information of music-files/tags

/*
 * TODO: before executing anything for a directory compare filemtime of database and filesystem and skip track if possible
 * TODO: instead of heaving multiple update-cli.php scripts merge the together with a procedural logic
 * TODO: move memory_limit - value to config
 *
 **/
 
 
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



require_once('getid3/getid3/getid3.php');
$getID3 = new getID3;
//initial settings for getID3:
include 'include/getID3init.inc.php';

$albumGenreIds = array();
$previousAlbumId = '';

#mysql_query("UPDATE `album` SET `image_id`=''");
#mysql_query("TRUNCATE `bitmap`"); 
$res = mysql_query('SELECT id,relative_file,album_id,genre FROM track WHERE 1 ORDER BY album_id');

$counter = 0;
while($rec = mysql_fetch_assoc($res)) {
	
	$counter++;
	echo "#" . $counter . " " . $rec['relative_file'] . "\n";
	scanAndUpdateTrack($rec['relative_file'], $rec['id'], $rec['album_id']);
	
	// update album.genre_id with most appearing genre:id of tracks.
	// TODO: allow multiple genre_ids per album
	$albumGenreIds[] = $rec['genre'];
	if($counter === 1) {
		$previousAlbumId = $rec['album_id'];
		continue;
	}
	if($previousAlbumId !== $rec['album_id']) {
		# set genre-id for album
		mysql_query('
			UPDATE album
			SET genre_id= ' . (int)extractMostFrequentArrayValue($albumGenreIds) . '
			WHERE album_id="' . mysql_real_escape_string($previousAlbumId) . '"'
		);
		$previousAlbumId = $rec['album_id'];
		$albumGenreIds = array();
	}
	
} 


echo "FINISHED reading TAGS\n";
exit;


function extractMostFrequentArrayValue($inputArray) {
	$c = array_count_values($inputArray); 
	return array_search(max($c), $c);
}


//  +------------------------------------------------------------------------+
//  | File info                                                              |
//  +------------------------------------------------------------------------+
function scanAndUpdateTrack($musicFilePath, $track_id, $album_id) {
	global $cfg, $db, $getID3;
	
	$year = NULL;
	$dr = NULL;
	
	$filesize = filesize($cfg['media_dir'] . $musicFilePath);
	// skip very large files
	// TODO: how to handle this?
	if($filesize > 1000000000) {
		return;
	}

	$ThisFileInfo = $getID3->analyze($cfg['media_dir'] . $musicFilePath);
	getid3_lib::CopyTagsToComments($ThisFileInfo);
	
	$mime_type					= (isset($ThisFileInfo['mime_type'])) ? $ThisFileInfo['mime_type'] : 'application/octet-stream';
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
	$error						= (isset($ThisFileInfo['error'])) ? implode('<br>', $ThisFileInfo['error']) : '';
	

	$a = array_values($ThisFileInfo['comments']['comment']);
	if (isset($a[0]))
		$comment = trim(strip_tags($a[0]));
	else 
		$comment = '';
	
	

	if (isset($ThisFileInfo['comments']['dynamic range'][0])) $dr = $ThisFileInfo['comments']['dynamic range'][0];
	elseif (isset($ThisFileInfo['tags']['id3v2']['text']['DYNAMIC RANGE'])) $dr = $ThisFileInfo['tags']['id3v2']['text']['DYNAMIC RANGE'];
	

	if (isset($ThisFileInfo['audio']['dataformat'])) {
		$audio_dataformat = $ThisFileInfo['audio']['dataformat'];
		$audio_encoder = (isset($ThisFileInfo['audio']['encoder'])) ? $ThisFileInfo['audio']['encoder'] : 'Unknown encoder';
		
		if (isset($ThisFileInfo['mpc']['header']['profile']))			$audio_profile = $ThisFileInfo['mpc']['header']['profile'];
		if (isset($ThisFileInfo['aac']['header']['profile_text']))		$audio_profile = $ThisFileInfo['aac']['header']['profile_text'];
		
		if (empty($ThisFileInfo['audio']['lossless']) == false) {
			$audio_lossless = 1;
			if (empty($ThisFileInfo['audio']['compression_ratio']) == false) {
				if ($ThisFileInfo['audio']['compression_ratio'] == 1)
				$audio_profile = 'Lossless';
				else $audio_profile = 'Lossless compression';
			}
			else $audio_profile = 'Lossless';
		}
		
		if (isset($ThisFileInfo['audio']['compression_ratio']))			$audio_compression_ratio = $ThisFileInfo['audio']['compression_ratio'];
		if (isset($ThisFileInfo['audio']['bitrate_mode']))				$audio_bitrate_mode = $ThisFileInfo['audio']['bitrate_mode'];
		if (isset($ThisFileInfo['audio']['bitrate']))					$audio_bitrate = $ThisFileInfo['audio']['bitrate'];
		if (!$audio_profile)											$audio_profile = $audio_bitrate_mode . ' ' . round($audio_bitrate / 1000, 1) . '  kbps';
	
		$audio_bits_per_sample	= (isset($ThisFileInfo['audio']['bits_per_sample'])) ? $ThisFileInfo['audio']['bits_per_sample'] : 16;
		$audio_sample_rate		= (isset($ThisFileInfo['audio']['sample_rate'])) ? $ThisFileInfo['audio']['sample_rate'] : 44100;
		$audio_channels			= (isset($ThisFileInfo['audio']['channels'])) ? $ThisFileInfo['audio']['channels'] : 2;
		$audio_bitrate			= round($audio_bitrate); // integer in database					
	}
	if (isset($ThisFileInfo['video']['dataformat'])) {
		$video_dataformat = $ThisFileInfo['video']['dataformat'];
		$video_codec = (isset($ThisFileInfo['video']['codec'])) ? $ThisFileInfo['video']['codec'] : 'Unknown codec';
		
		if (isset($ThisFileInfo['video']['resolution_x']))		$video_resolution_x	= $ThisFileInfo['video']['resolution_x'];
		if (isset($ThisFileInfo['video']['resolution_y']))		$video_resolution_y	= $ThisFileInfo['video']['resolution_y'];
		if (isset($ThisFileInfo['video']['frame_rate']))		$video_framerate	= $ThisFileInfo['video']['frame_rate'] . ' fps';
	}

	mysql_query('UPDATE track SET
		mime_type					= "' . mysql_real_escape_string($mime_type) . '",
		filesize					= ' . (int) $filesize . ',
		audio_bitrate				= ' . (int) $audio_bitrate . ',
		audio_bits_per_sample		= ' . (int) $audio_bits_per_sample . ',
		audio_sample_rate			= ' . (int) $audio_sample_rate . ',
		audio_channels				= ' . (int) $audio_channels . ',
		audio_lossless				= ' . (int) $audio_lossless . ',
		audio_compression_ratio		= ' . (float) $audio_compression_ratio . ',			
		audio_dataformat			= "' . mysql_real_escape_string($audio_dataformat) . '",
		audio_encoder 				= "' . mysql_real_escape_string($audio_encoder) . '",
		audio_profile				= "' . mysql_real_escape_string($audio_profile) . '",
		video_dataformat			= "' . mysql_real_escape_string($video_dataformat) . '",
		video_codec					= "' . mysql_real_escape_string($video_codec) . '",
		video_resolution_x			= ' . (int) $video_resolution_x . ',
		video_resolution_y			= ' . (int) $video_resolution_y . ',
		video_framerate				= ' . (int) $video_framerate . ',
		error						= "' . mysql_real_escape_string($error) . '",
		comment			= "' . mysql_real_escape_string($comment) . '",
		dr		= ' . ((is_null($dr)) ? 'NULL' : (int) $dr) . '
		WHERE id 		= ' . (int)$track_id
	);
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
