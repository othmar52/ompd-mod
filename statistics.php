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
//  | statistics.php                                                         |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
require_once('include/cache.inc.php');
$cfg['menu'] = 'config';

$action	 			= @$_GET['action'];
$audio_dataformat 	= @$_GET['audio_dataformat'];
$video_dataformat	= @$_GET['video_dataformat'];

if	($audio_dataformat)	{
	$title = $audio_dataformat . ' audio';
	$imageTitle = true;
	$query = mysqli_query($db, 'SELECT album.artist_alphabetic, album.album, album.image_id, album.album_id
		FROM track, album 
		WHERE track.audio_dataformat = "' . mysqli_real_escape_string($db, $audio_dataformat) . '"
		AND track.video_dataformat = ""
		AND track.album_id = album.album_id 
		GROUP BY album.album_id 
		ORDER BY album.artist_alphabetic, album.album');
}
elseif ($video_dataformat) {
	$title = $video_dataformat . ' video';
	$imageTitle = true;
	$query = mysqli_query($db, 'SELECT album.artist_alphabetic, album.album, album.image_id, album.album_id
		FROM track, album 
		WHERE track.video_dataformat = "' . mysqli_real_escape_string($db, $video_dataformat) . '"
		AND track.album_id = album.album_id 
		GROUP BY album.album_id 
		ORDER BY album.artist_alphabetic, album.album');
}
elseif ($action == 'all') {
	$title = 'All';
	$imageTitle = true;
	$query = mysqli_query($db, 'SELECT artist_alphabetic, album, album.image_id, album.album_id
		FROM album 
		ORDER BY artist_alphabetic, album');
}
elseif ($action == 'noImage') {
	$title = 'No image';
	$imageTitle = false;
	$query = mysqli_query($db, 'SELECT album.artist_alphabetic, album.album, album.album_id
		FROM album, bitmap
		WHERE image_front = ""
		AND album.album_id = bitmap.album_id 
		ORDER BY album.artist_alphabetic, album.album');
}
elseif ($action == 'noFrontCover') {
	$pixel = round(sqrt($cfg['image_front_cover_treshold']));
	$title = 'No front cover';
	$imageTitle = false;
	$query = mysqli_query($db, 'SELECT album.artist_alphabetic, album.album, album.album_id
		FROM album, bitmap
		WHERE image_front_width * image_front_height < ' . $cfg['image_front_cover_treshold'] . '
		AND album.album_id = bitmap.album_id 
		ORDER BY album.artist_alphabetic, album.album');
}
elseif ($action == 'noBackCover') {
	$title = 'No back cover';
	$imageTitle = false;
	$query = mysqli_query($db, 'SELECT album.artist_alphabetic, album.album, album.album_id
		FROM album, bitmap
		WHERE image_back = ""
		AND album.album_id = bitmap.album_id 
		ORDER BY album.artist_alphabetic, album.album');
}
elseif 	($action == 'duplicateContent')			duplicateContent();
elseif 	($action == 'duplicateName')			duplicateName();
elseif 	($action == 'duplicateFileName')		duplicateFileName();
elseif 	($action == 'fileError')				fileError();
elseif 	($action == 'deleteFile')				deleteFile();
elseif	($action == '')							mediaStatistics();
else											message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');

authenticate('access_statistics');

// Navigator
$nav			= array();
$nav['name'][]	= 'Configuration';
$nav['url'][]	= 'config.php';
$nav['name'][]	= 'Media statistics';
$nav['url'][]	= 'statistics.php';
$nav['name'][]	= $title;
require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Artist</td>
	<td class="textspace"></td>
	<td>Album</td>
	<td class="space"></td>
</tr>
<?php
$i = 0;
while ($album = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><a href="index.php?action=view2&amp;artist=<?php echo rawurlencode($album['artist_alphabetic']); ?>"><?php echo html($album['artist_alphabetic']); ?></a></td>
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>" <?php echo ($imageTitle) ? imageTitle($album['image_id']) : ''; ?>><?php echo html($album['album']); ?></a></td>
	<td></td>
</tr>
<?php
} ?>
</table>
<?php
require_once('include/footer.inc.php');




//  +------------------------------------------------------------------------+
//  | Media statistics                                                       |
//  +------------------------------------------------------------------------+
function mediaStatistics() {
	global $cfg, $db;
	authenticate('access_statistics');
	
	$query = mysqli_query($db, 'SELECT artist FROM album GROUP BY artist');
	$artists = mysqli_affected_rows($db);
	
	$query = mysqli_query($db, 'SELECT COUNT(discs) AS albums, SUM(discs) AS discs FROM album');
	$album = mysqli_fetch_assoc($query);
		
	$query = mysqli_query($db, 'SELECT COUNT(relative_file) AS all_tracks,
		SUM(miliseconds) DIV 1000 AS sum_seconds,
		SUM(filesize) AS sum_size
		FROM track');
	$track = mysqli_fetch_assoc($query);
	$total_seconds = $track['sum_seconds'];
	
	$query = mysqli_query($db, 'SELECT
		SUM(filesize) AS sum_size
		FROM cache');
	$cache = mysqli_fetch_assoc($query);
	
	$database_size = 0;
	$query = mysqli_query($db, 'SHOW TABLE STATUS');
	while ($database = mysqli_fetch_assoc($query))
		$database_size += $database['Data_length'] + $database['Index_length'];
		
	$query = mysqli_query($db, 'SELECT artist, title, COUNT(artist) AS n1, COUNT(title) AS n2
		FROM track
		GROUP BY artist, title
		HAVING n1 > 1 AND n2 > 1');
	$duplicate_name = mysqli_affected_rows($db);
	
	$query = mysqli_query($db, 'SELECT SUBSTRING_INDEX( track_id, "_", -1 ) AS hash, filesize, COUNT( SUBSTRING_INDEX( track_id, "_", -1 ) ) AS n1, COUNT( filesize ) AS n2
	FROM track
	GROUP BY filesize, hash
	HAVING n1 > 1 AND n2 > 1');
	$duplicate_content = mysqli_affected_rows($db);
		
	$media_total_space = disk_total_space($cfg['media_dir']);
	$media_free_space = disk_free_space($cfg['media_dir']);
	$media_used_space = $media_total_space - $media_free_space;
		
	$cache_total_space = disk_total_space(NJB_HOME_DIR . 'cache/');
	$cache_free_space = disk_free_space(NJB_HOME_DIR . 'cache/');
	$cache_used_space = $cache_total_space - $cache_free_space;
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Media statistics';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Quantity:</td>
	<td class="textspace"></td>
	<td></td>
	<td class="textspace"></td>
	<td></td>
	<td class="space"></td>
</tr>
<tr class="odd mouseover">
	<td></td>
	<td>Number of album artists:</td>
	<td></td>
	<td class="text-align-right"><?php echo $artists; ?></td>
	<td colspan="3"></td>
</tr>
<tr class="even mouseover">
	<td></td>
	<td>Number of albums:</td>
	<td></td>
	<td class="text-align-right"><?php echo $album['albums']; ?></td>
	<td colspan="3"></td>
</tr>
<tr class="odd mouseover">
	<td></td>
	<td>Number of discs:</td>
	<td></td>
	<td class="text-align-right"><?php echo $album['discs']; ?></td>
	<td colspan="3"></td>
</tr>
<tr class="even mouseover">
	<td></td>	
	<td>Number of tracks:</td>
	<td></td>
	<td class="text-align-right"><?php echo $track['all_tracks']; ?></td>
	<td colspan="3"></td>
</tr>
<tr class="section">
	<td></td>
	<td>Filesize:</td>
	<td colspan="5"></td>
</tr>
<tr class="odd mouseover">
	<td></td>
	<td>Media:</td>
	<td></td>
	<td class="text-align-right"><?php echo formattedSize($track['sum_size']); ?></td>
	<td></td>
	<td><div title="<?php echo number_format($media_used_space / $media_total_space * 100, 1) . html('%<br>') . formattedSize($media_used_space) . ' / ' . formattedSize($media_total_space); ?>" class="bar"><div style="width: <?php echo round($media_used_space / $media_total_space * 100); ?>%;"></div></div></td>
	<td></td>
</tr>
<tr class="even mouseover">
	<td></td>
	<td>Cache:</td>
	<td></td>
	<td class="text-align-right"><?php echo formattedSize($cache['sum_size']); ?></td>
	<td></td>
	<td><div title="<?php echo number_format($cache_used_space / $cache_total_space * 100, 1) . html('%<br>') . formattedSize($cache_used_space) . ' / ' . formattedSize($cache_total_space); ?>" class="bar"><div style="width: <?php echo round($cache_used_space / $cache_total_space * 100); ?>%;"></div></div></td>	
	<td></td>
</tr>
<tr class="odd mouseover">
	<td></td>
	<td>Database:</td>
	<td></td>
	<td class="text-align-right"><?php echo formattedSize($database_size); ?></td>
	<td colspan="3"></td>
</tr>
<?php
	if (is_dir($cfg['external_storage'])) {
		$external_storage_total_space = disk_total_space($cfg['external_storage']);
		$external_storage_free_space = disk_free_space($cfg['external_storage']);
		$external_storage_used_space = $external_storage_total_space - $external_storage_free_space; ?>
	<tr class="even mouseover">
		<td></td>
		<td>External storage:</td>
		<td></td>
		<td class="text-align-right"><?php echo formattedSize($external_storage_used_space); ?></td>
		<td></td>
		<td><div title="<?php echo number_format($external_storage_used_space / $external_storage_total_space * 100, 1) . html('%<br>') . formattedSize($external_storage_used_space) . ' / ' . formattedSize($external_storage_total_space); ?>" class="bar"><div style="width: <?php echo round($external_storage_used_space / $external_storage_total_space * 100); ?>%;"></div></div></td>
		<td></td>
	</tr>
<?php
	}
?>
<tr class="section">
	<td></td>
	<td>Playtime:</td>
	<td colspan="5"></td>
</tr>
<?php
	$i = 0;
	$query = mysqli_query($db, 'SELECT audio_dataformat FROM track WHERE audio_dataformat != "" AND video_dataformat = "" GROUP BY audio_dataformat ORDER BY audio_dataformat');
	while($track = mysqli_fetch_assoc($query)) {
		$audio_dataformat = $track['audio_dataformat'];
		$track = mysqli_fetch_assoc(mysqli_query($db, 'SELECT SUM(miliseconds) DIV 1000 AS sum_seconds FROM track WHERE audio_dataformat = "' . mysqli_real_escape_string($db, $audio_dataformat) . '" AND video_dataformat = ""')); ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover nowrap">
	<td></td>
	<td><a href="statistics.php?audio_dataformat=<?php echo $audio_dataformat; ?>">Playtime <?php echo $audio_dataformat;?>:</a></td>
	<td></td>
	<td class="text-align-right" title="<?php echo formattedDays($track['sum_seconds']); ?>"><?php echo formattedTime($track['sum_seconds'], false); ?></td>
	<td></td>
	<td><a href="statistics.php?audio_dataformat=<?php echo $audio_dataformat; ?>" title="<?php echo number_format($track['sum_seconds'] / $total_seconds * 100, 1); ?> %" class="bar"><div style="width: <?php echo round($track['sum_seconds'] / $total_seconds * 100); ?>%;"></div></a></td>
	<td></td>
</tr>
<?php
	}
	$query = mysqli_query($db, 'SELECT video_dataformat FROM track WHERE video_dataformat != "" GROUP BY video_dataformat ORDER BY video_dataformat');
	while($track = mysqli_fetch_assoc($query)) {
		$video_dataformat = $track['video_dataformat'];
		$track = mysqli_fetch_assoc(mysqli_query($db, 'SELECT SUM(miliseconds) DIV 1000 AS sum_seconds FROM track WHERE video_dataformat = "' . mysqli_real_escape_string($db, $video_dataformat) . '"')); ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover nowrap">
	<td></td>
	<td><a href="statistics.php?video_dataformat=<?php echo $video_dataformat; ?>">Playtime <?php echo $video_dataformat;?>:</a></td>
	<td></td>
	<td class="text-align-right" title="<?php echo formattedDays($track['sum_seconds']); ?>"><?php echo formattedTime($track['sum_seconds'], false);?></td>
	<td></td>
	<td><a href="statistics.php?video_dataformat=<?php echo $video_dataformat; ?>" title="<?php echo number_format($track['sum_seconds'] / $total_seconds * 100, 1); ?> %" class="bar"><div style="width: <?php echo round($track['sum_seconds'] / $total_seconds * 100); ?>%;"></div></a></td>
	<td></td>
</tr>
<?php
	}
?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover nowrap">
	<td></td>	
	<td><a href="statistics.php?action=all">Total playtime:</a></td>
	<td></td>
	<td class="text-align-right" title="<?php echo formattedDays($total_seconds); ?>"><?php echo formattedTime($total_seconds, false); ?></td>
	<td colspan="3"></td>
</tr>
<?php 
	if ($cfg['access_admin']) { ?>
<tr class="section">
	<td></td>
	<td>Duplicate:</td>
	<td colspan="5"></td>
</tr>
<tr class="odd mouseover">
	<td></td>
	<td><a href="statistics.php?action=duplicateContent">Content:</a></td>
	<td></td>
	<td class="text-align-right"><?php echo (int) $duplicate_content; ?></td>
	<td colspan="3"></td>
</tr>
<tr class="even mouseover">
	<td></td>
	<td><a href="statistics.php?action=duplicateName">Name:</a></td>
	<td></td>
	<td class="text-align-right"><?php echo (int) $duplicate_name; ?></td>
	<td colspan="3"></td>
</tr>
<?php
	}
	$i = 0;
	$no_image			= mysqli_num_rows(mysqli_query($db, 'SELECT album_id FROM bitmap WHERE image_front = ""'));
	$no_front_cover		= mysqli_num_rows(mysqli_query($db, 'SELECT album_id FROM bitmap WHERE image_front_width * image_front_height < ' . $cfg['image_front_cover_treshold']));
	$no_back_cover		= mysqli_num_rows(mysqli_query($db, 'SELECT album_id FROM bitmap WHERE image_back = ""'));
	if ($cfg['access_admin'] && ($no_image > 0 || $no_front_cover > 0 || $no_back_cover > 0)) { ?>
<tr class="section">
	<td></td>
	<td>No image:</td>
	<td colspan="5"></td>
</tr>
<?php
		if ($no_image > 0) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><a href="statistics.php?action=noImage" title="<?php echo $cfg['image_front']; ?>">Image</a></td>
	<td></td>
	<td class="text-align-right"><?php echo $no_image; ?></td>
	<td colspan="3"></td>
</tr>
<?php
		}
		if ($no_front_cover > 0) {
			$pixel = round(sqrt($cfg['image_front_cover_treshold']));  ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><a href="statistics.php?action=noFrontCover" title="<?php echo $cfg['image_front'] . html(' >= ') . $pixel . 'x' . $pixel; ?> px">Front cover:</a></td>
	<td></td>
	<td class="text-align-right"><?php echo $no_front_cover; ?></td>
	<td colspan="3"></td>
</tr>
<?php
		}
		if ($no_back_cover > 0) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><a href="statistics.php?action=noBackCover" title="<?php echo $cfg['image_back']; ?>">Back cover:</a></td>
	<td></td>
	<td class="text-align-right"><?php echo $no_back_cover; ?></td>
	<td colspan="3"></td>
</tr>
<?php
		}
	}
	$error = mysqli_num_rows(mysqli_query($db, 'SELECT error FROM track WHERE error != ""'));
	if ($cfg['access_admin'] && $error > 0) { ?>
<tr class="section">
	<td></td>
	<td>File:</td>
	<td colspan="5"></td>
</tr>
<tr class="odd_error mouseover">
	<td></td>
	<td><a href="statistics.php?action=fileError">Error:</a></td>
	<td></td>
	<td class="text-align-right"><?php echo $error; ?></td>
	<td colspan="3"></td>
</tr>
<?php
	}
	if ($cfg['access_admin'] == false) { ?>
<tr class="footer">
	<td></td>
	<td colspan="5">Other rows are only visible with administrator rights.</td>
	<td></td>
</tr>
<?php
	} ?>
</table>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Duplicate content                                                      |
//  +------------------------------------------------------------------------+
function duplicateContent() {
	global $cfg, $db;
	authenticate('access_admin');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Media statistics';
	$nav['url'][]	= 'statistics.php';
	$nav['name'][]	= 'Duplicate content';
	require_once('include/header.inc.php');
?>
<table class="border">
<?php
	$i = 0;
	$query = mysqli_query($db, 'SELECT SUBSTRING_INDEX(track_id, "_", -1) AS hash, filesize, COUNT(SUBSTRING_INDEX(track_id, "_", -1)) AS n1, COUNT(filesize) AS n2
		FROM track
		GROUP BY filesize, hash
		HAVING n1 > 1 AND n2 > 1
		ORDER BY filesize');
	while ($track = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i > 1) ? 'section' : 'header'; ?>">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Relative file</td>
	<td class="textspace"></td>
	<td class="text-align-right">Filesize</td>
	<td<?php if ($cfg['delete_file']) echo' class="space"'; ?>></td>
	<td></td><!-- optional delete -->
	<td class="space"></td>
</tr>
<?php
	$hash = $track['hash'];
	$filesize = $track['filesize'];
	$i = 0;
	$query2 = mysqli_query($db, 'SELECT relative_file, miliseconds, track_id FROM track
		WHERE SUBSTRING_INDEX(track_id, "_", -1) = "' . mysqli_real_escape_string($db, $hash) . '"
		AND filesize = ' . (int) $filesize . '
		ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query2)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>';?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><?php echo html($track['relative_file']); ?></td>
	<td></td>
	<td class="text-align-right"><?php echo formattedSize($filesize); ?></td>
	<td></td>
	<td><?php if ($cfg['delete_file']) echo '<a href="statistics.php?action=deleteFile&amp;referer=statistics.php%3faction%3dduplicateContent&amp;relative_file=' . rawurlencode($track['relative_file']) . '&amp;sign=' . $cfg['sign'] . '" onclick="return confirm(\'Are you sure you want to delete: ' . addslashes(html($track['relative_file'])) . '?\');"><img src="' . $cfg['img'] . 'small_delete.png" alt="" class="small"></a>'; ?></td>
	<td></td>
</tr>
<?php
		}
	}
echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Duplicate name                                                         |
//  +------------------------------------------------------------------------+
function duplicateName() {
	global $cfg, $db;
	authenticate('access_admin');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Media statistics';
	$nav['url'][]	= 'statistics.php';
	$nav['name'][]	= 'Duplicate name';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Artist</td>
	<td class="textspace"></td>
	<td>Title</td>
	<td class="textspace"></td>
	<td>Count</td>
	<td class="space"></td>
</tr>
<?php
	$i=0;
	$query = mysqli_query($db, 'SELECT artist, title, COUNT(artist) AS n1, COUNT(title) AS n2
		FROM track
		GROUP BY artist, title
		HAVING n1 > 1
		AND n2 > 1
		ORDER BY artist, title');
	while ($track = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><a href="statistics.php?action=duplicateFileName&amp;artist=<?php echo rawurlencode($track['artist']); ?>&amp;title=<?php echo rawurlencode($track['title']); ?>"><?php echo html($track['artist']); ?></a></td>
	<td></td>
	<td><a href="statistics.php?action=duplicateFileName&amp;artist=<?php echo rawurlencode($track['artist']); ?>&amp;title=<?php echo rawurlencode($track['title']); ?>"><?php echo html($track['title']); ?></a></td>
	<td></td>
	<td class="text-align-right"><?php echo (int) $track['n1']; ?></td>
	<td></td>
</tr>
<?php
	}
echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Duplicate file name                                                    |
//  +------------------------------------------------------------------------+
function duplicateFileName() {
	global $cfg, $db;
	authenticate('access_admin');
	
	$artist	 		= @$_GET['artist'];
	$title			= @$_GET['title'];
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Media statistics';
	$nav['url'][]	= 'statistics.php';
	$nav['name'][]	= 'Duplicate name';
	$nav['url'][]	= 'statistics.php?action=duplicateName';
	$nav['name'][]	= $artist . ' - ' . $title;
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Relative file</td>
	<td class="textspace"></td>
	<td class="text-align-right">Filesize</td>
	<td<?php if ($cfg['delete_file']) echo' class="space"'; ?>></td>
	<td></td><!-- optional delete -->
	<td class="space"></td>
</tr>
<?php
	$i=0;
	$query = mysqli_query($db, 'SELECT relative_file, filesize, miliseconds, track_id FROM track
		WHERE artist	= "' . mysqli_real_escape_string($db, $artist) . '"
		AND title		= "' . mysqli_real_escape_string($db, $title) . '"
		ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>';?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><?php echo html($track['relative_file']); ?></td>
	<td></td>
	<td class="text-align-right"><?php echo formattedSize($track['filesize']); ?></td>
	<td></td>
	<td><?php if ($cfg['delete_file']) echo '<a href="statistics.php?action=deleteFile&amp;referer=statistics.php%3faction%3dduplicateName&amp;relative_file=' . rawurlencode($track['relative_file']) . '&amp;sign=' . $cfg['sign'] . '" onclick="return confirm(\'Are you sure you want to delete: ' . addslashes(html($track['relative_file'])) . '?\');"><img src="' . $cfg['img'] . 'small_delete.png" alt="" class="small"></a>'; ?></td>
	<td></td>
</tr>
<?php
	}
echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | File error                                                             |
//  +------------------------------------------------------------------------+
function fileError() {
	global $cfg, $db;
	authenticate('access_admin');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Media statistics';
	$nav['url'][]	= 'statistics.php';
	$nav['name'][]	= 'File error';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Relative file</td>
	<td class="textspace"></td>
	<td>getID3() error message</td>
	<td class="textspace"></td>
	<td class="text-align-right">Filesize</td>
	<td<?php if ($cfg['delete_file']) echo' class="space"'; ?>></td>
	<td></td><!-- optional delete -->
	<td class="space"></td>
</tr>
<?php
	$i=0;
	$query = mysqli_query($db, 'SELECT relative_file, filesize, error, track_id FROM track WHERE error != "" ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd_error'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');"<img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>';?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><?php echo html($track['relative_file']); ?></td>
	<td></td>
	<td><?php echo html($track['error']); ?></td>
	<td></td>
	<td class="text-align-right"><?php echo formattedSize($track['filesize']); ?></td>
	<td></td>
	<td><?php if ($cfg['delete_file']) echo '<a href="statistics.php?action=deleteFile&amp;referer=statistics.php%3faction%3dfileError&amp;relative_file=' . rawurlencode($track['relative_file']) . '&amp;sign=' . $cfg['sign'] . '" onclick="return confirm(\'Are you sure you want to delete: ' . addslashes(html($track['relative_file'])) . '?\');"><img src="' . $cfg['img'] . 'small_delete.png" alt="" class="small"></a>'; ?></td>
	<td></td>
</tr>
<?php
	}
echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Delete file                                                            |
//  +------------------------------------------------------------------------+
function deleteFile() {
	global $cfg, $db;
	authenticate('access_admin', false, true, true);
	
	if ($cfg['delete_file'] == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]Delete file disabled');
	
	$referer 		= @$_GET['referer'];
	$relative_file	= @$_GET['relative_file'];
	$file			= $cfg['media_dir'] . $relative_file;
	
	$query = mysqli_query($db, 'SELECT relative_file
		FROM track
		WHERE relative_file	= BINARY "' . mysqli_real_escape_string($db, $relative_file) . '"');
	$track = mysqli_fetch_assoc($query);
	
	if ($track == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]relative_file not found in database');
		
	if (is_file($file) && @unlink($file) == false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to delete file:[/b][br]' . $file);
	
	mysqli_query($db, 'DELETE FROM track 
		WHERE relative_file	= BINARY "' . mysqli_real_escape_string($db, $relative_file) . '"');
	
	cacheCleanup();
	
	header('Location: ' . NJB_HOME_URL . $referer);
	exit();
}
