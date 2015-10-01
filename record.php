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
//  | record.php                                                             |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
require_once('include/cache.inc.php');

$action		= @$_GET['action'];
$track_id	= @$_GET['track_id'];
$album_id	= @$_GET['album_id'];

if 		($action == '')					record($album_id);
elseif	($action == 'extractJson')		extractJson($track_id);
elseif	($action == 'recordJson')		recordJson($album_id);

else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Record                                                                 |
//  +------------------------------------------------------------------------+
function record($album_id) { 
	global $cfg, $db;
	authenticate('access_record');

	$cfg['menu']	= 'media';
	$disc 			= (int) @$_GET['disc'];
	
	$query = mysqli_query($db, 'SELECT artist, artist_alphabetic, album FROM album WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	$cache		= cacheGetDir($album_id, -2);
	$tocfile  	= $cache . $album_id . '_disc' . $disc . '.toc';
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $album['artist_alphabetic'];
	$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
	$nav['name'][]	= $album['album'];
	$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . rawurlencode($album_id);
	$nav['name'][]	= 'Record';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Decode</td>
	<td class="textspace"></td>
	<td></td>
	<td class="textspace"></td>
	<td></td><!-- status -->
	<td class="space"></td>
</tr>
<?php
	$toc_content = 'CD_DA' . "\n";
	if ($cfg['record_cdtext']) {
		$toc_content .= 'CD_TEXT {' . "\n";
		$toc_content .= '  LANGUAGE_MAP {' . "\n";
		$toc_content .= '    0: EN' . "\n";
		$toc_content .= '  }' . "\n";
		$toc_content .= '  LANGUAGE 0 {' . "\n";
		$toc_content .= '    TITLE "' . $album['album'] . '"' . "\n";	
		$toc_content .= '    PERFORMER "' . $album['artist'] . '"' . "\n";
		$toc_content .= '  }' . "\n";
		$toc_content .= '}' . "\n";
	}
	$i = 0;
	$query = mysqli_query($db, 'SELECT title, artist, relative_file, track_id FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id). '" AND disc = ' . (int) $disc . ' ORDER BY relative_file');
	while ($track = mysqli_fetch_assoc($query)) {
		$cacheXXX = (cacheGetFile($track['track_id'], -2)) ? true : false; ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td><?php echo html($track['artist']); ?></td>
	<td></td>
	<td><?php echo html($track['title']); ?></td>
	<td></td>
	<td><span class="<?php echo ($cacheXXX) ? '' : 'track_id'; ?>" id="<?php echo $track['track_id']; ?>"><?php echo ($cacheXXX) ? '<img src="' . $cfg['img'] . 'small_check.png" alt="" class="small">' : ''; ?></span></td>
	<td></td>
</tr>
<?php
		$destination = $cache . $track['track_id'] . '.wav';
		if (NJB_WINDOWS)
			$destination = str_replace('/', '\\', $destination);
		
		$toc_content .= 'TRACK AUDIO' . "\n";
		if ($cfg['record_cdtext']) {
			$toc_content .= 'CD_TEXT {' . "\n";
			$toc_content .= '  LANGUAGE 0 {' . "\n";
			$toc_content .= '    TITLE "' . $track['title'] . '"' . "\n";
			$toc_content .= '    PERFORMER "' . $track['artist'] . '"' . "\n";
			$toc_content .= '  }' . "\n";
			$toc_content .= '}' . "\n";
		}
		$toc_content .= 'FILE "' . $destination . '" 0' . "\n";
	}
	
	if (file_put_contents($tocfile, $toc_content) === false)
		message(__FILE__, __LINE__, 'error', '[b]Failed to write file:[/b][br]' . $tocfile);
?>
<tr class="section">
	<td></td>
	<td colspan="5">Record</td>
	<td></td>
</tr>
<tr class="odd">
	<td></td>
	<td colspan="4">Record to disc</td>
	<td><span id="record"></span></td>
	<td></td>
</tr>
</table>
<script type="text/javascript">
	var track = document.getElementsByClassName('track_id');
	if (track.length > 0) {
		document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
		ajaxRequest('record.php?action=extractJson&track_id=' + track[0].id,next);
	}
	else {
		document.getElementById('record').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_record.gif" alt="" class="small">';
		ajaxRequest('record.php?action=recordJson&album_id=<?php echo $album_id; ?>&disc=<?php echo $disc; ?>',ready);
	}
	

	
	function next() {
		var track = document.getElementsByClassName('track_id');
		document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
		document.getElementById(track[0].id).className='';
		if (track.length > 0) {
			document.getElementById(track[0].id).innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_progress.gif" alt="" class="small">';
			ajaxRequest('record.php?action=extractJson&track_id=' + track[0].id,next);
		}
		else {
			document.getElementById('record').innerHTML='<img src="<?php echo $cfg['img']; ?>small_animated_record.gif" alt="" class="small">';
			ajaxRequest('record.php?action=recordJson&album_id=<?php echo $album_id; ?>&disc=<?php echo $disc; ?>',ready);
		}
	}
	
	
	function ready() {
		document.getElementById('record').innerHTML='<img src="<?php echo $cfg['img']; ?>small_check.png" alt="" class="small">';
	}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Extract JSON                                                           |
//  +------------------------------------------------------------------------+
function extractJson($track_id) { 
	global $cfg, $db;
	authenticate('access_record');
	
	$destination = cacheGetFile($track_id, -2);
	
	if ($destination == false) {
		$query	= mysqli_query($db, 'SELECT relative_file FROM track WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
		$track	= mysqli_fetch_assoc($query);
		$source	= $cfg['media_dir'] . $track['relative_file'];
		
		$album_id 		= substr($track_id, 0, strpos($track_id, '_'));	
		$cache			= cacheGetDir($album_id, -2);
		$destination	= $cache . $track_id . '.wav';
		$extension		= substr(strrchr($source, '.'), 1);
		$extension		= strtolower($extension);
		
		// Extract to wave
		if (NJB_WINDOWS)	$cmd = $cfg['decode_stdout'][$extension];
		else				$cmd = $cfg['decode_stdout'][$extension] . ' 2>&1'; 
		
		$cmd = str_replace('%source', escapeCmdArg($source), $cmd);
		$cmd = $cmd . ' > ' .  escapeCmdArg($destination);
		
		$cmd_output	= array();
		$cmd_return_var = 0;
		@exec($cmd, $cmd_output, $cmd_return_var);
				
		if ($cmd_return_var != 0)
			message(__FILE__, __LINE__, 'error', '[b]Exec error[/b][br][b]Command:[/b] ' . $cmd . '[br][b]System output:[/b] ' . implode('[br]', $cmd_output));
		
		if (is_file($destination) == false)
			message(__FILE__, __LINE__, 'error', '[b]Destination file not created[/b][br]File: ' . $destination . '[br]Command: ' . $cmd);
				
		cacheUpdateFile($track_id, -2, $destination);
		cacheCleanup();
	}
	echo '""';
}




//  +------------------------------------------------------------------------+
//  | Record JSON                                                            |
//  +------------------------------------------------------------------------+
function recordJson($album_id) { 
	global $cfg, $db;
	authenticate('access_record');
	
	$disc 		= (int) @$_GET['disc'];
	
	$cache		= cacheGetDir($album_id, -2);
	$tocfile  	= $cache . $album_id . '_disc' . $disc . '.toc';
	
	if (NJB_WINDOWS)	$cmd = $cfg['record'];
	else				$cmd = $cfg['record'] . ' 2>&1';
	
	$cmd = str_replace('%tocfile', escapeCmdArg($tocfile), $cmd);
	
	$cmd_output = array();
	$cmd_return_var = 0;
	@exec($cmd, $cmd_output, $cmd_return_var);
	
	if ($cmd_return_var != 0)
		message(__FILE__, __LINE__, 'error', '[b]Exec error[/b][br][b]Command:[/b] ' . $cmd . '[br][b]System output:[/b] ' . implode('[br]', $cmd_output));	
	
	@unlink($tocfile);
	updateCounter($album_id, NJB_COUNTER_RECORD);
	echo '""';
}
