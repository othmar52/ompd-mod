<?php

// Phase 2
// extract album artist and album title based on
// music-file-attributes which had been fetched
// from mpd-databasefile 

/*
 * TODO: before executing anything for a directory compare filemtime of database and filesystem and skip album if possible
 * TODO: handle errors like "Not a JPEG file: starts with 0x42 0x4d"
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



// pluralize $cfg['common_artwork_dir_names']
if($cfg['image_look_cover_directory'] === TRUE) {
	$tmp = $cfg['common_artwork_dir_names'];
	foreach($tmp as $i) {
		$cfg['common_artwork_dir_names'][] = $i . 's';
	}
	unset($tmp);
}

#mysql_query("UPDATE `album` SET `image_id`=''");
#mysql_query("TRUNCATE `bitmap`"); 
$res = mysql_query('SELECT album_id,path FROM album_id WHERE 1');

$counter = 0;
while($rec = mysql_fetch_assoc($res)) {
	
	$counter++;
	echo "#" . $counter . " " . $rec['path'] . "\n";
	fetchAlbumImages($rec['path'], $rec['album_id']);
} 


echo "FINISHED inserting images\n";
exit;


function fetchAlbumImages($albumDir, $album_id) {
	global $cfg, $db, $getID3;
	$fallBackImage = NJB_HOME_DIR . 'image/no_image.png';
	if( is_dir($albumDir) == FALSE) {
	  return array($fallBackImage);
	}
	
	$musicFiles = array();
	$imageFiles = array();
		
	$flag = 0; // No image
	
	if($cfg['image_read_embedded'] === TRUE) {
		// get all files music files of directory
		$handle=opendir($albumDir);
		while ($file = readdir ($handle)) {
			$ext = strtolower(preg_replace('/^.*\./', '', $file));
			if(is_file($albumDir . $file) && in_array($ext, $cfg['media_extension'])  !== FALSE ) {
				$musicFiles[] = $albumDir . $file;
			}
		}
		closedir($handle);
		foreach($musicFiles as $i) {
			$coverBinary = writeEmbeddedCoverToTempfile($i);
			if($coverBinary !== FALSE) {
				// md5() of extracted images of same album files seems to be different - lets use filesize
				$imageFiles[filesize($coverBinary)] = $coverBinary;
			}	
		}
	}
	
	if($cfg['image_look_current_directory'] === TRUE) {
		$imageFiles = array_merge($imageFiles, getImages($albumDir));
	}
	
	if($cfg['image_look_cover_directory'] === TRUE) {
		
		// get all image files of album directory
		$handle=opendir($albumDir);
		while ($dirname = readdir ($handle)) {
			if(is_dir($albumDir . $dirname)) {
				if(in_array(az09($dirname), $cfg['common_artwork_dir_names'])) {
					$imageFiles = array_merge($imageFiles, getImages($albumDir . $dirname));
				}
			}
		}
		closedir($handle);
	}
	
	if($cfg['image_look_parent_directory'] === TRUE && count($imageFiles) === 0) {
		$imageFiles = getImages(dirname($albumDir));
	}
	
	if(count($imageFiles) === 0) {
		$imageFiles = array($fallBackImage);
	}
	
	$albumUpdated = FALSE;
	
	// insert all images into database...
	foreach($imageFiles as $image) { 
		
		$filesize	= filesize($image);
		$filemtime	= filemtime($image);

		$flag = 0;
		$image_front = '';
		$image_back = '';
		if(stripos($image,NJB_HOME_DIR) !== FALSE && $image !== $fallBackImage) {
			$flag = 3;
			$image_front = str_replace($cfg['media_dir'], '', $image);
		}
		
		$imagesize = @getimagesize($image);
		if(!$imagesize){
			//TODO: logging
			#message(__FILE__, __LINE__, 'error', '[b]Failed to read image information from:[/b][br]' . $image);
			error_log('OMPD-import-image error for: ' . $image);
		}
		$image_id = (($flag == 3) ? $album_id : 'no_image');
		$image_id .= '_' . base_convert(NJB_IMAGE_SIZE * 100 + NJB_IMAGE_QUALITY, 10, 36) . base_convert($filemtime, 10, 36) . base_convert($filesize, 10, 36);
		
		
		
		mysql_query(
			'INSERT INTO bitmap (image, filesize, filemtime, flag, image_front, image_back, image_front_width, image_front_height, image_id, album_id, updated)
			VALUES ("' . mysql_real_escape_string(resampleImage($image)) . '",
			' . (int) $filesize . ',
			' . (int) $filemtime . ',
			' . (int) $flag . ',
			"' . mysql_real_escape_string($image_front) . '",
			"' . mysql_real_escape_string($image_back) . '",
			' . ($flag == 3 ? $imagesize[0] : 0) . ',
			' . ($flag == 3 ? $imagesize[1] : 0) . ',
			"' . mysql_real_escape_string($image_id) . '",
			"' . mysql_real_escape_string($album_id) . '",
			1)'
		);
		
		if($albumUpdated === FALSE) {
			mysql_query(
				'UPDATE album
				SET image_id			= "' . mysql_real_escape_string($image_id) . '"
				WHERE album_id		= "' . mysql_real_escape_string($album_id) . '"
				LIMIT 1');
			$albumUpdated = TRUE;
		}
		if(stripos($image, NJB_HOME_DIR . 'tmp/') === 0) {
			@unlink($image);
		} 
	}
}

function writeEmbeddedCoverToTempfile($musicFilePath) {
	global $cfg, $db, $getID3;

	$ThisFileInfo = $getID3->analyze($musicFilePath);
	getid3_lib::CopyTagsToComments($ThisFileInfo);
	if(isset($ThisFileInfo['error']) === TRUE) {
		unset($getID3); return FALSE;
	}
	if(isset($ThisFileInfo['comments']['picture'][0]['image_mime']) === FALSE) {
		unset($getID3); return FALSE;
	}
	if(isset($ThisFileInfo['comments']['picture'][0]['data']) === FALSE) {
		unset($getID3); return FALSE;
	}
	
	$tmpfile = NJB_HOME_DIR . 'tmp/' . md5($ThisFileInfo['comments']['picture'][0]['data']);
	switch($ThisFileInfo['comments']['picture'][0]['image_mime']) {
		case 'image/png':  $tmpfile .= '.png'; break;
		case 'image/jpeg': $tmpfile .= '.jpg'; break;
		default: unset($getID3); return FALSE;
	}
	
	if (file_put_contents($tmpfile, $ThisFileInfo['comments']['picture'][0]['data']) === FALSE) {
		//message(__FILE__, __LINE__, 'error', '[b]Failed to wtite image to:[/b][br]' . $image);
		@unlink($tmpfile);
		unset($getID3); return FALSE;
	} else {
		// web-update vs cli-update could cause an error due to permissions
		chmod($tmpfile, 0777);
	}
	return $tmpfile;

}
	


//  +------------------------------------------------------------------------+
//  | Resample image                                                         |
//  +------------------------------------------------------------------------+
function resampleImage($image, $size = NJB_IMAGE_SIZE) {
	$extension = strtolower(substr(strrchr($image, '.'), 1));
		
	#if		($extension == 'jpg')	$src_image = imageCreateFromJpeg($image)	or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
	#elseif	($extension == 'png')	$src_image = imageCreateFromPng($image)	or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
	#else																		message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]Unsupported extension.');
	
	
	if		($extension == 'jpg' || $extension == 'jpeg') {
		if(!$src_image = imageCreateFromJpeg($image)) {
			 //or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
			 $src_image = imageCreateFromJpeg('/var/www/playground/ompd/www/image/no_image.jpg');
		}
	} elseif	($extension == 'png'){
		if(!$src_image = imageCreateFromPng($image)) {
			 //or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
			 $src_image = imageCreateFromPng('/var/www/playground/ompd/www/image/no_image.png');
		}
	} elseif	($extension == 'gif'){
		if(!$src_image = imageCreateFromGif($image)) {
			 //or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
			 $src_image = imageCreateFromGif('/var/www/playground/ompd/www/image/no_image.png');
		}
	} else	{
		message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]Unsupported extension.');	
	}	


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



//  +------------------------------------------------------------------------+
//  | Resample image                                                         |
//  +------------------------------------------------------------------------+
Function noResampleImage($image, $size = NJB_IMAGE_SIZE) {
	$extension = strtolower(substr(strrchr($image, '.'), 1));
		
	if		($extension == 'jpg')	$src_image = @imageCreateFromJpeg($image)	or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
	elseif	($extension == 'png')	$src_image = @imageCreateFromPng($image)	or message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]' . $image);
	else																		message(__FILE__, __LINE__, 'error', '[b]Failed to resample image:[/b][br]Unsupported extension.');
	
	$data = @file_get_contents($image) or message(__FILE__, __LINE__, 'error', '[b]Failed to open file:[/b][br]' . $image);
	
	return $data;
}


function getImages($dir) {
	$foundFiles = array();
	if( is_dir($dir) == FALSE) {
	  return $foundFiles;
	}
	// TODO: move to conf...
	$valid_ext = array('jpg', 'jpeg', 'gif', 'png'/*, 'bmp'*/);
	$dir .= (substr($dir, -1) !== DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : ''; 
	
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$handle=opendir ($dir);
	while ($file = readdir ($handle)) {
		$ext = strtolower(preg_replace('/^.*\./', '', $file));
		if(is_file($dir . $file) && in_array($ext, $valid_ext) &&
		   stripos(finfo_file($finfo, $dir.$file), 'image')  !== FALSE ) {
			$foundFiles[] = $dir . $file;
		}
	}

	finfo_close($finfo);
	closedir($handle);
	return $foundFiles;
}

