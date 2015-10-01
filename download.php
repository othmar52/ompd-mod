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
//  | download.php                                                           |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
require_once('include/stream.inc.php');
require_once('include/cache.inc.php');

$action		= @$_GET['action'];
$track_id	= @$_GET['track_id'];
$album_id	= @$_GET['album_id'];

if		($action == 'downloadTrack')		downloadTrack($track_id);
elseif	($action == 'downloadAlbum')		downloadAlbum($album_id);
elseif	($action == 'transcodeJson')		transcodeJson($track_id);
elseif	($action == 'zipJson')				zipJson($album_id);
elseif	($action == 'cacheValidate')		cacheValidate();
elseif	($action == 'cacheValidateJson')	cacheValidateJson();
elseif	($action == 'batchTranscode')		batchTranscode();
elseif	($action == 'batchTranscodeInit')	batchTranscodeInit();
elseif	($action == 'batchTranscodeJson')	batchTranscodeJson();
elseif	($action == 'shareAlbum')			shareAlbum($album_id);
elseif	($action == 'copyAlbum')			copyAlbum($album_id);
elseif	($action == 'copyJson')				copyJson($track_id);

else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Download track                                                         |
//  +------------------------------------------------------------------------+
function downloadTrack($track_id) { 
	global $cfg, $db;
	authenticateStream(true);
	
	$download_id 	= (int) @$_GET['download_id'];
	$short_sid		= @$_GET['short_sid'];
	$hash			= @$_GET['hash'];
	
	if ($download_id != -1 && isset($cfg['encode_extension'][$download_id]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]download_id');
	
	$query = mysqli_query($db, 'SELECT
		LOWER(SUBSTRING_INDEX(relative_file, ".", -1)) AS extension,
		relative_file,
		mime_type,
		miliseconds,
		filesize,
		audio_bitrate
		FROM track
		WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	$track = mysqli_fetch_assoc($query);
	
	if (sourceFile($track['extension'], $track['audio_bitrate'], $download_id))	{
		// Download source file
		$file = $cfg['media_dir'] . $track['relative_file'];
		
		$pathinfo	= pathinfo($file);
		$filename	= $pathinfo['basename'];
		$filename	= downloadFilename($filename);
		
		streamFile($file, $track['mime_type'], 'attachment', $filename);
		return true;
	}
	elseif ($file = cacheGetFile($track_id, $download_id)) {
		// Download from cache
		$pathinfo	= pathinfo($track['relative_file']);
		$filename	= $pathinfo['filename'] . '.' . $cfg['encode_extension'][$download_id];
		$filename	= downloadFilename($filename);
		
		cacheUpdateTag($track_id, $download_id, $file);
		streamFile($file, $cfg['encode_mime_type'][$download_id], 'attachment', $filename);
		return true;
	}
	
	$query = mysqli_query($db, 'SELECT album.artist_alphabetic, album.album, album.album_id
		FROM album, track
		WHERE track.album_id = album.album_id
		AND track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	$album = mysqli_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]track_id not found in database');
		
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $album['artist_alphabetic'];
	$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
	$nav['name'][]	= $album['album'];
	$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . $album['album_id'];
	$nav['name'][]	= 'Download track';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Transcode to <?php echo html($cfg['encode_name'][$download_id]); ?></td>
	<td class="textspace"></td>
	<td></td>
	<td class="textspace"></td>
	<td></td>
	<td class="space"></td>
</tr>
<?php
	$query = mysqli_query($db, 'SELECT title, artist FROM track WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	$track = mysqli_fetch_assoc($query) ?>
<tr class="odd">
	<td></td>
	<td><?php echo html($track['artist']); ?></td>
	<td></td>
	<td><?php echo html($track['title']); ?></td>
	<td></td>
	<td><span id="status"><img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small"></span></td>
	<td></td>
</tr>
<tr class="section">
	<td></td>
	<td colspan="5">Download</td>
	<td></td>
</tr>
<tr class="odd">
	<td></td>
	<td colspan="5"><span id="text">Prepare file</span></td>
	<td></td>
</tr>
</table>
<?php
	$download_url = NJB_HOME_URL . 'download.php?action=downloadTrack';
	$download_url .= '&amp;download_id=' . $download_id . '&amp;track_id=' . rawurlencode($track_id) . '&amp;short_sid=' . rawurlencode($short_sid) . '&amp;hash=' . rawurlencode($hash);
	$download_url .= '&amp;timestamp=' . dechex(time());
?>
<script type="text/javascript">
	ajaxRequest('download.php?action=transcodeJson&track_id=<?php echo $track_id; ?>&download_id=<?php echo $download_id; ?>',download);

	function download() {
		document.getElementById('status').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
		document.getElementById('text').innerHTML='<a href="<?php echo $download_url; ?>"><img src="<?php echo $cfg['img']; ?>small_download.png" alt="" class="small space">Download <?php echo $cfg['encode_extension'][$download_id]; ?> file</a>';
		window.location.assign('<?php echo str_replace('&amp;', '&', $download_url); ?>');
	}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Download album                                                         |
//  +------------------------------------------------------------------------+
function downloadAlbum($album_id) { 
	global $cfg, $db;
	
	$sid			= @$_GET['sid'];
	$short_sid		= @$_GET['short_sid'];
	$download_id	= (int) @$_GET['download_id'];
	$expire_time	= (int) @$_GET['expire_time'];
	$hash			= @$_GET['hash'];
	
	if ($hash) {
		// Common download
		if (cacheGetFile($album_id, $download_id))		authenticateStream(true);
		else											authenticateStream();
		
		if ($cfg['album_download'] == false)
			message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]Download album disabled');	
		
		$download_url = NJB_HOME_URL . 'download.php?action=downloadAlbum';
		$download_url .= '&amp;download_id=' . $download_id . '&amp;album_id=' . rawurlencode($album_id) . '&amp;short_sid=' . rawurlencode($short_sid) . '&amp;hash=' . rawurlencode($hash);
		$download_url .= '&amp;timestamp=' . dechex(time());
		
		$zip_url = 'download.php?action=zipJson&album_id=' . rawurlencode($album_id) . '&download_id=' . $download_id;
	}
	else {
		// Download shared file
		mysqli_query($db, 'UPDATE share_download SET
			ip			= "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '"
			WHERE sid	= BINARY "' . mysqli_real_escape_string($db, $sid) . '"
			AND ip		= ""');
		
		$query = mysqli_query($db, 'SELECT album_id, download_id
			FROM share_download
			WHERE sid = BINARY "' . mysqli_real_escape_string($db, $sid) . '"
			AND ip = "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '"
			AND expire_time > ' . (int) time());
		$share_download = mysqli_fetch_assoc($query);
		
		if ($share_download == false || $cfg['album_share_download'] == false)
			message(__FILE__, __LINE__, 'error', '[b]Download error[/b][br]Authentication failed or share download is disabled');
		
		$album_id 		= $share_download['album_id'];
		$download_id	= $share_download['download_id'];
		
		if (cacheGetFile($album_id, $download_id))		authenticate('access_always', true);
		else											authenticate('access_always');
		
		$download_url = NJB_HOME_URL . 'download.php?action=downloadAlbum';
		$download_url .= '&amp;sid=' . rawurlencode($sid);
		$download_url .= '&amp;timestamp=' . dechex(time());
		
		$zip_url = 'download.php?action=zipJson&album_id=' . rawurlencode($album_id) . '&download_id=' . $download_id . '&sid=' . rawurlencode($sid);
	}
	
	if ($download_id != -1 && isset($cfg['encode_extension'][$download_id]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]download_id');
	
	
	$query = mysqli_query($db, 'SELECT track_id
		FROM track
		WHERE album_id	= "' . mysqli_real_escape_string($db, $album_id) . '"');
	

	if ($file = cacheGetFile($album_id, $download_id)) {
		// Download from cache
		$query		= mysqli_query($db, 'SELECT artist_alphabetic, album, year FROM album WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
		$album		= mysqli_fetch_assoc($query);
		
		$filename	= $album['artist_alphabetic'] . ' - ';
		$filename	.= ($album['year']) ? $album['year'] . ' - ' : '';
		$filename	.= $album['album'] . '.' . $cfg['download_album_extension'];
		$filename	= downloadFilename($filename);
		
		streamFile($file, $cfg['download_album_mime_type'], 'attachment', $filename);
		
		if ($hash)		updateCounter($album_id, NJB_COUNTER_DOWNLOAD);
		else			mysqli_query($db, 'DELETE FROM share_download WHERE sid = BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
		
		exit();
	}
		
	$query = mysqli_query($db, 'SELECT artist_alphabetic, album FROM album WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $album['artist_alphabetic'];
	$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
	$nav['name'][]	= $album['album'];
	$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . $album_id;
	$nav['name'][]	= 'Download album';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td><?php echo ($download_id == -1 ) ? 'Source' : 'Transcode to ' . html($cfg['encode_name'][$download_id]); ?></td>
	<td class="textspace"></td>
	<td></td>
	<td class="textspace"></td>
	<td></td>
	<td class="space"></td>
</tr>
<?php
	$i = 0;
	$query = mysqli_query($db, 'SELECT title, artist, track_id FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '" ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query))	{
		$cache = (cacheGetFile($track['track_id'], $download_id)) ? true : false; ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td><?php echo html($track['artist']); ?></td>
	<td></td>
	<td><?php echo html($track['title']); ?></td>
	<td></td>
	<td><span class="<?php echo ($cache) ? '' : 'track_id'; ?>" id="<?php echo $track['track_id']; ?>"><?php echo ($cache) ? '<img src="' . $cfg['img'] . 'small_check.png" alt="" class="small">' : ''; ?></span></td>
	<td></td>
</tr>
<?php
	} ?>
<tr class="section">
	<td></td>
	<td colspan="5">Download</td>
	<td></td>
</tr>
<tr class="odd">
	<td></td>
	<td colspan="3"><span id="text">Create <?php echo $cfg['download_album_extension']; ?> file</span></td>
	<td></td>
	<td><span id="icon"></span></td>
	<td></td>
</tr>
</table>
<script type="text/javascript">
	var track = document.getElementsByClassName('track_id');
	if (track.length > 0) {
		// transcode
		document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
		ajaxRequest('download.php?action=transcodeJson&track_id=' + track[0].id + '&download_id=<?php echo $download_id . (($sid) ? '&sid=' . rawurlencode($sid) : ''); ?>',next);
	}
	else {
		// zip
		document.getElementById('icon').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
		ajaxRequest('<?php echo $zip_url; ?>',download);
	}
	
	
	function next() {
		var track = document.getElementsByClassName('track_id');
		document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
		document.getElementById(track[0].id).className='';
		if (track.length > 0) {
			// transcode
			document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
			ajaxRequest('download.php?action=transcodeJson&track_id=' + track[0].id + '&download_id=<?php echo $download_id . (($sid) ? '&sid=' . rawurlencode($sid) : ''); ?>',next);
		}
		else {
			// zip
			document.getElementById('icon').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
			ajaxRequest('<?php echo $zip_url; ?>',download);
		}
	}
	
	
	function download() {
		document.getElementById('icon').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
		document.getElementById('text').innerHTML='<a href="<?php echo $download_url; ?>"><img src="<?php echo $cfg['img']; ?>small_download.png" alt="" class="small space">Download <?php echo $cfg['download_album_extension']; ?> file</a>';
		window.location.assign('<?php echo str_replace('&amp;', '&', $download_url); ?>');
	}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Transcode JSON                                                         |
//  +------------------------------------------------------------------------+
function transcodeJson($track_id) { 
	global $cfg, $db;
	$download_id		= (int) @$_GET['download_id'];
	$album_id			= substr($track_id, 0, strpos($track_id, '_'));
	
	if (@$_GET['sid'] != '')	authenticateShareDownload($album_id);
	else						authenticate('access_download');
	
	ini_set('max_execution_time', 0);
	
	if ($download_id != -1 && isset($cfg['encode_extension'][$download_id]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]download_id');
	
	transcode($track_id, $download_id);
	
	echo '""';
}




//  +------------------------------------------------------------------------+
//  | Zip JSON                                                               |
//  +------------------------------------------------------------------------+
function zipJson($album_id) {
	global $cfg, $db;
	$download_id		= (int) @$_GET['download_id'];
	
	if (@$_GET['sid'] != '')	authenticateShareDownload($album_id);
	else						authenticate('access_download');
	
	ini_set('max_execution_time', 0);
	
	if ($download_id != -1 && isset($cfg['encode_extension'][$download_id]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]download_id');
	
	$cache_dir = cacheGetDir($album_id, $download_id);
	$list = $cache_dir . $album_id . '.txt';
	
	$hash_data = '';
	$list_content = '';
	$query = mysqli_query($db, 'SELECT track_id, relative_file FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '" ORDER BY relative_file');
	while($track = mysqli_fetch_assoc($query)) {
		$source 			= transcode($track['track_id'], $download_id);
		$pathinfo			= pathinfo($source);
		$source_name		= $pathinfo['filename'];
		
		$pathinfo			= pathinfo($track['relative_file']);
		$destination_name	= $pathinfo['filename'];
		$destination_name	= downloadFilename($destination_name, true, true);
		
		if ($source_name == $destination_name) {
			$destination = $source;
		}
		else {	
			$destination = $cache_dir . $destination_name . '.' . $cfg['encode_extension'][$download_id];
			@copy($source, $destination) or message(__FILE__, __LINE__, 'error', '[b]Failed to copy file[/b][br]From: ' . $source . '[br]To: ' . $destination);
		}
		
		$pathinfo	= pathinfo($destination);
		$hash_data	.= $pathinfo['filename'];
		
		$list_content .= iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $destination) . "\n";
	}
	
	if (file_put_contents($list, $list_content) === false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to write file:[/b][br]' . $list);
	
	$hash = md5($hash_data);
	$destination = $cache_dir . $album_id . '_' . $hash . '.' . $cfg['download_album_extension'];
	
	// Download album
	if (NJB_WINDOWS)	$cmd = $cfg['download_album_cmd'];
	else				$cmd = $cfg['download_album_cmd'] . ' 2>&1';
	
	$cmd = str_replace('%list', escapeCmdArg($list), $cmd);
	$cmd = str_replace('%destination', escapeCmdArg($destination), $cmd);
	
	if (isset($cfg['download_album_env_lang']) && $cfg['download_album_env_lang'] != '')
		putenv('LANG=' . $cfg['download_album_env_lang']);
	
	$cmd_output	= array();
	$cmd_return_var	= 0;
	@exec($cmd, $cmd_output, $cmd_return_var);
	
	if ($cmd_return_var != 0)
		message(__FILE__, __LINE__, 'error', '[b]Exec error[/b][br][b]Command:[/b] ' . $cmd . '[br][b]System output:[/b] ' . implode('[br]', $cmd_output));
	
	if (is_file($destination) == false)
		message(__FILE__, __LINE__, 'error', '[b]Destination file not created[/b][br]File: ' . $destination . '[br]Command: ' . $cmd);
	
	cacheUpdateFile($album_id, $download_id, $destination, '', $hash);
		
	// Cleanup
	cacheRecursiveValidate($cache_dir);
	cacheCleanup();
	
	echo '""';
}




//  +------------------------------------------------------------------------+
//  | Authenticate share download                                            |
//  +------------------------------------------------------------------------+
function authenticateShareDownload($album_id) {
	global $cfg, $db;
	header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	
	$sid			= @$_GET['sid'];
	$download_id	= @$_GET['download_id'];
	
	$query = mysqli_query($db, 'SELECT ip, album_id, download_id, expire_time FROM share_download
		WHERE sid = BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
	$share_download = mysqli_fetch_assoc($query);
	
	if ($share_download['ip']			== $_SERVER['REMOTE_ADDR'] &&
		$share_download['album_id']		== $album_id &&
		$share_download['download_id']	== $download_id &&
		$share_download['expire_time']	> time())
		return true;

	exit();
}




//  +------------------------------------------------------------------------+
//  | Cache validate                                                         |
//  +------------------------------------------------------------------------+
function cacheValidate() { 
	global $cfg, $db;
	$cfg['menu'] = 'config';
	authenticate('access_admin', false, true, true);
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Batch transcode';
	$nav['url'][]	= 'config.php?action=batchTranscode';
	$nav['name'][]	= 'Validate cache';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Progress</td>
	<td class="textspace"></td>
	<td></td>
	<td class="space"></td>
</tr>
<tr class="even">
	<td></td>
	<td>Validate cache</td>
	<td></td>
	<td><span id="validate"></span></td>
	<td></td>
</tr>
</table>
<script type="text/javascript">;
	document.getElementById('validate').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
	ajaxRequest('download.php?action=cacheValidateJson&sign=<?php echo $cfg['sign']; ?>',ready);
	
	function ready() {
		document.getElementById('validate').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
	}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Cache validate JSON                                                    |
//  +------------------------------------------------------------------------+
function cacheValidateJson() {
	global $cfg, $db;
	authenticate('access_admin', false, true, true);
	ini_set('max_execution_time', 0);
	
	cacheCreateRoot();
	cacheCleanup();
	
	mysqli_query($db, 'UPDATE cache SET updated = 0');
	cacheRecursiveValidate(NJB_HOME_DIR . 'cache/');
	mysqli_query($db, 'DELETE FROM cache WHERE NOT updated');
	
	echo '""';
}




//  +------------------------------------------------------------------------+
//  | Batch transcode                                                        |
//  +------------------------------------------------------------------------+
function batchTranscode() { 
	global $cfg, $db;
	authenticate('access_admin');
	
	$cfg['menu']	= 'config';
	$profile		= (int) @$_GET['profile'];
	
	if (isset($cfg['encode_name'][$profile]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]profile');
		
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Batch transcode';
	$nav['url'][]	= 'config.php?action=batchTranscode';
	$nav['name'][]	= $cfg['encode_name'][$profile];
	require_once('include/header.inc.php');
?>
<table class="border">
	<tr class="header">
		<td class="space"></td>
		<td>Progress</td>
		<td class="textspace"></td>
		<td></td>
		<td class="space"></td>
	</tr>
	<tr class="odd">
		<td></td>
		<td>Initialize:</td>
		<td></td>
		<td><span id="initialize"></span></td>
		<td></td>
	</tr>
	<tr class="even">
		<td></td>
		<td>Transcode:</td>
		<td></td>
		<td><span id="transcode"></span></td>
		<td></td>
	</tr>
	<tr class="odd">
		<td></td>
		<td>Tracks to transcode:</td>
		<td></td>
		<td><span id="to_do"></span></td>
		<td></td>
	</tr>
	<tr class="even">
		<td></td>
		<td>Elapsed time:</td>
		<td></td>
		<td><span id="elapsed_time"></span></td>
		<td></td>
	</tr>
	<tr class="odd">
		<td></td>
		<td>Estimated time to complete:</td>
		<td></td>
		<td><span id="etc"></span></td>
		<td></td>
	</tr>
</table>
<script type="text/javascript">
	document.getElementById('initialize').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
	ajaxRequest('download.php?action=batchTranscodeInit&profile=<?php echo $profile; ?>',start);
	
	function start(to_do) {
		document.getElementById('initialize').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
		document.getElementById('transcode').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
		document.getElementById('to_do').innerHTML=to_do;
		ajaxRequest('download.php?action=batchTranscodeJson&profile=<?php echo $profile; ?>',transcode);
	}

	
	function transcode(data){
		document.getElementById('elapsed_time').innerHTML=data.elapsed_time;
		document.getElementById('etc').innerHTML=data.etc;
		document.getElementById('to_do').innerHTML=data.to_do;
		
		if (data.to_do > 0)	ajaxRequest('download.php?action=batchTranscodeJson&profile=<?php echo $profile; ?>',transcode);
		else				document.getElementById('transcode').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
	}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Batch transcode initialize                                             |
//  +------------------------------------------------------------------------+
function batchTranscodeInit() {
	global $cfg, $db;
	authenticate('access_admin');
	ini_set('max_execution_time', 0);
	
	$profile = (int) @$_GET['profile'];
	
	if (isset($cfg['encode_name'][$profile]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]profile');
	
	cacheCleanup();
	mysqli_query($db, 'UPDATE track SET transcoded = 0');
	mysqli_query($db, 'UPDATE track SET transcoded = 9 WHERE EXISTS (SELECT * FROM cache WHERE track_id = id AND cache.profile = ' . (int) $profile . ')');
	$query = mysqli_query($db, 'SELECT track_id, audio_bitrate,
		LOWER(SUBSTRING_INDEX(track.relative_file, ".", -1)) AS extension
		FROM track
		WHERE transcoded = 0');
	while ($track = mysqli_fetch_assoc($query)) {
		if (sourceFile($track['extension'], $track['audio_bitrate'], $profile)) {
			// File will be downloaded from source
			mysqli_query($db, 'UPDATE track SET transcoded = 9
				WHERE track_id = "' . mysqli_real_escape_string($db, $track['track_id']) . '"');
		}
	}
	$query = mysqli_query($db, 'SELECT COUNT(*) AS to_do FROM track WHERE transcoded = 0');
	$track = mysqli_fetch_assoc($query);
	
	mysqli_query($db, 'UPDATE server SET value = "' . time() . '" WHERE name = "batch_transcode_start_time" LIMIT 1');
	
	echo safe_json_encode($track['to_do']);
}




//  +------------------------------------------------------------------------+
//  | Batch transcode JSON                                                   |
//  +------------------------------------------------------------------------+
function batchTranscodeJson() { 
	global $cfg, $db;
	authenticate('access_admin');
	ini_set('max_execution_time', 0);
	
	$profile = (int) @$_GET['profile'];
	
	if (isset($cfg['encode_name'][$profile]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]profile');
	
	$query = mysqli_query($db, 'SELECT track_id FROM track WHERE transcoded = 0 ORDER BY album_id DESC, relative_file');
	$track = mysqli_fetch_assoc($query);
	$file = transcode($track['track_id'], $profile);
	mysqli_query($db, 'UPDATE track SET transcoded = 1 WHERE track_id = "' . mysqli_real_escape_string($db, $track['track_id']) . '"');	

	$query = mysqli_query($db, 'SELECT SUM(miliseconds) DIV 1000 AS unprocessed_playtime FROM track WHERE transcoded = 0');
	$track = mysqli_fetch_assoc($query);
	$unprocessed_playtime = $track['unprocessed_playtime'];
	
	$query = mysqli_query($db, 'SELECT SUM(miliseconds) DIV 1000 AS transcoded_playtime FROM track WHERE transcoded = 1');
	$track = mysqli_fetch_assoc($query);
	$transcoded_playtime = $track['transcoded_playtime'];
	
	$elapsed_time = time() - $cfg['batch_transcode_start_time'];
	
	$data = array();
	$query = mysqli_query($db, 'SELECT COUNT(*) AS to_do FROM track WHERE transcoded = 0');
	$track = mysqli_fetch_assoc($query);
	$data['to_do']			= (int) $track['to_do'];
	
	$data['elapsed_time']	= (string) formattedTime($elapsed_time, false); 
	$data['etc']			= (string) ($data['to_do'] && $transcoded_playtime) ? formattedTime($elapsed_time / $transcoded_playtime * $unprocessed_playtime, false) : formattedTime(0);

	echo safe_json_encode($data);
}




//  +------------------------------------------------------------------------+
//  | Transcode                                                              |
//  +------------------------------------------------------------------------+
function transcode($track_id, $profile) {
	global $cfg, $db;
	
	$query = mysqli_query($db, 'SELECT
		LOWER(SUBSTRING_INDEX(relative_file, ".", -1)) AS extension,
		relative_file, miliseconds, filesize, audio_bitrate
		FROM track
		WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	$track = mysqli_fetch_assoc($query);
	
	$source	= $cfg['media_dir'] . $track['relative_file'];
	
	if (sourceFile($track['extension'], $track['audio_bitrate'], $profile)) {
		// Return source file
		return $source;
	}
	elseif ($file = cacheGetFile($track_id, $profile)) {
		cacheUpdateTag($track_id, $profile, $file);
		return $file;
	}
	else {
		$cache_total_space = disk_total_space(NJB_HOME_DIR . 'cache/');
		$cache_free_space = disk_free_space(NJB_HOME_DIR . 'cache/');
		$cache_used_space = $cache_total_space - $cache_free_space;	
		
		if ($cache_used_space > $cache_total_space * .95)
			message(__FILE__, __LINE__, 'error', '[b]To less drive space[/b][br]The cache drive has reached 95% of the total capacity');
		
		// Return transcoded file
		$cache_dir		= cacheGetDir($track_id, $profile);
		$pathinfo		= pathinfo($track['relative_file']);
		$destination	= $pathinfo['filename'];
		$destination	= $cache_dir . $destination . '.' . $cfg['encode_extension'][$profile];
		
		// Transcode
		if (NJB_WINDOWS)						$cmd = $cfg['decode_stdout'][$track['extension']] . ' | ' . $cfg['encode_file'][$profile];
		elseif ($track['extension'] == 'mpc')	$cmd = $cfg['decode_stdout'][$track['extension']] . ' | ' . $cfg['encode_file'][$profile] . ' 2>&1'; // Linux musepack fix 
		else									$cmd = $cfg['decode_stdout'][$track['extension']] .' 2>&1 | ' . $cfg['encode_file'][$profile] . ' 2>&1';
		
		$cmd = str_replace('%source', escapeCmdArg($source), $cmd);
		$cmd = str_replace('%destination', escapeCmdArg($destination), $cmd);
		
		$cmd_output	= array();
		$cmd_return_var	= 0;
		@exec($cmd, $cmd_output, $cmd_return_var);

		if ($cmd_return_var != 0)
			message(__FILE__, __LINE__, 'error', '[b]Exec error[/b][br][b]Command:[/b] ' . $cmd . '[br][b]System output:[/b] ' . implode('[br]', $cmd_output));
						
		if (is_file($destination) == false)
			message(__FILE__, __LINE__, 'error', '[b]Destination file not created[/b][br]File: ' . $destination . '[br]Command: ' . $cmd);

			
		cacheUpdateTag($track_id, $profile, $destination);
			
		return $destination;
	}
}




//  +------------------------------------------------------------------------+
//  | Share album                                                            |
//  +------------------------------------------------------------------------+
function shareAlbum($album_id) {
	global $cfg, $db;
	authenticate('access_admin', false, true);
	
	if ($cfg['album_share_download'] == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]Share album disabled');
	
	$query = mysqli_query($db, 'SELECT artist_alphabetic, album, year
		FROM album
		WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $album['artist_alphabetic'];
	$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
	$nav['name'][]	= $album['album'];
	$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . $album_id;
	$nav['name'][]	= 'Share download';
	require_once('include/header.inc.php');
	
	$expire_time = time() + $cfg['share_download_lifetime'];
	$sid = randomSid();
	mysqli_query($db, 'INSERT INTO share_download (sid, album_id, download_id, expire_time) VALUES (
		"' . mysqli_real_escape_string($db, $sid) . '",
		"' . mysqli_real_escape_string($db, $album_id) . '",
		' . (int) $cfg['download_id'] . ',
		' . (int) $expire_time . ')');
	
	$url		= NJB_HOME_URL . 'download.php?action=downloadAlbum&amp;sid=' . $sid;
	
	$filename	= $album['artist_alphabetic'] . ' - ';
	$filename	.=  ($album['year']) ? $album['year'] . ' - ' : '';
	$filename	.= $album['album'] . '.' . $cfg['download_album_extension'];
	$filename 	= encodeEscapeChar($filename);
	
	$filesize	= 0;
	$transcode	= false;
	$exact		= true;
	$extensions	= array();
	$query = mysqli_query($db, 'SELECT track.filesize, cache.filesize AS cache_filesize,
		miliseconds, audio_bitrate, track_id,
		LOWER(SUBSTRING_INDEX(track.relative_file, ".", -1)) AS extension
		FROM track LEFT JOIN cache
		ON track.track_id = cache.id
		AND cache.profile = ' . (int) $cfg['download_id'] . '
		WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	
	while($track = mysqli_fetch_assoc($query)) {
		if (in_array($track['extension'], $extensions) == false) {
			$extensions[] = $track['extension'];
		}
		$transcode_track = false;
		if (sourceFile($track['extension'], $track['audio_bitrate'], $cfg['download_id']) == false) {
			$transcode_track	= true;
			$transcode			= true;
		}
		if ($track['cache_filesize']) {
			$filesize += $track['cache_filesize'];
		}
		elseif ($transcode_track) {
			$filesize += round($cfg['encode_bitrate'][$cfg['download_id']] * $track['miliseconds'] / 8 / 1000);
			$exact = false;
		}
		else {
			$filesize += $track['filesize'];
		}
	}
	
	sort($extensions);
	$source = implode($extensions, ', ');
	
	if ($exact)	$size = formattedSize($filesize);
	else		$size = html_entity_decode('&plusmn; ', null, NJB_DEFAULT_CHARSET) . formattedSize($filesize);
				
	$profile_name = ($transcode) ? $cfg['encode_name'][$cfg['download_id']] . ' (' . $source . ' source)' : 'Source (' . $source . ')';
	
	if ($transcode && $exact)		{$cache_txt = 'Transcoded:'; 	$cache_png = $cfg['img'] . 'small_check.png';}
	elseif ($transcode && !$exact)	{$cache_txt = 'Transcoded:'; 	$cache_png = $cfg['img'] . 'small_uncheck.png';}
	else							{$cache_txt = 'Source:'; 		$cache_png = $cfg['img'] . 'small_check.png';}
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td colspan="3"><?php echo html($filename); ?></td>
	<td class="space"></td>
</tr>
<tr class="odd">
	<td></td>
	<td>File size:</td>
	<td></td>
	<td><?php echo $size; ?></td>
	<td class="space"></td>
</tr>
<tr class="even">
	<td class="space"></td>
	<td>Download profile:</td>
	<td class="textspace"></td>
	<td><?php echo html($profile_name); ?></td>
	<td class="space"></td>
</tr>
<tr class="odd">
	<td></td>
	<td><?php echo $cache_txt; ?></td>
	<td></td>
	<td><img src="<?php echo $cache_png; ?>" alt="" class="small"></td>
	<td class="space"></td>
</tr>
<tr class="even">
	<td></td>
	<td>Mail:</td>
	<td></td>
	<td><a href="mailto:?SUBJECT=<?php echo rawurlencode($filename); ?>&amp;BODY=---%0AFilesize%3A%20<?php echo rawurlencode($size);?>%0ADownload%3A%20<?php echo rawurlencode($filename); ?>%0A<?php echo rawurlencode(str_replace('&amp;', '&', $url)); ?>%0A%0AThis%20file%20will%20expire%20<?php echo  rawurlencode(date($cfg['date_format'], $expire_time)); ?>%20or%20after%20one%20download%3B%20whatever%20comes%20first."><img src="<?php echo $cfg['img']; ?>small_mail.png" alt="" class="small"></a></td>
	<td></td>
</tr>
<tr class="odd">
	<td></td>
	<td>URL:</td>
	<td></td>
	<td><input type="text" value="<?php echo $url; ?>" readonly class="edit" onclick="focus(this); select(this);"></td>
	<td></td>
</tr>
<tr class="even">
	<td></td>
	<td>QR Code:</td>
	<td></td>
	<td><img src="qrcode.php?d=<?php echo rawurlencode(str_replace('&amp;', '&', $url)); ?>&amp;e=l&amp;s=3" alt=""></td>
	<td></td>
</tr>
</table>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Copy album                                                             |
//  +------------------------------------------------------------------------+
function copyAlbum($album_id) { 
	global $cfg, $db;
	authenticate('access_admin', false, true);
	
	if ($cfg['album_copy'] == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]Album copy disabled');
		
	if (is_dir($cfg['external_storage']) == false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to open directory:[/b][br]' . $cfg['external_storage'] . '[br][url=index.php?action=view3&album_id=' . $album_id . '][img]small_back.png[/img]Back to previous page[/url]');
		
	$query = mysqli_query($db, 'SELECT artist_alphabetic, album FROM album WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $album['artist_alphabetic'];
	$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
	$nav['name'][]	= $album['album'];
	$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . $album_id;
	$nav['name'][]	= 'Copy album';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td colspan="5">Transcode to <?php echo html($cfg['encode_name'][$cfg['download_id']]); ?> &amp; copy</td>
	<td class="space"></td>
</tr>
<?php
	$i = 0;
	$query = mysqli_query($db, 'SELECT artist, title, track_id FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '" ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query)) {
?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td><?php echo html($track['artist']); ?></td>
	<td class="textspace"></td>
	<td><?php echo html($track['title']); ?></td>
	<td class="textspace"></td>
	<td><span class="track_id" id="<?php echo $track['track_id']; ?>"></span></td>
	<td></td>
</tr>
<?php
	} ?>
</table>
<script type="text/javascript">
	var track = document.getElementsByClassName('track_id');
	document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
	ajaxRequest('download.php?action=copyJson&track_id=' + track[0].id,next);

	
	function next() {
		var track = document.getElementsByClassName('track_id');
		document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
		document.getElementById(track[0].id).className='';
		if (track.length > 0) {
			document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
			ajaxRequest('download.php?action=copyJson&track_id=' + track[0].id,next);
		}
	}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Copy JSON                                                              |
//  +------------------------------------------------------------------------+
function copyJson($track_id) { 
	global $cfg, $db;
	authenticate('access_admin');
	
	$album_id = substr($track_id, 0, strpos($track_id, '_'));
	
	$query = mysqli_query($db, 'SELECT artist_alphabetic, album, year FROM album WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	$destination = $cfg['external_storage'];
	$destination .= copyFilename($album['artist_alphabetic']) . '/';
	
	if (is_dir($destination) == false && @mkdir($destination, 0777) == false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to create directory:[/b][br]' . $destination);
	
	$destination .= ($album['year']) ? $album['year'] . ' - ' : '';
	$destination .= copyFilename($album['album']) . '/';
	
	if (is_dir($destination) == false && @mkdir($destination, 0777) == false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to create directory:[/b][br]' . $destination);
		
	$source = transcode($track_id, $cfg['download_id']);
	$source = str_replace('\\', '/', $source);
	
	$file = substr($source, strrpos($source, '/') + 1);
	$file = copyFilename($file);
	
	if (@copy($source, $destination . $file) == false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to copy[/b][br]from: ' . $source . '[br]to: ' . $destination . $file);
	
	echo '""';
}