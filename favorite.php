<?php
//  +------------------------------------------------------------------------+
//  | O!MPD, Copyright © 2015 Artur Sierzant		                         |
//  | http://www.ompd.pl                                             		 |
//  |                                                                        |
//  |                                                                        |
//  | netjukebox, Copyright © 2001-2012 Willem Bartels                       |
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

$action 		= getpost('action');
$favorite_id	= getpost('favorite_id');


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
	global $cfg, $db;
	authenticate('access_favorite');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Favorites';
	require_once('include/header.inc.php');
	
	$i = 0;
	$previous_stream = 0;

	if ($cfg['access_admin']) {
?>
<div class="buttons">
	<span><a href="favorite.php?action=addFavorite&amp;sign=<?php echo $cfg['sign'] ?>" onmouseover="return overlib('Add new playlist');" onmouseout="return nd();">Add new</a></span>
</div>
<?php }
	?>
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="space"></td>
	<td class="icon"></td><!-- optional play -->
	<td class="icon"></td><!-- optional stream -->
	<td>Playlist</td>
	<td>Comment</td>
	<td class="icon"></td><!-- optional delete -->
	<td class="icon"></td>
	<td class="space"></td>
</tr>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=new\');" onMouseOver="return overlib(\'Play random tracks\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;random=new&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream random tracks\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i></a>'; ?></td>
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=new\');" onMouseOver="return overlib(\'Play random tracks\');" onMouseOut="return nd();">Random tracks</a>';
						elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;random=new&amp;stream_id=' . $cfg['stream_id']  . '" onMouseOver="return overlib(\'Stream random tracks\');" onMouseOut="return nd();">Random tracks</a>'; 
						else echo 'Random tracks'; ?></td>
	<td>Play random tracks from library</td>
	<td></td>
	<td><?php if ($cfg['access_media']) echo '<a href="genre.php?action=blacklist" onMouseOver="return overlib(\'Edit random blacklist\');" onMouseOut="return nd();"><i class="fa fa-pencil fa-fw icon-small"></i></a>'; ?></td>
	<td></td>
</tr>
<?php
	$query = mysql_query('SELECT name, comment, stream, favorite_id FROM favorite WHERE 1 ORDER BY stream, name, comment');
	while ($favorite = mysql_fetch_assoc($query)) {
		if ($previous_stream != $favorite['stream'] && $i > 0) {
			$i = 0;
			//echo '<tr class="line"><td colspan="8"></td></tr>' . "\n";
			echo '<tr class="header">
			<td></td>
			<td class="icon"></td>
			<td class="icon"></td>
			<td>Stream</td>
			<td colspan="4">Comment</td>
			</tr>' . "\n";
			//echo '<tr class="line"><td colspan="11"></td></tr>' . "\n";
		} ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;favorite_id=' . $favorite['favorite_id'] . '&amp;menu=favorite\');" onMouseOver="return overlib(\'Play\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;favorite_id=' . $favorite['favorite_id'] . ($favorite['stream'] == false ? '&amp;stream_id=' . $cfg['stream_id'] : '') . '" onMouseOver="return overlib(\'Stream\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i></a>'; ?></td>
	<td><?php if ($cfg['access_play'])								echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;favorite_id=' . $favorite['favorite_id'] . '&amp;menu=favorite\');" onMouseOver="return overlib(\'Play\');" onMouseOut="return nd();">' . html($favorite['name']) . '</a>';
			elseif (!$cfg['access_play'] && $cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;favorite_id=' . $favorite['favorite_id'] . ($favorite['stream'] == false ? '&amp;stream_id=' . $cfg['stream_id'] : '') . '" onMouseOver="return overlib(\'Stream\');" onMouseOut="return nd();">' . html($favorite['name']) . '</a>';
			else 													echo html($favorite['name']); ?></td>
	<td><?php echo bbcode($favorite['comment']); ?></td>
	<td><?php if ($cfg['access_admin']) echo '<a href="favorite.php?action=deleteFavorite&amp;favorite_id=' . $favorite['favorite_id'] . '&amp;sign=' . $cfg['sign'] . '" onClick="return confirm(\'Are you sure you want to delete favorite: ' . addslashes(html($favorite['name'])) . '?\');" onMouseOver="return overlib(\'Delete\');" onMouseOut="return nd();"><i class="fa fa-times-circle fa-fw icon-small"></i></a>'; ?></td>
	<td><?php if ($cfg['access_admin']) echo '<a href="favorite.php?action=editFavorite&amp;favorite_id=' . $favorite['favorite_id'] . '" onMouseOver="return overlib(\'Edit\');" onMouseOut="return nd();"><i class="fa fa-pencil fa-fw icon-small"></i></a>'; ?></td>
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
	
	$query = mysql_query('SELECT name, comment, stream FROM favorite WHERE favorite_id = ' . (int) $favorite_id);
	$favorite = mysql_fetch_assoc($query);
	if ($favorite == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]favorite_id not found in database');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Favorites';
	$nav['url'][]	= 'favorite.php';
	$nav['name'][]	= 'Edit';
	require_once('include/header.inc.php');
	$disabled = ($favorite_id == $cfg['favorite_id'] ? ' disabled' : '');
?>	
<form action="favorite.php" method="post" name="favorite" id="favorite">
	<input type="hidden" name="action" value="saveFavorite">
	<input type="hidden" name="favorite_id" value="<?php echo $favorite_id; ?>">
	<input type="hidden" name="sign" value="<?php echo $cfg['sign']; ?>">
<table cellspacing="0" cellpadding="0" id="favoriteTable">
<tr class="header">
	<td colspan="3">&nbsp;Playlist info:</td>
</tr>
</tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr>
<tr>
	<td id="favoriteTableFirstCol">Name:</td>
	<td class="textspace">&nbsp;</td>
	<td class="fullscreen"><input type="text" name="name" id="name" value="<?php echo html($favorite['name']); ?>" maxlength="255" style="width: 100%;"<?php echo $disabled ;?>></td>
</tr>
<tr>
	<td>Comment:</td>
	<td></td>
	<td><input type="text" name="comment" id="comment" value="<?php echo html($favorite['comment']); ?>" maxlength="255" style="width: 100%;" <?php echo onmouseoverBbcodeReference(); ?> <?php echo $disabled; ?>></td>
</tr>
<tr class="space"><td colspan="3"></td></tr>
<tr>
	<td></td>
	<td></td>
	<td>
	<?php if ($disabled =='') {?>
	<div class="buttons"><span><a href="#" onclick="$('#favorite').submit();">Save</a></span><span><a href="favorite.php">Cancel</a></span></div>
	<?php } ?>
	</td>
</tr>

<tr class="textspace"><td colspan="3"></td></tr>
<tr class="header">
	<td colspan="3">&nbsp;Import (replace) or add to this playlist tracks from:</td>
</tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr>
	<td>current playlist on:</td>
	<td></td>
	<td><a href="config.php?action=playerProfile"><?php echo html($cfg['player_name']); ?></a></td>
</tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr><td colspan="3">or</td></tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr>
	<td>playlist in URL:</td>
	<td></td>
	<td><input type="text" name="url" value="" maxlength="255" style="width: 100%;"></td>
</tr>
<tr class="space"><td colspan="3"></td></tr>		
<tr>
	<td></td>
	<td></td>
	<td>
		<div class="buttons">
		<span><a href="#" onClick="addPlaylist()">Add</a></span>
		<span><a href="#" onClick="importPlaylist();">Import</a></span>
		</div>
	</td>
</tr>
<tr class="textspace"><td colspan="3"></td></tr>
<tr class="header">
	<td colspan="3">&nbsp;Tracks in this playlist:</td>
</tr>		
<tr class="line"><td colspan="9"></td></tr>
<tr>
	<td colspan="3">
	<!-- begin indent -->
<table width="100%" cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="space"></td>
	<td class="icon"></td><!-- optional play -->
	<td<?php if ($cfg['access_play'] && $favorite['stream'] == false) echo' class="space"'; ?>></td>
	<td><?php echo $favorite['stream'] ? 'Stream' : 'Artist' ?></td>
	<td<?php if ($favorite['stream'] == false) echo ' class="textspace"'; ?>></td>
	<td><?php echo $favorite['stream'] ? '' : 'Title' ?></td>
	<td class="space"></td>
	<td></td><!-- delete -->
	<td class="space"></td>
</tr>
<!-- <tr class="line"><td colspan="9"></td></tr> -->
<?php
	$i = 0;
	$query1 = mysql_query('SELECT track_id, stream_url, position FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' ORDER BY position');
	while ($favoriteitem = mysql_fetch_assoc($query1)) {
		if ($favoriteitem['track_id']) {
			$query2	= mysql_query('SELECT artist, title FROM track WHERE track_id = "' . mysql_real_escape_string($favoriteitem['track_id']) . '"');
			$track	= mysql_fetch_assoc($query2);
			$artist	= $track['artist'];
			$title	= $track['title'];
		}
		elseif ($favoriteitem['stream_url']) {
			$artist	= $favoriteitem['stream_url'];
			$title	= '';
		} ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play'] && $favoriteitem['track_id']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $favoriteitem['track_id'] . '&amp;menu=favorite\');" onMouseOver="return overlib(\'Play track\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i></a>'; ?></td>
	<td></td>
	<td><?php echo html($artist); ?></td>
	<td></td>
	<td><?php if ($cfg['access_play'] && $favoriteitem['track_id']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $favoriteitem['track_id'] . '&amp;menu=favorite\');" onMouseOver="return overlib(\'play track\');" onMouseOut="return nd();">' . html($title) . '</a>';
		else echo html($title); ?></td>
	<td></td>
	<td align="right"><a href="favorite.php?action=deleteFavoriteItem&amp;favorite_id=<?php echo $favorite_id; ?>&amp;position=<?php echo $favoriteitem['position']; ?>&amp;sign=<?php echo $cfg['sign']; ?>"><i class="fa fa-times-circle sign"></i></a></td>
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

<script type="text/javascript">
<!--
document.favorite.name.focus();

function importPlaylist() {
	document.favorite.action.value='importPlaylist'; 
	$('#favorite').submit();
}

function addPlaylist() {
	document.favorite.action.value='addPlaylist'; 
	$('#favorite').submit();
}
//-->
</script>

<?php
	require_once('include/footer.inc.php');
}





//  +------------------------------------------------------------------------+
//  | Add favorite                                                           |
//  +------------------------------------------------------------------------+
function addFavorite() {
	global $cfg, $db;
	authenticate('access_admin', false, true, true);
	mysql_query('INSERT INTO favorite (name) VALUES ("")');
	$favorite_id = mysql_insert_id($db);
	
	editFavorite($favorite_id);
}




//  +------------------------------------------------------------------------+
//  | Save favorite                                                          |
//  +------------------------------------------------------------------------+
function saveFavorite($favorite_id) {
	global $db;
	authenticate('access_admin', false, true, true);
	$name	 = getpost('name');
	$comment = getpost('comment');
	mysql_query('UPDATE favorite SET
		name	= "' . mysql_real_escape_string($name) . '",
		comment	= "' . mysql_real_escape_string($comment) . '"
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
	
	$name		= post('name');
	$comment	= post('comment');
	$url		= post('url');
	
	if ($url != '') {
		$file = array();
		
		$items = @file($url, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) or message(__FILE__, __LINE__, 'error', '[b]Failed to open url:[/b][br]' . $url);
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
			mysql_query('DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id);
			$offset = 0;
		}
		
		if ($mode = 'add') {
			$query = mysql_query('SELECT position FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' ORDER BY position DESC');
			$track = mysql_fetch_assoc($query);
			$offset = $track['position'];
		}	
		
		// Update favorite stream status
		mysql_query('UPDATE favorite
					SET stream			= "' . (int) $stream . '"
					WHERE favorite_id	= ' . (int) $favorite_id);
		
		// Don't allow stream_url and track_id in the same playlist!
		if ($stream)	mysql_query('DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' AND track_id != ""');
		else			mysql_query('DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id . ' AND stream_url != ""');
	}
	
			
	for ($i = 0; $i < count($file); $i++) {
		$query = mysql_query('SELECT track_id FROM track WHERE relative_file = "' . mysql_real_escape_string($file[$i]) . '"');
		$track = mysql_fetch_assoc($query);
		
		if ($stream == 0 && $track['track_id']) {
			$position = $i + $offset + 1;
			mysql_query('INSERT INTO favoriteitem (track_id, position, favorite_id)
				VALUES ("' . mysql_real_escape_string($track['track_id']) . '",
				' . (int) $position . ',
				' . (int) $favorite_id . ')');
		}
		
		if ($stream == 1 && preg_match('#^(ftp|http|https|mms|mmst|pnm|rtp|rtsp|sdp)://#', $file[$i])) {
			$position = $i + $offset + 1;
			mysql_query('INSERT INTO favoriteitem (stream_url, position, favorite_id)
				VALUES ("' . mysql_real_escape_string($file[$i]) . '",
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
	mysql_query('DELETE FROM favorite WHERE favorite_id = ' . (int) $favorite_id);
	mysql_query('DELETE FROM favoriteitem WHERE favorite_id = ' . (int) $favorite_id);
	home();
}




//  +------------------------------------------------------------------------+
//  | Delete favorite item                                                   |
//  +------------------------------------------------------------------------+
function deleteFavoriteItem($favorite_id) {
	global $db;
	authenticate('access_admin', false, true, true);
	$position = get('position');
	mysql_query('DELETE FROM favoriteitem
		WHERE favorite_id = ' . (int) $favorite_id . '
		AND position = ' . (int) $position);
	editFavorite($favorite_id);
}


?>