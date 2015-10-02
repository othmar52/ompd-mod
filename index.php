<?php
//  +------------------------------------------------------------------------+
//  | O!MPD, Copyright � 2015 Artur Sierzant		                         |
//  | http://www.ompd.pl                                             		 |
//  |                                                                        |
//  |                                                                        |
//  | netjukebox, Copyright � 2001-2012 Willem Bartels                       |
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
//error_reporting(-1);
//ini_set("display_errors", 1);



require_once('include/initialize.inc.php');



if (cookie('netjukebox_width')<385) {$base_size = 90;}
elseif (cookie('netjukebox_width')<641) {$base_size = 120;}
else {$base_size = 150;}


$cfg['menu']	= 'Library';
$action 		= get('action');
$tileSizePHP	= get('tileSizePHP')	or $tileSizePHP = false;

if		($action == '')					home();
elseif	($action == 'view1')			view1();
elseif	($action == 'view2')			view2();
elseif	($action == 'view3')			view3();
elseif	($action == 'view1all')			view1all();
elseif	($action == 'view3all')			view3all();
elseif	($action == 'viewRandomAlbum')	viewRandomAlbum();
elseif	($action == 'viewRandomTrack')	viewRandomTrack();
elseif	($action == 'viewYear')			viewYear();
elseif	($action == 'viewNew')			viewNew();
elseif	($action == 'viewPopular')		viewPopular();
else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();







//  +------------------------------------------------------------------------+
//  | Home                                                                   |
//  +------------------------------------------------------------------------+
function home() {
	global $cfg, $db;
	
	authenticate('access_media');
	genreNavigator('start');
	
	?>
	
<script type="text/javascript">
<!--

var baseUrl = 'json.php?action=suggestAlbumArtist&artist=';

function showStatus() {
	alert ('ok');
}
	
function initialize() {
	//document.searchform.txt.name = 'artist';
	//document.searchform.txt.focus();
	//evaluateSuggest('');
}


function evaluateSuggest(list) {
	var suggest;
	if (list == '') {
		suggest = '<form action="">';
		suggest += '<input type="text" value="no suggestion" readonly class="autosugest_readonly">';
		suggest += '<\/form>';
	}
	else {
		suggest = '<form action="" name="suggest" id="suggest" onSubmit="suggestKeyStroke(1)" onClick="suggestKeyStroke(1)" onKeyDown="return suggestKeyStroke(event)">';
		suggest += '<select name="txt" size="6" class="autosugest">';
		for (var i in list)
			suggest += '<option value="' + list[i] + '">' + list[i] + '<\/option>';
		suggest += '<\/select><\/form>';
	}
	//document.getElementById('suggest').innerHTML = suggest;
}


function searchformKeyStroke(e) {
	var keyPressed;
	if (typeof e.keyCode != 'undefined') 	keyPressed = e.keyCode;
	else if (typeof e.which != 'undefined')	keyPressed = e.which;
	if (keyPressed == 40 && typeof document.suggest == 'object') // Down key
		{//document.suggest.txt.focus()
		};
}


function suggestKeyStroke(e) {
	var keyPressed;
	if (e == 1)									keyPressed = 13;
	else if (typeof e.keyCode != 'undefined')	keyPressed = e.keyCode;
	else if (typeof e.which != 'undefined')		keyPressed = e.which;
	if (keyPressed == 13 && document.suggest.txt.value != '') { // Enter key
		if (document.searchform.action.value == 'view1all')
			document.searchform.action.value = 'view3all';
		document.searchform.txt.value = document.suggest.txt.value;
		document.searchform.filter.value = 'exact';
		document.searchform.submit();
		return false;
	}
	else if (keyPressed == 38 && document.suggest.txt.selectedIndex == 0) { // Up key
		document.suggest.txt.selectedIndex = -1;
		document.searchform.txt.focus();
		return false;
	}
}
	

function selectTab(obj) {
	if (obj.id == 'albumartist') {
		document.getElementById('albumartist').className = 'tab_on';
		document.getElementById('trackartist').className = 'tab_off';
		document.getElementById('tracktitle').className  = 'tab_off';
		document.getElementById('albumtitle').className  = 'tab_off';
		document.getElementById('quicksearch').className  = 'tab_off';
		document.getElementById('searchform').style.visibility  = 'visible';
		document.getElementById('quicksearchform').style.visibility  = 'hidden';
		document.searchform.txt.select();
		document.searchform.txt.focus();
		document.searchform.txt.name = 'artist';
		document.searchform.action.value = 'view1';
		baseUrl = 'json.php?action=suggestAlbumArtist&artist=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(document.searchform.txt.value), evaluateSuggest);
	}
	else if (obj.id == 'albumtitle') {
		document.getElementById('albumartist').className = 'tab_off';
		document.getElementById('trackartist').className = 'tab_off';
		document.getElementById('tracktitle').className  = 'tab_off';
		document.getElementById('albumtitle').className  = 'tab_on';
		document.getElementById('quicksearch').className  = 'tab_off';
		document.getElementById('searchform').style.visibility  = 'visible';
		document.getElementById('quicksearchform').style.visibility  = 'hidden';
		document.searchform.txt.select();
		document.searchform.txt.focus();
		document.searchform.txt.name = 'title';
		document.searchform.action.value = 'view2';
		baseUrl = 'json.php?action=suggestAlbumTitle&title=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(document.searchform.txt.value), evaluateSuggest);
	}
	else if (obj.id == 'trackartist') {
		document.getElementById('albumartist').className = 'tab_off';
		document.getElementById('trackartist').className = 'tab_on';
		document.getElementById('tracktitle').className  = 'tab_off';
		document.getElementById('albumtitle').className  = 'tab_off';
		document.getElementById('quicksearch').className  = 'tab_off';
		document.getElementById('searchform').style.visibility  = 'visible';
		document.getElementById('quicksearchform').style.visibility  = 'hidden';
		document.searchform.txt.select();
		document.searchform.txt.focus();
		document.searchform.txt.name = 'artist';
		document.searchform.action.value = 'view1all';
		baseUrl = 'json.php?action=suggestTrackArtist&artist=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(document.searchform.txt.value), evaluateSuggest);
	}
	else if (obj.id == 'tracktitle') {
		document.getElementById('albumartist').className = 'tab_off';
		document.getElementById('trackartist').className = 'tab_off';
		document.getElementById('tracktitle').className  = 'tab_on';
		document.getElementById('albumtitle').className  = 'tab_off';
		document.getElementById('quicksearch').className  = 'tab_off';
		document.getElementById('searchform').style.visibility  = 'visible';
		document.getElementById('quicksearchform').style.visibility  = 'hidden';
		document.searchform.txt.select();
		document.searchform.txt.focus();
		document.searchform.txt.name = 'title';
		document.searchform.action.value = 'view3all';
		baseUrl = 'json.php?action=suggestTrackTitle&title=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(document.searchform.txt.value), evaluateSuggest);
	}
	
	else if (obj.id == 'quicksearch') {
		document.getElementById('albumartist').className = 'tab_off';
		document.getElementById('trackartist').className = 'tab_off';
		document.getElementById('tracktitle').className  = 'tab_off';
		document.getElementById('albumtitle').className  = 'tab_off';
		document.getElementById('quicksearch').className  = 'tab_on';
		document.getElementById('searchform').style.visibility  = 'hidden';
		document.getElementById('quicksearchform').style.visibility  = 'visible';
		document.searchform.txt.select();
		document.searchform.txt.focus();
		document.searchform.txt.name = 'title';
		document.searchform.action.value = 'view3all';
		baseUrl = 'json.php?action=suggestTrackTitle&title=';
		ajaxRequest(baseUrl + <?php echo (NJB_DEFAULT_CHARSET == 'UTF-8') ? 'encodeURIComponent' : 'escape'; ?>(document.searchform.txt.value), evaluateSuggest);
	}
}
//-->
</script>
<!-- <div style="height: 8px;"></div> -->
<div class="area">
<?php viewNewStartPage(); ?>  
</div>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 1                                                                 |
//  +------------------------------------------------------------------------+
function view1() {
	global $cfg, $db;
	authenticate('access_media');
	// TODO: check if genre_id is is genre_id or genre_string
	$artist	 	= get('artist');
	$genre_id 	= get('genre_id');
	$filter  	= get('filter');
	
	if ($genre_id) {
		if (substr($genre_id, -1) == '~') {
			$query = mysql_query('SELECT artist_alphabetic
				FROM album
				WHERE genre_id = "' . mysql_real_escape_string(substr($genre_id, 0, -1)) . '"
				GROUP BY artist_alphabetic
				ORDER BY artist_alphabetic');
		}
		else {
			$query = mysql_query('SELECT artist_alphabetic
				FROM album
				WHERE genre_id LIKE "' . mysql_real_escape_like($genre_id) . '%"
				GROUP BY artist_alphabetic
				ORDER BY artist_alphabetic');
		}
		
		if (mysql_num_rows($query) == 1) {
			view2();
			exit();
		}
		
		// require_once('include/header.inc.php');
		genreNavigator($genre_id);
		
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;genre_id=' . rawurlencode($genre_id) . '&amp;order=artist';
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;genre_id=' . rawurlencode($genre_id) . '&amp;order=artist';
		}
	else {
		if ($filter == '' || $artist == '') {
			$artist = 'All album artists';
			$filter = 'all';
		}
		
		$query = '';
		if ($filter == 'all')			$query = mysql_query('SELECT artist_alphabetic FROM album WHERE 1 GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		elseif ($filter == 'exact')		$query = mysql_query('SELECT artist_alphabetic FROM album WHERE artist_alphabetic = "' . mysql_real_escape_string($artist) . '" OR artist = "' . mysql_real_escape_string($artist) . '" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		elseif ($filter == 'smart')		$query = mysql_query('SELECT artist_alphabetic FROM album WHERE artist_alphabetic LIKE "%' . mysql_real_escape_like($artist) . '%" OR artist LIKE "%' . mysql_real_escape_like($artist) . '%" OR artist SOUNDS LIKE "' . mysql_real_escape_string($artist) . '" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		elseif ($filter == 'start')		$query = mysql_query('SELECT artist_alphabetic FROM album WHERE artist_alphabetic LIKE "' . mysql_real_escape_like($artist) . '%" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		elseif ($filter == 'symbol')	$query = mysql_query('SELECT artist_alphabetic FROM album WHERE artist_alphabetic  NOT BETWEEN "a" AND "zzzzzz" GROUP BY artist_alphabetic ORDER BY artist_alphabetic');
		//elseif ($filter == 'symbol')	$query = mysql_query('SELECT * FROM album ORDER BY artist_alphabetic');
		else							message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
		
		if (mysql_num_rows($query) == 1) {
			$album = mysql_fetch_assoc($query);
			$_GET['artist'] = $album['artist_alphabetic'];
			$_GET['filter'] = 'exact';
			view2();
			exit();
		}
		
		// formattedNavigator
		$nav			= array();
		$nav['name'][]	= 'Library';
		$nav['url'][]	= 'index.php';
		if ($artist != '') $nav['name'][] = $artist;
		require_once('include/header.inc.php');
		
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;artist=' . rawurlencode($artist) . '&amp;filter=' . $filter . '&amp;order=artist';
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;artist=' . rawurlencode($artist) . '&amp;filter=' . $filter . '&amp;order=artist';
	} 

?>
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="space left"></td>
	<td>Artist</td>
	<td align="right">
	<!--
	<a href="<?php echo $thumbnail_url; ?>"><img src="<?php echo $cfg['img']; ?>small_header_thumbnail.png" alt="" class="small"></a>
	-->
	</td>	
	<td align="right" class="right">
	<!--
	<a href="<?php echo $list_url; ?>"><img src="<?php echo $cfg['img']; ?>small_header_list.png" alt="" class="small"></a>
	-->
	&nbsp;
	</td>	
</tr>

<?php
	$i = 0;
	while ($album = mysql_fetch_assoc($query)) {
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
	$isGenreRequired = get('genre_id');
	if ($isGenreRequired)
		genreNavigator($isGenreRequired);

	
	$title	 	= get('title');
	$artist	 	= get('artist');
	$genre_id 	= get('genre_id');
	$tag		= get('tag');
	$year		= (get('year') == 'Unknown' ? get('year'): (int) get('year'));
	$filter  	= get('filter')				or $filter = 'whole';
	//$thumbnail	= get('thumbnail')			? 1 : 0;
	$thumbnail	= 1;
	$order	 	= get('order')				or $order = ($year ? 'artist' : (in_array(strtolower($artist), $cfg['no_album_artist']) ? 'album' : 'year'));
	$sort	 	= get('sort') == 'desc'		? 'desc' : 'asc';
	$qsType 	= (int) get('qsType')				or $qsType = false;
	
	$sort_artist			= 'asc';
	$sort_album				= 'asc';
	$sort_genre				= 'asc';
	$sort_year 				= 'asc';
	$sort_decade			= 'asc';
	
	$order_bitmap_artist	= '<span class="typcn"></span>';
	$order_bitmap_album		= '<span class="typcn"></span>';
	$order_bitmap_genre		= '<span class="typcn"></span>';
	$order_bitmap_year		= '<span class="typcn"></span>';
	$order_bitmap_decade	= '<span class="typcn"></span>';
	
	$yearAct				= 0;
	$yearPrev				= 1;
	
	$page = (get('page') ? get('page') : 1);
	$max_item_per_page = $cfg['max_items_per_page'];
	
	if (isset($_GET['thumbnail'])) {
		mysql_query('UPDATE session
			SET thumbnail	= ' . (int) $thumbnail . '
			WHERE sid		= BINARY "' . mysql_real_escape_string($cfg['sid']) . '"');
	}
	else
		$thumbnail = $cfg['thumbnail'];
	
	
	if ($genre_id || $title) {
		if ($genre_id) {
		//genreNavigator($genre_id);
		
		//if (substr($genre_id, -1) == '~')	$filter_query = 'WHERE genre_id = "' . mysql_real_escape_string(substr($genre_id, 0, -1)) . '"';
		//else								
		$filter_query = 'WHERE genre_id =' . (int)($genre_id) . '';}
		
		else if ($title) {
			genreNavigator('');
			$filter_query = 'WHERE album LIKE "%' . mysql_real_escape_like($title) . '%"';
		}
		
		
		if ($order == 'artist' && $sort == 'asc') {
			$order_query = 'ORDER BY artist_alphabetic, year, month, album';
			$order_bitmap_artist = '<span class="fa fa-sort-alpha-asc"></span>';
			$sort_artist = 'desc';
		}
		elseif ($order == 'artist' && $sort == 'desc') {
			$order_query = 'ORDER BY artist_alphabetic DESC, year DESC, month DESC, album DESC';
			$order_bitmap_artist = '<span class="fa fa-sort-alpha-desc"></span>';
			$sort_artist = 'asc';
		}
		elseif ($order == 'album' && $sort == 'asc') {
			$order_query = 'ORDER BY album, artist_alphabetic, year, month';
			$order_bitmap_album = '<span class="fa fa-sort-alpha-asc"></span>';
			$sort_album = 'desc';
		}
		elseif ($order == 'album' && $sort == 'desc') {
			$order_query = 'ORDER BY album DESC, artist_alphabetic DESC, year DESC, month DESC';
			$order_bitmap_album = '<span class="fa fa-sort-alpha-desc"></span>';
			$sort_album = 'asc';
		}
		elseif ($order == 'genre' && $sort == 'asc') {
			$order_query = 'ORDER BY genre_id, artist_alphabetic, year, month, album';
			$order_bitmap_genre = '<span class="fa fa-sort-alpha-asc"></span>';
			$sort_genre = 'desc';
		}
		elseif ($order == 'genre' && $sort == 'desc') {
			$order_query = 'ORDER BY genre_id DESC, artist_alphabetic DESC, year DESC, month DESC, album DESC';
			$order_bitmap_genre = '<span class="fa fa-sort-alpha-desc"></span>';
			$sort_genre = 'asc';
		}
		elseif ($order == 'year' && $sort == 'asc') {
			$order_query = 'ORDER BY year, month, artist_alphabetic, album';
			$order_bitmap_year = '<span class="fa fa-sort-numeric-asc"></span>';
			$sort_year = 'desc';
		}
		elseif ($order == 'year' && $sort == 'desc') {
			$order_query = 'ORDER BY year DESC, month DESC, artist_alphabetic DESC, album DESC';
			$order_bitmap_year = '<span class="fa fa-sort-numeric-desc"></span>';
			$sort_year = 'asc';
		}
		elseif ($order == 'decade' && $sort == 'asc') {
			$order_query = 'ORDER BY year, month, artist_alphabetic, album';
			$order_bitmap_decade = '<span class="fa fa-sort-numeric-asc"></span>';
			$sort_decade = 'desc';
		}
		elseif ($order == 'decade' && $sort == 'desc') {
			$order_query = 'ORDER BY year DESC, month DESC, artist_alphabetic DESC, album DESC';
			$order_bitmap_decade = '<span class="fa fa-sort-numeric-desc"></span>';
			$sort_decade = 'asc';
		}
		else
			message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]order');
		
		#error_log('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
		$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
		
		$cfg['items_count'] = $album_count = mysql_num_rows($query);
		
		if ($album_count > $max_item_per_page) {
			$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query .
			' LIMIT ' . ($page - 1) * $max_item_per_page . ','  . ($max_item_per_page));
		}
		
		$url			= 'index.php?action=view2&amp;genre_id=' . rawurlencode($genre_id);
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;genre_id=' . rawurlencode($genre_id) . '&amp;filter=' . $filter . '&amp;order=' . $order;
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;genre_id=' . rawurlencode($genre_id) . '&amp;filter=' . $filter . '&amp;order=' . $order;
	}
	elseif ($year) {
		// formattedNavigator
		$queryYear = mysql_query("SELECT MIN(year) as maxYear from album WHERE year > '" . $year . "'");
		$rst = mysql_fetch_assoc($queryYear);
		$maxYear = $rst['maxYear'];
		
		$queryYear = mysql_query("SELECT MAX(year) as minYear from album WHERE year < '" . $year . "'");
		$rst = mysql_fetch_assoc($queryYear);
		$minYear = $rst['minYear'];
		
		$nav = array();
		$nav['name'][]	= 'Library';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= 'Year';
		$nav['url'][]	= 'index.php?action=viewYear';
		if (is_numeric($minYear)) {
			$nav['name'][]	= $minYear;
			$nav['url'][]	= 'index.php?action=view2&year=' . ($minYear);
		}
		$nav['name'][] 	= $year;
		$nav['url'][]	= "";
		if (is_numeric($maxYear) && $year < date("Y")) {
			$nav['name'][]	= $maxYear;
			$nav['url'][]	= 'index.php?action=view2&year=' . ($maxYear);
		}
		require_once('include/header.inc.php');
		
		if ($year == 'Unknown') $filter_query = 'WHERE year is null ';
		else $filter_query = 'WHERE year = ' . (int) $year;
		$url			= 'index.php?action=view2&amp;year=' . $year;
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;year=' . $year . '&amp;order=' . $order . '&amp;sort=' . $sort;
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;year=' . $year . '&amp;order=' . $order . '&amp;sort=' . $sort;
	}
	else {
		if ($filter == 'all' || $artist == '') {
			$artist = 'All albums';
			$filter = 'all';
		}
		
		// formattedNavigator
		$nav			= array();
		$nav['name'][]	= 'Library';
		$nav['url'][]	= 'index.php';
		if ($qsType) $nav['name'][] = $cfg['quick_search'][$qsType][0];
		elseif ($tag) 	$nav['name'][]	= $tag;
		else 	$nav['name'][]	= $artist;
		
		
		
		require_once('include/header.inc.php');
		
		
		
		if ($filter == 'all')			$filter_query = 'WHERE 1';
		elseif ($filter == 'exact')		$filter_query = 'WHERE (artist_alphabetic = "' . mysql_real_escape_string($artist) . '" OR artist = "' . mysql_real_escape_string($artist) . '")';
		elseif ($filter == 'like')		$filter_query = 'WHERE (artist_alphabetic LIKE "%' . mysql_real_escape_string($artist) . '%" OR artist LIKE "%' . mysql_real_escape_string($artist) . '%")';
		elseif ($filter == 'smart')		$filter_query = 'WHERE (artist_alphabetic  LIKE "%' . mysql_real_escape_like($artist) . '%" OR artist LIKE "%' . mysql_real_escape_like($artist) . '%" OR artist SOUNDS LIKE "' . mysql_real_escape_string($artist) . '")';
		elseif ($filter == 'start')		$filter_query = 'WHERE (artist_alphabetic  LIKE "' . mysql_real_escape_like($artist) . '%")';
		elseif ($filter == 'symbol')	$filter_query = 'WHERE (artist_alphabetic  NOT BETWEEN "a" AND "zzzzzz")';
		//elseif ($filter == 'symbol')	$filter_query = '';
		elseif ($filter == 'whole') {
			$art = mysql_real_escape_string($artist);
			$as = $cfg['artist_separator'];
			$count = count($as);
			$i=0;
			$search_str = '';
			
			/* for($i=0; $i<$count; $i++) {
			$search_str .= ' OR artist REGEXP "^(' . $art . ')[[.space.]]*[[.' . $as[$i] . '.]]" 
			OR artist REGEXP "[[.' . $as[$i] . '.]][[.space.]]*(' . $art . ')$" 
			OR artist REGEXP "[[.' . $as[$i] . '.]][[.space.]]*(' . $art . ')[[.space.]]*[[.' . $as[$i] . '.]]"';
			} */
			
			for($i=0; $i<$count; $i++) {
			$search_str .= ' OR artist LIKE "' . $art . '' . $as[$i] . '%" 
			OR artist LIKE "%' . $as[$i] . '' . $art . '" 
			OR artist LIKE "%' . $as[$i] . '' . $art . '' . $as[$i] . '%" 
			OR artist LIKE "% & ' . $art . '' . $as[$i] . '%" 
			OR artist LIKE "%' . $as[$i] . '' . $art . ' & %"';
			//last 2 lines above for artist like 'Mitch & Mitch' in 'Zbigniew Wodecki; Mitch & Mitch; Orchestra and Choir'
			}
			
			
			$filter_query = 'WHERE (
			artist = "' . mysql_real_escape_string($artist) . '"' . $search_str . ')';
			//echo $filter_query;
			/* 
			OR artist LIKE "%' . $as . mysql_real_escape_string($artist) . '" 
			OR artist LIKE "%' . $as . ' ' . mysql_real_escape_string($artist) . '" 
			OR artist LIKE "' . mysql_real_escape_string($artist) . $as . '%"
			OR artist LIKE "' . mysql_real_escape_string($artist) . ' ' . $as . '%"
			
			OR artist LIKE "% ' . mysql_real_escape_string($artist) . '" 
			OR artist LIKE "' . mysql_real_escape_string($artist) . ' %" 
			OR artist LIKE "' . mysql_real_escape_string($artist) . ';%"
			OR artist LIKE "' . mysql_real_escape_string($artist) . ',%"
			OR artist LIKE "% ' . mysql_real_escape_string($artist) . ';%"
			OR artist LIKE "% ' . mysql_real_escape_string($artist) . ',%"
			OR artist LIKE "% ' . mysql_real_escape_string($artist) . ' %"
			 */
		}
		else							message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
		
		$url			= 'index.php?action=view2&amp;artist=' . rawurlencode($artist) . '&amp;filter=' . $filter;
		$list_url		= 'index.php?action=view2&amp;thumbnail=0&amp;artist=' . rawurlencode($artist) . '&amp;filter=' . $filter . '&amp;order=' . $order . '&amp;sort=' . $sort;
		$thumbnail_url	= 'index.php?action=view2&amp;thumbnail=1&amp;artist=' . rawurlencode($artist) . '&amp;filter=' . $filter . '&amp;order=' . $order . '&amp;sort=' . $sort;
	}
	if (($artist || $year)) {
	
		if ($order == 'year' && $sort == 'asc') {
			$order_query = 'ORDER BY year, month, artist_alphabetic, album';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
			$order_bitmap_year = '<span class="fa fa-sort-numeric-asc"></span>';
			$sort_year = 'desc';
		}
		elseif ($order == 'year' && $sort == 'desc') {
			$order_query = 'ORDER BY year DESC, month DESC, artist_alphabetic DESC, album DESC';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
			$order_bitmap_year = '<span class="fa fa-sort-numeric-desc"></span>';
			$sort_year = 'asc';
		}
		elseif ($order == 'decade' && $sort == 'asc') {
			$order_query = 'ORDER BY year, month, artist_alphabetic, album';
			$order_bitmap_decade = '<span class="fa fa-sort-numeric-asc"></span>';
			$sort_decade = 'desc';
		}
		elseif ($order == 'decade' && $sort == 'desc') {
			$order_query = 'ORDER BY year DESC, month DESC, artist_alphabetic DESC, album DESC';
			$order_bitmap_decade = '<span class="fa fa-sort-numeric-desc"></span>';
			$sort_decade = 'asc';
		}
		elseif ($order == 'album' && $sort == 'asc') {
			$order_query = 'ORDER BY album, artist_alphabetic, year, month';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
			$order_bitmap_album = '<span class="fa fa-sort-alpha-asc"></span>';
			$sort_album = 'desc';
		}
		elseif ($order == 'album' && $sort == 'desc') {
			$order_query = 'ORDER BY album DESC, artist_alphabetic DESC, year DESC, month DESC';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
			$order_bitmap_album = '<span class="fa fa-sort-alpha-desc"></span>';
			$sort_album = 'asc';
		}
		elseif ($order == 'artist' && $sort == 'asc') {
			$order_query = 'ORDER BY artist_alphabetic, year, month, album';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
			$order_bitmap_artist = '<span class="fa fa-sort-alpha-asc"></span>';
			$sort_artist = 'desc';
		}
		elseif ($order == 'artist' && $sort == 'desc') {
			$order_query = 'ORDER BY artist_alphabetic DESC, year DESC, month DESC, album DESC';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, genre_id, image_id, album_id FROM album ' . $filter_query . ' ' . $order_query);
			$order_bitmap_artist = '<span class="fa fa-sort-alpha-desc"></span>';
			$sort_artist = 'asc';
		}
		elseif ($order == 'genre' && $sort == 'asc') {
			$order_query = 'ORDER BY genre, artist_alphabetic, year, month';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, album.genre_id, image_id, album_id FROM album, genre ' . $filter_query . ' AND album.genre_id = genre.genre_id ' . $order_query);
			$order_bitmap_genre = '<span class="fa fa-sort-alpha-asc"></span>';
			$sort_genre = 'desc';
		}
		elseif ($order == 'genre' && $sort == 'desc') {
			$order_query = 'ORDER BY genre DESC, artist_alphabetic DESC , year DESC, month DESC';
			//$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, album.genre_id, image_id, album_id FROM album, genre ' . $filter_query . ' AND album.genre_id = genre.genre_id ' . $order_query);
			$order_bitmap_genre = '<span class="fa fa-sort-alpha-desc"></span>';
			$sort_genre = 'asc';
		}
		else message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]order or sort');
		
		$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, album.genre_id, image_id, album_id FROM album, genre ' . $filter_query . ' AND album.genre_id = genre.genre_id ' . $order_query);
		
		$cfg['items_count'] = $album_count = mysql_num_rows($query);
		//echo $filter_query;
		if ($album_count > $max_item_per_page) {
			$query = mysql_query('SELECT album, artist, artist_alphabetic, year, month, album.genre_id, image_id, album_id FROM album, genre ' . $filter_query . ' AND album.genre_id = genre.genre_id ' . $order_query .
			' LIMIT ' . ($page - 1) * $max_item_per_page . ','  . ($max_item_per_page));	
		}
		
	}
	
	if ($tag) {
		$order_query = 'ORDER BY album.artist, year';
		$query_str = ('SELECT album, album.artist, artist_alphabetic, album.year, month, genre_id, image_id, album.album_id, comment FROM album, track WHERE album.album_id=track.album_id AND comment like "%' . $tag . '%" GROUP BY track.album_id ' . $order_query);		
		$order_bitmap_artist = '<span class="fa fa-sort-alpha-asc"></span>';
		$sort_album = 'desc';
		
		$query = mysql_query($query_str);
			$cfg['items_count'] = $album_count = mysql_num_rows($query);
			if ($album_count > $max_item_per_page) {
				$query_str = $query_str . ' LIMIT ' . ($page - 1) * $max_item_per_page . ','  . ($max_item_per_page);
				$query = mysql_query($query_str);
			}
		
	}
	
	if ($qsType) {
	
		$order_query = 'ORDER BY album.artist, year';
		$query_str = ('SELECT album, album.artist, artist_alphabetic, album.year, month, genre_id, image_id, album.album_id FROM album, track WHERE album.album_id=track.album_id AND (' . $cfg['quick_search'][$qsType][1] . ') GROUP BY track.album_id ' . $order_query);		
		$order_bitmap_artist = '<span class="fa fa-sort-alpha-asc"></span>';
		$sort_album = 'desc';
			
			$query = mysql_query($query_str);
			$cfg['items_count'] = $album_count = mysql_num_rows($query);
			if ($album_count > $max_item_per_page) {
				$query_str = $query_str . ' LIMIT ' . ($page - 1) * $max_item_per_page . ','  . ($max_item_per_page);
				$query = mysql_query($query_str);
			}	
		}
	
	
	
	
//  +------------------------------------------------------------------------+
//  | View 2 - thumbnail mode                                                |
//  +------------------------------------------------------------------------+
	if ($thumbnail) {
	
	global $base_size, $spaces, $scroll_bar_correction, $tileSizePHP;
	$rowsTA = 0;
	$group_found = 'none';
	$display_all_tracks = false;
	$i			= 0;
	
	//$colombs	= floor((cookie('netjukebox_width') - 20) / ($size + 10));
	$sort_url	= $url;
	$size_url	= $url . '&amp;order=' . $order . '&amp;sort=' . $sort;
	
	/* $base		= (cookie('netjukebox_width') - 20) / ($base_size + 10);
	$colombs	= floor($base);
	$aval_width = (cookie('netjukebox_width') - 20 - $scroll_bar_correction) - ($colombs - 1) * $spaces;
	$size = floor($aval_width / $colombs);
	 */
	$rows = mysql_num_rows($query);
	
	$resultsFound = false;
	
	if ($rows > 0) {
		$display_all_tracks = true;
		$resultsFound = true;
	
	?>
	
<table cellspacing="0" cellpadding="0" class="border">
<tr>
	<td colspan="<?php echo $colombs + 2; ?>">
	<!-- begin table header -->
	<table width="100%" cellspacing="0" cellpadding="0">
	<tr class="header">
		
		<td>
		<?php if (!($tag || $qsType)) {?>
			<a <?php echo ($order_bitmap_artist == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $sort_url; ?>&amp;order=artist&amp;sort=<?php echo $sort_artist; ?>">&nbsp;Artist <?php echo $order_bitmap_artist; ?></a>
			&nbsp;<a <?php echo ($order_bitmap_album == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $sort_url; ?>&amp;order=album&amp;sort=<?php echo $sort_album; ?>">Album <?php echo $order_bitmap_album; ?></a>
			&nbsp;<a <?php echo ($order_bitmap_genre == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $sort_url; ?>&amp;order=genre&amp;sort=<?php echo $sort_genre; ?>">Genre <?php echo $order_bitmap_genre; ?></a>
			&nbsp;<a <?php echo ($order_bitmap_year == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $sort_url; ?>&amp;order=year&amp;sort=<?php echo $sort_year; ?>">Year <?php echo $order_bitmap_year; ?></a>
			&nbsp;<a <?php echo ($order_bitmap_decade == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $sort_url; ?>&amp;order=decade&amp;sort=<?php echo $sort_decade; ?>">Decade <?php echo $order_bitmap_decade; ?></a>
		<?php };?>
		</td>
		<td align="right" class="right">
			(<?php echo ($album_count > 1) ? $album_count . ' albums' :  $album_count . ' album' ?> found)&nbsp;
		</td>
	</tr>
	</table>
	<!-- end table header -->
	</td>
</tr>
</table>
<div class="albums_container">
<?php
	while ($album = mysql_fetch_assoc($query)) {		
			if ($album) {
				if ($order == 'decade') {
					$yearAct = floor(($album['year'])/10) * 10;
					if ($yearAct != $yearPrev){
						echo '<div class="decade">' . $yearAct . '\'s</div>';
					}
					else {
						//echo '<div style="clear: both;">Act: ' . $yearAct . ' Prev: ' . $yearPrev . '</div>';
					}
				}
				if ($tileSizePHP) $size = $tileSizePHP;
				draw_tile($size,$album);
				$yearPrev = $yearAct;
			}
		} 
?>
</div>



<?php
}; //if $rows > 0


if ($filter == 'whole' && !$genre_id && !$year) {
//  +------------------------------------------------------------------------+
//  | track artist                                                           |
//  +------------------------------------------------------------------------+
	
	
	$filter_queryTA = str_replace('artist ','track.artist ',$filter_query);
	$queryTA = mysql_query('SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, track.number, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id '
	. $filter_queryTA . 
	' AND (track.artist <> album.artist) 
	AND (album.artist NOT LIKE "%' . mysql_real_escape_string(get('artist')) . '%")
	GROUP BY track.artist');
	
	
	//WHERE track.artist LIKE "%' . mysql_real_escape_string($search_string) . '%"
	$rows = mysql_num_rows($queryTA);
	
	if ($rows > 0) {
		if($rows > 1) $display_all_tracks = false;
		$match_found = true;
		if ($group_found == 'none') $group_found = 'TA';
?>
<h1 onclick='toggleSearchResults("TA");' class="pointer"><i id="iconSearchResultsTA" class="fa fa-chevron-circle-down icon-anchor"></i> Track artist (<?php if ($rows > 1) {
			echo $rows . " matches found";
		}
		else {
			$album = mysql_fetch_assoc($queryTA);
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
	<td align="right" class="time">Time</td>
	<td class="space right"></td>
</tr>

<?php
	$i=0;
	$queryTA = mysql_query('SELECT track.artist as track_artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds, track.relative_file, track.number, album.image_id, album.album, album.artist
	FROM track
	INNER JOIN album ON track.album_id = album.album_id '
	. $filter_queryTA .
	' AND (track.artist <> album.artist)
	AND (album.artist NOT LIKE "%' . mysql_real_escape_string(get('artist')) . '%")
	ORDER BY track.artist, album.album');
	
	$rowsTA = mysql_num_rows($queryTA);
	
	while ($track = mysql_fetch_assoc($queryTA)) {
			$resultsFound = true;?>
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
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>';?>
	</span>
	</td>
		
	<td class="track-list-artist"><?php if (mysql_num_rows(mysql_query('SELECT track_id FROM track WHERE track.artist="' . mysql_real_escape_string($track['track_artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['track_artist']) . '&amp;order=year">' . html($track['track_artist']) . '</a>'; else echo html($track['track_artist']); ?></td>
	
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?>
	<span class="track-list-artist-narrow">by <?php echo html($track['track_artist']); ?></span> 
	</td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $track['album_id']; ?>" <?php echo onmouseoverImage($track['image_id']); ?>><?php echo html($track['album']); ?></a></td>
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
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Insert after currently playing track and play</a>'; ?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Insert track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-indent fa-fw icon-small"></i>Insert after currently playing track</a>';?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i>Add track to playlist</a>';?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Remove all from playlist and play track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i>Stream track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_download']) echo '<a href="download.php?action=downloadTrack&amp;track_id=' . $track['track_id'] .'&amp;download_id=' . $cfg['download_id'] . '" ' . onmouseoverDownloadTrack($track['track_id']) . '><i class="fa fa-download fa-fw icon-small"></i>Download track</a>'; ?>
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
	echo '<script>';
	if ($group_found != 'none') { echo 'toggleSearchResults("' . $group_found . '")';}
	echo '</script>';
}
//End of Track artist	

if ($resultsFound == false && $group_found == 'none') echo 'No results found.';

} //if ($filter == 'whole')

?>

<table cellspacing="0" cellpadding="0" class="border">
<tr style="display:none" class="smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<tr style="display:none" class="line"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<?php
	$query = mysql_query('SELECT artist FROM album ' . $filter_query . ' GROUP BY artist');
	if ((mysql_num_rows($query) < 2) && $display_all_tracks) {
		$album = mysql_fetch_assoc($query);
		if ($album['artist'] == '') $album['artist'] = $artist;
		$query = mysql_query('SELECT album_id from track where artist = "' . mysql_real_escape_string($album['artist']) . '"');
		$tracks = mysql_num_rows($query);
?>

<tr class="footer">
	<td colspan="<?php echo $colombs; ?>">&nbsp;<a href="index.php?action=view3all&amp;artist=<?php echo rawurlencode($album['artist']); ?>&amp;order=title">View all <?php echo ($tracks + $rowsTA) . ((($tracks + $rowsTA) == 1) ? ' track from ' : ' tracks from ') . html($album['artist']); ?></a></td>
	<td></td>
	<td></td>
</tr>
<?php
	} ?>

</table>

<?php

}



/* 
//  +------------------------------------------------------------------------+
//  | View 2 - list mode                                                     |
//  +------------------------------------------------------------------------+
	else { ?>
<form action="genre.php" method="post" name="genreform" id="genreform">
	<input type="hidden" name="action" value="edit">
	<input type="hidden" name="genre_id" value="<?php echo html($genre_id); ?>">
	<input type="hidden" name="artist" value="<?php echo html($artist); ?>">
	<input type="hidden" name="filter" value="<?php echo html($filter); ?>">
	<input type="hidden" name="order" value="<?php echo html($order); ?>">
	<input type="hidden" name="sort" value="<?php echo html($sort); ?>">
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="space"></td>
	<td></td><!-- bitmap -->
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td><a href="<?php echo $url; ?>&amp;order=artist&amp;sort=<?php echo $sort_artist; ?>">Artist&nbsp;<?php echo $order_bitmap_artist; ?></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=album&amp;sort=<?php echo $sort_album; ?>">Album&nbsp;<?php echo $order_bitmap_album; ?></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=genre&amp;sort=<?php echo $sort_genre; ?>">Genre&nbsp;<?php echo $order_bitmap_genre; ?></a></td>
	<td class="textspace"></td>
	<td><a href="<?php echo $url; ?>&amp;order=year&amp;sort=<?php echo $sort_year; ?>">Year&nbsp;<?php echo $order_bitmap_year; ?></a></td>
	<td align="right"><a href="<?php echo $thumbnail_url; ?>"><img src="<?php echo $cfg['img']; ?>small_header_thumbnail.png" alt="" class="small"></a></td>
</tr>

<?php
		$i = 0;
		while ($album = mysql_fetch_assoc($query)) {
			$genre_id = $album['genre_id'];
			$genre = mysql_fetch_assoc(mysql_query('SELECT genre FROM genre WHERE genre_id = "' . mysql_real_escape_string($genre_id) . '"')); ?>
<tr class="list <?php echo ($i++ & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>" onMouseOver="return overlib('<?php echo formattedDate($album['year'], $album['month']); ?>');" onMouseOut="return nd();"><img src="image.php?image_id=<?php echo $album['image_id']; ?>" alt="" width="50" height="50" class="align"></a></td>
	<td></td>
	<td><?php if ($cfg['access_play']) 						echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;album_id=' . $album['album_id'] . '\');" onMouseOver="return overlib(\'Play album\');" onMouseOut="return nd();"><img src="' . $cfg['img'] . 'small_high_play.png" alt="" class="small_high"></a>';
	elseif ($cfg['access_stream'] && !$cfg['access_add'])	echo '<a href="stream.php?action=playlist&amp;album_id=' . $album['album_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream album\');" onMouseOut="return nd();"><img src="' . $cfg['img'] . 'small_high_stream.png" alt="" class="small_high"></a>'; ?></td>
	<td><?php if ($cfg['access_add']) 						echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;album_id=' . $album['album_id'] . '\');" onMouseOver="return overlib(\'Add album\');" onMouseOut="return nd();"><img src="' . $cfg['img'] . 'small_high_add.png" alt="" class="small_high"></a>'; ?></td>
	<td></td>
	<td><a href="index.php?action=view2&amp;artist=<?php echo rawurlencode($album['artist_alphabetic']); ?>"><?php echo html($album['artist_alphabetic']); ?></a></td>
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>"><?php echo html($album['album']); ?></a></td>
	<td></td>
	<td><?php if ($cfg['access_admin']) echo '<input type="checkbox" name="album_id_array[]" value="' . $album['album_id'] . '" class="space">'; ?><a href="index.php?action=view2&amp;order=artist&amp;sort=asc&amp;genre_id=<?php echo $album['genre_id']; ?>"><?php echo html($genre['genre']); ?></a></td>
	<td></td>
	<td><a href="index.php?action=view2&amp;year=<?php echo $album['year']; ?>"><?php echo $album['year']; ?></a></td>
	<td></td>
</tr>
<?php
		}
		$query = mysql_query('SELECT artist FROM album ' . $filter_query . ' GROUP BY artist');
		if (mysql_num_rows($query) < 2) {
			$album = mysql_fetch_assoc($query);
			if ($album['artist'] == '') $album['artist'] = $artist;
			$query = mysql_query('SELECT album_id from track where artist = "' . mysql_real_escape_string($album['artist']) . '"');
			$tracks = mysql_num_rows($query);
?>
<tr class="line"><td colspan="14"></td></tr>
<tr class="footer">
	<td></td>
	<td colspan="9"><a href="index.php?action=view3all&amp;artist=<?php echo rawurlencode($album['artist']); ?>&amp;order=title">View all <?php echo $tracks . (($tracks == 1) ? ' track from ' : ' tracks from ') . html($album['artist']); ?></a></td>
	<td colspan="4"><?php if ($cfg['access_admin']) echo '<img src="' . $cfg['img'] . 'button_small_inverse.png" alt="" class="space" style="cursor: pointer;" onClick="inverseCheckbox(document.genreform);" onMouseOver="return overlib(\'Inverse selection\');" onMouseOut="return nd();"><input type="image" src="' . $cfg['img'] . 'button_small_edit.png" class="space" onMouseOver="return overlib(\'Edit selected genre(s)\');" onMouseOut="return nd();">'; ?></td>
</tr>
</table>
</form>
<?php
		}
		else { ?>
<tr class="line"><td colspan="14"></td></tr>
<tr class="footer">
	<td></td>
	<td colspan="9"></td>
	<td colspan="4"><?php if ($cfg['access_admin']) echo '<img src="' . $cfg['img'] . 'button_small_inverse.png" alt="" border="0" class="space" onClick="inverseCheckbox(document.genreform);" onMouseOver="return overlib(\'Inverse selection\');" onMouseOut="return nd();"><input type="image" src="' . $cfg['img'] . 'button_small_edit.png" class="space" onMouseOver="return overlib(\'Edit selected genre(s)\');" onMouseOut="return nd();">'; ?></td>
</tr>
</table>
</form>
<?php
		}
	}
 */
	
?>

<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 3                                                                 |
//  +------------------------------------------------------------------------+
function view3() {
	global $cfg, $db;

	$album_id = get('album_id');
	
	if ($album_id == '' && $cfg['image_share']) {
		if ($cfg['image_share_mode'] == 'played') {
			$query = mysql_query('SELECT album_id
				FROM counter
				WHERE flag <= 1
				ORDER BY time DESC
				LIMIT 1');
			$counter	= mysql_fetch_assoc($query);
			$album_id	= $counter['album_id'];
		}
		else {
			$query = mysql_query('SELECT album_id, album_add_time
				FROM album
				ORDER BY album_add_time DESC
				LIMIT 1');
			$album		= mysql_fetch_assoc($query);
			$album_id	= $album['album_id'];
		}
	
	header('Location: ' . NJB_HOME_URL . 'index.php?action=view3&album_id=' . rawurldecode($album_id));
	exit();
	}
	
	authenticate('access_media');
	
	$query = mysql_query('SELECT artist_alphabetic, artist, album, year, month, image_id, album_add_time, genre.genre as album_genre, album.genre_id
		FROM album, genre
		WHERE album_id = "' . mysql_real_escape_string($album_id) . '"
		AND genre.genre_id=album.genre_id');
	$album = mysql_fetch_assoc($query);
	$image_id = $album['image_id'];
	
	if ($album == false)
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]' . $album_id . ' not found in database');
	
	$query = mysql_query('SELECT featuring FROM track WHERE featuring != "" AND album_id = "' . mysql_real_escape_string($album_id) . '"');
	if (mysql_fetch_row($query))	$featuring = true;
	else 							$featuring = false;
	
	$query = mysql_query('SELECT audio_bits_per_sample, audio_sample_rate, audio_profile, audio_dataformat, comment FROM track WHERE album_id = "' . mysql_real_escape_string($album_id) . '" 
	LIMIT 1');
	$album_info = mysql_fetch_assoc($query);
	
	$query = mysql_query('SELECT track.relative_file FROM track left join album on album.album_id = track.album_id where album.album_id = "' . mysql_real_escape_string($album_id) . '" 
	LIMIT 1');
	$rel_file = mysql_fetch_assoc($query);
	
	/*$query = mysql_query('SELECT image_front FROM bitmap WHERE image_id="' . mysql_real_escape_string($album['image_id']) . '"');
	$bitmap = mysql_fetch_assoc($query);
	$cover = $cfg['cover_server'] . (string) $bitmap['image_front'];
	*/
	
	$query = mysql_query('SELECT COUNT(c.album_id) as counter, c.time FROM (SELECT time, album_id FROM counter WHERE album_id = "' . mysql_real_escape_string($album_id) . '" ORDER BY time DESC) c ORDER BY c.time');
	$played = mysql_fetch_assoc($query);

	
	
	$query = mysql_query('SELECT artist, artist_alphabetic, album, image_id, album.album_id, COUNT(*) AS counter
			FROM counter, album
			WHERE counter.album_id = album.album_id
			GROUP BY album.album_id
			ORDER BY counter DESC, time DESC
			LIMIT 1');
	$max_played = mysql_fetch_assoc($query);
	
	// formattedNavigator
	$nav			= array();
	/* $nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $album['artist'];
	//$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($album['artist']);
	$nav['url'][]	= 'index.php?action=view2&amp;artist=' . ($album['artist']);
	$nav['name'][]	= '[Genre: ' . trim($album['album_genre']) . ']';
	$nav['url'][]	= 'index.php?action=view2&order=artist&sort=asc&&genre_id=' . $album['genre_id'];
	$nav['name'][]	= '[Year: ' . $album['year'] . ']';
	$nav['url'][]	= 'index.php?action=view2&order=artist&sort=asc&year=' . $album['year'];
	$nav['name'][]	= $album['album'];
	
	$nav = ""; */
	$nav['name'][]	= $album['artist'] . ' - ' . $album['album'];
	
	require_once('include/header.inc.php');
	
	$advanced = array();
	if ($cfg['access_admin'] && $cfg['album_copy'] && is_dir($cfg['external_storage']))
		$advanced[] = '<a href="download.php?action=copyAlbum&amp;album_id='. $album_id . '&amp;sign=' . $cfg['sign'] . '"><i class="fa fa-copy icon-small"></i>Copy album</a>';
	if ($cfg['access_admin'] && $cfg['album_update_image']) {
		$advanced[] = '<a href="update.php?action=imageUpdate&amp;flag=9&amp;album_id='. $album_id . '"><img src="' . $cfg['img'] . 'small_image.png" alt="" class="small space">Update image</a>';
		$advanced[] = '<a href="update.php?action=selectImageUpload&amp;flag=9&amp;album_id='. $album_id . '"><img src="' . $cfg['img'] . 'small_upload.png" alt="" class="small space">Upload image</a>';
	}
	if ($cfg['access_admin'] && $cfg['album_edit_genre'])
		$advanced[] = '<a href="genre.php?action=edit&amp;album_id=' . $album_id . '"><img src="' . $cfg['img'] . 'small_genre.png" alt="" class="small space">Edit genre</a>';
	if ($cfg['access_admin'])
		$advanced[] = '<a href="javascript:showHide(\'basic\',\'advanced\');"><i class="fa fa-reply  icon-small"></i>Go back</a>';
	
	$basic = array();
	$search = array();
	if ($cfg['access_play'])
		$basic[] = '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;album_id=' . $album_id . '\');ajaxRequest(\'additional.php?action=updateAddPlay&amp;album_id=' . $album_id . '\',updateAddPlay);"><i class="fa fa-play-circle-o  icon-small"></i>Play album</a>';
	if ($cfg['access_add'])
		//ajaxRequest(\'play.php?action=addSelect&amp;album_id=' . $album_id . '\');
		$basic[] = '<a href="javascript:ajaxRequest(\'additional.php?action=updateAddPlay&amp;album_id=' . $album_id . '\',updateAddPlay);ajaxRequest(\'play.php?action=addSelect&amp;album_id=' . $album_id . '\');"><i class="fa fa-plus-circle  icon-small"></i>Add to playlist</a>';
	if ($cfg['access_stream'])
		$basic[] = '<a href="stream.php?action=playlist&amp;album_id=' . $album_id . '&amp;stream_id=' . $cfg['stream_id'] . '"><i class="fa fa-rss  icon-small"></i>Stream album</a>';
	if ($cfg['access_download'] && $cfg['album_download'])
		$basic[] = '<a href="download.php?action=downloadAlbum&amp;album_id=' . $album_id . '&amp;download_id=' . $cfg['download_id'] . '" ' . onmouseoverDownloadAlbum($album_id) . '><i class="fa fa-download  icon-small"></i>Download album</a>';
	if ($cfg['access_admin'] && $cfg['album_share_stream'])
		$basic[] = '<a href="stream.php?action=shareAlbum&amp;album_id='. $album_id . '&amp;sign=' . $cfg['sign'] . '"><i class="fa fa-share-square-o  icon-small"></i>Share stream</a>';
	if ($cfg['access_admin'] && $cfg['album_share_download'])
		$basic[] = '<a href="download.php?action=shareAlbum&amp;album_id=' . $album_id . '&amp;sign=' . $cfg['sign'] . '"><i class="fa fa-share-square-o  icon-small"></i>Share download</a>';
	
	$count_basic = count($basic);
	$advanced_enabled = (count($advanced) > 1) ? 1 : 0;
	if (8 - $count_basic - $advanced_enabled < count($cfg['search_name']) ) {
		$basic[] = '<a href="javascript:showHide(\'basic\',\'search\');"><i class="fa fa-search  icon-small"></i>Search...</a>';
		for ($i = 0; $i < count($cfg['search_name']) && $i < 7; $i++)
			$search[] = '<a href="ridirect.php?search_id=' . $i . '&amp;album_id=' . $album_id . '" target="_blank"><i class="fa fa-search  icon-small"></i>' . html($cfg['search_name'][$i]) .'</a>';
		$search[] = '<a href="javascript:showHide(\'basic\',\'search\');"><i class="fa fa-reply  icon-small"></i>Go back</a>';
	}
	else {
		for ($i = 0; $i < count($cfg['search_name']) && $i < 8 - $count_basic; $i++)
			$basic[] = '<a href="ridirect.php?search_id=' . $i . '&amp;album_id=' . $album_id . '" target="_blank"><i class="fa fa-search  icon-small"></i>' . html($cfg['search_name'][$i]) .'</a>';
	}
	if ($cfg['access_admin'] && $advanced_enabled)
		$basic[] = '<a href="javascript:showHide(\'basic\',\'advanced\');"><i class="fa fa-cogs icon-small"></i>Advanced...</a>';

	
	
	if (strpos(strtolower($rel_file['relative_file']), strtolower($cfg['misc_tracks_folder'])) !== false) {
		$album['year'] = '';
		$album_info['audio_bits_per_sample'] = '';
		$album_info['audio_sample_rate'] = '';
		$album_info['audio_dataformat'] = '';
		$album_info['audio_profile'] = '';
	}	
	elseif (strpos(strtolower($rel_file['relative_file']), strtolower($cfg['misc_tracks_misc_artists_folder'])) !== false) {
		$album['year'] = '';
		$album['album_genre'] = '';
		$album_info['audio_bits_per_sample'] = '';
		$album_info['audio_sample_rate'] = '';
		$album_info['audio_dataformat'] = '';
		$album_info['audio_profile'] = '';
		
	}
	
?>


<div id="album-info-area">
<div id="image_container">
	<div id="cover-spinner">
		<img src="image/loader.gif" alt="">
	</div>
	<span id="image">
		<img id="image_in" src="image/transparent.gif" alt="">
	</span>
</div>


<!-- start options -->



<div class="album-info-area-right">

<div id="album-info" class="line">
	<div class="sign-play" onclick=<?php echo '"javascript:ajaxRequest(\'play.php?action=playSelect&amp;album_id=' . $album_id . '\');ajaxRequest(\'additional.php?action=updateAddPlay&amp;album_id=' . $album_id . '\',updateAddPlay);"' ?>>
	<i class="fa fa-play-circle-o pointer"></i>
		
	</div>
	<div class="col-right">
		<div id="album-info-title"><?php echo $album['album']?></div>
		<div id="album-info-artist"><?php 
		$artist = '';
		$exploded = multiexplode($cfg['artist_separator'],$album['artist']);
		$l = count($exploded);
		if ($l > 1) {
			for ($i=0; $i<$l; $i++) {
				$artist = $artist . '<a href="index.php?action=view2&amp;artist=' . rawurlencode($exploded[$i]) . '">' . html($exploded[$i]) . '</a>';
				if ($i != $l - 1) $artist = $artist . '<a href="index.php?action=view2&amp;artist=' . rawurlencode($album['artist']) . '&amp;order=year"><span class="artist_all">&</span></a>';
			}
			echo $artist;
		}
		else {
			echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($album['artist']) . '&amp;order=year">' . html($album['artist']) . '</a>';
		}
		?></div>
	</div>
</div>
<div class="line">
<div class="add-info-left">Popularity:</div>
<div id="bar-popularity-out" class="out"><div id="bar_popularity" class="in"></div></div>
&nbsp;
<span id="popularity"><?php echo round($played['counter'] / $max_played['counter'] * 100) ?></span>%
</div>

<div id="additional-info">
	<?php if ($album['album_genre'] != '') { ?>
	<div class="line">
		<div class="add-info-left">Genre:</div>
		<div class="add-info-right"><a href="<?php echo 'index.php?action=view2&order=artist&sort=asc&&genre_id=' . $album['genre_id'];?>"><?php echo trim($album['album_genre']);?></a></div>
	</div>
	<?php }; ?>
	
	<?php if ($album['year'] != '') { ?>
	<div class="line">
		<div class="add-info-left">Year:</div>
		<div class="add-info-right"><a href="<?php echo 'index.php?action=view2&order=artist&sort=asc&year=' . $album['year'];?>"><?php echo trim($album['year']);?></a></div>
	</div>
	<?php }; ?>
	
	<?php if (($album_info['audio_bits_per_sample'] != '') && ($album_info['audio_sample_rate'] != '')) { ?>
	<div class="line">
		<div class="add-info-left">File format:
		</div>
		<div class="add-info-right">
		<?php 
		echo   
		 '' . $album_info['audio_bits_per_sample'] . 'bit - ' . $album_info['audio_sample_rate']/1000 . 'kHz '; 
		 ?>
		</div>
	</div>
	<?php }; ?>
	
	<?php if ($album_info['audio_dataformat'] != '' && $album_info['audio_profile'] != '') { ?>
	<div class="line">
		<div class="add-info-left">File type:
		</div>
		<div class="add-info-right">
		<?php 
		if ($album_info['audio_dataformat'] != '' && $album_info['audio_profile'] != '')
		echo strtoupper($album_info['audio_dataformat']) . ' - ' . $album_info['audio_profile'] . ''; ?>
		</div>
	</div>
	<?php }; ?>
	
	<div class="line">
		<div class="add-info-left">Added at:</div>
		<div class="add-info-right"><?php echo date("Y-m-d H:i:s",$album['album_add_time']); ?>
		</div>
	</div>
	
	<div class="line">
		<div class="add-info-left">Played:</div>
		<div class="add-info-right"><span id="played"><?php 
		if ($played['counter'] == 0) {
			echo 'Never';
		}
		else {
			echo $played['counter']; 
			echo ($played['counter'] == 1) ? ' time' : ' times'; 
		}
		?></span>
		</div>
	</div>
	
	<div class="line">
		<div class="add-info-left">Last time:</div>
		<div class="add-info-right"><span id="last_played"><?php echo ($played['time']) ? (date("Y-m-d H:i",$played['time']) . '<span id="playedCal" class=" icon-anchor" onclick="togglePlayedHistory();">&nbsp;&nbsp;<i class="fa fa-calendar fa-lg"></i></span>') : '-'; ?></span>
		</div>
	</div>
	
	<div id="playedHistory" class="line" style="display: none;">
		<div class="add-info-left"></div>
		<div class="add-info-right">Played on:</div>
		<?php 
		$queryHist = mysql_query('SELECT time, album_id FROM counter WHERE album_id = "' . mysql_real_escape_string($album_id) . '" ORDER BY time DESC');
		while($playedHistory = mysql_fetch_assoc($queryHist)) { ?>
		<div class="add-info-left"></div>
		<div class="add-info-right"><span><?php echo ($playedHistory['time']) ? date("Y-m-d H:i",$playedHistory['time']) : '-'; ?></span>
		</div>
		<?php } ?>
	</div>
	
	<?php if ($album_info['comment']) { ?>
	<div class="line">
		<div class="add-info-left"><i class="fa fa-tags fa-lg"></i> Tags:</div>
		<div class="add-info-right"><div class="buttons">
		<?php
			$sep = 'no_sep';
			if (strpos($album_info['comment'],$cfg['tags_separator']) !== false) {
				$sep = $cfg['tags_separator'];
			}
			elseif ($cfg['testing'] == 'on' && strpos($album_info['comment']," ") !== false) {
				$sep = " ";
			}
			if ($sep != 'no_sep') {
				$tags = array_filter(explode($sep,$album_info['comment']));
				foreach ($tags as $value) { 
					echo '<span><a href="index.php?action=view2&order=artist&sort=asc&&tag=' . trim($value) . '">' . trim($value) . '</a></span>' ;
					//echo '<span>' . ($value) . '</span>' ;
				}
			}
			else {
				echo '<span><a href="index.php?action=view2&order=artist&sort=asc&&tag=' . $album_info['comment'] . '">' . $album_info['comment'] . '</a></span>' ;
				//echo '<span>' . ($album_info['comment']) . '</span>';
			}
		?>
		</div>
		</div>
	</div>
	<?php }; ?>
</div>

<br>	
<table cellspacing="0" cellpadding="0" id="basic" class="fullscreen">
<?php
	for ($i = 0; $i < 8; $i=$i+2) { ?>
<tr class="<?php echo ($i & 1) ? 'even_info' : 'odd_info'; ?> nowrap" style="height: 35px;">
	<td class="halfscreen"><?php echo (isset($basic[$i])) ? $basic[$i] : '&nbsp;'; ?></td>
	<td class="halfscreen"><?php echo (isset($basic[$i+1])) ? $basic[$i+1] : '&nbsp;'; ?></td>
	<td></td>
</tr>

<?php
	} ?>

</table>
<table cellspacing="0" cellpadding="0" id="search" style="display: none;" class="fullscreen">
<?php
	for ($i = 0; $i < 8; $i=$i+2) { ?>
<tr class="<?php echo ($i & 1) ? 'even_info' : 'odd_info'; ?> nowrap" style="height: 35px;">
	<td class="halfscreen"><?php echo (isset($search[$i])) ? $search[$i] : '&nbsp;'; ?></td>
	<td class="halfscreen"><?php echo (isset($search[$i+1])) ? $search[$i+1] : '&nbsp;'; ?></td>
</tr>
<?php
	} ?>
</table>
<table cellspacing="0" cellpadding="0" id="advanced" style="display: none;">
<?php
	for ($i = 0; $i < 8; $i=$i+2) { ?>
<tr class="<?php echo ($i & 1) ? 'even_info' : 'odd_info'; ?> nowrap" style="height: 35px;">
	<td<?php echo ($i == 0) ? ' class="space"' : ''; ?>></td>
	<td><?php echo (isset($advanced[$i])) ? $advanced[$i] : '&nbsp;'; ?></td>
	<td<?php echo ($i == 0) ? ' class="vertical_line"' : ''; ?>></td>
	<td><?php echo (isset($advanced[$i+1])) ? $advanced[$i+1] : '&nbsp;'; ?></td>
</tr>
<?php
	} ?>
</table>
<br>
</div>
<!-- end options -->	
</div>

<div id="playlist">
<span  class="playlist-title">Tracklist</span>
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="icon"></td><!-- track menu -->
	<td class="icon"></td>
	<td class="trackNumber">#</td>
	<td>Title</td>
	<td class="track-list-artist">Artist</td>
	<td class="textspace track-list-artist"></td>
	<td><?php if ($featuring) echo'Featuring'; ?></td><!-- optional featuring -->
	<td ></td>
	<td align="right" class="time">Time</td>
	<td class="space right"><div class="space"></div></td>
</tr>
<?php
	$query = mysql_query('SELECT discs FROM album WHERE album_id = "' . mysql_real_escape_string($album_id) . '"');
	$album = mysql_fetch_assoc($query);
	for ($disc = 1; $disc <= $album['discs']; $disc++) {
		/* $query = mysql_query('
		SELECT track.track_artist, track.artist, track.title, track.featuring, track.miliseconds, track.track_id, track.number, favoriteitem.favorite_id
		FROM track LEFT JOIN favoriteitem ON track.track_id = favoriteitem.track_id
		WHERE album_id = "' . mysql_real_escape_string($album_id) . '" AND disc = ' . (int) $disc . ' 
		GROUP BY track.track_id
		ORDER BY relative_file');
		 */
		$query = mysql_query('
		SELECT track.track_artist, track.artist, track.title, track.featuring, track.miliseconds, track.track_id, track.number, track.relative_file
		FROM track 
		WHERE album_id = "' . mysql_real_escape_string($album_id) . '" AND disc = ' . (int) $disc . ' 
		GROUP BY track.track_id
		ORDER BY number,relative_file');
		$i = 0;
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
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>';?>
	</span>
	</td>
	
	<td class="trackNumber"><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();">' . html($track['number']) . '.</a>';?></td>
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track ' . $track['number'] . '\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?>
	<span class="track-list-artist-narrow">by <?php echo html($track['track_artist']); ?></span>		
	</td>
	
	<td class="track-list-artist">
	<?php
	$artist = '';
		$exploded = multiexplode($cfg['artist_separator'],$track['track_artist']);
		$l = count($exploded);
		if ($l > 1) {
			for ($j=0; $j<$l; $j++) {
				$artist = $artist . '<a href="index.php?action=view2&amp;artist=' . rawurlencode($exploded[$j]) . '">' . html($exploded[$j]) . '</a>';
				if ($j != $l - 1) $artist = $artist . '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['track_artist']) . '&amp;order=year"><span class="artist_all">&</span></a>';
			}
			echo $artist;
		}
		else {
			echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['track_artist']) . '&amp;order=year">' . html($track['track_artist']) . '</a>';
		}
		?>
	
	<?php /* if (mysql_num_rows(mysql_query('SELECT track_id FROM track WHERE track_artist like "%' . mysql_real_escape_string($track['track_artist']) . '%"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['track_artist']) . '&amp;order=year">' . html($track['track_artist']) . '</a>'; else echo html($track['track_artist']);  */?>
	</td>
	<td class="track-list-artist"></td>
	<td><?php if ($track['featuring']) echo html($track['featuring']); ?></td>
	<?php
	$queryFav = mysql_query("SELECT favorite_id FROM favoriteitem WHERE track_id = '" . $track['track_id'] . "' AND favorite_id = '" . $cfg['favorite_id'] . "'");
	$isFavorite = mysql_num_rows($queryFav);
	?>
	
	<td onclick="
		var action = '';
		if ($('#favorite_star-<?php echo $track['track_id'] ?>').attr('class') == 'fa fa-star-o') {
			action = 'add';
			}
		else {
			action = 'remove';
		}
		ajaxRequest('ajax-favorite.php?action=' + action + '&track_id=<?php echo $track['track_id'] ?>', setFavorite);
	" class="pl-favorites"><i class="fa fa-star<?php if (!$isFavorite) echo '-o'?>" id="favorite_star-<?php echo $track['track_id'] ?>"></i></td>
	<td align="right"><?php echo formattedTime($track['miliseconds']); ?></td>
	<td></td>
</tr>
<tr class="line">
	<td></td>
	<td colspan="16"></td>
</tr>

<tr>
<td colspan="10">
<div class="menuSub" id="menu-sub-track<?php echo $i ?>" onclick='offMenuSub(<?php echo $i ?>);'> 
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Insert after currently playing track and play</a>'; ?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Insert track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-indent fa-fw icon-small"></i>Insert after currently playing track</a>';?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i>Add track to playlist</a>';?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Remove all from playlist and play track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i>Stream track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_download']) echo '<a href="download.php?action=downloadTrack&amp;track_id=' . $track['track_id'] .'&amp;download_id=' . $cfg['download_id'] . '" ' . onmouseoverDownloadTrack($track['track_id']) . '><i class="fa fa-download fa-fw icon-small"></i>Download track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="getid3/demos/demo.browse.php?filename='. $cfg['media_dir'] . urlencode($track['relative_file']) . '" onClick="showSpinner();"><i class="fa fa-info-circle fa-fw icon-small"></i>File details</a>'; ?>
	</div>
	
</div>
</td>
</tr>
<?php
		}
		$query = mysql_query('SELECT SUM(miliseconds) AS sum_miliseconds FROM track WHERE album_id = "' . mysql_real_escape_string($album_id) . '" AND disc = ' . (int) $disc);
		$track = mysql_fetch_assoc($query); ?>

<!--
<tr class="footer">
	<td class="track-list-artist"></td>
	<td class="track-list-artist"></td>
	<td class="track-list-artist"></td>
	<td colspan="7" align="right">Total: <?php echo formattedTime($track['sum_miliseconds']); ?></td>
	
	<td></td>
</tr>
-->
<script type="text/javascript">

function setBarLength() {
	$('#bar_popularity').css('width',function() { return (<?php echo floor($played['counter'] / $max_played['counter'] * 100) ?> * 1/100 * $('#bar-popularity-out').width())} );
	//document.getElementById('bar_popularity').style.width="50px";
	return(true);
};

function setAlbumInfoWidth() {
	$('#album-info').css('maxWidth', function() {return ($(window).width() - 10 +'px')});
};

function setFavorite(data) {
	if (data.action == "add") {
		$("#favorite_star-" + data.track_id).removeClass("fa fa-star-o").addClass("fa fa-star");
	}
	else if (data.action == "remove") {
		$("#favorite_star-" + data.track_id).removeClass("fa fa-star").addClass("fa fa-star-o");
	}
};

window.onload = function () {
    //setAlbumInfoWidth();
	setBarLength();
	$("#image_in").attr("src","image.php?image_id=<?php echo $image_id ?>&quality=hq");
	$("#cover-spinner").hide();
	
	/* var im = "image.php?image_id=<?php echo $image_id ?>&quality=hq"
	
	$('#bgTemp').remove();
	
	//$('head').append('<style id="bgTemp">#back-ground:before{background-image:url(' + im + ') !important;}</style>');
	
	$("#back-ground-img").attr("src",im); */
	
	return(true);
};

/*window.onresize = function () {
    //setAlbumInfoWidth();
	//setBarLength();
	return(true);
};
*/


</script>
<?php if ($disc < $album['discs']) echo '<tr class="line"><td colspan="15"></td></tr>' . "\n";
	}
	echo '</table>';
?>
<div><h1><div class="total-time">Total: <?php echo formattedTime($track['sum_miliseconds']); ?></div></h1>
</div>
<?php
	echo '</div>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View 1 all                                                             |
//  +------------------------------------------------------------------------+
function view1all() {
	global $cfg, $db;
	authenticate('access_media');
	
	$artist	 	= get('artist');
	$filter  	= get('filter');
	
	if ($artist == '') {
		$artist = 'All track artists';
		$filter = 'all';
	}
	
	if ($filter == 'all')		$query = mysql_query('SELECT artist FROM track WHERE 1 GROUP BY artist ORDER BY artist');
	elseif ($filter == 'smart')	$query = mysql_query('SELECT artist FROM track WHERE artist LIKE "%' . mysql_real_escape_like($artist) . '%" OR artist SOUNDS LIKE "' . mysql_real_escape_like($artist) . '" GROUP BY artist ORDER BY artist');
	else						message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= $artist;
	require_once('include/header.inc.php');
?>
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="space"></td>
	<td>Artist</td>
	<td class="space"></td>
</tr>

<?php
	$i = 0;
	while ($track = mysql_fetch_assoc($query))	{ ?>
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
	
	$artist	 	= get('artist');
	$title	 	= get('title');
	$filter  	= get('filter')				or $filter	= 'start';
	$order	 	= get('order')				or $order	= 'title';
	$sort	 	= get('sort') == 'desc'		? 'desc' : 'asc';
	
	$sort_artist 			= 'asc';
	$sort_title 			= 'asc';
	$sort_featuring 		= 'asc';
	$sort_album 			= 'asc';
	
	$order_bitmap_artist	= '<span class="typcn"></span>';
	$order_bitmap_title		= '<span class="typcn"></span>';
	$order_bitmap_featuring = '<span class="typcn"></span>';
	$order_bitmap_album		= '<span class="typcn"></span>';
	
	if (strlen($title) >= 1) {
		// formattedNavigator
		$nav			= array();
		$nav['name'][]	= 'Library';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= $title;
		require_once('include/header.inc.php');
		
		if ($filter == 'start')	{
			/* $title = strtolower($title);
			$separator = $cfg['separator'];
			$count = count($separator);
			$i=0;
			for ($i=0; $i<$count; $i++) {
				$pos = strpos($title,strtolower($separator[$i]));
				if ($pos !== false) {
					$title = trim(substr($title, 0 , $pos));
					//break;
				}
			} */
			$separator = $cfg['separator'];
			$count = count($separator);
			$title = findCoreTrackTitle($title);
			$title = mysql_real_escape_like($title);
			
			$query_string = '';
			$i=0;
			for ($i=0; $i<$count; $i++) {
				$query_string = $query_string . ' OR LOWER(track.title) LIKE "' . $title . $separator[$i] . '%"'; 
			}
				
			$filter_query = 'WHERE (LOWER(track.title) = "' . ($title) . '" ' . $query_string . ') AND track.album_id = album.album_id';
			//echo $filter_query;
		}
		elseif ($filter == 'smart')	$filter_query = 'WHERE (track.title LIKE "%' . mysql_real_escape_like($title) . '%" OR track.title SOUNDS LIKE "' . mysql_real_escape_string($title) . '") AND track.album_id = album.album_id';
		elseif ($filter == 'exact')	$filter_query = 'WHERE track.title = "' . mysql_real_escape_string($title) . '" AND track.album_id = album.album_id';
		else						message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]filter');
		
		$url = 'index.php?action=view3all&amp;title=' . rawurlencode($title) . '&amp;filter=' . $filter;
	}
	elseif (strlen($artist) >= 1) {
		// formattedNavigator
		$nav			= array();
		$nav['name'][]	= 'Library';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= $artist;
		$nav['url'][]	= 'index.php?action=view2&amp;artist=' . rawurlencode($artist) . '&amp;order=year';
		$nav['name'][]	= 'All tracks';
		require_once('include/header.inc.php');
		
		$filter_query = 'WHERE track.artist="' . mysql_real_escape_string($artist) . '" AND track.album_id = album.album_id';
		//$filter_query = 'WHERE track.artist="' . mysql_real_escape_string($artist) . '"';
		$url = 'index.php?action=view3all&amp;artist=' . rawurlencode($artist);
	}
	else
		message(__FILE__, __LINE__, 'warning', '[b]Search string too short - min. 2 characters[/b][br][url=index.php][img]small_back.png[/img]Back to previous page[/url]');
	
	if ($order == 'artist' && $sort == 'asc') {
		$order_query = 'ORDER BY artist, title';
		$order_bitmap_artist = '<span class="fa fa-sort-alpha-asc"></span>';
		$sort_artist = 'desc';
	}
	elseif ($order == 'artist' && $sort == 'desc') {
		$order_query = 'ORDER BY artist DESC, title DESC';
		$order_bitmap_artist = '<span class="fa fa-sort-alpha-desc"></span>';
		$sort_artist = 'asc';
	}
	elseif ($order == 'title' && $sort == 'asc') {
		$order_query = 'ORDER BY title, album';
		$order_bitmap_title = '<span class="fa fa-sort-alpha-asc"></span>';
		$sort_title = 'desc';
	}
	elseif ($order == 'title' && $sort == 'desc') {
		$order_query = 'ORDER BY title DESC, album DESC';
		$order_bitmap_title = '<span class="fa fa-sort-alpha-desc"></span>';
		$sort_title = 'asc';
	}
	elseif ($order == 'featuring' && $sort == 'asc') {
		$order_query = 'ORDER BY featuring, title, artist';
		$order_bitmap_featuring = '<span class="fa fa-sort-alpha-asc"></span>';
		$sort_featuring = 'desc';
	}
	elseif ($order == 'featuring' && $sort == 'desc') {
		$order_query = 'ORDER BY featuring DESC, title DESC, artist DESC';
		$order_bitmap_featuring = '<span class="fa fa-sort-alpha-desc"></span>';
		$sort_featuring = 'asc';
	}
	elseif ($order == 'album' && $sort == 'asc') {
		$order_query = 'ORDER BY album, relative_file';
		$order_bitmap_album = '<span class="fa fa-sort-alpha-asc"></span>';
		$sort_album = 'desc';
	}
	elseif ($order == 'album' && $sort == 'desc') {
		$order_query = 'ORDER BY album DESC, relative_file DESC';
		$order_bitmap_album = '<span class="fa fa-sort-alpha-desc"></span>';
		$sort_album = 'asc';
	}
	else
		message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]order');
	
	/*$query = mysql_query('SELECT featuring FROM track, album ' . $filter_query . ' AND featuring <> ""');
	if (mysql_fetch_row($query))	$featuring = true;
	else							$featuring = false;
	*/	
?>
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="icon">&nbsp;</td><!-- track menu -->
	<td class="icon">&nbsp;</td><!-- add track -->
	<td class="track-list-artist"><a <?php echo ($order_bitmap_artist == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $url; ?>&amp;order=artist&amp;sort=<?php echo $sort_artist; ?>">Artist&nbsp;<?php echo $order_bitmap_artist; ?></a></td>
	<td><a <?php echo ($order_bitmap_title == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $url; ?>&amp;order=title&amp;sort=<?php echo $sort_title; ?>">Title&nbsp;<?php echo $order_bitmap_title; ?></a></td>
	<td><a <?php echo ($order_bitmap_album == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="<?php echo $url; ?>&amp;order=album&amp;sort=<?php echo $sort_album; ?>">Album&nbsp;<?php echo $order_bitmap_album; ?></a></td>
	<td align="right" class="time">Time</td>
	<td class="space right"></td>
</tr>

<?php
	$i=0;
	$query = mysql_query('SELECT track.artist, track.title, track.number, track.featuring, track.album_id, track.track_id, track.miliseconds, track.relative_file, album.image_id, album.album FROM track, album ' . $filter_query . ' ' . $order_query);
	//$query = mysql_query('SELECT track.artist, track.title, track.featuring, track.album_id, track.track_id, track.miliseconds  FROM track ' . $filter_query . ' ' . $order_query);
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
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>';?>
	</span>
	</td>
		
	<td class="track-list-artist"><?php if (mysql_num_rows(mysql_query('SELECT track_id FROM track WHERE artist="' . mysql_real_escape_string($track['artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['artist']) . '&amp;order=year">' . html($track['artist']) . '</a>'; else echo html($track['artist']); ?></td>
	
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?>
		<span class="track-list-artist-narrow">by <?php echo html($track['artist']); ?></span>
	</td>
	
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $track['album_id']; ?>" <?php echo onmouseoverImage($track['image_id']); ?>><?php echo html($track['album']); ?></a></td>
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
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Insert after currently playing track and play</a>'; ?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Insert track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-indent fa-fw icon-small"></i>Insert after currently playing track</a>';?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i>Add track to playlist</a>';?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Play track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Remove all from playlist and play track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track ' . $track['number'] . '\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i>Stream track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_download']) echo '<a href="download.php?action=downloadTrack&amp;track_id=' . $track['track_id'] .'&amp;download_id=' . $cfg['download_id'] . '" ' . onmouseoverDownloadTrack($track['track_id']) . '><i class="fa fa-download fa-fw icon-small"></i>Download track</a>'; ?>
	</div>
	
	<div><?php if ($cfg['access_play']) echo '<a href="getid3/demos/demo.browse.php?filename='. $cfg['media_dir'] . urlencode($track['relative_file']) . '" onClick="showSpinner();"><i class="fa fa-info-circle fa-fw icon-small"></i>File details</a>'; ?>
	</div>
	
</div>
</td>
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
	global $base_size, $spaces, $scroll_bar_correction, $tileSizePHP;
	
	authenticate('access_media');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'Random';
	
	require_once('include/header.inc.php');
	
	$base		= (cookie('netjukebox_width') - 20) / ($base_size + 10);
	$colombs	= floor($base);
	$aval_width = (cookie('netjukebox_width') - 20 - $scroll_bar_correction) - ($colombs - 1) * $spaces;
	$size = floor($aval_width / $colombs);

	
?>
<table cellspacing="0" cellpadding="0" style="width: 100%;">
<tr>
	<td>
<!--  -->
<table cellspacing="0" cellpadding="0" class="tab">
<tr>
	<td class="tab_on" onClick="location.href='index.php?action=viewRandomAlbum';">Album</td>
	<td class="tab_none tabspace"></td>
	<td class="tab_off" onClick="location.href='index.php?action=viewRandomTrack';">Track</td>
	<td class="tab_none tabspace"></td>
	<td class="tab_off" onClick="location.href='genre.php?action=blacklist';">Blacklist</td>
	<td class="tab_none">&nbsp;</td>
	
</tr>
</table>
<table width="100%" cellspacing="0" cellpadding="0" class="tab_border">
<tr>
	<td colspan="<?php echo $colombs + 2; ?>">
	<!-- begin table header -->
	
	<!-- end table header -->
	</td>
</tr>
<tr class="line"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>

<tr class="odd smallspace"><td></td></tr>



</table>
<div class="albums_container">
<?php
	$blacklist = explode(',', $cfg['random_blacklist']);
	$blacklist = '"' . implode('","', $blacklist) . '"';
	$query = mysql_query('SELECT artist_alphabetic, album, genre_id, year, month, image_id, album_id
		FROM album
		WHERE genre_id = "" OR genre_id NOT IN (' . $blacklist . ')
		ORDER BY RAND()
		LIMIT ' . (int) $colombs * 2);
	while ($album = mysql_fetch_assoc($query)) {		
			if ($album) {
			if ($tileSizePHP) $size = $tileSizePHP;
			draw_tile($size,$album);
			}
		} 
?>
</div>

<table width="100%" cellspacing="0" cellpadding="0" class="tab_border">

<tr class="<?php echo $class; ?> smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
</table>
<!--  -->
	</td>
</tr>
</table>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View random track                                                      |
//  +------------------------------------------------------------------------+
function viewRandomTrack() {
	global $cfg, $db;
	
	authenticate('access_media');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'Random';
	
	require_once('include/header.inc.php');
?>
<table cellspacing="0" cellpadding="0" style="width: 100%;">
<tr>
	<td>
<!--  -->
<table cellspacing="0" cellpadding="0" class="tab">
<tr>
	<td class="tab_off" onClick="location.href='index.php?action=viewRandomAlbum';">Album</td>
	<td class="tab_none tabspace"></td>
	<td class="tab_on" onClick="location.href='index.php?action=viewRandomTrack';">Track</td>
	<td class="tab_none tabspace"></td>
	<td class="tab_off" onClick="location.href='genre.php?action=blacklist';">Blacklist</td>
	<td class="tab_none">&nbsp;</td>
</tr>
</table>
<table width="100%" cellspacing="0" cellpadding="0" class="tab_border">
<?php
	if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) { ?>
<tr class="tab_header">
	<td></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td colspan="4"></td>
	<td></td>
</tr>
<!--
<tr class="odd mouseover">
	<td></td>
	<td class="icon"><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=new\');" onMouseOver="return overlib(\'Play random tracks\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i></a>'; ?></td>
	<td class="icon"><?php if ($cfg['access_add']) echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;random=new\');" onMouseOver="return overlib(\'Add random tracks\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>'; ?></td>
	<td class="icon"><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;random=new&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream random tracks\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i></a>'; ?></td>
	<td></td>
	<td colspan="3"><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=new\');" onMouseOver="return overlib(\'Play random tracks\');" onMouseOut="return nd();">Play random tracks from list below:</a>';
	elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=&amp;random=new\');" onMouseOver="return overlib(\'Add random tracks\');" onMouseOut="return nd();">Add random tracks from list below:</a>';
	elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;random=new&amp;stream_id=' . $cfg['stream_id']  . '" onMouseOver="return overlib(\'Stream random tracks\');" onMouseOut="return nd();">Stream random tracks from list below:</a>'; ?></td>
	<td></td>
</tr>
-->
<tr class="even mouseover">
	<td></td>
	<td class="icon"><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=database\');" onMouseOver="return overlib(\'Play playlist\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i></a>'; ?></td>
	<td class="icon"><?php if ($cfg['access_add']) echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;random=database\');" onMouseOver="return overlib(\'Add playlist\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>'; ?></td>
	<td class="icon"><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;random=database&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream playlist\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i></a>'; ?></td>
	<td></td>
	<td colspan="3"><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;random=database\');" onMouseOver="return overlib(\'Play playlist\');" onMouseOut="return nd();">Play random list shown below:</a>';
	elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=&amp;random=database\');" onMouseOver="return overlib(\'Add playlist\');" onMouseOut="return nd();">Add random list shown below:</a>';
	elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;random=database&amp;stream_id=' . $cfg['stream_id']  . '" onMouseOver="return overlib(\'Stream playlist\');" onMouseOut="return nd();">Stream random list shown below:</a>'; ?></td>
	<td></td>
</tr>
<tr class="line"><td colspan="9"></td></tr>
<?php
	} ?>
<tr class="tab_header">
	<td class="space"></td>
	<td></td><!-- optional play -->
	<td></td><!-- optional add -->
	<td></td><!-- optional stream -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>&nbsp;&nbsp;Artist</td>
	<td class="textspace"></td>
	<td>Title</td>
	<td class="space"></td>
</tr>
<?php
	mysql_query('DELETE FROM random WHERE sid = "' . mysql_real_escape_string($cfg['sid']) . '"');
	
	$i = 0;
	$blacklist = explode(',', $cfg['random_blacklist']);
	$blacklist = '"' . implode('","', $blacklist) . '"';
	$query = mysql_query('SELECT track.artist, title, track_id
		FROM track, album
		WHERE (genre_id = "" OR genre_id NOT IN (' . $blacklist . ')) AND
		audio_dataformat != "" AND
		video_dataformat = "" AND
		track.album_id = album.album_id
		ORDER BY RAND()
		LIMIT 30');
	while ($track = mysql_fetch_assoc($query)) {
		mysql_query('INSERT INTO random (sid, track_id, position, create_time) VALUES (
			"' . mysql_real_escape_string($cfg['sid']) . '",
			"' . mysql_real_escape_string($track['track_id']) . '",
			"' . mysql_real_escape_string($i) . '",
			"' . mysql_real_escape_string(time()) . '")'); ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td class="icon"><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Insert and play track\');" onMouseOut="return nd();"><i class="fa fa-play-circle-o fa-fw icon-small"></i></a>'; ?></td>
	<td class="icon"><?php if ($cfg['access_add']) echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track\');" onMouseOut="return nd();"><i class="fa fa-plus-circle fa-fw icon-small"></i></a>'; ?></td>
	<td class="icon"><?php if ($cfg['access_stream']) echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track\');" onMouseOut="return nd();"><i class="fa fa-rss fa-fw icon-small"></i></a>'; ?></td>
	<td></td>
	<td><?php if (mysql_num_rows(mysql_query('SELECT track_id FROM track WHERE artist="' . mysql_real_escape_string($track['artist']) . '"')) > 1) echo '<a href="index.php?action=view2&amp;artist=' . rawurlencode($track['artist']) . '&amp;order=year">' . html($track['artist']) . '</a>'; else echo html($track['artist']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_play']) 		echo '<a href="javascript:ajaxRequest(\'play.php?action=insertSelect&amp;playAfterInsert=yes&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Insert and play track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_add'])		echo '<a href="javascript:ajaxRequest(\'play.php?action=addSelect&amp;track_id=' . $track['track_id'] . '\');" onMouseOver="return overlib(\'Add track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			elseif ($cfg['access_stream'])	echo '<a href="stream.php?action=playlist&amp;track_id=' . $track['track_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '" onMouseOver="return overlib(\'Stream track\');" onMouseOut="return nd();">' . html($track['title']) . '</a>';
			else 							echo html($track['title']); ?></td>
	<td></td>
</tr>
<?php
	} ?>
</table>
<!--  -->
	</td>
</tr>
</table>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View year                                                              |
//  +------------------------------------------------------------------------+
function viewYear() {
	global $cfg, $db;
	
	authenticate('access_media');
	
	$sort = get('sort') == 'asc' ? 'asc' : 'desc';
	
	if ($sort == 'asc') {
		$order_query = 'ORDER BY year';
		$order_bitmap_year = '<span class="fa fa-sort-numeric-asc"></span>';
		$sort_year = 'desc';
	}
	else {
		// desc
		$order_query = 'ORDER BY year DESC';
		$order_bitmap_year = '<span class="fa fa-sort-numeric-desc"></span>';
		$sort_year = 'asc';
	}
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'Year';
	require_once('include/header.inc.php');
?>
<table cellspacing="0" cellpadding="0" class="border">
<tr class="header">
	<td class="space left"></td>
	<td width="80px"><a <?php echo ($order_bitmap_year == '<span class="typcn"></span>') ? '':'class="sort_selected"';?> href="index.php?action=viewYear&amp;sort=<?php echo $sort_year; ?>">Year&nbsp;<?php echo $order_bitmap_year; ?></a></td>	
	<td align="left" class="bar">Graph</td>
	<td align="center" width="130px">Album counts</td>
	<td class="right">&nbsp;</td>
</tr>

<?php
	$query = mysql_query('SELECT COUNT(*) AS counter
		FROM album
		WHERE year
		GROUP BY year
		ORDER BY counter DESC');
	$album = mysql_fetch_assoc($query);
	$max = $album['counter'];
	
	$query = mysql_query('SELECT COUNT(discs) AS albums, SUM(discs) AS discs FROM album');
	$album = mysql_fetch_assoc($query);
	$all = $album['albums'];
	
	$i=0;
	$query = mysql_query('SELECT album,
		COUNT(*) AS counter
		FROM album
		WHERE year is null');
	$album = mysql_fetch_assoc($query);
	$yearNULL = $album['counter'];
	if ($yearNULL > 0) {
?>
<tr class="<?php echo ($i++ & 1) ? 'year' : 'year'; ?> mouseover">
	<td></td>
	<td><a href="index.php?action=view2&amp;year=Unknown">Unknown</a></td>
	<td class="bar" style="cursor: pointer;" onClick="window.location.href='<?php echo NJB_HOME_URL ?>index.php?action=view2&amp;year=Unknown';"><div class="out"><div id="yNULL" style="width: 0px;" class="in"></div></div></td>
	<td align="center"><?php echo $album['counter']; ?> (<?php echo  round($album['counter'] / $all * 100, 1); ?>%)</td>
	<td> </td>
	
</tr>
<?php	
	}
	$i=1;
	$query = mysql_query('SELECT year,
		COUNT(*) AS counter
		FROM album
		WHERE year
		GROUP BY year ' . $order_query);
	while ($max && $album = mysql_fetch_assoc($query)) {
?>
<tr class="<?php echo ($i++ & 1) ? 'year' : 'year'; ?> mouseover">
	<td></td>
	<td><a href="index.php?action=view2&amp;year=<?php echo $album['year']; ?>"><?php echo $album['year']; ?></a></td>
	<td class="bar" style="cursor: pointer;" onClick="window.location.href='<?php echo NJB_HOME_URL ?>index.php?action=view2&amp;year=<?php echo $album['year']; ?>';"><div class="out"><div id="y<?php echo $album['year']; ?>" style="width: 0px;" class="in"></div></div></td>
	<td align="center"><?php echo $album['counter']; ?> (<?php echo  round($album['counter'] / $all * 100, 1); ?>%)</td>
	<td> </td>
	
</tr>
<?php
	}
	$query = mysql_query('SELECT year,
		COUNT(*) AS counter
		FROM album
		WHERE year
		GROUP BY year ' . $order_query);
		
	echo '</table>' . "\n";
	echo '<script type="text/javascript">' . "\n";
	echo 'function setYearBar() {' . "\n";
	if ($yearNULL>0) {
	echo 'document.getElementById(\'yNULL\').style.width="' . round($yearNULL / $max * 200) . 'px";' . "\n";}
	while ($max && $album = mysql_fetch_assoc($query)) {
	//echo floor($album['counter'] / $max_played['counter'] * 100) . ' * 1/100 * $(\'#bar-popularity-out\').width() + \'px\';' . "\n";
		
		echo 'document.getElementById(\'y' . $album['year'] .'\').style.width="' . round($album['counter'] / $max * 200) . 'px";' . "\n";
		//echo '$(\'#y'. $album['year'] .'\').transition({ width: \'' . round($album['counter'] / $max * 200) .  'px\', duration: 2000 });' . "\n";
		}
	echo '}' . "\n";
	echo 'window.onload = function () {' . "\n";
    echo 'setYearBar();' . "\n";
	echo '};' . "\n";
	echo '</script>' . "\n";
	
	require_once('include/footer.inc.php');
}






//  +------------------------------------------------------------------------+
//  | View new on start page                                                 |
//  +------------------------------------------------------------------------+
function viewNewStartPage() {
	global $cfg, $db;
	global $base_size, $spaces, $scroll_bar_correction, $tileSizePHP;
	
	//authenticate('access_media');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'New';
	
require_once('include/header.inc.php');

	$i			= 0;
	/*
	$base		= (cookie('netjukebox_width') - 20) / ($base_size + 10);
	$colombs	= floor($base);
	$aval_width = (cookie('netjukebox_width') - 20 - $scroll_bar_correction) - ($colombs - 1) * $spaces;
	$size = floor($aval_width / $colombs);
	*/
	
	//$sort_url	= $url;
	//$size_url	= $url . '&amp;order=' . $order . '&amp;sort=' . $sort;
	
	$query = mysql_query('SELECT SUM(discs) AS discs FROM album');
	$album = mysql_fetch_assoc($query);
	$discs = floor($album['discs'] * 0.6);
	$time4months = time() - (60 * 60 * 24 * 7 * 12);
	
	if ($album['discs'] > 0) {
?>

<h1>&nbsp;Albums not played for more then 3 months (random)&nbsp;&nbsp;&nbsp;<i class="fa fa-refresh pointer icon-anchor larger" id="iframeRefresh"></i></h1>

<div class="full">
<div id="suggested_container">

<?php
	
	
	
	/* $query = mysql_query('SELECT *
						FROM (
							SELECT album.artist, album.artist_alphabetic, album.album, album.image_id, album.album_id, q.last_time, q.counter
							FROM album
							LEFT JOIN (
								SELECT counter.album_id, max(counter.time) as last_time , count( counter.album_id ) AS counter
								FROM counter
								GROUP BY counter.album_id						
							)q ON album.album_id = q.album_id						
						)a
						WHERE a.last_time < ' . $time4months . ' or a.last_time IS NULL
						ORDER BY RAND()
						LIMIT 10');
	
	while ($album = mysql_fetch_assoc($query)) {
		if ($tileSizePHP) $size = $tileSizePHP;
		draw_tile($size,$album);
	} */
?>
</div>
</div>
<!-- <iframe src="suggested.php" class="full" id="suggested"></iframe> -->

<script>



$('#iframeRefresh').click(function() {	
	//$( '#suggested' ).attr( 'src', function ( i, val ) { return val; });
	/*$.post( "ajax-suggested.php", function( data ) {  
		$( "#suggested_container" ).html( data );
	
	});
	*/
	$('#iframeRefresh').removeClass("icon-anchor");
	$('#iframeRefresh').addClass("icon-selected fa-spin");
	var size = $tileSize;
	var request = $.ajax({  
		url: "ajax-suggested.php",  
		type: "POST",  
		data: { tileSize : size },  
		dataType: "html"
	}); 
	
	request.done(function( data ) {  
		$( "#suggested_container" ).html( data );
		calcTileSize();
	}); 
	
	request.fail(function( jqXHR, textStatus ) {  
		//alert( "Request failed: " + textStatus );	
	}); 

	request.always(function() {
		$('#iframeRefresh').addClass("icon-anchor");
		$('#iframeRefresh').removeClass("icon-selected fa-spin");
	});

});

$(document).ready(function () {
	$('#iframeRefresh').click();
});

</script>

<h1>&nbsp;New albums</h1>

<div class="albums_container">
<?php
	$query = mysql_query('SELECT COUNT(*) AS counter
		FROM album
		WHERE album_add_time');
	$items_count = mysql_fetch_assoc($query);
	
	$cfg['items_count'] = $items_count['counter'];

	$query = mysql_query('SELECT *
		FROM album
		WHERE album_add_time
		ORDER BY album_add_time DESC, album DESC
		LIMIT ' . $cfg['max_items_per_page']);
		
	while ($album = mysql_fetch_assoc($query)) {		
			if ($album) {
			if ($tileSizePHP) $size = $tileSizePHP;
			draw_tile($size,$album);
			}
		} 
?>
</div>

<?php
} //albums > 0
else {
?>
<div>
<h1>
<br>
Welcome to O!MPD.<br><br>
Your database is empty. Please <a href="config.php">update it.</a><br><br>
<!-- Your database is empty. Please <a href="update.php?action=update&amp;sign=<?php echo $cfg['sign']; ?>">update it.</a><br><br>
-->
</h1>
</div>
<?php
}
?>


<table cellspacing="0" cellpadding="0" class="border">
	<tr class="line"><td colspan="11"></td></tr>
</table>

<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View new                                                               |
//  +------------------------------------------------------------------------+
function viewNew() {
	global $cfg, $db;
	global $base_size, $spaces, $scroll_bar_correction;
	
	authenticate('access_media');
	
	// formattedNavigator
	$nav			= array();
	$nav['name'][]	= 'Library';
	$nav['url'][]	= 'index.php';
	$nav['name'][]	= 'New';
require_once('include/header.inc.php');

	//$size = get('size');
	//$size = $cfg['thumbnail_size'];
	$i			= 0;
	//$colombs	= floor((cookie('netjukebox_width') - 20) / ($size + 10));
	
	/*$base		= (cookie('netjukebox_width') - 20) / ($base_size + 10);
	$colombs	= floor($base);
	$aval_width = (cookie('netjukebox_width') - 20 - $scroll_bar_correction) - ($colombs - 1) * $spaces;
	$size = floor($aval_width / $colombs);
	*/
	
	//$sort_url	= $url;
	//$size_url	= $url . '&amp;order=' . $order . '&amp;sort=' . $sort;
	
	$query = mysql_query('SELECT COUNT(*) AS counter
		FROM album
		WHERE album_add_time');
	$items_count = mysql_fetch_assoc($query);
	
	$cfg['items_count'] = $items_count['counter'];
	
	$page = get('page');
	$max_item_per_page = $cfg['max_items_per_page'];
	
	$query = mysql_query('SELECT *
		FROM album
		WHERE album_add_time
		ORDER BY album_add_time DESC
		LIMIT ' . ($page - 1) * $max_item_per_page . ','  . ($max_item_per_page));	
		//$colombs * 20);
	
?>


<h1>
new albums
</h1>


<div class="albums_container">
<?php
	while ($album = mysql_fetch_assoc($query)) {		
			if ($album) {
			if ($tileSizePHP) $size = $tileSizePHP;
			draw_tile($size,$album);
			}
		} 
?>
</div>

<table cellspacing="0" cellpadding="0" class="border">

<tr class="<?php echo $class; ?> smallspace"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<tr class="line"><td colspan="<?php echo $colombs + 2; ?>"></td></tr>
<?php
	//$query = mysql_query('SELECT artist FROM album ' . $filter_query . ' GROUP BY artist');
	if (mysql_num_rows($query) < 2) {
		$album = mysql_fetch_assoc($query);
		if ($album['artist'] == '') $album['artist'] = $artist;
		$query = mysql_query('SELECT album_id from track where artist = "' . mysql_real_escape_string($album['artist']) . '"');
		$tracks = mysql_num_rows($query);
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
	
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | View popular                                                           |
//  +------------------------------------------------------------------------+
function viewPopular() {
	global $cfg, $db;
	
	
	$period		= get('period');
	$user_id 	= (int) get('user_id');
	$flag	 	= (int) get('flag');
	
	if		($period == 'week')		$days = 7;
	elseif	($period == 'month')	$days = 31;
	elseif	($period == 'year')		$days = 365;
	elseif	($period == 'overall')	$days = 365 * 1000;
	else							message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]period');
	
	if ($user_id == 0) {
		authenticate('access_popular');
		
		// formattedNavigator
		$nav			= array();
		$nav['name'][]	= 'Library';
		$nav['url'][]	= 'index.php';
		$nav['name'][]	= 'Popular';
		
		$query_pop = mysql_query('SELECT artist, artist_alphabetic, album, image_id, album.album_id, COUNT(*) AS counter
			FROM counter, album
			WHERE counter.flag <= 1
			AND counter.time > ' . (int) (time() - 86400 * $days) . '
			AND counter.album_id = album.album_id
			GROUP BY album.album_id
			ORDER BY counter DESC, time DESC
			LIMIT 50');
		//echo 'num_rows: ' . mysql_num_rows($query);
		$url = 'index.php?action=viewPopular';
	}
	else {
		authenticate('access_admin');
		
		$cfg['menu'] = 'config';
		$query = mysql_query('SELECT username FROM user WHERE user_id = ' . (int) $user_id);
		$user = mysql_fetch_assoc($query);
		if ($user == false)
			message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]user_id not found in database');
		
		// formattedNavigator
		$nav			= array();
		$nav['name'][]	= 'Configuration';
		$nav['url'][]	= 'config.php';
		$nav['name'][]	= 'User statistics';
		$nav['url'][]	= 'users.php?action=userStatistics&amp;period=' . $period;
		if		($flag == 0) $nav['name'][] = 'Play: ' . $user['username'];
		elseif	($flag == 1) $nav['name'][] = 'Stream: ' . $user['username'];
		elseif	($flag == 2) $nav['name'][] = 'Download: ' . $user['username'];
		elseif	($flag == 3) $nav['name'][] = 'Cover: ' . $user['username'];
		elseif	($flag == 4) $nav['name'][] = 'Record: ' . $user['username'];
		else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]flag');
		
		$query_pop = mysql_query('SELECT artist, artist_alphabetic, album, image_id, album.album_id, COUNT(*) AS counter
			FROM counter, album
			WHERE user_id = ' . (int) $user_id . '
			AND counter.flag = ' . $flag . '
			AND counter.time > ' . (int) (time() - 86400 * $days) . '
			AND counter.album_id = album.album_id
			GROUP BY album.album_id
			ORDER BY counter DESC, time DESC
			LIMIT 50');
		
		$url = 'index.php?action=viewPopular&amp;flag=' . $flag . '&amp;user_id=' . $user_id;
	}
	require_once('include/header.inc.php'); 
	?>
<table cellspacing="0" cellpadding="0">
<tr>
	<td>
<!--  -->
<table cellspacing="0" cellpadding="0" class="tab">
<tr>
	
	<td class="<?php echo ($period == 'week') ? 'tab_on' : 'tab_off'; ?>" onClick="location.href='<?php echo $url; ?>&amp;period=week';">Week</td>
	<td class="tab_none tabspace"></td>
	<td class="<?php echo ($period == 'month') ? 'tab_on' : 'tab_off'; ?>" onClick="location.href='<?php echo $url; ?>&amp;period=month';">Month</td>
	<td class="tab_none tabspace"></td>
	<td class="<?php echo ($period == 'year') ? 'tab_on' : 'tab_off'; ?>" onClick="location.href='<?php echo $url; ?>&amp;period=year';">Year</td>
	<td class="tab_none tabspace"></td>
	<td class="<?php echo ($period == 'overall') ? 'tab_on' : 'tab_off'; ?>" onClick="location.href='<?php echo $url; ?>&amp;period=overall';">Overall</td>
	<td class="tab_none">&nbsp;</td>
</tr>
</table>
<table width="100%" cellspacing="0" cellpadding="0" class="tab_border">
<tr class="tab_header">
	<td class="icon"></td><!-- menu -->
	<td<?php if ($cfg['access_play'] || $cfg['access_add'] || $cfg['access_stream']) echo' class="space"'; ?>></td>
	<td>Artist</td>
	<td class="textspace"></td>
	<td>Album</td>
	<td class="textspace"></td>
	<td colspan="2">Count</td>
	<td colspan="2"></td>
	<td class="space"></td>
</tr>

<?php
	$query = mysql_query('SELECT artist, artist_alphabetic, album, image_id, album.album_id, COUNT(*) AS counter
			FROM counter, album
			WHERE user_id = ' . (int) $user_id . '
			AND counter.flag = ' . $flag . '
			AND counter.time > ' . (int) (time() - 86400 * $days) . '
			AND counter.album_id = album.album_id
			GROUP BY album.album_id
			ORDER BY counter DESC, time DESC
			LIMIT 50');
	$i=0;
	while ($album = mysql_fetch_assoc($query_pop)) {
		if ($i == 0) $max = $album['counter']; ?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td class="icon">
	<span id="menu-track<?php echo $i ?>">
	<div onclick='toggleMenuSub(<?php echo $i ?>);'>
		<i id="menu-icon<?php echo $i ?>" class="fa fa-bars icon-small"></i>
	</div>
	</span>
	</td>
	<td></td>
	<td><a href="index.php?action=view2&amp;artist=<?php echo rawurlencode($album['artist_alphabetic']); ?>&amp;order=year"><?php echo html($album['artist']); ?></a></td>
	<td></td>
	<td><a href="index.php?action=view3&amp;album_id=<?php echo $album['album_id']; ?>" <?php echo onmouseoverImage($album['image_id']); ?>><?php echo html($album['album']); ?></a></td>
	<td></td>
	<td class="bar_space">&nbsp;</td>
	<td><?php echo $album['counter']; ?> &nbsp;</td>
	<td class="bar" onMouseOver="return overlib('<?php echo $album['counter']; ?>');" onMouseOut="return nd();">
	<div class="out-popular"><div style="width: <?php echo  round($album['counter'] / $max * 100); ?>px;" class="in"></div></div>
	</td>
	<td class="bar_space">&nbsp;</td>
	<td></td>
</tr>
<tr class="line">
	<td></td>
	<td colspan="16"></td>
</tr>
<tr>
<td colspan="16">
<div class="menuSub" id="menu-sub-track<?php echo $i ?>" onclick='offMenuSub(<?php echo $i ?>);'> 
	
	<div><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playSelect&amp;album_id=' . $album['album_id'] . '\');"><i class="fa fa-play-circle-o fa-fw icon-small"></i>Play album</a>'; ?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="javascript:ajaxRequest(\'additional.php?action=updateAddPlay&amp;album_id=' . $album['album_id'] . '\',updateAddPlay);"><i class="fa fa-plus-circle fa-fw icon-small"></i>Add to playlist</a>';?>
	</div>
	
	<div>
	<?php if ($cfg['access_add'])  echo '<a href="stream.php?action=playlist&amp;album_id=' . $album['album_id'] . '&amp;stream_id=' . $cfg['stream_id'] . '"><i class="fa fa-rss fa-fw icon-small"></i>Stream album</a>';?>
	</div>
	
	
</div>
</td>
</tr>

<?php
	}
?>
</table>
<!--  -->
	</td>
</tr>
</table>
<?php
	require_once('include/footer.inc.php');
}
?>