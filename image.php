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
//  | image.php                                                              |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
require_once('include/stream.inc.php');

$image_id 	= @$_GET['image_id'];
$image	 	= @$_GET['image'];

if		($image_id)				image($image_id);
elseif	($image)				resampleImage($image);
elseif	($cfg['image_share'])	shareImage();
exit();




//  +------------------------------------------------------------------------+
//  | Image                                                                  |
//  +------------------------------------------------------------------------+
function image($image_id) {
	global $cfg, $db;
	$query  = mysqli_query($db, 'SELECT image FROM bitmap WHERE image_id = "' . mysqli_real_escape_string($db, $image_id) . '" LIMIT 1');
	$bitmap = mysqli_fetch_assoc($query) or imageError();
	
	header('Cache-Control: max-age=31536000');
	streamData($bitmap['image'], 'image/jpeg', false, false, '"never_expire"');	
}




//  +------------------------------------------------------------------------+
//  | Resample image                                                         |
//  +------------------------------------------------------------------------+
function resampleImage($image, $size = NJB_IMAGE_SIZE) {
	global $cfg, $db;
	authenticate('access_admin', true);
	
	if (substr($image, 0, 7) != 'http://' && substr($image, 0, 8) != 'https://')
		imageError();
	
	$extension = substr(strrchr($image, '.'), 1);
	$extension = strtolower($extension);
	
	if		($extension == 'jpg')	$src_image = @imageCreateFromJpeg($image) 	or imageError();
	elseif	($extension == 'jpeg')	$src_image = @imageCreateFromJpeg($image)	or imageError();
	elseif	($extension == 'png')	$src_image = @imageCreateFromPng($image)	or imageError();
	else {
		$imagesize = @getimagesize($image) or imageError();
		if ($imagesize[2] == IMAGETYPE_JPEG) {
			$src_image = @imageCreateFromJpeg($image) or imageError();
			$extension = 'jpg';
		}
		elseif ($imagesize[2] == IMAGETYPE_PNG) {
			$src_image = @imageCreateFromJpeg($image) or imageError();
			$extension = 'png';
		}
		else
			imageError();
		
	}
	
	if (($extension == 'jpg' || $extension == 'jpeg') && imageSX($src_image) == $size && imageSY($src_image) == $size) {
		$data = @file_get_contents($image) or imageError();
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
	
	header('Cache-Control: max-age=900');
	streamData($data, 'image/jpeg');
}




//  +------------------------------------------------------------------------+
//  | Share image                                                            |
//  +------------------------------------------------------------------------+
function shareImage() {
	global $cfg, $db;
	
	if ($cfg['image_share_mode'] == 'played') {
		$query = mysqli_query($db, 'SELECT image, artist, album, filesize, filemtime, album.album_id
			FROM counter, album, bitmap
			WHERE counter.flag <= 1
			AND counter.album_id = album.album_id
			AND counter.album_id = bitmap.album_id
			ORDER BY counter.time DESC
			LIMIT 1');
		$bitmap = mysqli_fetch_assoc($query);
		$text	=  'Recently played:';
	}
	else {
		$query	= mysqli_query($db, 'SELECT image, artist, album, filesize, filemtime, album.album_id
			FROM album, bitmap 
			WHERE album.album_id = bitmap.album_id 
			ORDER BY album_add_time DESC
			LIMIT 1');
		$bitmap = mysqli_fetch_assoc($query);
		$text	=  'New album:';
		$cfg['image_share_mode'] = 'new';
	}
	
	$hash_data = $cfg['image_share_mode'] . $bitmap['album_id'] . $bitmap['filemtime'];
	$hash_data .= filemtime('image/share.png') . filemtime('image.php');
	
	$etag = '"' . md5($hash_data) . '"';
	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
		header('HTTP/1.1 304 Not Modified');
		header('ETag: ' . $etag);
		header('Cache-Control: max-age=5');
		exit();
	}
	
	// Background (253 x 52 pixel)
	$dst_image = imageCreateFromPng('image/share.png');
	
	// Image copy source NJB_IMAGE_SIZE x NJB_IMAGE_SIZE => 50x50
	$src_image = imageCreateFromString($bitmap['image']);
	imageCopyResampled($dst_image, $src_image, 1, 1, 0, 0, 50, 50, NJB_IMAGE_SIZE, NJB_IMAGE_SIZE);
	imageDestroy($src_image);
	
	// Text
	$font_color	= imagecolorallocate($dst_image, 0, 0, 99);
	$font 		= NJB_HOME_DIR . 'fonts/Roboto-Medium.ttf';
	imagettftext($dst_image, 10, 0, 55, 13, $font_color, $font, $text);
	$font		= NJB_HOME_DIR . 'fonts/Roboto-Regular.ttf';
	imagettftext($dst_image, 10, 0, 55, 30, $font_color, $font, $bitmap['artist']);
	imagettftext($dst_image, 10, 0, 55, 47, $font_color, $font, $bitmap['album']);
	
	// For to long text overwrite 4 pixels right margin
	$src_image = imageCreateFromPng('image/share.png');
	ImageCopy($dst_image, $src_image, 249, 0, 249, 0, 4, 52);
	imageDestroy($src_image);
	
	// Buffer data
	ob_start();
	ImagePng($dst_image);
	$data = ob_get_contents();
	ob_end_clean();
	
	imageDestroy($dst_image);
	
	header('Cache-Control: max-age=5');
	streamData($data, 'image/jpeg', false, false, $etag);
}




//  +------------------------------------------------------------------------+
//  | Image error                                                            |
//  +------------------------------------------------------------------------+
function imageError() {
	$etag = '"image_error_' . dechex(filemtime('image/image_error.png')) . '"';
	streamData(file_get_contents('image/image_error.png'), 'image/png', false, false, $etag);
	exit();
}
