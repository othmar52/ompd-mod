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
//  | index.php                                                              |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');

$cfg['menu']	= 'media';
$action 		= @$_GET['action'];

if		($action == '')					home();
elseif	($action == 'view1')			view1();
elseif	($action == 'view2')			view2();
elseif	($action == 'view3')			view3();
elseif	($action == 'view1all')			view1all();
elseif	($action == 'view3all')			view3all();
elseif	($action == 'viewRandomAlbum')	viewRandomAlbum();
elseif	($action == 'viewRandomTrack')	viewRandomTrack();
elseif	($action == 'viewYear')			viewYear();
elseif	($action == 'viewPopular')		viewPopular();
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Download album title                                                   |
//  +------------------------------------------------------------------------+
function downloadAlbumTitle($album_id) {
	global $cfg, $db;
	
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
	if (mysqli_num_rows($query) == 1) {
		// By one track return downloadTrackTitle() info
		$track = mysqli_fetch_assoc($query);
		return downloadTrackTitle($track['track_id']);
	}
	while($track = mysqli_fetch_assoc($query)) {
		if (in_array($track['extension'], $extensions) == false)
			$extensions[] = $track['extension'];
		$transcode_track = false;
		if (sourceFile($track['extension'], $track['audio_bitrate'], $cfg['download_id']) == false) {
			$transcode_track	= true;
			$transcode			= true;
		}
		if ($track['cache_filesize'])
			$filesize += $track['cache_filesize'];
		elseif ($transcode_track) {
			$filesize += $cfg['tag_padding'][$cfg['download_id']];
			$filesize += round($cfg['encode_bitrate'][$cfg['download_id']] * $track['miliseconds'] / 8 / 1000);
			$exact = false;
		}
		else
			$filesize += $track['filesize'];
	}
	
	sort($extensions);
	$source = implode($extensions, ', ');
	
	if ($exact)	$list = '<strong>' . formattedSize($filesize) . '</strong>';
	else		$list = '<strong>' . html_entity_decode('&plusmn; ', null, NJB_DEFAULT_CHARSET) . formattedSize($filesize) . '</strong>';
	
	$list .= '<hr>';
	if ($transcode && count($extensions) == 1)		$list .= $cfg['encode_name'][$cfg['download_id']] . ' (' . $source . ' source)';
	elseif ($transcode && count($extensions) > 5)	$list .= $cfg['encode_name'][$cfg['download_id']] . ' (mixed source)';
	elseif ($transcode)								$list .= $cfg['encode_name'][$cfg['download_id']] . '<br>(' . $source . ' source)';
	else 											$list .= $source;
	$list .= '<hr>';
	
	if ($transcode && $exact)		$list .= 'Transcoded:<img src="' . $cfg['img'] . 'tiny_check.png" alt="" class="tiny"><br>';
	elseif ($transcode && !$exact)	$list .= 'Transcoded:<img src="' . $cfg['img'] . 'tiny_uncheck.png" alt="" class="tiny"><br>';
	else							$list .= 'Source:<img src="' . $cfg['img'] . 'tiny_check.png" alt="" class="tiny"><br>';
	
	return 'title="' . html($list) . '"';
}




//  +------------------------------------------------------------------------+
//  | Download track title                                                   |
//  +------------------------------------------------------------------------+
function downloadTrackTitle($track_id) {
	global $cfg, $db;
	$query = mysqli_query($db, 'SELECT
		LOWER(SUBSTRING_INDEX(relative_file, ".", -1)) AS extension,
		relative_file,
		miliseconds,
		filesize,
		audio_bitrate, audio_dataformat, audio_encoder, audio_profile, audio_bits_per_sample, audio_sample_rate, audio_channels,
		video_codec, video_resolution_x, video_resolution_y, video_framerate
		FROM track
		WHERE track_id = "' . mysqli_real_escape_string($db, $track_id) . '"');
	$track = mysqli_fetch_assoc($query);
	
	if (sourceFile($track['extension'], $track['audio_bitrate'], $cfg['download_id']))	$transcode = false;
	else																				$transcode = true;
	
	$list = '';
	if ($transcode) {
		$query = mysqli_query($db, 'SELECT filesize
			FROM cache 
			WHERE id		= "' . mysqli_real_escape_string($db, $track_id) . '"
			AND  profile	= "' . mysqli_real_escape_string($db, $cfg['download_id']) . '"');
		if ($cache = mysqli_fetch_assoc($query)) {
			$list .= '<strong>' . formattedSize($cache['filesize']) . '</strong>';
			$list .= '<hr>';
			$list .= $cfg['encode_name'][$cfg['download_id']];
			$list .= ' (' . $track['extension'] . ' source)';
			$list .= '<hr>';
			$list .= 'Transcoded:<img src="' . $cfg['img'] . 'tiny_check.png" alt="" class="tiny">';
		}
		else {
			$list .= '<strong>' . html_entity_decode('&plusmn; ', null, NJB_DEFAULT_CHARSET);
			$list .=  formattedSize($cfg['tag_padding'][$cfg['download_id']] + $cfg['encode_bitrate'][$cfg['download_id']] * $track['miliseconds'] / 8 / 1000) . '</strong>';
			$list .= '<hr>';
			$list .= $cfg['encode_name'][$cfg['download_id']];
			$list .= ' (' . $track['extension'] . ' source)';
			$list .= '<hr>';
			$list .= 'Transcoded:<img src="' . $cfg['img'] . 'tiny_uncheck.png" alt="" class="tiny">';
		}
	}
	else { // transcode == false
		$list .= '<strong>' . formattedSize($track['filesize']) . '</strong>';
	
		if ($track['video_codec']) {
			$list .= '<hr>';
			$list .= $track['video_codec'] . '<br>';
			$list .= $track['video_resolution_x'] . 'x';
			$list .= $track['video_resolution_y'] . '<br>';
			$list .= $track['video_framerate'] . ' fps';
		}
		
		if ($track['audio_dataformat']) {
			$list .= '<hr>';
			$list .= $track['audio_dataformat'] . '<br>';
			$list .= $track['audio_encoder'] . '<br>';
			$list .= $track['audio_profile'] . '<br>';
			if		($track['audio_channels'] == 1)	$channels = 'Mono';
			elseif	($track['audio_channels'] == 2)	$channels = 'Stereo';
			else									$channels = $track['audio_channels'] . ' Channels';
			$list .= $track['audio_bits_per_sample'] . ' bit | ' . $channels . ' | ' . formattedFrequency($track['audio_sample_rate']);
		}
		
		$list .= '<hr>';
		$list .= 'Source:<img src="' . $cfg['img'] . 'tiny_check.png" alt="" class="tiny">';
		
		if (!$track['video_codec'] && !$track['audio_dataformat']) {
			$list .= '<hr>';
			$list .= '-';
		}
	}
	
	return 'title="' . html($list) . '"';
}




//  +------------------------------------------------------------------------+
//  | View cover title                                                       |
//  +------------------------------------------------------------------------+
function viewCoverTitle($album_id) {
	global $cfg, $db;
	$query	= mysqli_query($db, 'SELECT image_front_width * image_front_height AS front_resolution, image_back
		FROM bitmap
		WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$bitmap = mysqli_fetch_assoc($query);
	$list = 'Front cover:<img src="' . $cfg['img'] . 'tiny_' . ($bitmap['front_resolution'] >= $cfg['image_front_cover_treshold'] ? 'check' : 'uncheck') . '.png" alt="" class="tiny"><br>';
	$list .= 'Back cover:<img src="' . $cfg['img'] . 'tiny_' . ($bitmap['image_back'] ? 'check' : 'uncheck') . '.png" alt="" class="tiny">';
	
	return 'title="' . html($list) . '"';
}



//  +------------------------------------------------------------------------+
//  | Genre navigator                                                        |
//  +------------------------------------------------------------------------+
function genreNavigator($genre_id) {
	global $cfg, $db, $nav;
	
	if ($genre_id) {
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Media';
		$nav['class'][]	= 'nav';
		$nav['url'][]	= 'index.php';
	}
	else {
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Media';
		$nav['class'][]	= 'nav';
		$nav['url'][]	= '';
		
		if ($cfg['access_play'] || $cfg['access_add']) {
			navPlayerProfile();
			if (@$_GET['navigator'] == 'selectPlayerProfile') {
				require_once('include/header.inc.php');
				return;
			}
		}
	}
	
	for ($i = 1; $i < strlen($genre_id); $i++) {
		$search	= substr($genre_id, 0, $i);
		$query	= mysqli_query($db, 'SELECT genre, genre_id
			FROM genre
			WHERE genre_id = "' . mysqli_real_escape_string($db, $search) . '"
			ORDER BY genre');
		$genre	= mysqli_fetch_assoc($query);
		if ($genre) {
			$nav['name'][]	= $genre['genre'];
			$nav['class'][]	= 'nav';
			$nav['url'][]	= 'index.php?action=view1&amp;genre_id=' . $search;
		}
	}
	$query = mysqli_query($db, 'SELECT genre, genre_id
		FROM genre
		WHERE genre_id = "' . mysqli_real_escape_string($db, $genre_id) . '"
		ORDER BY genre');
	$genre = mysqli_fetch_assoc($query);
	if (substr($genre_id, -1) == '~') {
		$nav['name'][]	= 'Other';
		$nav['class'][]	= 'nav';
		$nav['url'][]	= '';
	}
	if ($genre['genre']) {
		$nav['name'][]	= $genre['genre'];
		$nav['class'][]	= 'nav';
		$nav['url'][]	= '';
	}
	$nav['open'] = true;
	
	// Genre suggest
	$query = mysqli_query($db, 'SELECT genre, genre_id
		FROM genre
		WHERE genre_id LIKE "' . mysqli_real_escape_like($db, $genre_id) . '_"
		ORDER BY genre');
	if (mysqli_num_rows($query) > 0) {
		while ($genre = mysqli_fetch_assoc($query)) {
			$nav['name'][]	= $genre['genre'];
			$nav['class'][]	= 'suggest';
			$nav['url'][]	= 'index.php?action=view1&amp;genre_id=' . $genre['genre_id'];
		}
		$query = mysqli_query($db, 'SELECT genre.genre_id FROM genre, album
			WHERE album.genre_id = "' . mysqli_real_escape_string($db, $genre_id) . '"
			AND genre.genre_id LIKE "' . mysqli_real_escape_like($db, $genre_id) . '_"');
		if (mysqli_fetch_row($query)) {
			$nav['name'][]	= 'Other';
			$nav['class'][]	= 'suggest';
			$nav['url'][]	= 'index.php?action=view1&amp;genre_id=' . $genre_id . '~';
		}
	}
	require_once('include/header.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Home                                                                   |
//  +------------------------------------------------------------------------+
function home() {
	global $cfg, $db;
	authenticate('access_media');
	genreNavigator('');
?>
<table><tr><td><!-- table tab wrapper -->
<ul id="tab">
	<li id="albumartist" class="tab on" onclick="selectTab(this)">Album Artist</li>
	<li id="trackartist" class="tab off" onclick="selectTab(this)">Track Artist</li>
	<li id="tracktitle" class="tab off" onclick="selectTab(this)">Title</li>
</ul>
<table class="tab">
<tr class="space"><td colspan="3"></td></tr>
<tr>
	<td class="space"></td>
	<td>
	<form action="index.php" id="searchform" autocomplete="off">
		<input type="hidden" name="action" value="view1">
		<input type="hidden" name="filter" value="smart">
		<input type="text" name="undefined" id="txt" maxlength="255" class="autosugest" onkeydown="searchformKeyStroke(event)" onkeyup="ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(this.value),evaluateSuggest)">
	</form>
	</td>
	<td class="space"></td>
</tr>
<tr class="space" id="suggestspace" style="display: none;"><td colspan="3"></td></tr>
<tr class="vertical-align-top">
	<td></td>
	<td><span id="suggest"></span></td>
	<td></td>
</tr>
<tr class="space"><td colspan="3"></td></tr>
</table>
</td></tr></table><!-- table tab wrapper -->

<script type="text/javascript">
var baseUrl = 'json.php?action=suggestAlbumArtist&artist=';

	
function initialize() {
	searchform.txt.name = 'artist';
	searchform.txt.focus();
	evaluateSuggest('');
}


function evaluateSuggest(list) {
	var suggest;
	if (list == '') {
		document.getElementById('suggestspace').style.display =  'none';
		suggest = '';
	}
	else {
		document.getElementById('suggestspace').style.display =  'block';
		suggest = '<form action="" name="suggest" id="suggest">';
		suggest += '<select name="txt" onsubmit="suggestKeyStroke(1)" onclick="suggestKeyStroke(1)" onkeydown="return suggestKeyStroke(event)" size="' + ((list.length < 10) ? list.length : 10) + '" class="autosugest">';
		for (var i in list)
			suggest += '<option value="' + list[i].split('"').join('&quot;') + '">' + list[i] + '<\/option>';
		suggest += '<\/select><\/form>';
	}
	document.getElementById('suggest').innerHTML = suggest;
}


function searchformKeyStroke(e) {
	var keyPressed;
	if (typeof e.keyCode != 'undefined') 	keyPressed = e.keyCode;
	else if (typeof e.which != 'undefined')	keyPressed = e.which;
	if (keyPressed == 40 && typeof document.suggest == 'object') // Down key
		document.suggest.txt.focus();
}


function suggestKeyStroke(e) {
	var keyPressed;
	if (e == 1)									keyPressed = 13;
	else if (typeof e.keyCode != 'undefined')	keyPressed = e.keyCode;
	else if (typeof e.which != 'undefined')		keyPressed = e.which;
	if (keyPressed == 13 && document.suggest.txt.value != '') { // Enter key
		if (searchform.action.value == 'view1all')
			searchform.action.value = 'view3all';
		searchform.txt.value = document.suggest.txt.value;
		searchform.filter.value = 'exact';
		searchform.submit();
	}
	else if (keyPressed == 38 && document.suggest.txt.selectedIndex == 0) { // Up key
		document.suggest.txt.selectedIndex = -1;
		searchform.txt.focus();
	}
}
	

function selectTab(obj) {
	if (obj.id == 'albumartist') {
		document.getElementById('albumartist').className = 'tab on';
		document.getElementById('trackartist').className = 'tab off';
		document.getElementById('tracktitle').className  = 'tab off';
		searchform.txt.select();
		searchform.txt.focus();
		searchform.txt.name = 'artist';
		searchform.action.value = 'view1';
		baseUrl = 'json.php?action=suggestAlbumArtist&artist=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(searchform.txt.value),evaluateSuggest);
	}
	else if (obj.id == 'trackartist') {
		document.getElementById('albumartist').className = 'tab off';
		document.getElementById('trackartist').className = 'tab on';
		document.getElementById('tracktitle').className  = 'tab off';
		searchform.txt.select();
		searchform.txt.focus();
		searchform.txt.name = 'artist';
		searchform.action.value = 'view1all';
		baseUrl = 'json.php?action=suggestTrackArtist&artist=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(searchform.txt.value),evaluateSuggest);
	}
	else if (obj.id == 'tracktitle') {
		document.getElementById('albumartist').className = 'tab off';
		document.getElementById('trackartist').className = 'tab off';
		document.getElementById('tracktitle').className  = 'tab on';
		searchform.txt.select();
		searchform.txt.focus();
		searchform.txt.name = 'title';
		searchform.action.value = 'view3all';
		baseUrl = 'json.php?action=suggestTrackTitle&title=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(searchform.txt.value),evaluateSuggest);
	}
}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 1                                                                 |
//  +------------------------------------------------------------------------+
function view1() {
	global $cfg, $db;
	authenticate('access_media');
	
	$artist	 	= @$_GET['artist'];
	$genre_id 	= @$_GET['genre_id'];
	$filter  	= @$_GET['filter'];
	
	if ($genre_id) {
		if (substr($genre_id, -1) == '~') {
			$query = mysqli_query($db, 'SELECT artist_alphabetic
				FROM album
				WHERE genre_id = "' . mysqli_real_escape_string($db, substr($genre_id, 0, -1)) . '"
				GROUP BY artist_alphabetic
				ORDER BY artist_alphabetic');
		}
		else {
			$query = mysqli_query($db, 'SELECT artist_alphabetic
				FROM album
				WHERE genre_id LIKE "' . mysqli_real_escape_like($db, $genre_id) . '%"
				GROUP BY artist_alphabetic
				ORDER BY artist_alphabetic');
		}
		
		if (mysqli_num_rows($query) == 1) {
			view2();
			exit();
		}
		
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;genre_id=' . rawurlencode($genre_id) . '&amp;order=artist';
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;genre_id=' . rawurlencode($genre_id) . '&amp;order=artist';
		
		genreNavigator($genre_id);
		}
	else {
		if ($filter == 'all' || $artist == '') {
			$filter = 'all';
			$artist = '';
			// Navigator
			$nav			= array();
			$nav['name'][]	= 'Media';
			$nav['url'][]	= 'index.php';
			$nav['name'][]	= 'All album artists';
		}
		elseif ($filter == 'start') {
			$artist			= strtolower($artist[0]);
			$artist			= ($artist >= 'a' && $artist <= 'z') ? $artist : '#';
			// Navigator
			$nav			= array();
			$nav['name'][]	= 'Media';
			$nav['url'][]	= 'index.php';
			$nav['name'][]	= $artist;
			$nav['url'][]	= '';
		}		
		else {
			// Navigator
			$nav			= array();
			$nav['name'][]	= 'Media';
			$nav['url'][]	= 'index.php';
			$nav['name'][]	= $artist;
		}
		
		$query = '';
		if ($filter == 'all')			$query = mysqli_query($db, 'SELECT artist_alphabetic FROM album WHERE 1 GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		elseif ($filter == 'exact')		$query = mysqli_query($db, 'SELECT artist_alphabetic FROM album WHERE artist_alphabetic = "' . mysqli_real_escape_string($db, $artist) . '" OR artist = "' . mysqli_real_escape_string($db, $artist) . '" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		elseif ($filter == 'smart')		$query = mysqli_query($db, 'SELECT artist_alphabetic FROM album WHERE artist_alphabetic LIKE "%' . mysqli_real_escape_like($db, $artist) . '%" OR artist LIKE "%' . mysqli_real_escape_like($db, $artist) . '%" OR artist SOUNDS LIKE "' . mysqli_real_escape_string($db, $artist) . '" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		
		elseif ($filter == 'start' && $artist == '#')	$query = mysqli_query($db, 'SELECT artist_alphabetic FROM album WHERE artist_alphabetic  NOT BETWEEN "a" AND "zzzzzzzz" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		elseif ($filter == 'start')		$query = mysqli_query($db, 'SELECT artist_alphabetic FROM album WHERE artist_alphabetic LIKE "' . mysqli_real_escape_like($db, $artist) . '%" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		else							message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
		
		if (mysqli_num_rows($query) == 1 && $filter != 'start') {
			$album = mysqli_fetch_assoc($query);
			$_GET['artist'] = $album['artist_alphabetic'];
			$_GET['filter'] = 'exact';
			view2();
			exit();
		}
	
		require_once('include/header.inc.php');
		
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;artist=' . rawurlencode($artist) . '&amp;filter=' . $filter . '&amp;order=artist';
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;artist=' . rawurlencode($artist) . '&amp;filter=' . $filter . '&amp;order=artist';
	} ?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Artist</td>
	<td class="text-align-right"><a href="<?php echo $thumbnail_url; ?>"><img src="<?php echo $cfg['img']; ?>small_header_thumbnail.png" alt="" class="small"></a></td>	
	<td class="text-align-right"><a href="<?php echo $list_url; ?>"><img src="<?php echo $cfg['img']; ?>small_header_list.png" alt="" class="small"></a></td>	
</tr>
<?php
	$i = 0;
	while ($album = mysqli_fetch_assoc($query)) {
?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td colspan="2"><a href="index.php?action=view2&amp;artist=<?php echo rawurlencode($album['artist_alphabetic']); ?>"><?php echo html($album['artist_alphabetic']); ?></a></td>
	<td></td>
</tr>
<?php
	}
	echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 2                                                                 |
//  +------------------------------------------------------------------------+
function view2() {
	global $cfg, $db;
	authenticate('access_media');
	
	$artist	 	= @$_GET['artist'];
	$genre_id 	= @$_GET['genre_id'];
	$year		= (int) @$_GET['year'];
	$filter  	= @$_GET['filter']				or $filter = 'exact';
	$thumbnail	= @$_GET['thumbnail']			? 1 : 0;
	$order	 	= @$_GET['order']				or $order = ($year ? 'artist' : (in_array(strtolower($artist), $cfg['no_album_artist']) ? 'album' : 'year'));
	$sort	 	= @$_GET['sort'] == 'desc'		? 'desc' : 'asc';
		
	$sort_added			= 'asc';
	$sort_artist		= 'asc';
	$sort_album			= 'asc';
	$sort_genre			= 'asc';
	$sort_year 			= 'asc';
	
	$order_img_added	= $cfg['img'] . 'small_header_sort.png';
	$order_img_artist	= $cfg['img'] . 'small_header_sort.png';
	$order_img_album	= $cfg['img'] . 'small_header_sort.png';
	$order_img_genre	= $cfg['img'] . 'small_header_sort.png';
	$order_img_year		= $cfg['img'] . 'small_header_sort.png';
	
	$limit_query = '';
	
	if (isset($_GET['thumbnail'])) {
		mysqli_query($db, 'UPDATE session
			SET thumbnail	= ' . (int) $thumbnail . '
			WHERE sid		= BINARY "' . mysqli_real_escape_string($db, $cfg['sid']) . '"');
	}
	else
		$thumbnail = $cfg['thumbnail'];
	
	
	if ($genre_id) {
		genreNavigator($genre_id);
		
		if (substr($genre_id, -1) == '~')	$filter_query = 'WHERE genre_id = "' . mysqli_real_escape_string($db, substr($genre_id, 0, -1)) . '"';
		else								$filter_query = 'WHERE genre_id LIKE "' . mysqli_real_escape_like($db, $genre_id) . '%"';
		
		if ($order == 'added' && $sort == 'asc') {
			$order_query = 'ORDER BY album_add_time';
			$order_img_added = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_added = 'desc';
		}
		elseif ($order == 'added' && $sort == 'desc') {
			$order_query = 'ORDER BY album_add_time DESC';
			$order_img_added = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_added = 'asc';
		}
		elseif ($order == 'artist' && $sort == 'asc') {
			$order_query = 'ORDER BY artist_alphabetic, year, month, album';
			$order_img_artist = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_artist = 'desc';
		}
		elseif ($order == 'artist' && $sort == 'desc') {
			$order_query = 'ORDER BY artist_alphabetic DESC, year DESC, month DESC, album DESC';
			$order_img_artist = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_artist = 'asc';
		}
		elseif ($order == 'album' && $sort == 'asc') {
			$order_query = 'ORDER BY album, artist_alphabetic, year, month';
			$order_img_album = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_album = 'desc';
		}
		elseif ($order == 'album' && $sort == 'desc') {
			$order_query = 'ORDER BY album DESC, artist_alphabetic DESC, year DESC, month DESC';
			$order_img_album = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_album = 'asc';
		}
		elseif ($order == 'genre' && $sort == 'asc') {
			$order_query = 'ORDER BY genre_id, artist_alphabetic, year, month, album';
			$order_img_genre = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_genre = 'desc';
		}
		elseif ($order == 'genre' && $sort == 'desc') {
			$order_query = 'ORDER BY genre_id DESC, artist_alphabetic DESC, year DESC, month DESC, album DESC';
			$order_img_genre = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_genre = 'asc';
		}
		elseif ($order == 'year' && $sort == 'asc') {
			$order_query = 'ORDER BY year, month, artist_alphabetic, album';
			$order_img_year = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_year = 'desc';
		}
		elseif ($order == 'year' && $sort == 'desc') {
			$order_query = 'ORDER BY year DESC, month DESC, artist_alphabetic DESC, album DESC';
			$order_img_year = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_year = 'asc';
		}
		else
			message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]order');
		
		$query			= mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
		$url			= 'index.php?action=view2&amp;genre_id=' . rawurlencode($genre_id);
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;genre_id=' . rawurlencode($genre_id) . '&amp;filter=' . $filter . '&amp;order=' . $order;
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;genre_id=' . rawurlencode($genre_id) . '&amp;filter=' . $filter . '&amp;order=' . $order;
	}
	elseif ($year) {
		// Navigator
		$nav = array();
		$nav['name'][]	= 'Media';
		$nav['class'][]	= 'nav';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= 'Year';
		$nav['class'][]	= 'nav';
		$nav['url'][]	= 'index.php?action=viewYear';
		
		$query = mysqli_query($db, 'SELECT year
			FROM album
			WHERE year < ' . (int) $year . '
			ORDER BY year DESC
			LIMIT 1');
		
		if ($previous = mysqli_fetch_assoc($query)) {
			$nav['name'][] 	= $previous['year'];
			$nav['class'][]	= 'suggest';
			$nav['url'][]	= 'index.php?action=view2&amp;year=' . $previous['year'];
		}
				
		$nav['name'][] 	= $year;
		$nav['class'][]	= 'nav';
		$nav['url'][]	= '';
		
		$query = mysqli_query($db, 'SELECT year
			FROM album
			WHERE year > ' . (int) $year . '
			ORDER BY year
			LIMIT 1');
		
		if ($next = mysqli_fetch_assoc($query)) {
			$nav['url'][]	= 'index.php?action=view2&amp;year=' . $next['year'];
			$nav['class'][]	= 'suggest';
			$nav['name'][] 	= $next['year'];		
		}
				
		require_once('include/header.inc.php');
		
		$filter_query = 'WHERE year = ' . $year;
		
		$url			= 'index.php?action=view2&amp;year=' . $year;
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;year=' . $year . '&amp;order=' . $order . '&amp;sort=' . $sort;
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;year=' . $year . '&amp;order=' . $order . '&amp;sort=' . $sort;
	}
	else {
		if ($filter == 'all' || $artist == '') {
			$filter = 'all';
					
			$query = mysqli_query($db, 'SELECT album_id FROM album');
			$albums = mysqli_affected_rows($db);
			
			$page = (int) @$_GET['page'];
			$pages = ceil($albums / $cfg['page_limit']);
			
			if ($page < 1) {
				header('Location: ' . NJB_HOME_URL . 'index.php?action=view2&thumbnail=' . $thumbnail . '&filter=all&order=' . $order . '&sort=' . $sort . '&page=1');
				exit();
			}
			
			if ($page > $pages) {
				header('Location: ' . NJB_HOME_URL . 'index.php?action=view2&thumbnail=' . $thumbnail . '&filter=all&order=' . $order . '&sort=' . $sort . '&page=' . $pages);
				exit();
			}
					
			// Navigator
			$nav			= array();
			$nav['name'][]	= 'Media';
			$nav['class'][]	= 'nav';
			$nav['url'][]	= 'index.php';
			
			if ($order == 'year') {
				$nav['name'][]	= 'Year';
				$nav['class'][]	= 'nav';
				$nav['url'][]	= 'index.php?action=viewYear';
			}
			elseif ($order == 'added' && $sort == 'desc') {
				$nav['name'][]	= 'New';
				$nav['class'][]	= 'nav';
				$nav['url'][]	= ($page > 1) ? 'index.php?action=view2&amp;thumbnail=' . $thumbnail . '&amp;filter=all&amp;order=' . $order . '&amp;sort=' . $sort . '&amp;page=1' : '';
			}
			else {		
				$nav['name'][]	= 'All albums';
				$nav['class'][]	= 'nav';
				$nav['url'][]	= ($page > 1) ? 'index.php?action=view2&amp;thumbnail=' . $thumbnail . '&amp;filter=all&amp;order=' . $order . '&amp;sort=' . $sort . '&amp;page=1' : '';
			}
						
			if ($page > 1) {
				$nav['name'][]	= 'Previous';
				$nav['class'][]	= 'suggest';
				$nav['url'][]	= 'index.php?action=view2&amp;thumbnail=' . $thumbnail . '&amp;filter=all&amp;order=' . $order . '&amp;sort=' . $sort . '&amp;page=' . ($page - 1);		
			}
			for ($i = 1; $i <= $pages; $i++) {
				$nav['name'][]	= $i;
				$nav['class'][]	= ($i == $page) ? 'nav' : 'suggest';
				$nav['url'][]	= ($i == $page) ? '' : 'index.php?action=view2&amp;thumbnail=' . $thumbnail . '&amp;filter=all&amp;order=' . $order . '&amp;sort=' . $sort . '&amp;page=' . $i;		
			}
			if ($page < $pages) {
				$nav['name'][]	= 'Next';
				$nav['class'][]	= 'suggest';
				$nav['url'][]	= 'index.php?action=view2&amp;thumbnail=' . $thumbnail . '&amp;filter=all&amp;order=' . $order . '&amp;sort=' . $sort . '&amp;page=' . ($page + 1);		
			}
					
			$offset = ($page - 1) * $cfg['page_limit'];
			$limit_query = 'LIMIT ' . (int) $offset . ',' . (int) $cfg['page_limit'];
		}
		elseif ($filter == 'start') {
			$artist			= strtolower($artist[0]);
			$artist			= ($artist >= 'a' && $artist <= 'z') ? $artist : '#';
			// Navigator
			$nav			= array();
			$nav['name'][]	= 'Media';
			$nav['url'][]	= 'index.php';
			$nav['name'][]	= $artist;
			$nav['url'][]	= '';
		}		
		else {
			// Navigator
			$nav			= array();
			$nav['name'][]	= 'Media';
			$nav['class'][]	= 'nav';
			$nav['url'][]	= 'index.php';
			// Navigator genre suggest
			$query = mysqli_query($db, 'SELECT genre, genre.genre_id
				FROM genre, album
				WHERE artist_alphabetic = "' . mysqli_real_escape_like($db, $artist) . '" 
				AND album.genre_id = genre.genre_id
				GROUP BY genre
				ORDER BY genre');
			while ($genre = mysqli_fetch_assoc($query)) {
				$nav['name'][]	= $genre['genre'];
				$nav['class'][]	= 'suggest';
				$nav['url'][]	= 'index.php?action=view1&amp;genre_id=' . $genre['genre_id'];
			}
			$nav['class'][]	= 'nav';
			$nav['name'][]	= $artist;
			$nav['url'][]	= '';
		}
				
		require_once('include/header.inc.php');
		
		if ($filter == 'all')			$filter_query = 'WHERE 1';
		elseif ($filter == 'exact')		$filter_query = 'WHERE (artist_alphabetic = "' . mysqli_real_escape_string($db, $artist) . '" OR artist = "' . mysqli_real_escape_string($db, $artist) . '")';
		elseif ($filter == 'smart')		$filter_query = 'WHERE (artist_alphabetic  LIKE "%' . mysqli_real_escape_like($db, $artist) . '%" OR artist LIKE "%' . mysqli_real_escape_like($db, $artist) . '%" OR artist SOUNDS LIKE "' . mysqli_real_escape_string($db, $artist) . '")';
		elseif ($filter == 'start' && $artist == '#')	$filter_query = 'WHERE (artist_alphabetic  NOT BETWEEN "a" AND "zzzzzzzz")';
		elseif ($filter == 'start')		$filter_query = 'WHERE (artist_alphabetic  LIKE "' . mysqli_real_escape_like($db, $artist) . '%")';
		else							message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
		
		$url			= 'index.php?action=view2&amp;filter=' . $filter;
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;filter=' . $filter . '&amp;order=' . $order . '&amp;sort=' . $sort;
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;filter=' . $filter . '&amp;order=' . $order . '&amp;sort=' . $sort;
		if ($filter != 'all') {
			$url			.= '&amp;artist=' . rawurlencode($artist);
			$list_url		.= '&amp;artist=' . rawurlencode($artist);
			$thumbnail_url	.= '&amp;artist=' . rawurlencode($artist);
		}
		if (isset($page)) {
			$url			.= '&amp;page=' . $page;
			$list_url		.= '&amp;page=' . $page;
			$thumbnail_url	.= '&amp;page=' . $page;
		}
	}
	if ($artist || $year || $filter == 'all') {
		if ($order == 'added' && $sort == 'asc') {
			$order_query = 'ORDER BY album_add_time';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_added = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_added = 'desc';
		}
		elseif ($order == 'added' && $sort == 'desc') {
			$order_query = 'ORDER BY album_add_time DESC';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_added = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_added = 'asc';
		}
		elseif ($order == 'year' && $sort == 'asc') {
			$order_query = 'ORDER BY year, month, artist_alphabetic, album';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_year = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_year = 'desc';
		}
		elseif ($order == 'year' && $sort == 'desc') {
			$order_query = 'ORDER BY year DESC, month DESC, artist_alphabetic DESC, album DESC';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_year = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_year = 'asc';
		}
		elseif ($order == 'album' && $sort == 'asc') {
			$order_query = 'ORDER BY album, artist_alphabetic, year, month';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_album = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_album = 'desc';
		}
		elseif ($order == 'album' && $sort == 'desc') {
			$order_query = 'ORDER BY album DESC, artist_alphabetic DESC, year DESC, month DESC';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_album = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_album = 'asc';
		}
		elseif ($order == 'artist' && $sort == 'asc') {
			$order_query = 'ORDER BY artist_alphabetic, year, month, album';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_artist = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_artist = 'desc';
		}
		elseif ($order == 'artist' && $sort == 'desc') {
			$order_query = 'ORDER BY artist_alphabetic DESC, year DESC, month DESC, album DESC';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, genre_id, album_add_time, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query . ' ' . $limit_query);
			$order_img_artist = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_artist = 'asc';
		}
		elseif ($order == 'genre' && $sort == 'asc') {
			$order_query = 'ORDER BY genre, artist_alphabetic, year, month';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, album.genre_id, album_add_time, image_id, album_id FROM album, genre ' . $filter_query . ' AND album.genre_id = genre.genre_id ' . $order_query . ' ' . $limit_query);
			$order_img_genre = $cfg['img'] . 'small_header_sort_asc.png';
			$sort_genre = 'desc';
		}
		elseif ($order == 'genre' && $sort == 'desc') {
			$order_query = 'ORDER BY genre DESC, artist_alphabetic DESC , year DESC, month DESC';
			$query = mysqli_query($db, 'SELECT album, artist, artist_alphabetic, year, month, album.genre_id, album_add_time, image_id, album_id FROM album, genre ' . $filter_query . ' AND album.genre_id = genre.genre_id ' . $order_query . ' ' . $limit_query);
			$order_img_genre = $cfg['img'] . 'small_header_sort_desc.png';
			$sort_genre = 'asc';
		}
		else message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]order or sort');
	}
	
	
	
//  +------------------------------------------------------------------------+
//  | View 2 - thumbnail mode                                                |
//  +------------------------------------------------------------------------+
	if ($thumbnail) {
	$size = @$_GET['size'];
	if (in_array($size, array('50', '100', '200'))) {
		mysqli_query($db, 'UPDATE session
			SET thumbnail_size	= ' . (int) $size . '
			WHERE sid			= BINARY "' . mysqli_real_escape_string($db, $cfg['sid']) . '"');
	}
	else
		$size = $cfg['thumbnail_size'];
	
	
	$i			= 0;
	$width		= (@$_COOKIE['netjukebox_width']) ? (int) $_COOKIE['netjukebox_width'] : 1024;
	$colombs	= floor(($width - 40) / ($size + 10));
	$sort_url	= $url;
	$size_url	= $url . '&amp;order=' . $order . '&amp;sort=' . $sort;
	?>
<table class="border">
<tr>
	<td colspan="<?php echo $colombs + 2; ?>">
	<!-- begin table header -->
	<table style="width: 100%;">
	<tr class="header">
		<td class="space"></td>
		<td>
			<a href="<?php echo $sort_url; ?>&amp;order=added&amp;sort=<?php echo $sort_added; ?>" class="space">Add<img src="<?php echo $order_img_added; ?>" alt="" class="small"></a><!--
			--><a href="<?php echo $sort_url; ?>&amp;order=artist&amp;sort=<?php echo $sort_artist; ?>" class="space">Artist<img src="<?php echo $order_img_artist; ?>" alt="" class="small"></a><!--
			--><a href="<?php echo $sort_url; ?>&amp;order=album&amp;sort=<?php echo $sort_album; ?>" class="space">Album<img src="<?php echo $order_img_album; ?>" alt="" class="small"></a><!--
			--><a href="<?php echo $sort_url; ?>&amp;order=genre&amp;sort=<?php echo $sort_genre; ?>" class="space">Genre<img src="<?php echo $order_img_genre; ?>" alt="" class="small"></a><!--
			--><a href="<?php echo $sort_url; ?>&amp;order=year&amp;sort=<?php echo $sort_year; ?>">Year<img src="<?php echo $order_img_year; ?>" alt="" class="small"></a>			
		</td>
		<td class="text-align-right">
			<a href="<?php echo $size_url; ?>&amp;size=50"><img src="<?php echo $cfg['img']; ?>small_header_image50_<?php echo ($size == '50') ? 'on' : 'off'; ?>.png" alt="" class="small"></a><!--
			--><a href="<?php echo $size_url; ?>&amp;size=100"><img src="<?php echo $cfg['img']; ?>small_header_image100_<?php echo ($size == '100') ? 'on' : 'off'; ?>.png" alt="" class="small"></a><!--
			--><a href="<?php echo $size_url; ?>&amp;size=200"><img src="<?php echo $cfg['img']; ?>small_header_image200_<?php echo ($size == '200') ? 'on' : 'off'; ?>.png" alt="" class="small"></a><!--
			--><a href="<?php echo $list_url; ?>"><img src="<?php echo $cfg['img']; ?>small_header_list.png" alt="" class="small"></a>
		</td>
	</tr>
	</table>
	<!-- end table header -->
	</td>
</tr>
<tr class="odd smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<?php
	while ($album = mysqli_fetch_assoc($query)) {
		$class = ($i++ & 1) ? 'even' : 'odd';
		echo '<tr class="' . $class . '">'. "\n";
		echo '	<td class="smallspace">&nbsp;</td>' . "\n";
		for ($j = 1; $j <= $colombs; $j++) {
			if ($j != 1) $album = mysqli_fetch_assoc($query);
			if ($album) {
				$genre_id = $album['genre_id'];
				$genre = mysqli_fetch_assoc(mysqli_query($db, 'SELECT genre FROM genre WHERE genre_id = "' . mysqli_real_escape_string($db, $genre_id) . '"')); ?>
	<td >
	<a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>" title="<?php echo html('<strong>' . $album['artist'] . '</strong><br>' . $album['album']); if ($genre['genre'] || $album['year']) echo html('<hr>'); if ($genre['genre']) echo html($genre['genre']) . html('<br>'); echo formattedDate($album['year'], $album['month']); ?>"><img src="image.php?image_id=<?php echo $album['image_id']; ?>" alt="" width="<?php echo $size; ?>" height="<?php echo $size; ?>" class="thumbnail"></a>
	</td>
<?php
			}
			else {
				echo "\t" . '<td><img src="image/dummy.png" alt="" width="' . $size . '" height="' . $size . '" class="thumbnail"></td>' . "\n";
			}
		}
	echo "\t" . '<td class="smallspace">&nbsp;</td>' . "\n";
	echo '</tr>' . "\n";
	} ?>
<tr class="<?php echo $class; ?> smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<?php
	$query = mysqli_query($db, 'SELECT artist FROM album ' . $filter_query . ' GROUP BY artist');
	if (mysqli_num_rows($query) < 2) {
		$album = mysqli_fetch_assoc($query);
		if ($album['artist'] == '') $album['artist'] = $artist;
		$query = mysqli_query($db, 'SELECT album_id from track where artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"');
		$tracks = mysqli_num_rows($query);
?>
<tr class="footer">
	<td></td>
	<td colspan="<?php echo $colombs; ?>"><a href="index.php?action=view3all&amp;artist=<?php echo rawurlencode($album['artist']); ?>&amp;order=title">View all <?php echo $tracks . (($tracks == 1) ? ' track from ' : ' tracks from ') . html($album['artist']); ?></a></td>
	<td></td>
</tr>
<?php
	} ?>
</table>
<?php
}




//  +------------------------------------------------------------------------+
//  | View 2 - list mode                                                     |
//  +------------------------------------------------------------------------+
	else { ?>
<form action="genre.php" method="post" id="genreform">
	<input type="hidden" name="action" value="selectGenre">
	<input type="hidden" name="genre_id" value="<?php echo html($genre_id); ?>">
	<input type="hidden" name="artist" value="<?php echo html($artist); ?>">
	<input type="hidden" name="filter" value="<?php echo html($filter); ?>">
	<input type="hidden" name="order" value="<?php echo html($order); ?>">
	<input type="hidden" name="sort" value="<?php echo html($sort); ?>">
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td><a href="<?php echo $url; ?>&amp;order=added&amp;sort=<?php echo $sort_added; ?>">Add<img src="<?php echo $order_img_added; ?>" alt="" class="small"></a></td><!-- bitmap -->
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td><a href="<?php echo $url; ?>&amp;order=artist&amp;sort=<?php echo $sort_artist; ?>">Artist<img src="<?php echo $order_img_artist; ?>" alt="" class="small"></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=album&amp;sort=<?php echo $sort_album; ?>">Album<img src="<?php echo $order_img_album; ?>" alt="" class="small"></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=genre&amp;sort=<?php echo $sort_genre; ?>">Genre<img src="<?php echo $order_img_genre; ?>" alt="" class="small"></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=year&amp;sort=<?php echo $sort_year; ?>">Year<img src="<?php echo $order_img_year; ?>" alt="" class="small"></a></td>
	<td class="text-align-right"><a href="<?php echo $thumbnail_url; ?>"><img src="<?php echo $cfg['img']; ?>small_header_thumbnail.png" alt="" class="small"></a></td>
</tr>
<?php
		$i = 0;
		while ($album = mysqli_fetch_assoc($query)) {
			$genre_id = $album['genre_id'];
			$genre = mysqli_fetch_assoc(mysqli_query($db, 'SELECT genre FROM genre WHERE genre_id = "' . mysqli_real_escape_string($db, $genre_id) . '"')); ?>
<tr class="list <?php echo ($i++ & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>" title="Added: <?php echo date($cfg['date_added'], $album['album_add_time']); ?>"><img src="image.php?image_id=<?php echo $album['image_id']; ?>" alt="" width="50" height="50"></a></td>
	<td></td>
	<td><?php if ($cfg['access_play']) 						echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;album_id=' . $album['album_id'] . '\');"><img src="' . $cfg['img'] . 'small_high_play.png" alt="" class="small_high"></a>';
	elseif ($cfg['access_stream'] && !$cfg['access_add'])	echo '<a href="stream.php?action=m3u&amp;album_id=' . $album['album_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $album['album_id'] . $cfg['stream_id']) . '"><img src="' . $cfg['img'] . 'small_high_stream.png" alt="" class="small_high"></a>'; ?></td>
	<td><?php if ($cfg['access_add']) 						echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;album_id=' . $album['album_id'] . '\');"><img src="' . $cfg['img'] . 'small_high_add.png" alt="" class="small_high"></a>'; ?></td>
	<td></td>
	<td><a href="index.php?action=view2&amp;artist=<?php echo rawurlencode($album['artist_alphabetic']); ?>"><?php echo html($album['artist_alphabetic']); ?></a></td>
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>"><?php echo html($album['album']); ?></a></td>
	<td></td>
	<td><?php if ($cfg['access_admin']) echo '<input type="checkbox" name="album_id_array[]" value="' . $album['album_id'] . '" class="space">'; ?><a href="index.php?action=view1&amp;genre_id=<?php echo $album['genre_id']; ?>"><?php echo html($genre['genre']); ?></a></td>
	<td></td>
	<td title="<?php echo formattedDate($album['year'], $album['month']); ?>"><a href="index.php?action=view2&amp;year=<?php echo $album['year']; ?>"><?php echo $album['year']; ?></a></td>
	<td></td>
</tr>
<?php
		}
		$query = mysqli_query($db, 'SELECT artist FROM album ' . $filter_query . ' GROUP BY artist');
		if (mysqli_num_rows($query) < 2) {
			$album = mysqli_fetch_assoc($query);
			if ($album['artist'] == '') $album['artist'] = $artist;
			$query = mysqli_query($db, 'SELECT album_id from track where artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"');
			$tracks = mysqli_num_rows($query);
?>
<tr class="footer">
	<td></td>
	<td colspan="9"><a href="index.php?action=view3all&amp;artist=<?php echo rawurlencode($album['artist']); ?>&amp;order=title">View all <?php echo $tracks . (($tracks == 1) ? ' track from ' : ' tracks from ') . html($album['artist']); ?></a></td>
	<td colspan="4"><?php if ($cfg['access_admin']) echo '<a href="javascript:inverseCheckbox(genreform);" class="smallbutton space">inverse</a><a href="javascript:genreform.submit();" class="smallbutton">edit</a>'; ?></td>
</tr>
</table>
</form>
<?php
		}
		else { ?>
<tr class="footer">
	<td></td>
	<td colspan="9"></td>
	<td colspan="4"><?php if ($cfg['access_admin']) echo '<a href="javascript:inverseCheckbox(genreform);" class="smallbutton space">inverse</a><a href="javascript:genreform.submit();" class="smallbutton">edit</a>'; ?></td>
</tr>
</table>
</form>
<?php
		}
	}
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 3                                                                 |
//  +------------------------------------------------------------------------+
function view3() {
	global $cfg, $db;
	$album_id = @$_GET['album_id'];
	
	if ($album_id == '' && $cfg['image_share']) {
		if ($cfg['image_share_mode'] == 'played') {
			$query = mysqli_query($db, 'SELECT album_id
				FROM counter
				WHERE flag <= 1
				ORDER BY time DESC
				LIMIT 1');
			$counter	= mysqli_fetch_assoc($query);
			$album_id	= $counter['album_id'];
		}
		else {
			$query = mysqli_query($db, 'SELECT album_id
				FROM album
				ORDER BY album_add_time DESC
				LIMIT 1');
			$album		= mysqli_fetch_assoc($query);
			$album_id	= $album['album_id'];
		}
		header('Location: ' . NJB_HOME_URL . 'index.php?action=view3&album_id=' . rawurlencode($album_id));
		exit();
	}
	
	authenticate('access_media');
	
	$query = mysqli_query($db, 'SELECT artist_alphabetic, artist, album, year, month, genre_id, image_id
		FROM album
		WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]album_id not found in database');
	
	$query = mysqli_query($db, 'SELECT featuring FROM track WHERE featuring != "" AND album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	if (mysqli_fetch_row($query))	$featuring = true;
	else 							$featuring = false;
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['class'][]	= 'nav';
	$nav['url'][]	= 'index.php';
	if ($album['genre_id']) {
		$query	= mysqli_query($db, 'SELECT genre, genre_id
			FROM genre
			WHERE genre_id = "' . mysqli_real_escape_string($db, $album['genre_id']) . '"
			ORDER BY genre');
		$genre	= mysqli_fetch_assoc($query);
		$nav['name'][]	= $genre['genre'];
		$nav['class'][]	= 'suggest';
		$nav['url'][]	= 'index.php?action=view1&amp;genre_id=' . $genre['genre_id'];
	}
	$nav['name'][]	= $album['artist_alphabetic'];
	$nav['class'][]	= 'nav';
	$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist_alphabetic']);
	
	$query	= mysqli_query($db, 'SELECT
							(
								SELECT album_id FROM album 
								WHERE (
									artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"
									AND (year > t.year OR (year IS NOT NULL AND t.year IS NULL))
								)					
								OR (
									artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"
									AND (year = t.year OR (year IS NULL AND t.year IS NULL))
									AND (month > t.month OR (month IS NOT NULL AND t.month IS NULL))
								)
								OR (
									artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"
									AND (year = t.year OR (year IS NULL AND t.year IS NULL))
									AND (month = t.month OR (month IS NULL AND t.month IS NULL))
									AND album > t.album
								)					
								ORDER BY	year,
											month,
											album
								LIMIT 1
							) AS next_album_id,
							(
								SELECT album_id FROM album 
								WHERE  (
									artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"
									AND (year < t.year OR (year IS NULL AND t.year IS NOT NULL))
								)
								OR (
									artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"
									AND (year = t.year OR (year IS NULL AND t.year IS NULL))
									AND (month < t.month OR (month IS NULL AND t.month IS NOT NULL))
								)								
								OR (
									artist = "' . mysqli_real_escape_string($db, $album['artist']) . '"
									AND (year = t.year OR (year IS NULL AND t.year IS NULL))
									AND (month = t.month OR (month IS NULL AND t.month IS NULL))
									AND album < t.album
								)								
								ORDER BY	year DESC, 
											month DESC,
											album DESC
								LIMIT 1
							) AS previous_album_id,
							( SELECT year FROM album WHERE album_id = next_album_id LIMIT 1 ) AS next_year,
							( SELECT year FROM album WHERE album_id = previous_album_id LIMIT 1 ) AS previous_year
							FROM album AS t
							WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$paging = mysqli_fetch_assoc($query);
	
	if ($paging['previous_album_id']) {
		$nav['name'][]	= ($paging['previous_year']) ? $paging['previous_year'] : 'Previous';
		$nav['class'][]	= 'suggest';
		$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . rawurlencode($paging['previous_album_id']);
	}
	
	if ($album['year']) {
		$nav['name'][]	= $album['year'];
		$nav['class'][]	= 'nav';
		$nav['url'][]	= '';
	}
	
	if ($paging['next_album_id']) {
		$nav['name'][]	= ($paging['next_year']) ? $paging['next_year'] : 'Next';
		$nav['class'][]	= 'suggest';
		$nav['url'][]	= 'index.php?action=view3&amp;album_id=' . rawurlencode($paging['next_album_id']);
	}
	
	$nav['name'][]	= $album['album'];
	$nav['class'][]	= 'nav';
	$nav['url'][]	= '';
	
	
	require_once('include/header.inc.php');
	
	
	$search = array();
	for ($i = 0; $i < count($cfg['search_name']) && $i < 7; $i++)
		if (pow(2,$i) & $cfg['access_search']) $search[] = '<a href="ridirect.php?search_id=' . $i . '&amp;album_id=' . $album_id . '"><img src="' . $cfg['img'] . 'small_search.png" alt="" class="small space">' . html($cfg['search_name'][$i]) .'</a>';
	$search[] = '<a href="javascript:showHide(\'basic\',\'search\');"><img src="' . $cfg['img'] . 'small_back.png" alt="" class="small space">Go back</a>';
	
	$advanced = array();
	if ($cfg['access_admin'] && $cfg['album_copy'] && is_dir($cfg['external_storage']))
		$advanced[] = '<a href="download.php?action=copyAlbum&amp;album_id='. $album_id . '&amp;sign=' . $cfg['sign'] . '"><img src="' . $cfg['img'] . 'small_external_storage.png" alt="" class="small space">Copy album</a>';
	if ($cfg['access_admin'] && $cfg['album_update_image']) {
		$advanced[] = '<a href="update.php?action=imageUpdate&amp;flag=9&amp;album_id='. $album_id . '"><img src="' . $cfg['img'] . 'small_image.png" alt="" class="small space">Update image</a>';
		$advanced[] = '<a href="update.php?action=selectImageUpload&amp;flag=9&amp;album_id='. $album_id . '"><img src="' . $cfg['img'] . 'small_upload.png" alt="" class="small space">Upload image</a>';
	}
	if ($cfg['access_admin'] && $cfg['album_edit_genre'])
		$advanced[] = '<a href="genre.php?action=selectGenre&amp;album_id=' . $album_id . '"><img src="' . $cfg['img'] . 'small_genre.png" alt="" class="small space">Select genre</a>';
	if ($cfg['access_admin'])
		$advanced[] = '<a href="javascript:showHide(\'basic\',\'advanced\');"><img src="' . $cfg['img'] . 'small_back.png" alt="" class="small space">Go back</a>';
		
	
	$basic = array();
	if ($cfg['access_play'])
		$basic[] = '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;album_id=' . $album_id . '\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small space">Play album</a>';
	if ($cfg['access_add'])
		$basic[] = '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;album_id=' . $album_id . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small space">Add album</a>';
	if ($cfg['access_stream'])
		$basic[] = '<a href="stream.php?action=m3u&amp;album_id=' . $album_id . '&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $album_id . $cfg['stream_id']) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small space">Stream album</a>';
	if ($cfg['access_download'] && $cfg['album_download'])
		$basic[] = '<a href="download.php?action=downloadAlbum&amp;album_id=' . $album_id . '&amp;download_id=' . $cfg['download_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'downloadAlbum' . $album_id . $cfg['download_id']) . '" ' . downloadAlbumTitle($album_id) . '><img src="' . $cfg['img'] . 'small_download.png" alt="" class="small space">Download album</a>';
	if ($cfg['access_admin'] && $cfg['album_share_stream'])
		$basic[] = '<a href="stream.php?action=shareAlbum&amp;album_id='. $album_id . '&amp;sign=' . $cfg['sign'] . '"><img src="' . $cfg['img'] . 'small_share.png" alt="" class="small space">Share stream</a>';
	if ($cfg['access_admin'] && $cfg['album_share_download'])
		$basic[] = '<a href="download.php?action=shareAlbum&amp;album_id=' . $album_id . '&amp;sign=' . $cfg['sign'] . '"><img src="' . $cfg['img'] . 'small_share.png" alt="" class="small space">Share download</a>';
	if (count($search) > 1)
		$basic[] = '<a href="javascript:showHide(\'basic\',\'search\');"><img src="' . $cfg['img'] . 'small_search.png" alt="" class="small space">Search...</a>';	
	if (count($advanced) > 1)
		$basic[] = '<a href="javascript:showHide(\'basic\',\'advanced\');"><img src="' . $cfg['img'] . 'small_config.png" alt="" class="small space">Advanced...</a>';
?>
<table class="border bottom_space">
<tr class="odd">
	<td><?php echo ($cfg['access_cover']) ? '<a href="cover.php?action=downloadCover&amp;album_id=' . $album_id . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'downloadCover' . $album_id) . '" ' . viewCoverTitle($album_id) . '><img src="image.php?image_id=' . $album['image_id'] . '" alt="" width="200" height="200"></a>' : '<img src="image.php?image_id=' . $album['image_id'] . '" alt="" width="200" height="200">'; ?></td>
	<td class="vertical_line"></td>
	<td>
<!-- start options -->
<table id="basic" style="display: block;">
<?php
	for ($i = 0; $i < 8; $i++) { ?>
<tr class="<?php echo ($i & 1) ? 'even' : 'odd'; ?> nowrap" style="height: 25px;">
	<td<?php echo ($i == 0) ? ' class="space"' : ''; ?>></td>
	<td><?php echo (isset($basic[$i])) ? $basic[$i] : '&nbsp;'; ?></td>
	<td<?php echo ($i == 0) ? ' class="space"' : ''; ?>></td>
</tr>
<?php
	} ?>
</table>
<table id="search" style="display: none;">
<?php
	for ($i = 0; $i < 8; $i++) { ?>
<tr class="<?php echo ($i & 1) ? 'even' : 'odd'; ?> nowrap" style="height: 25px;">
	<td<?php echo ($i == 0) ? ' class="space"' : ''; ?>></td>
	<td><?php echo (isset($search[$i])) ? $search[$i] : '&nbsp;'; ?></td>
	<td<?php echo ($i == 0) ? ' class="space"' : ''; ?>></td>
</tr>
<?php
	} ?>
</table>
<table id="advanced" style="display: none;">
<?php
	for ($i = 0; $i < 8; $i++) { ?>
<tr class="<?php echo ($i & 1) ? 'even' : 'odd'; ?> nowrap" style="height: 25px;">
	<td<?php echo ($i == 0) ? ' class="space"' : ''; ?>></td>
	<td><?php echo (isset($advanced[$i])) ? $advanced[$i] : '&nbsp;'; ?></td>
	<td<?php echo ($i == 0) ? ' class="space"' : ''; ?>></td>
</tr>
<?php
	} ?>
</table>
<!-- end options -->	
	</td>
</tr>
</table>


<table class="border">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Artist</td>
	<td class="textspace"></td>
	<td>Title</td>
	<td class="textspace"></td>
	<td><?php if ($featuring) echo'Featuring'; ?></td><!-- optional featuring -->
	<td<?php if ($featuring) echo' class="textspace"'; ?>></td><!-- optional featuring -->
	<td class="text-align-right">Time</td>
	<td<?php if ($cfg['access_download']) echo' class="space"'; ?>></td>
	<td></td><!-- optional download -->
	<td class="space"></td>
</tr>
<?php
	$query = mysqli_query($db, 'SELECT discs FROM album WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '"');
	$album = mysqli_fetch_assoc($query);
	for ($disc = 1; $disc <= $album['discs']; $disc++) {
		$query = mysqli_query($db, 'SELECT artist, title, featuring, miliseconds, track_id, number FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '" AND disc = ' . (int) $disc . ' ORDER BY relative_file');
		$i = 0;
		while ($track = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>';?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;stream_id=' . $cfg['stream_id'] . '&amp;track_id=' . $track['track_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $cfg['stream_id'] . $track['track_id']) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><?php if (mysqli_num_rows(mysqli_query($db, 'SELECT track_id FROM track WHERE artist="' . mysqli_real_escape_string($db, $track['artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['artist']) . '&amp;order=year">' . html($track['artist']) . '</a>'; else echo html($track['artist']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');" title="Play track ' . $track['number'] . '">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" title="Add track ' . $track['number'] . '">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=m3u&amp;stream_id=' . $cfg['stream_id'] . '&amp;track_id=' . $track['track_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $cfg['stream_id'] . $track['track_id']) . '">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?></td>
	<td></td>
	<td><?php if ($track['featuring']) echo html($track['featuring']); ?></td>
	<td></td>
	<td class="text-align-right"><?php echo formattedTime($track['miliseconds']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_download']) echo '<a href="download.php?action=downloadTrack&amp;download_id=' . $cfg['download_id'] . '&amp;track_id=' . $track['track_id'] .'&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'downloadTrack' . $cfg['download_id'] . $track['track_id']) . '" ' . downloadTrackTitle($track['track_id']) . '><img src="' . $cfg['img'] . 'small_download.png" alt="" class="small"></a>'; ?></td>
	<td></td>
</tr>
<?php
		}
		$query = mysqli_query($db, 'SELECT SUM(miliseconds) DIV 1000 AS sum_seconds FROM track WHERE album_id = "' . mysqli_real_escape_string($db, $album_id) . '" AND disc = ' . (int) $disc);
		$track = mysqli_fetch_assoc($query); ?>
<tr class="<?php echo ($disc < $album['discs']) ? 'section' : 'footer' ?>">
	<td></td>
	<td colspan="4"><?php if ($cfg['access_record']) echo '<a href="record.php?album_id=' . $album_id . '&amp;disc=' . $disc . '"><img src="' . $cfg['img'] . 'small_record.png" alt="" class="small"></a>'; ?></td>
	<td colspan="5"><?php if ($cfg['access_record']) echo '<a href="record.php?album_id=' . $album_id . '&amp;disc=' . $disc . '">Record (' . $i . ' tracks)</a>'; else echo $i . ' tracks';?></td>
	<td colspan="2" class="text-align-right"><?php echo formattedTime($track['sum_seconds'], false); ?></td>
	<td colspan="3"></td>
</tr>
<?php
	}
	echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 1 all                                                             |
//  +------------------------------------------------------------------------+
function view1all() {
	global $cfg, $db;
	authenticate('access_media');
	
	$artist	 	= @$_GET['artist'];
	$filter  	= @$_GET['filter'];
	
	if ($artist == '') {
		$artist = 'All track artists';
		$filter = 'all';
	}
	
	if ($filter == 'all')		$query = mysqli_query($db, 'SELECT artist FROM track WHERE 1 GROUP BY artist ORDER BY artist');
	elseif ($filter == 'smart')	$query = mysqli_query($db, 'SELECT artist FROM track WHERE artist LIKE "%' . mysqli_real_escape_like($db, $artist) . '%" OR artist SOUNDS LIKE "' . mysqli_real_escape_like($db, $artist) . '" GROUP BY artist ORDER BY artist');
	else						message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $artist;
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Artist</td>
	<td class="space"></td>
</tr>
<?php
	$i = 0;
	while ($track = mysqli_fetch_assoc($query))	{ ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td><a href="index.php?action=view3all&amp;artist=<?php echo rawurlencode($track['artist']); ?>&amp;order=title"><?php echo html($track['artist']); ?></a></td>
	<td></td>
</tr>
<?php
	}
echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 3 all                                                             |
//  +------------------------------------------------------------------------+
function view3all() {
	global $cfg, $db;
	authenticate('access_media');
	
	$artist	 	= @$_GET['artist'];
	$title	 	= @$_GET['title'];
	$filter  	= @$_GET['filter']				or $filter	= 'start';
	$order	 	= @$_GET['order']				or $order	= 'title';
	$sort	 	= @$_GET['sort'] == 'desc'		? 'desc' : 'asc';
	
	$sort_artist 			= 'asc';
	$sort_title 			= 'asc';
	$sort_featuring 		= 'asc';
	$sort_album 			= 'asc';
	
	$order_img_artist		= $cfg['img'] . 'small_header_sort.png';
	$order_img_title		= $cfg['img'] . 'small_header_sort.png';
	$order_img_featuring 	= $cfg['img'] . 'small_header_sort.png';
	$order_img_album		= $cfg['img'] . 'small_header_sort.png';
	
	if (strlen($title) >= 2) {
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Media';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= $title;
		require_once('include/header.inc.php');
		
		if ($filter == 'start')		$filter_query = 'WHERE track.title LIKE "' . mysqli_real_escape_like($db, $title) . '%" AND track.album_id = album.album_id';
		elseif ($filter == 'smart')	$filter_query = 'WHERE (track.title LIKE "%' . mysqli_real_escape_like($db, $title) . '%" OR track.title SOUNDS LIKE "' . mysqli_real_escape_string($db, $title) . '") AND track.album_id = album.album_id';
		elseif ($filter == 'exact')	$filter_query = 'WHERE track.title = "' . mysqli_real_escape_string($db, $title) . '" AND track.album_id = album.album_id';
		else						message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
		
		$url = 'index.php?action=view3all&amp;title=' . rawurlencode($title) . '&amp;filter=' . $filter;
	}
	elseif (strlen($artist) >= 2) {
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Media';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= $artist;
		$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($artist) . '&amp;order=year';
		$nav['name'][]	= 'All tracks';
		require_once('include/header.inc.php');
		
		$filter_query = 'WHERE track.artist="' . mysqli_real_escape_string($db, $artist) . '" AND track.album_id = album.album_id';
		$url = 'index.php?action=view3all&amp;artist=' . rawurlencode($artist);
	}
	else
		message(__FILE__, __LINE__, 'warning', '[b]Search string to short[/b][br][url=index.php][img]small_back.png[/img]Back to previous page[/url]');
	
	if ($order == 'artist' && $sort == 'asc') {
		$order_query = 'ORDER BY artist, title';
		$order_img_artist = $cfg['img'] . 'small_header_sort_asc.png';
		$sort_artist = 'desc';
	}
	elseif ($order == 'artist' && $sort == 'desc') {
		$order_query = 'ORDER BY artist DESC, title DESC';
		$order_img_artist = $cfg['img'] . 'small_header_sort_desc.png';
		$sort_artist = 'asc';
	}
	elseif ($order == 'title' && $sort == 'asc') {
		$order_query = 'ORDER BY title, album';
		$order_img_title = $cfg['img'] . 'small_header_sort_asc.png';
		$sort_title = 'desc';
	}
	elseif ($order == 'title' && $sort == 'desc') {
		$order_query = 'ORDER BY title DESC, album DESC';
		$order_img_title = $cfg['img'] . 'small_header_sort_desc.png';
		$sort_title = 'asc';
	}
	elseif ($order == 'featuring' && $sort == 'asc') {
		$order_query = 'ORDER BY featuring, title, artist';
		$order_img_featuring = $cfg['img'] . 'small_header_sort_asc.png';
		$sort_featuring = 'desc';
	}
	elseif ($order == 'featuring' && $sort == 'desc') {
		$order_query = 'ORDER BY featuring DESC, title DESC, artist DESC';
		$order_img_featuring = $cfg['img'] . 'small_header_sort_desc.png';
		$sort_featuring = 'asc';
	}
	elseif ($order == 'album' && $sort == 'asc') {
		$order_query = 'ORDER BY album, relative_file';
		$order_img_album = $cfg['img'] . 'small_header_sort_asc.png';
		$sort_album = 'desc';
	}
	elseif ($order == 'album' && $sort == 'desc') {
		$order_query = 'ORDER BY album DESC, relative_file DESC';
		$order_img_album = $cfg['img'] . 'small_header_sort_desc.png';
		$sort_album = 'asc';
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]order');
	
	$query = mysqli_query($db, 'SELECT featuring FROM track, album ' . $filter_query . ' AND featuring != ""');
	if (mysqli_fetch_row($query))	$featuring = true;
	else							$featuring = false;
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td><a href="<?php echo $url; ?>&amp;order=artist&amp;sort=<?php echo $sort_artist; ?>">Artist<img src="<?php echo $order_img_artist; ?>" alt="" class="small"></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=title&amp;sort=<?php echo $sort_title; ?>">Title<img src="<?php echo $order_img_title; ?>" alt="" class="small"></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=album&amp;sort=<?php echo $sort_album; ?>">Album<img src="<?php echo $order_img_album; ?>" alt="" class="small"></a></td>
	<td class="textspace"></td>
	<td><?php if ($featuring) echo '<a href="' . $url . '&amp;order=featuring&amp;sort=' . $sort_featuring . '">Featuring<img src="' . $order_img_featuring . '" alt="" class="small"></a>'; ?></td><!-- optional featuring -->
	<td<?php if ($featuring) echo ' class="textspace"'; ?>></td><!-- optional featuring -->
	<td class="text-align-right">Time</td>
	<td<?php if ($cfg['access_download']) echo' class="space"'; ?>></td>
	<td></td><!-- optional download -->
	<td class="space"></td>
</tr>
<?php
	$i=0;
	$query = mysqli_query($db, 'SELECT track.artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, album.image_id, album.album FROM track, album ' . $filter_query . ' ' . $order_query);
	while ($track = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;stream_id=' . $cfg['stream_id'] . '&amp;track_id=' . $track['track_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $cfg['stream_id'] . $track['track_id']) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>';?></td>
	<td></td>	
	<td><?php if (mysqli_num_rows(mysqli_query($db, 'SELECT track_id FROM track WHERE artist="' . mysqli_real_escape_string($db, $track['artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['artist']) . '&amp;order=year">' . html($track['artist']) . '</a>'; else echo html($track['artist']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');" title="Play track">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" title="Add track">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=m3u&amp;stream_id=' . $cfg['stream_id'] . '&amp;track_id=' . $track['track_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $cfg['stream_id'] . $track['track_id']) . '" title="Stream track">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?></td>
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $track['album_id']; ?>" <?php echo imageTitle($track['image_id']); ?>><?php echo html($track['album']); ?></a></td>
	<td></td>
	<td><?php if ($track['featuring']) echo html($track['featuring']); ?></td>
	<td></td>
	<td class="text-align-right"><?php echo formattedTime($track['miliseconds']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_download']) echo '<a href="download.php?action=downloadTrack&amp;download_id=' . $cfg['download_id'] . '&amp;track_id=' . $track['track_id'] .'&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'downloadTrack' . $cfg['download_id'] . $track['track_id']) . '" ' . downloadTrackTitle($track['track_id']) . '><img src="' . $cfg['img'] . 'small_download.png" alt="" class="small"></a>'; ?></td>
	
	<td></td>
</tr>
<?php
	}
	echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}



//  +------------------------------------------------------------------------+
//  | View random album                                                      |
//  +------------------------------------------------------------------------+
function viewRandomAlbum() {
	global $cfg, $db;
	authenticate('access_media');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'Random';
	$nav['url'][]	= 'index.php?action=viewRandomAlbum';
	
	require_once('include/header.inc.php');
	
	$size = @$_GET['size'];
	if (in_array($size, array('50', '100', '200'))) {
		mysqli_query($db, 'UPDATE session
			SET thumbnail_size	= ' . (int) $size . '
			WHERE sid			= BINARY "' . mysqli_real_escape_string($db, $cfg['sid']) . '"');
	}
	else
		$size = $cfg['thumbnail_size'];
	
	$i			= 0;
	$width		= (@$_COOKIE['netjukebox_width']) ? (int) $_COOKIE['netjukebox_width'] : 1024;
	$colombs	= floor(($width - 40) / ($size + 10));
?>
<table><tr><td><!-- table tab wrapper -->
<ul id="tab">
	<li class="tab on" onclick="location.href='index.php?action=viewRandomAlbum';">Album</li>
	<li class="tab off" onclick="location.href='index.php?action=viewRandomTrack';">Track</li>
	<li class="tab off" onclick="location.href='genre.php?action=blacklist';">Blacklist</li>
</ul>
<table class="tab">
<tr class="header">
	<td colspan="<?php echo $colombs + 2; ?>" class="text-align-right">
		<a href="index.php?action=viewRandomAlbum&amp;size=50"><img src="<?php echo $cfg['img']; ?>small_header_image50_<?php echo ($size == '50') ? 'on' : 'off'; ?>.png" alt="" class="small"></a><!--
		--><a href="index.php?action=viewRandomAlbum&amp;size=100"><img src="<?php echo $cfg['img']; ?>small_header_image100_<?php echo ($size == '100') ? 'on' : 'off'; ?>.png" alt="" class="small"></a><!--
		--><a href="index.php?action=viewRandomAlbum&amp;size=200"><img src="<?php echo $cfg['img']; ?>small_header_image200_<?php echo ($size == '200') ? 'on' : 'off'; ?>.png" alt="" class="small"></a>
	</td>
</tr>
<tr class="odd smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<?php
	$blacklist = explode(',', $cfg['random_blacklist']);
	$blacklist = '"' . implode('","', $blacklist) . '"';
	$query = mysqli_query($db, 'SELECT artist, album, genre_id, year, month, image_id, album_id
		FROM album
		WHERE genre_id = "" OR genre_id NOT IN (' . $blacklist . ')
		ORDER BY RAND()
		LIMIT ' . (int) $colombs * 2);
	while ($album = mysqli_fetch_assoc($query)) {
		$class = ($i++ & 1) ? 'even' : 'odd';
		echo '<tr class="' . $class . '">'. "\n";
		echo '	<td class="smallspace">&nbsp;</td>' . "\n";
		for ($j = 1; $j <= $colombs; $j++) {
			if ($j != 1) $album = mysqli_fetch_assoc($query);
			if ($album) {
				$genre_id = $album['genre_id'];
				$genre = mysqli_fetch_assoc(mysqli_query($db, 'SELECT genre FROM genre WHERE genre_id = "' . mysqli_real_escape_string($db, $genre_id) . '"')); ?>
	<td>
	<a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>" title="<?php echo html('<strong>' . $album['artist'] . '</strong><br>' . $album['album']); if ($genre['genre'] || $album['year']) echo html('<hr>'); if ($genre['genre']) echo html($genre['genre']) . html('<br>'); echo formattedDate($album['year'], $album['month']); ?>"><img src="image.php?image_id=<?php echo $album['image_id']; ?>" alt="" width="<?php echo $size; ?>" height="<?php echo $size; ?>" class="thumbnail"></a>
	</td>
<?php
			}
			else {
				echo '	<td><img src="image/dummy.png" alt="" width="' . $size . '" height="' . $size . '" class="thumbnail"></td>' . "\n";
			}
		}
	echo '	<td class="smallspace">&nbsp;</td>' . "\n";
	echo '</tr>' . "\n";
	}
?>
<tr class="<?php echo $class; ?> smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
</table>
</td></tr></table><!-- table tab wrapper -->
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View random track                                                      |
//  +------------------------------------------------------------------------+
function viewRandomTrack() {
	global $cfg, $db;
	authenticate('access_media');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'Random';
	$nav['url'][]	= 'index.php?action=viewRandomTrack';
	
	require_once('include/header.inc.php');
?>
<table><tr><td><!-- table tab wrapper -->
<ul id="tab">
	<li id="albumartist" class="tab off" onclick="location.href='index.php?action=viewRandomAlbum';">Album</li>
	<li id="trackartist" class="tab on" onclick="location.href='index.php?action=viewRandomTrack';">Track</li>
	<li id="tracktitle" class="tab off" onclick="location.href='genre.php?action=blacklist';">Blacklist</li>
</ul>
<table class="tab">
<?php
	if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) { ?>
<tr class="header">
	<td></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td colspan="4"></td>
	<td></td>
</tr>
<tr class="odd mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=database\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add']) echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;random=database\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;random=database&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . 'database' . $cfg['stream_id']) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td colspan="3"><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=database\');" title="Play">Playlist</a>';
	elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=&amp;random=database\');" title="Add">Playlist</a>';
	elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=m3u&amp;random=database&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . 'database' . $cfg['stream_id']) . '" title="Stream">Playlist</a>'; ?></td>
	<td></td>
</tr>
<?php
	} ?>
<tr class="<?php echo ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) ? 'section' : 'header' ;?>">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Artist</td>
	<td class="textspace"></td>
	<td>Title</td>
	<td class="space"></td>
</tr>
<?php
	mysqli_query($db, 'DELETE FROM random WHERE sid = "' . mysqli_real_escape_string($db, $cfg['sid']) . '"');
	
	$i = 0;
	$blacklist = explode(',', $cfg['random_blacklist']);
	$blacklist = '"' . implode('","', $blacklist) . '"';
	$query = mysqli_query($db, 'SELECT track.artist, title, featuring, track_id
		FROM track, album
		WHERE (genre_id = "" OR genre_id NOT IN (' . $blacklist . ')) AND
		audio_dataformat != "" AND
		video_dataformat = "" AND
		track.album_id = album.album_id
		ORDER BY RAND()
		LIMIT 30');
	while ($track = mysqli_fetch_assoc($query)) {
		mysqli_query($db, 'INSERT INTO random (sid, track_id, position, create_time) VALUES (
			"' . mysqli_real_escape_string($db, $cfg['sid']) . '",
			"' . mysqli_real_escape_string($db, $track['track_id']) . '",
			"' . mysqli_real_escape_string($db, $i) . '",
			"' . mysqli_real_escape_string($db, time()) . '")'); ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add']) echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;stream_id=' . $cfg['stream_id'] . '&amp;track_id=' . $track['track_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $cfg['stream_id'] . $track['track_id']) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><?php if (mysqli_num_rows(mysqli_query($db, 'SELECT track_id FROM track WHERE artist="' . mysqli_real_escape_string($db, $track['artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['artist']) . '&amp;order=year">' . html($track['artist']) . '</a>'; else echo html($track['artist']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');" title="Play track">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" title="Add track">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=m3u&amp;stream_id=' . $cfg['stream_id'] . '&amp;track_id=' . $track['track_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $cfg['stream_id'] . $track['track_id']) . '" title="Stream track">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?></td>
	<td></td>
</tr>
<?php
	} ?>
</table>
</td></tr></table><!-- table tab wrapper -->
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View year                                                              |
//  +------------------------------------------------------------------------+
function viewYear() {
	global $cfg, $db;
	authenticate('access_media');
	
	$sort = @$_GET['sort'] == 'asc' ? 'asc' : 'desc';
	
	if ($sort == 'asc') {
		$order_query = 'ORDER BY year';
		$order_img_year = $cfg['img'] . 'small_header_sort_asc.png';
		$sort_year = 'desc';
	}
	else {
		// desc
		$order_query = 'ORDER BY year DESC';
		$order_img_year = $cfg['img'] . 'small_header_sort_desc.png';
		$sort_year = 'asc';
	}
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Media';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'Year';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td colspan="2"><a href="index.php?action=viewYear&amp;sort=<?php echo $sort_year; ?>">Year<img src="<?php echo $order_img_year; ?>" alt="" class="small"></a></td>
	<td colspan="2" class="text-align-right"><a href="index.php?action=view2&amp;thumbnail=1&amp;filter=all&amp;order=year&amp;sort=<?php echo $sort; ?>&amp;page=1"><img src="<?php echo $cfg['img']; ?>small_header_thumbnail.png" alt="" class="small"></a><a href="index.php?action=view2&amp;thumbnail=0&amp;filter=all&amp;order=year&amp;sort=<?php echo $sort; ?>&amp;page=1"><img src="<?php echo $cfg['img']; ?>small_header_list.png" alt="" class="small"></a></td>	
</tr>
<?php
	$query = mysqli_query($db, 'SELECT COUNT(*) AS counter
		FROM album
		WHERE year
		GROUP BY year
		ORDER BY counter DESC');
	$album = mysqli_fetch_assoc($query);
	$max = $album['counter'];
	
	$i=0;
	$query = mysqli_query($db, 'SELECT year,
		COUNT(*) AS counter
		FROM album
		WHERE year
		GROUP BY year ' . $order_query);
	while ($max && $album = mysqli_fetch_assoc($query)) {
?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><a href="index.php?action=view2&amp;year=<?php echo $album['year']; ?>"><?php echo $album['year']; ?></a></td>
	<td class="textspace"></td>
	<td><a href="index.php?action=view2&amp;year=<?php echo $album['year']; ?>" title="<?php echo $album['counter']; echo ($album['counter'] == 1) ? ' album' : ' albums'; ?>" class="bar"><div style="width: <?php echo  round($album['counter'] / $max * 100); ?>%;"></div></a></td>
	<td class="space"></td>
</tr>
<?php
	}
	echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View popular                                                           |
//  +------------------------------------------------------------------------+
function viewPopular() {
	global $cfg, $db;
	
	$period		= @$_GET['period'];
	$user_id 	= (int) @$_GET['user_id'];
	$flag	 	= (int) @$_GET['flag'];
	
	if		($period == 'week')		$timestamp = time() - 86400 * 7;
	elseif	($period == 'month')	$timestamp = time() - 86400 * 31;
	elseif	($period == 'year')		$timestamp = time() - 86400 * 365;
	elseif	($period == 'overall')	$timestamp = 0;
	else							message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]period');

	
	if ($user_id == 0) {
		authenticate('access_popular');
		
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Media';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= 'Popular';
		
		$query = mysqli_query($db, 'SELECT artist, artist_alphabetic, album, image_id, album.album_id, COUNT(*) AS counter
			FROM counter, album
			WHERE counter.flag <= 1
			AND counter.time > ' . (int) $timestamp . '
			AND counter.album_id = album.album_id
			GROUP BY album.album_id
			ORDER BY counter DESC, time DESC
			LIMIT ' . (int) $cfg['page_limit']);
		
		$url = 'index.php?action=viewPopular';
	}
	else {
		authenticate('access_admin');
		
		$cfg['menu'] = 'config';
		$query = mysqli_query($db, 'SELECT username, access_play, access_add, access_stream, access_download, access_cover, access_record FROM user WHERE user_id = ' . (int) $user_id);
		$user = mysqli_fetch_assoc($query);

		
		if ($user == false)
			message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]user_id not found in database');
			
		if ($flag < 0 || $flag > 4)
			message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]flag');
		
		// Navigator
		$nav			= array();
		$nav['name'][]	= 'Configuration';
		$nav['class'][]	= 'nav';
		$nav['url'][]	= 'config.php';
		
		$nav['name'][]	= 'User statistics';
		$nav['class'][]	= 'nav';
		$nav['url'][]	= 'users.php?action=userStatistics&amp;period=' . $period;
		
		$nav['name'][]	= $user['username'];
		$nav['class'][]	= 'nav';
		$nav['url'][]	= '';
		
		if ($user['access_play'] || $user['access_play']) {
			$nav['name'][]	= 'Play';
			$nav['class'][]	= ($flag == 0) ? 'nav' : 'suggest';
			$nav['url'][]	= ($flag == 0) ? '' : 'index.php?action=viewPopular&amp;flag=0&amp;period=' . $period . '&amp;user_id=' . $user_id;
		}
		if ($user['access_stream']) {
			$nav['name'][]	= 'Stream';
			$nav['class'][]	= ($flag == 1) ? 'nav' : 'suggest';
			$nav['url'][]	= ($flag == 1) ? '' : 'index.php?action=viewPopular&amp;flag=1&amp;period=' . $period . '&amp;user_id=' . $user_id;
		}
		if ($user['access_download']) {
			$nav['name'][]	= 'Download';
			$nav['class'][]	= ($flag == 2) ? 'nav' : 'suggest';
			$nav['url'][]	= ($flag == 2) ? '' : 'index.php?action=viewPopular&amp;flag=2&amp;period=' . $period . '&amp;user_id=' . $user_id;
		}
		if ($user['access_cover']) {
			$nav['name'][]	= 'Cover';
			$nav['class'][]	= ($flag == 3) ? 'nav' : 'suggest';
			$nav['url'][]	= ($flag == 3) ? '' : 'index.php?action=viewPopular&amp;flag=3&amp;period=' . $period . '&amp;user_id=' . $user_id;
		}
		if ($user['access_record']) {
			$nav['name'][]	= 'Record';
			$nav['class'][]	= ($flag == 4) ? 'nav' : 'suggest';
			$nav['url'][]	= ($flag == 4) ? '' : 'index.php?action=viewPopular&amp;flag=4&amp;period=' . $period . '&amp;user_id=' . $user_id;
		}
		
		$query = mysqli_query($db, 'SELECT artist, artist_alphabetic, album, image_id, album.album_id, COUNT(*) AS counter
			FROM counter, album
			WHERE user_id = ' . (int) $user_id . '
			AND counter.flag = ' . $flag . '
			AND counter.time > ' . (int) $timestamp . '
			AND counter.album_id = album.album_id
			GROUP BY album.album_id
			ORDER BY counter DESC, time DESC
			LIMIT 50');
		
		$url = 'index.php?action=viewPopular&amp;flag=' . $flag . '&amp;user_id=' . $user_id;
	}
	require_once('include/header.inc.php'); ?>
<table><tr><td><!-- table tab wrapper -->
<ul id="tab">
	<li class="tab <?php echo ($period == 'week') ? 'on' : 'off'; ?>" onclick="location.href='<?php echo $url; ?>&amp;period=week';">Week</li>
	<li class="tab <?php echo ($period == 'month') ? 'on' : 'off'; ?>" onclick="location.href='<?php echo $url; ?>&amp;period=month';">Month</li>
	<li class="tab <?php echo ($period == 'year') ? 'on' : 'off'; ?>" onclick="location.href='<?php echo $url; ?>&amp;period=year';">Year</li>
	<li class="tab <?php echo ($period == 'overall') ? 'on' : 'off'; ?>" onclick="location.href='<?php echo $url; ?>&amp;period=overall';">Overall</li>
</ul>
<table class="tab">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Artist</td>
	<td class="textspace"></td>
	<td>Album</td>
	<td class="textspace"></td>
	<td>Count</td>
	<td class="space"></td>
</tr>
<?php
	$i=0;
	while ($album = mysqli_fetch_assoc($query)) {
		if ($i == 0) $max = $album['counter']; ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;album_id=' . $album['album_id'] . '&amp;menu=' . $cfg['menu'] . '\');"><img src="' . $cfg['img'] . 'small_play.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_add']) echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;album_id=' . $album['album_id'] . '&amp;menu=' . $cfg['menu'] . '\');"><img src="' . $cfg['img'] . 'small_add.png" alt="" class="small"></a>'; ?></td>
	<td><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=m3u&amp;album_id=' . $album['album_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1($cfg['server_seed'] . $cfg['sid'], 'm3u' . $album['album_id'] . $cfg['stream_id']) . '"><img src="' . $cfg['img'] . 'small_stream.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td><a href="index.php?action=view2&amp;artist=<?php echo rawurlencode($album['artist_alphabetic']); ?>&amp;order=year"><?php echo html($album['artist']); ?></a></td>
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>" <?php echo imageTitle($album['image_id']); ?>><?php echo html($album['album']); ?></a></td>
	<td></td>
	<td><div title="<?php echo $album['counter']; ?>" class="bar"><div style="width: <?php echo  round($album['counter'] / $max * 100); ?>%;"></div></div></td>
	<td></td>
</tr>
<?php
	}
?>
</table>
</td></tr></table><!-- table tab wrapper -->
<?php
	require_once('include/footer.inc.php');
}
