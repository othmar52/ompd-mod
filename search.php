<?php
//  +------------------------------------------------------------------------+
//  | O!MPD, Copyright � 2015 Artur Sierzant		                         |
//  | http://www.ompd.pl                   									 |
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
//  | search.php                                                             |
//  +------------------------------------------------------------------------+
//error_reporting(-1);
//ini_set("display_errors", 1);

require_once('include/initialize.inc.php');

if (cookie('netjukebox_width')<385) {$base_size = 90;}
elseif (cookie('netjukebox_width')<641) {$base_size = 120;}
else {$base_size = 150;}

$base		= (cookie('netjukebox_width') - 20) / ($base_size + 10);
$colombs	= floor($base);
$aval_width = (cookie('netjukebox_width') - 20 - $scroll_bar_correction) - ($colombs - 1) * $spaces;
$size = floor($aval_width / $colombs);


$cfg['menu']		= 'Library';
$action 			= get('action');
$search_string	 	= get('search_string');
$group_found		= 'none';
$match_found		= false;
	
if (strlen($search_string) == 0) {
	message(__FILE__, __LINE__, 'warning', '[b]Empty search string[/b][br]Enter valid string.');
	exit();
}

if (strlen($search_string) < 2) {
	message(__FILE__, __LINE__, 'warning', '[b]Search string too short - min. 2 characters[/b][br][url=index.php][img]small_back.png[/img]Back to previous page[/url]');
	exit();
}

if	($action == 'search_all')			search_all();
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();



//  +------------------------------------------------------------------------+
//  | Search all                                                             |
//  +------------------------------------------------------------------------+
function search_all() {
	global $cfg, $db, $size, $search_string, $group_found, $match_found;
	authenticate('access_media');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][] = 'search for: ' . $search_string;
	require_once('include/header.inc.php');
	
	echo '<script type="text/javascript">';
	echo 'showSpinner();';
	echo '</script>';
	
	@ob_flush();
	flush();
	
	album_artist();
	album_title();
	track_artist();
	filesystem_match();
	track_title();
	
	echo '<script type="text/javascript">';
	//echo 'hideSpinner();';
	if ($group_found != 'none') { echo 'toggleSearchResults("' . $group_found . '")';}
	echo '</script>';
	?>
	<script type="text/javascript">
	function setFavorite(data) {
		if (data.action == "add") {
			$("#favorite_star_" + data.group_type + "-" + data.track_id).removeClass("fa fa-star-o").addClass("fa fa-star");
		}
		else if (data.action == "remove") {
			$("#favorite_star_" + data.group_type + "-" + data.track_id).removeClass("fa fa-star").addClass("fa fa-star-o");
		}
	};
	</script>
	<?php
	if (!$match_found) echo "No match found.";
		require_once('include/footer.inc.php');
};

	
//  +------------------------------------------------------------------------+
//  | album artist                                                           |
//  +------------------------------------------------------------------------+
	
function album_artist() {
	global $cfg, $db, $size, $search_string, $group_found, $match_found;
	
	$query = mysql_query('SELECT artist, artist_alphabetic FROM album WHERE artist_alphabetic like "%' . mysql_real_escape_string($search_string) . '%" OR artist like "%' . mysql_real_escape_string($search_string) . '%" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');	

	$rows = mysql_num_rows($query);
	if ($rows > 0) {
		$match_found = true;
		$group_found = 'AA';
	?>
	<h1 onclick='toggleSearchResults("AA");' class="pointer"><i id="iconSearchResultsAA" class="fa fa-chevron-circle-down icon-anchor"></i> Album artist (<?php if ($rows > 1) {
			echo $rows . " matches found";
		}
		else {
			$album = mysql_fetch_assoc($query);
			echo $rows . " match found: " . $album['artist_alphabetic'];
		}
		?>)
	</h1>
	<div class="search_artist" id="searchResultsAA">
	<?php
	if ($rows > 1) {
		while ($album = mysql_fetch_assoc($query)) {
	?>
	<p>
	<a href="index.php?action=view2&amp;artist=<?php echo rawurlencode($album['artist_alphabetic']); ?>"><?php echo html($album['artist_alphabetic']); ?></a>
	</p>
	<?php
		}
	}
	else {
	
	$query = mysql_query('SELECT * FROM album WHERE artist_alphabetic like "%' . mysql_real_escape_string($search_string) . '%" ORDER BY year');
	
	while ($album = mysql_fetch_assoc($query)) {		
				draw_tile($size,$album);
				
		}
	
	}
	?>
	</div>
	<?php
	} 
}
// End of Album artist


//  +------------------------------------------------------------------------+
//  | track artist                                                           |
//  +------------------------------------------------------------------------+

function track_artist() {
	global $cfg, $db, $size, $search_string, $group_found, $match_found;

	$query = mysql_query('SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, track.number, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id
	WHERE track.artist LIKE "%' . mysql_real_escape_string($search_string) . '%"
	AND track.artist <> album.artist AND album.artist NOT LIKE "%' . mysql_real_escape_string($search_string) . '%" 
	GROUP BY track.artist');
	
	$rows = mysql_num_rows($query);
	
	if ($rows > 0) {
		$match_found = true;
		if ($group_found == 'none') $group_found = 'TA';
	?>
	<h1 onclick='toggleSearchResults("TA");' class="pointer"><i id="iconSearchResultsTA" class="fa fa-chevron-circle-down icon-anchor"></i> Track artist (<?php if ($rows > 1) {
				echo $rows . " matches found";
			}
			else {
				$album = mysql_fetch_assoc($query);
				echo $rows . " match found: " . $album['track_artist'];
			}
			?>)
	</h1>
	<div id="searchResultsTA">
	<table cellspacing="0" cellpadding="0" class="border">
	<tr class="header">
		<td class="icon"></td><!-- track menu -->
		<td class="icon"></td><!-- add track -->
		<td class="track-list-artist">Track artist&nbsp;</td>
		<td>Title&nbsp;</td>
		<td>Album&nbsp;</td>
		<td></td>
		<td align="right" class="time">Time</td>
		<td class="space right"></td>
	</tr>

	<?php
	$i=0;
	
	/* 
	$query = mysql_query('SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, track.number, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id
	WHERE track.artist LIKE "%' . mysql_real_escape_string($search_string) . '%"
	AND track.artist <> album.artist
	AND album.artist NOT LIKE "%' . mysql_real_escape_string($search_string) . '%"
	ORDER BY track.artist, album.album, track.title');
	 */
	$query = mysql_query('SELECT a.*, favoriteitem.favorite_id FROM
	(SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id as tid, track.miliseconds, track.number, track.relative_file, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id
	WHERE track.artist LIKE "%' . mysql_real_escape_string($search_string) . '%"
	AND track.artist <> album.artist
	AND album.artist NOT LIKE "%' . mysql_real_escape_string($search_string) . '%"
	ORDER BY track.artist, album.album, track.title) as a
	LEFT JOIN favoriteitem ON favoriteitem.track_id = a.tid
	ORDER BY a.track_artist
	');
	
	while ($track = mysql_fetch_assoc($query)) { ?>
	<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
		<td class="icon">
		<span id="menu-track<?php echo $i ?>">
		<div onclick='toggleMenuSub(<?php echo $i ?>);'>
			<i id="menu-icon<?php echo $i ?>" class="fa fa-bars icon-small"></i>
		</div>
		</span>
		</td>
		
		<td class="icon">
		<span>
		<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>';?>
		</span>
		</td>
			
		<td class="track-list-artist"><?php if (mysql_num_rows(mysql_query('SELECT track_id FROM track WHERE track.artist="' . mysql_real_escape_string($track['track_artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['track_artist']) . '&amp;order=year">' . html($track['track_artist']) . '</a>'; else echo html($track['track_artist']); ?></td>
		
		<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
				elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Add track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
				elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['tid'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
				else 							echo html($track['title']); ?>
		<span class="track-list-artist-narrow">by <?php echo html($track['track_artist']); ?></span> 
		</td>
		<td><a href="index.php?action=view3&amp;album_id=<?php echo $track['album_id']; ?>" <?php echo onmouseoverImage($track['image_id']); ?>><?php echo html($track['album']); ?></a></td>
		<td onclick="
		var action = '';
		if ($('#favorite_star_TA-<?php echo $track['tid'] ?>').attr('class') == 'fa fa-star-o') {
			action = 'add';
			}
		else {
			action = 'remove';
		}
		ajaxRequest('ajax-favorite.php?action=' + action + '&track_id=<?php echo $track['tid'] ?>&group_type=TA', setFavorite);
	" class="pl-favorites"><i class="fa fa-star<?php if (($track['favorite_id']) != $cfg['favorite_id']) echo '-o'?>" id="favorite_star_TA-<?php echo $track['tid'] ?>"></i></td>
		<td align="right"><?php echo formattedTime($track['miliseconds']); ?></td>
		<td></td>
	</tr>

	<tr class="line">
		<td></td>
		<td colspan="16"></td>
	</tr>

	<tr>
	<td colspan="20">
	<div class="menuSub" id="menu-sub-track<?php echo $i ?>" onclick='offMenuSub(<?php echo $i ?>);'> 
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Insert after currently playing track and play</a>'; ?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Insert track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-indent fa-fw icon-small"></i>Insert after currently playing track</a>';?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i>Add track to playlist</a>';?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Remove all from playlist and play track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['tid'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i>Stream track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_download']) echo '<a href="download.php?action=downloadTrack&amp;track_id=' . $track['tid'] .'&amp;download_id=' . $cfg['download_id'] . '" ' . onmouseoverDownloadTrack($track['tid']) . '><i class="fa fa-download fa-fw icon-small"></i>Download track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="getid3/demos/demo.browse.php?filename='. $cfg['media_dir'] . urlencode($track['relative_file']) . '" onClick="showSpinner();"><i class="fa fa-info-circle fa-fw icon-small"></i>File details</a>'; ?>
	</div>
	
	</div>
	</td>
	</tr>

	<?php
		}
		echo "</table>";
		echo "</div>";
	}
};



//End of Track artist	
	
//  +------------------------------------------------------------------------+
//  | album title                                                            |
//  +------------------------------------------------------------------------+

function album_title() {
	global $cfg, $db, $size, $search_string, $group_found, $match_found;
	
	$query = mysql_query('SELECT album_id, image_id, album, artist_alphabetic FROM album WHERE album like "%' . mysql_real_escape_string($search_string) . '%" ORDER BY artist_alphabetic');

	$rows = mysql_num_rows($query);
	if ($rows > 0) {
		$match_found = true;
		if ($group_found == 'none') $group_found = 'AT';
	?>
	<h1 onclick='toggleSearchResults("AT");' class="pointer"><i id="iconSearchResultsAT" class="fa fa-chevron-circle-down icon-anchor"></i> Album title (<?php if ($rows > 1) {
			echo $rows . " matches found";
		}
		else {
			$album = mysql_fetch_assoc($query);
			echo $rows . " match found: " . $album['album'];
		}
		?>)
	</h1>
	
	<div class="search_artist" id="searchResultsAT">
	<?php
	
	
	$query = mysql_query('SELECT album_id, image_id, album, artist_alphabetic FROM album WHERE album like "%' . mysql_real_escape_string($search_string) . '%" ORDER BY artist_alphabetic');
	
	while ($album = mysql_fetch_assoc($query)) {		
				draw_tile($size,$album);
				
		}
	
	
	?>
	</div>
	<?php
	} 
}
// End of Album title



	
//  +------------------------------------------------------------------------+
//  | filesystem match                                                          |
//  +------------------------------------------------------------------------+
	
function filesystem_match() {
	global $cfg, $db, $size, $search_string, $group_found, $match_found;
	
	$query = mysql_query('
		SELECT album_id,relative_file AS path
		FROM track
		WHERE relative_file LIKE "%' . mysql_real_escape_string($search_string) . '%"
		GROUP BY album_id
		ORDER BY path
		LIMIT 1000'
	);	

	$rows = mysql_num_rows($query);
	if ($rows > 0) {
		$match_found = true;
		$group_found = 'DD';
	?>
	<h1 onclick='toggleSearchResults("DD");' class="pointer">
		<i id="iconSearchResultsDD" class="fa fa-chevron-circle-down icon-anchor"></i> Path (<?php if ($rows > 1) {
			echo $rows . " matches found";
		}
		else {
			echo $rows . " match found";
		}
		?>)
	</h1>
	<div class="search_artist" id="searchResultsDD">
	<?php
	if ($rows > 0) {
		while ($album = mysql_fetch_assoc($query)) {
	?>
	<p>
	<a href="index.php?action=view3&amp;album_id=<?php echo rawurlencode($album['album_id']); ?>"><?php echo html(basename(dirname($album['path']))); ?></a>
	</p>
	<?php
		}
	}
	?>
	</div>
	<?php
	} 
}
// End of filesystem match




	
//  +------------------------------------------------------------------------+
//  | track title                                                            |
//  +------------------------------------------------------------------------+	

function track_title() {
	global $cfg, $db, $size, $search_string, $group_found, $match_found;
	
	//$query = mysql_query('SELECT track.artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, album.image_id, album.album FROM track, album ' . $filter_query . ' ' . $order_query);

	$query = mysql_query('SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, track.number, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id
	WHERE track.title LIKE "%' . mysql_real_escape_string($search_string) . '%"');
	
	
	/* $query = mysql_query('SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, track.number, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id
	WHERE track.title LIKE "%' . mysql_real_escape_string($search_string) . '%"
	ORDER BY track.artist, track.title'); */
	
	$rows = mysql_num_rows($query);
	
	if ($rows > 0) {
		$match_found = true;
		if ($group_found == 'none') $group_found = 'TT';
?>
<h1 onclick='toggleSearchResults("TT");' class="pointer"><i id="iconSearchResultsTT" class="fa fa-chevron-circle-down icon-anchor"></i> Track title (<?php if ($rows > 1) {
			echo $rows . " matches found";
		}
		else {
			$album = mysql_fetch_assoc($query);
			echo $rows . " match found: " . $album['track_artist'];
		}
		?>)
</h1>
<div id="searchResultsTT">
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="icon"></td><!-- track menu -->
	<td class="icon"></td><!-- add track -->
	<td class="track-list-artist">Track artist&nbsp;</td>
	<td>Title&nbsp;</td>
	<td>Album&nbsp;</td>
	<td></td>
	<td align="right" class="time">Time</td>
	<td class="space right"></td>
</tr>

<?php
	$i=0;
	/* 
	$query = mysql_query('SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, track.number, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id
	WHERE track.title LIKE "%' . mysql_real_escape_string($search_string) . '%"
	ORDER BY track.artist, track.title');
	 */
	 
	$query = mysql_query('SELECT * FROM 
	(SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id as tid, track.miliseconds, track.number, track.relative_file, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id
	WHERE track.title LIKE "%' . mysql_real_escape_string($search_string) . '%") as a
	LEFT JOIN 
	(SELECT track_id, favorite_id FROM favoriteitem WHERE favorite_id = "' . $cfg['favorite_id'] . '") as b ON b.track_id = a.tid
	ORDER BY a.title, a.artist, a.album');
	
	while ($track = mysql_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td class="icon">
	<span id="menu-track<?php echo ($i +  10000)?>">
	<div onclick='toggleMenuSub(<?php echo ($i + 10000)?>);'>
		<i id="menu-icon<?php echo ($i + 10000) ?>" class="fa fa-bars icon-small"></i>
	</div>
	</span>
	</td>
	
	<td class="icon">
	<span>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>';?>
	</span>
	</td>
		
	<td class="track-list-artist"><?php if (mysql_num_rows(mysql_query('SELECT track_id FROM track WHERE track.artist="' . mysql_real_escape_string($track['track_artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['track_artist']) . '&amp;order=year">' . html($track['track_artist']) . '</a>'; else echo html($track['track_artist']); ?></td>
	
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Add track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['tid'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?>
	<span class="track-list-artist-narrow">by <?php echo html($track['track_artist']); ?></span> 
	</td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $track['album_id']; ?>" <?php echo onmouseoverImage($track['image_id']); ?>><?php echo html($track['album']); ?></a></td>
	<td onclick="
		var action = '';
		if ($('#favorite_star_TT-<?php echo $track['tid'] ?>').attr('class') == 'fa fa-star-o') {
			action = 'add';
			}
		else {
			action = 'remove';
		}
		ajaxRequest('ajax-favorite.php?action=' + action + '&track_id=<?php echo $track['tid'] ?>&group_type=TT', setFavorite);
	" class="pl-favorites"><i class="fa fa-star<?php if (($track['favorite_id']) != $cfg['favorite_id']) echo '-o'?>" id="favorite_star_TT-<?php echo $track['tid'] ?>"></i></td>
	<td align="right"><?php echo formattedTime($track['miliseconds']); ?></td>
	<td></td>
</tr>

<tr class="line">
	<td></td>
	<td colspan="16"></td>
</tr>

<tr>
<td colspan="20">
<div class="menuSub" id="menu-sub-track<?php echo ($i + 10000) ?>" onclick='offMenuSub(<?php echo ($i + 10000) ?>);'> 
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Insert after currently playing track and play</a>'; ?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Insert track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-indent fa-fw icon-small"></i>Insert after currently playing track</a>';?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i>Add track to playlist</a>';?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['tid'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Remove all from playlist and play track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['tid'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i>Stream track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_download']) echo '<a href="download.php?action=downloadTrack&amp;track_id=' . $track['tid'] .'&amp;download_id=' . $cfg['download_id'] . '" ' . onmouseoverDownloadTrack($track['tid']) . '><i class="fa fa-download fa-fw icon-small"></i>Download track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="getid3/demos/demo.browse.php?filename='. $cfg['media_dir'] . urlencode($track['relative_file']) . '" onClick="showSpinner();"><i class="fa fa-info-circle fa-fw icon-small"></i>File details</a>'; ?>
	</div>
</div>
</td>
</tr>

<?php
	}
	echo "</table>";
	echo "</div>";
	}
}
//End of Track title	
?>
