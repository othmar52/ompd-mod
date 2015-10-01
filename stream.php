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
//  | stream.php                                                             |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
require_once('include/stream.inc.php');
require_once('include/cache.inc.php');

$action		= @$_GET['action'];
$album_id	= @$_GET['album_id'];

if		($action == 'm3u')			m3u();
elseif	($action == 'm3uPlaylist')	m3uPlaylist();
elseif	($action == 'stream')		stream();
elseif	($action == 'shareAlbum')	shareAlbum($album_id);
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | m3u                                                                    |
//  +------------------------------------------------------------------------+
function m3u() {
	global $cfg, $db;
	
	$stream_id		= @$_GET['stream_id'];
	$track_id		= @$_GET['track_id'];
	$album_id		= @$_GET['album_id'];
	$favorite_id	= @$_GET['favorite_id'];
	$random			= @$_GET['random'];
	$sid			= @$_GET['sid'];
	$hash			= @$_GET['hash'];
	
	if ($hash) {
		// Common stream
		authenticateStream();
	}
	else {
		// Share stream
		header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		
		mysqli_query($db, 'UPDATE share_stream SET
			ip			= "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '"
			WHERE sid	= BINARY "' . mysqli_real_escape_string($db, $sid) . '"
			AND ip		= ""');
		
		$query = mysqli_query($db, 'SELECT album_id, stream_id
			FROM share_stream
			WHERE sid = BINARY "' . mysqli_real_escape_string($db, $sid) . '"
			AND ip = "' . mysqli_real_escape_string($db, $_SERVER['REMOTE_ADDR']) . '"
			AND expire_time > ' . (int) time());
		$share_stream = mysqli_fetch_assoc($query);
		
		if ($share_stream == false || $cfg['album_share_stream'] == false)
			message(__FILE__, __LINE__, 'error', '[b]Stream error[/b][br]Authentication failed or share stream is disabled');
		
		$album_id 	= $share_stream['album_id'];
		$stream_id	= $share_stream['stream_id'];
	}
		
	if ($sid) {
		$query = mysqli_query($db, 'SELECT artist, title, relative_file, miliseconds, audio_bitrate, track_id
			FROM track, share_stream
			WHERE share_stream.sid	= "' . mysqli_real_escape_string($db, $sid) . '" AND
			share_stream.album_id	= track.album_id
			ORDER BY relative_file');
	}
	elseif ($track_id) {
		$query = mysqli_query($db, 'SELECT artist, title, relative_file, miliseconds, audio_bitrate, track_id FROM track WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	}
	elseif ($album_id) {
		$query = mysqli_query($db, 'SELECT artist, title, relative_file, miliseconds, audio_bitrate, track_id FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '" ORDER BY relative_file');
	}
	elseif ($favorite_id) {
		$query = mysqli_query($db, 'SELECT stream
			FROM favorite
			WHERE favorite_id = ' . (int) $favorite_id . '
			AND stream = 1');
		if (mysqli_fetch_row($query))
			streamPlaylist($favorite_id);
		
		$query = mysqli_query($db, 'SELECT track.artist, track.title, track.relative_file, track.miliseconds, track.audio_bitrate, track.track_id
			FROM track, favoriteitem
			WHERE favoriteitem.track_id = track.track_id 
			AND favorite_id = "' . mysqli_real_escape_string($db, $favorite_id) . '"
			ORDER BY position');
	}
	elseif ($random == 'database') {
		$query = mysqli_query($db, 'SELECT artist, title, relative_file, miliseconds, audio_bitrate, track.track_id
			FROM track, random
			WHERE random.sid	= "' . mysqli_real_escape_string($db, $cfg['sid']) . '" AND
			random.track_id		= track.track_id
			ORDER BY position');
	}
	elseif ($random == 'generate') {
		$blacklist = explode(',', $cfg['random_blacklist']);
		$blacklist = '"' . implode('","', $blacklist) . '"';
		$query = mysqli_query($db, 'SELECT track.artist, title, relative_file, miliseconds, audio_bitrate, track_id
			FROM track, album
			WHERE (genre_id = "" OR genre_id NOT IN (' . $blacklist . ')) AND
			audio_dataformat != "" AND
			video_dataformat = "" AND
			track.album_id = album.album_id
			ORDER BY RAND()
			LIMIT 30');
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Unsupported query string[/b][br]' . $_SERVER['QUERY_STRING']);
			
	if ($stream_id != -1 && isset($cfg['encode_extension'][$stream_id]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]stream_id');
	
	$m3u_files = '';
	$m3u_targetduration = 0;
	$m3u_content = '#EXTM3U' . "\n";
	while ($track = mysqli_fetch_assoc($query)) {
		$extension = substr(strrchr($track['relative_file'], '.'), 1);
		$extension = strtolower($extension);
		if (sourceFile($extension, $track['audio_bitrate'], $stream_id))
			$stream_extension = $extension;
		else
			$stream_extension = $cfg['encode_extension'][$stream_id];
		
		if ($sid) {
			// Share stream
			$url = NJB_HOME_URL . 'stream.php?action=stream';
			$url .= '&stream_id=' . rawurlencode($stream_id) . '&track_id=' . $track['track_id'];
			$url .= '&sid=' . rawurlencode($sid);
			$url .= '&ext=.' . $stream_extension;
		}
		else {
			// Common stream
			$url = NJB_HOME_URL . 'stream.php?action=stream';
			$url .= '&stream_id=' . rawurlencode($stream_id) . '&track_id=' . $track['track_id'];
			$url .= '&short_sid=' . substr($cfg['sid'], 0, 8);
			$url .= '&hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'stream' . $stream_id . $track['track_id']);
			$url .= '&ext=.' . $stream_extension;
		}
		
		if (preg_match('#(iPhone|iPad|iPod)#i', $_SERVER['HTTP_USER_AGENT'])) {
			$seconds = ceil($track['miliseconds'] / 1000);
			if ($seconds > $m3u_targetduration)
				$m3u_targetduration = $seconds;
			
			$m3u_files .= '#EXTINF:' . round($track['miliseconds'] / 1000, 2) . ',' . "\n";
			$m3u_files .= $url . "\n";
		
		}
		else {
			$m3u_content .= '#EXTINF:' . round($track['miliseconds'] / 1000) . ',' . $track['artist'] . ' - ' . $track['title'] . "\n";
			$m3u_content .= $url . "\n";
		}
	}
	if (preg_match('#(iPhone|iPad|iPod)#i', $_SERVER['HTTP_USER_AGENT'])) {
		$m3u_content .= '#EXT-X-VERSION:3' . "\n";
		$m3u_content .= '#EXT-X-TARGETDURATION:' . $m3u_targetduration . "\n";
		$m3u_content .= $m3u_files;
		$m3u_content .= '#EXT-X-ENDLIST' . "\n";
	}
	
	$filename = '.netjukebox_';
	$filename .= ($hash) ? $hash : $sid;
	$filename .= '.m3u';
	 
	streamData($m3u_content, 'audio/mpegurl', 'inline', $filename);
	
	if ($hash && $album_id)
		updateCounter($album_id, NJB_COUNTER_STREAM);
	
	exit();
}




//  +------------------------------------------------------------------------+
//  | m3u Playlist                                                           |
//  +------------------------------------------------------------------------+
function m3uPlaylist() {
	global $cfg, $db;

	authenticateStream();
	require_once('include/play.inc.php');
	
	$stream_id	= @$_GET['stream_id'];
	$hash		= @$_GET['hash'];
	
	if ($stream_id != -1 && isset($cfg['encode_extension'][$stream_id]) == false)
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]stream_id');
	
	if ($cfg['player_type'] == NJB_HTTPQ) {
		$file = httpq('getplaylistfilelist', 'delim=*');
		$file = str_replace('\\', '/', $file);
		$file = explode('*', $file);
			
		// Get relative directory based on $cfg['media_share']
		foreach ($file as $i => $value)	{
			if (strtolower(substr($file[$i], 0, strlen($cfg['media_share']))) == strtolower($cfg['media_share']))
				$file[$i] = substr($file[$i], strlen($cfg['media_share']));
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD)	{
		$file = mpd('playlist');
	}
	elseif ($cfg['player_type'] == NJB_VLC)
		message(__FILE__, __LINE__, 'warning', '[b]videoLAN playlist not supported yet[/b]');
	else
		message(__FILE__, __LINE__, 'error', '[b]Player not supported[/b]');
	
	$m3u_files = '';
	$m3u_targetduration = 0;
	$m3u_content = '#EXTM3U' . "\n";
	foreach ($file as $value) {
		$query = mysqli_query($db, 'SELECT artist, title, relative_file, miliseconds, audio_bitrate, track_id FROM track
			WHERE relative_file = "' . mysqli_real_escape_like($db, $value) . '"
			LIMIT 1');
		$track = mysqli_fetch_assoc($query);
		if ($track) {
			$extension = substr(strrchr($track['relative_file'], '.'), 1);
			$extension = strtolower($extension);
			if (sourceFile($extension, $track['audio_bitrate'], $stream_id))
				$stream_extension = $extension;
			else
				$stream_extension = $cfg['encode_extension'][$stream_id];
		
			$url = NJB_HOME_URL . 'stream.php?action=stream';
			$url .= '&stream_id=' . rawurlencode($stream_id) . '&track_id=' . $track['track_id'];
			$url .= '&short_sid=' . substr($cfg['sid'], 0, 8);
			$url .= '&hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'stream' . $stream_id . $track['track_id']);
			$url .= '&ext=.' . $stream_extension;

			if (preg_match('#(iPhone|iPad|iPod)#i', $_SERVER['HTTP_USER_AGENT'])) {
				$seconds = ceil($track['miliseconds'] / 1000);
				if ($seconds > $m3u_targetduration)
					$m3u_targetduration = $seconds;
				
				$m3u_files .= '#EXTINF:' . round($track['miliseconds'] / 1000, 2) . ',' . "\n";
				$m3u_files .= $url . "\n";
			
			}
			else {
				$m3u_content .= '#EXTINF:' . round($track['miliseconds'] / 1000) . ',' . $track['artist'] . ' - ' . $track['title'] . "\n";
				$m3u_content .= $url . "\n";
			}
			
		}
		elseif (preg_match('#^(ftp|http|https|mms|mmst|pnm|rtp|rtsp|sdp)://#', $value))
			$m3u_content .= $value . "\n";
	}
	if (preg_match('#(iPhone|iPad|iPod)#i', $_SERVER['HTTP_USER_AGENT']) && $m3u_targetduration > 0) {
		$m3u_content .= '#EXT-X-VERSION:3' . "\n";
		$m3u_content .= '#EXT-X-TARGETDURATION:' . $m3u_targetduration . "\n";
		$m3u_content .= $m3u_files;
		$m3u_content .= '#EXT-X-ENDLIST' . "\n";
	}
	elseif (preg_match('#(iPhone|iPad|iPod)#i', $_SERVER['HTTP_USER_AGENT'])) {
		$m3u_content .= $m3u_files;
		$m3u_content .= '#EXT-X-ENDLIST' . "\n";
	}
	
	$filename = '.netjukebox_';
	$filename .= $hash;
	$filename .= '.m3u';
	
	streamData($m3u_content, 'audio/mpegurl', 'inline', $filename);
	exit();
}




//  +------------------------------------------------------------------------+
//  | Stream playlist                                                        |
//  +------------------------------------------------------------------------+
function streamPlaylist($favorite_id) {
	global $cfg, $db;
	
	$hash = @$_GET['hash'];

	$m3u_content = '#EXTM3U' . "\n";
	$query = mysqli_query($db, 'SELECT stream_url FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' AND stream_url != "" ORDER BY position');
	while ($favoriteitem = mysqli_fetch_assoc($query))
		$m3u_content .= $favoriteitem['stream_url'] . "\n";
	
	if (preg_match('#(iPhone|iPad|iPod)#i', $_SERVER['HTTP_USER_AGENT']))
		$m3u_content .= '#EXT-X-ENDLIST' . "\n";
	
	$filename = '.netjukebox_';
	$filename .= $hash;
	$filename .= '.m3u';
	
	streamData($m3u_content, 'audio/mpegurl', 'inline', $filename);
	exit();
}




//  +------------------------------------------------------------------------+
//  | Stream                                                                 |
//  +------------------------------------------------------------------------+
function stream() {
	global $cfg, $db;
	
	$track_id	= @$_GET['track_id'];
	$stream_id	= (int) @$_GET['stream_id'];
	$sid		= @$_GET['sid'];
	$hash		= @$_GET['hash'];
	
	if ($stream_id != -1 && isset($cfg['encode_extension'][$stream_id]) == false) {
		header('HTTP/1.1 400 Bad Request');
		exit();
	}
	
	if ($hash)	authenticateStream();
	else		authenticateShareStream();
	
	$query = mysqli_query($db, 'SELECT
		LOWER(SUBSTRING_INDEX(relative_file, ".", -1)) AS extension,
		track.artist, title, album, year, disc, discs, number,
		relative_file, mime_type, miliseconds, filesize, filemtime, audio_bitrate
		FROM track, album
		WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"
		AND track.album_id = album.album_id');
	$track = mysqli_fetch_assoc($query);
	$file = $cfg['media_dir'] . $track['relative_file'];
	
	if (sourceFile($track['extension'], $track['audio_bitrate'], $stream_id)) {
		// Stream from source
		streamFile($file, $track['mime_type']);
	}
	elseif ($cache = cacheGetFile($track_id, $stream_id)) {
		// Stream from cache
		cacheUpdateTag($track_id, $stream_id, $cache);
		streamFile($cache, $cfg['encode_mime_type'][$stream_id]);
	}
	else {
		// Real time transcode stream
		ini_set('max_execution_time', 0);
		
		if (file_exists(NJB_HOME_DIR . '-'))
			@unlink(NJB_HOME_DIR . '-');
		
		$cmd = $cfg['decode_stdout'][$track['extension']] . ' | ' . $cfg['encode_stdout'][$stream_id];
		$cmd = str_replace('%source', escapeCmdArg($file), $cmd);
				
		header('Accept-Ranges: none');
		header('Content-Type: ' . $cfg['encode_mime_type'][$stream_id]);
		
		if (@passthru($cmd) == false) {
			header('HTTP/1.1 500 Internal Server Error');
			exit();
		}
	}
}




//  +------------------------------------------------------------------------+
//  | Authenticate share stream                                              |
//  +------------------------------------------------------------------------+
function authenticateShareStream() {
	global $cfg, $db;
	header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	
	$sid		= @$_GET['sid'];
	$track_id	= @$_GET['track_id'];
	$album_id	= substr($track_id, 0, strpos($track_id, '_'));
	$stream_id	= @$_GET['stream_id'];
	
	$query = mysqli_query($db, 'SELECT ip, album_id, stream_id, expire_time FROM share_stream
		WHERE sid = BINARY "' . mysqli_real_escape_string($db, $sid) . '"');
	$share_stream = mysqli_fetch_assoc($query);
	
	if ($share_stream['ip']			== $_SERVER['REMOTE_ADDR'] &&
		$share_stream['album_id']	== $album_id &&
		$share_stream['stream_id']	== $stream_id &&
		$share_stream['expire_time']	> time())
		return true;
	
	header('HTTP/1.1 403 Forbidden');
	exit();
}




//  +------------------------------------------------------------------------+
//  | Share album                                                            |
//  +------------------------------------------------------------------------+
function shareAlbum($album_id) {
	global $cfg, $db;
	authenticate('access_admin', false, true);
	
	if ($cfg['album_share_stream'] == false)
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
	$nav['name'][]	= 'Share stream';
	require_once('include/header.inc.php');
	
	$expire_time = time() + $cfg['share_stream_lifetime'];
	$sid = randomSid();
	mysqli_query($db, 'INSERT INTO share_stream (sid, album_id, stream_id, expire_time) VALUES (
		"' . mysqli_real_escape_string($db, $sid) . '",
		"' . mysqli_real_escape_string($db, $album_id) . '",
		' . (int) $cfg['stream_id'] . ',
		' . (int) $expire_time . ')');
	
	$url		= NJB_HOME_URL . 'stream.php?action=m3u&amp;sid=' . $sid;
	
	$name	= $album['artist_alphabetic'] . ' - ';
	$name	.=  ($album['year']) ? $album['year'] . ' - ' : '';
	$name	.= $album['album'];
	// $name 	= encodeEscapeChar($name);
		
	$transcode		= false;
	$exact			= true;
	$extensions		= array();
	$miliseconds	= 0;
	$query = mysqli_query($db, 'SELECT track.filesize, cache.filesize AS cache_filesize,
		miliseconds, audio_bitrate, track_id,
		LOWER(SUBSTRING_INDEX(track.relative_file, ".", -1)) AS extension
		FROM track LEFT JOIN cache
		ON track.track_id = cache.id
		AND cache.profile = ' . (int) $cfg['stream_id'] . '
		WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	while($track = mysqli_fetch_assoc($query)) {
		if (in_array($track['extension'], $extensions) == false) {
			$extensions[] = $track['extension'];
		}
		if (sourceFile($track['extension'], $track['audio_bitrate'], $cfg['stream_id']) == false) {
			$transcode = true;
			if ($track['cache_filesize'] == false)
				$exact = false;
		}
		$miliseconds += $track['miliseconds'];
	}
	
	sort($extensions);
	$source = implode($extensions, ', ');
	
	$profile_name = ($transcode) ? $cfg['encode_name'][$cfg['stream_id']] . ' (' . $source . ' source)' : 'Source (' . $source . ')';
	
	if ($transcode && $exact)		{$cache_txt = 'Transcoded:'; 	$cache_png = $cfg['img'] . 'small_check.png';}
	elseif ($transcode && !$exact)	{$cache_txt = 'Transcoded:'; 	$cache_png = $cfg['img'] . 'small_uncheck.png';}
	else							{$cache_txt = 'Source:'; 		$cache_png = $cfg['img'] . 'small_check.png';}
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td colspan="3"><?php echo html($name); ?></td>
	<td class="space"></td>
</tr>
<tr class="odd">
	<td></td>
	<td>Play time:</td>
	<td></td>
	<td><?php echo formattedTime($miliseconds); ?></td>
	<td class="space"></td>
</tr>
<tr class="even">
	<td class="space"></td>
	<td>Stream profile:</td>
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
	<td><a href="mailto:?SUBJECT=<?php echo rawurlencode($name); ?>&amp;BODY=---%0APlay%20time%3A%20<?php echo rawurlencode(formattedTime($miliseconds));?>%0AStream%3A%20<?php echo rawurlencode($name); ?>%0A<?php echo rawurlencode(str_replace('&amp;', '&', $url)); ?>%0A%0AThis%20stream%20will%20expire%20<?php echo  rawurlencode(date($cfg['date_format'], $expire_time)); ?>%20and%20locked%20to%20the%20first%20used%20IP%20address."><img src="<?php echo $cfg['img']; ?>small_mail.png" alt="" class="small"></a></td>
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
