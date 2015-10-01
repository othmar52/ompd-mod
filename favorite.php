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
//  | favorite.php                                                           |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
$cfg['menu'] = 'favorite';

$action 		= @$_REQUEST['action'];
$favorite_id	= @$_REQUEST['favorite_id'];

if		($action == '')						home();
elseif	($action == 'editFavorite')			editFavorite($favorite_id);
elseif	($action == 'addFavorite')	 		addFavorite();
elseif	($action == 'saveFavorite') 		saveFavorite($favorite_id);
elseif	($action == 'importPlaylist')		importFavorite($favorite_id, 'import');
elseif	($action == 'addPlaylist')			importFavorite($favorite_id, 'add');
elseif	($action == 'deleteFavorite') 		deleteFavorite($favorite_id);
elseif	($action == 'deleteFavoriteItem')	deleteFavoriteItem($favorite_id);
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Home                                                                   |
//  +------------------------------------------------------------------------+
function home() {
	global $cfg, $db, $nav;
	authenticate('access_favorite');

	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Favorites';
	$nav['url'][]	= '';
	$nav['class'][]	= 'nav';
	
	if ($cfg['access_play'])
		navPlayerProfile();
	
	require_once('include/header.inc.php');
	
	$i = 0;
	$previous_stream = 0;
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Playlist</td>
	<td class="textspace"></td>
	<td>Comment</td>
	<td<?php if ($cfg['access_admin']) echo' class="textspace"'; ?>></td>
	<td></td><!-- optional delete -->
	<td><?php if ($cfg['access_admin']) echo'<a href="favorite.php?action=addFavorite&amp;sign=' . $cfg['sign'] . '" title="Add new playlist"><img src="' . $cfg['img'] . 'small_header_new.png" alt="" class="small"></a>'; ?></td>
	<td class="space"></td>
</tr>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=generate\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;random=generate&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . 'generate' . $cfg['stream_id']) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td colspan="3"><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=generate\');" title="Play">Random tracks</a>';
						elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=m3u&amp;random=generate&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . 'generate' . $cfg['stream_id']) . '" title="Stream">Random tracks</a>'; 
						else 							echo 'Random tracks'; ?></td>
	<td></td>
	<td></td>
	<td><?php if ($cfg['access_media']) echo '<a href="genre.php?action=blacklist"><img src="' . $cfg['img'] . 'small_edit.png" alt="" class="small"></a>'; ?></td>
	<td></td>
</tr>
<?php
	$query = mysqli_query($db, 'SELECT name, comment, stream, favorite_id FROM favorite WHERE 1 ORDER BY stream, name, comment');
	while ($favorite = mysqli_fetch_assoc($query)) {
		if ($previous_stream != $favorite['stream'] && $i > 0) {
			$i = 0;
			echo '<tr class="section"><td colspan="4"></td><td colspan="2">Radio</td><td colspan="5">Comment</td></tr>' . "\n";
		} ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;favorite_id=' . $favorite['favorite_id'] . '&amp;menu=favorite\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;favorite_id=' . $favorite['favorite_id'] . ($favorite['stream'] ? '' : '&amp;stream_id=' . $cfg['stream_id']) . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $favorite['favorite_id'] . ($favorite['stream'] ? '' : $cfg['stream_id'])) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><?php if ($cfg['access_play'])								echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;favorite_id=' . $favorite['favorite_id'] . '&amp;menu=favorite\');" title="Play">' . html($favorite['name']) . '</a>';
			elseif (!$cfg['access_play'] && $cfg['access_stream'])	echo '<a href="stream.php?action=m3u&amp;favorite_id=' . $favorite['favorite_id'] . ($favorite['stream'] ? '' : '&amp;stream_id=' . $cfg['stream_id']) . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $favorite['favorite_id'] . ($favorite['stream'] ? '' : $cfg['stream_id'])) . '" title="Stream">' . html($favorite['name']) . '</a>';
			else 													echo html($favorite['name']); ?></td>
	<td></td>
	<td><?php echo bbcode($favorite['comment']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_admin']) echo '<a href="favorite.php?action=deleteFavorite&amp;favorite_id=' . $favorite['favorite_id'] . '&amp;sign=' . $cfg['sign'] . '" onclick="return confirm(\'Are you sure you want to delete favorite: ' . addslashes(html($favorite['name'])) . '?\');"><img src="' . $cfg['img'] . 'small_delete.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_admin']) echo '<a href="favorite.php?action=editFavorite&amp;favorite_id=' . $favorite['favorite_id'] . '"><img src="' . $cfg['img'] . 'small_edit.png" alt="" class="small"></a>'; ?></td>
	<td></td>
</tr>
<?php
		$previous_stream = $favorite['stream'];
	}
	echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Edit favorite                                                          |
//  +------------------------------------------------------------------------+
function editFavorite($favorite_id) {
	global $cfg, $db;
	authenticate('access_admin');
	
	require_once('include/play.inc.php');
	
	$query = mysqli_query($db, 'SELECT name, comment, stream FROM favorite WHERE favorite_id = ' . (int) $favorite_id);
	$favorite = mysqli_fetch_assoc($query);
	if ($favorite == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]favorite_id not found in database');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Favorites';
	$nav['url'][]	= 'favorite.php';
	if ($cfg['access_play'])
		$nav['name'][]	= $cfg['player_name'];
	$nav['name'][]	= 'Edit';
	require_once('include/header.inc.php');
?>	
<form action="favorite.php" method="post" id="favoriteform">
	<input type="hidden" name="action" value="saveFavorite">
	<input type="hidden" name="favorite_id" value="<?php echo $favorite_id; ?>">
	<input type="hidden" name="sign" value="<?php echo $cfg['sign']; ?>">
<table>
<tr>
	<td>Name:</td>
	<td class="textspace"></td>
	<td><input type="text" name="name" value="<?php echo html($favorite['name']); ?>" maxlength="255" class="edit"></td>
</tr>
<tr>
	<td>Comment:</td>
	<td></td>
	<td><input type="text" name="comment" value="<?php echo html($favorite['comment']); ?>" maxlength="255" class="edit" <?php echo bbcodeReferenceTitle(); ?>></td>
</tr>
<tr class="space"><td colspan="3"></td></tr>
<tr>
	<td></td>
	<td></td>
	<td>
		<a href="javascript:favoriteform.submit();" class="button space">save</a><!--
		--><a href="favorite.php" class="button">cancel</a>
	</td>
</tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr>
	<td>Stream url (mp3, ogg):</td>
	<td></td>
	<td><input type="text" name="stream_url" value="" maxlength="255" class="edit"></td>
</tr>
<tr>
	<td>Playlist url (m3u, pls):</td>
	<td></td>
	<td><input type="text" name="playlist_url" value="" maxlength="255" class="edit"></td>
</tr>
<tr class="space"><td colspan="3"></td></tr>		
<tr>
	<td></td>
	<td></td>
	<td>
		<a href="javascript:favoriteform.action.value='importPlaylist';favoriteform.submit();" class="button space">import</a><!--
		--><a href="javascript:favoriteform.action.value='addPlaylist';favoriteform.submit();" class="button">add</a>
	</td>
</tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr class="textspace"><td colspan="3"></td></tr>		
<tr>
	<td class="vertical-align-top">Tracks:</td>
	<td></td>
	<td>
	<!-- begin indent -->
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td<?php if ($cfg['access_play'] && $favorite['stream'] == false) echo' class="space"'; ?>></td>
	<td><?php echo $favorite['stream'] ? 'Stream' : 'Artist' ?></td>
	<td<?php if ($favorite['stream'] == false) echo ' class="textspace"'; ?>></td>
	<td><?php echo $favorite['stream'] ? '' : 'Title' ?></td>
	<td class="space"></td>
	<td></td><!-- delete -->
	<td class="space"></td>
</tr>
<?php
	$i = 0;
	$query1 = mysqli_query($db, 'SELECT track_id, stream_url, position FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' ORDER BY position');
	while ($favoriteitem = mysqli_fetch_assoc($query1)) {
		if ($favoriteitem['track_id']) {
			$query2	= mysqli_query($db, 'SELECT artist, title FROM track WHERE track_id = "' . mysqli_real_escape_string($db, $favoriteitem['track_id']) . '"');
			$track	= mysqli_fetch_assoc($query2);
			$artist	= $track['artist'];
			$title	= $track['title'];
		}
		elseif ($favoriteitem['stream_url']) {
			$artist	= $favoriteitem['stream_url'];
			$title	= '';
		} ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play'] && $favoriteitem['track_id']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $favoriteitem['track_id'] . '&amp;menu=favorite\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><?php echo html($artist); ?></td>
	<td></td>
	<td><?php if ($cfg['access_play'] && $favoriteitem['track_id']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $favoriteitem['track_id'] . '&amp;menu=favorite\');" title="play track">' . html($title) . '</a>';
		else echo html($title); ?></td>
	<td></td>
	<td class="text-align-right"><a href="favorite.php?action=deleteFavoriteItem&amp;favorite_id=<?php echo $favorite_id; ?>&amp;position=<?php echo $favoriteitem['position']; ?>&amp;sign=<?php echo $cfg['sign']; ?>"><img src="<?php echo $cfg['img']; ?>small_delete.png" alt="" class="small"></a></td>
	<td></td>
</tr>
<?php
	} ?>
</table>
	<!-- end indent -->
	</td>
</tr>
</table>
</form>

<script type="text/javascript">favoriteform.name.focus();</script>
<?php
	require_once('include/footer.inc.php');
}





//  +------------------------------------------------------------------------+
//  | Add favorite                                                           |
//  +------------------------------------------------------------------------+
function addFavorite() {
	global $cfg, $db;
	authenticate('access_admin', false, true, true);
	mysqli_query($db, 'INSERT INTO favorite (name) VALUES ("")');
	$favorite_id = mysqli_insert_id($db);
	
	editFavorite($favorite_id);
}




//  +------------------------------------------------------------------------+
//  | Save favorite                                                          |
//  +------------------------------------------------------------------------+
function saveFavorite($favorite_id) {
	global $db;
	authenticate('access_admin', false, true, true);
	$name	 = @$_REQUEST['name'];
	$comment = @$_REQUEST['comment'];
	mysqli_query($db, 'UPDATE favorite SET
		name	= "' . mysqli_real_escape_string($db, $name) . '",
		comment	= "' . mysqli_real_escape_string($db, $comment) . '"
		WHERE favorite_id = ' . (int) $favorite_id);
	
	home();
}




//  +------------------------------------------------------------------------+
//  | Import favorite                                                        |
//  +------------------------------------------------------------------------+
function importFavorite($favorite_id, $mode) {
	global $cfg, $db;
	authenticate('access_admin', false, true, true);
	require_once('include/play.inc.php');
	
	$name			= @$_POST['name'];
	$comment		= @$_POST['comment'];
	$stream_url		= @$_POST['stream_url'];
	$playlist_url	= @$_POST['playlist_url'];
	
	
	if ($stream_url != '') {
		$file = array();
		
		if (preg_match('#^((?:ftp|http|https|mms|mmst|pnm|rtp|rtsp|sdp)://.+)#', $stream_url, $match))
			$file[] = $match[1];
	}
	elseif ($playlist_url != '') {
		$file = array();
		
		$items = @file($playlist_url, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) or message(__FILE__, __LINE__, 'error', '[b]Failed to open url:[/b][br]' . $playlist_url);
		foreach ($items as $item) {
			// pls:		
			// File1=http://example.com:80
			// m3u:
			// http://example.com:80
			if (preg_match('#^(?:File[0-9]{1,3}=|)((?:ftp|http|https|mms|mmst|pnm|rtp|rtsp|sdp)://.+)#', $item, $match))
				$file[] = $match[1];
		}
	}
	elseif ($cfg['player_type'] == NJB_HTTPQ) {
		$file = httpq('getplaylistfilelist', 'delim=*');
		$file = str_replace('\\', '/', $file);
		$file = explode('*', $file);
		
		// Get relative directory based on $cfg['media_share']
		foreach ($file as $i => $value) {
			if (strtolower(substr($file[$i], 0, strlen($cfg['media_share']))) == strtolower($cfg['media_share']))
				$file[$i] = substr($file[$i], strlen($cfg['media_share']));
		}
	}
	elseif ($cfg['player_type'] == NJB_MPD) {
		$file = mpd('playlist');
		$file = implode('<seperation>', $file);
		$file = iconv(NJB_DEFAULT_CHARSET, 'UTF-8', $file);
		$file = explode('<seperation>', $file);
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Player not supported[/b]');

	
	$stream = 0;
	for ($i = 0; $i < count($file); $i++) {
		if (preg_match('#^(ftp|http|https|mms|mmst|pnm|rtp|rtsp|sdp)://#', $file[$i]))
			$stream = 1;
	}
	
	
	if (count($file) > 0) {
		if ($mode == 'import') {
			mysqli_query($db, 'DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id);
			$offset = 0;
		}
		
		if ($mode = 'add') {
			$query = mysqli_query($db, 'SELECT position FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' ORDER BY position DESC');
			$track = mysqli_fetch_assoc($query);
			$offset = $track['position'];
		}	
		
		// Update favorite stream status
		mysqli_query($db, 'UPDATE favorite
					SET stream			= "' . (int) $stream . '"
					WHERE favorite_id	= ' . (int) $favorite_id);
		
		// Don't allow stream_url and track_id in the same playlist!
		if ($stream)	mysqli_query($db, 'DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' AND track_id != ""');
		else			mysqli_query($db, 'DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' AND stream_url != ""');
	}
	
			
	for ($i = 0; $i < count($file); $i++) {
		$query = mysqli_query($db, 'SELECT track_id FROM track WHERE relative_file = "' . mysqli_real_escape_string($db, $file[$i]) . '"');
		$track = mysqli_fetch_assoc($query);
		
		if ($stream == 0 && $track['track_id']) {
			$position = $i + $offset + 1;
			mysqli_query($db, 'INSERT INTO favoriteitem (track_id, position, favorite_id)
				VALUES ("' . mysqli_real_escape_string($db, $track['track_id']) . '",
				' . (int) $position . ',
				' . (int) $favorite_id . ')');
		}
		
		if ($stream == 1 && preg_match('#^(ftp|http|https|mms|mmst|pnm|rtp|rtsp|sdp)://#', $file[$i])) {
			$position = $i + $offset + 1;
			mysqli_query($db, 'INSERT INTO favoriteitem (stream_url, position, favorite_id)
				VALUES ("' . mysqli_real_escape_string($db, $file[$i]) . '",
				' . (int) $position . ',
				' . (int) $favorite_id . ')');
		}
	}
	
	editFavorite($favorite_id);
}




//  +------------------------------------------------------------------------+
//  | Delete favorite                                                        |
//  +------------------------------------------------------------------------+
function deleteFavorite($favorite_id) {
	global $db;
	authenticate('access_admin', false, true, true);
	mysqli_query($db, 'DELETE FROM favorite WHERE favorite_id = ' . (int) $favorite_id);
	mysqli_query($db, 'DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id);
	home();
}




//  +------------------------------------------------------------------------+
//  | Delete favorite item                                                   |
//  +------------------------------------------------------------------------+
function deleteFavoriteItem($favorite_id) {
	global $db;
	authenticate('access_admin', false, true, true);
	$position = @$_GET['position'];
	mysqli_query($db, 'DELETE FROM favoriteitem
		WHERE favorite_id = ' . (int) $favorite_id . '
		AND position = ' . (int) $position);
	editFavorite($favorite_id);
}
