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
//  | css hash                                                               |
//  +------------------------------------------------------------------------+
function css_hash() {
	global $cfg;
	
	$hash_data =  filemtime(NJB_HOME_DIR . 'cache.php');
	$hash_data .= filemtime(NJB_HOME_DIR . 'skin/' . $cfg['skin'] . '/style.css');
	
	return md5($hash_data);
}




//  +------------------------------------------------------------------------+
//  | javascript hash                                                        |
//  +------------------------------------------------------------------------+
function javascript_hash() {
	$source = array('cache.php',
					'javascript-src/library.js',
					'javascript-src/tooltip.js',
					'javascript-src/sha1.js');
	
	$hash_data = '';
	foreach ($source as $file)
		$hash_data .= filemtime(NJB_HOME_DIR . $file);
	
	return md5($hash_data);
}




//  +------------------------------------------------------------------------+
//  | Navigator                                                              |
//  +------------------------------------------------------------------------+
$breadcrumbs = '';
$header['navigator'] = '';
if (empty($nav['name']) == false) {
	$max_index = count($nav['name']) -1;
	
	for ($i = 0; $i <= $max_index; $i++)
		$nav['class'][$i] = empty($nav['class'][$i]) ? 'nav' : $nav['class'][$i];
	
	// breadcrumbs
	$keys = array_keys($nav['class'], 'nav');
	foreach ($keys as $key => $value)
		$breadcrumbs .= $nav['name'][$value] . ' > ';
	$breadcrumbs = substr($breadcrumbs, 0, -3);
	
	$header['navigator'] .= '<ul id="navigator" class="bottom_space">' . "\n";
	$header['navigator'] .= "\t" . '<li class="home">&nbsp;</li>' . "\n";
	
	for ($i = 0; $i <= $max_index; $i++) {
		$class = $nav['class'][$i];
		if (empty($nav['url'][$i]))		$header['navigator'] .= "\t" . '<li class="' . html($class) . '"><span>' . html($nav['name'][$i]) . '</span></li>' . "\n";
		else							$header['navigator'] .= "\t" . '<li class="' . html($class) . '"><a href="' . $nav['url'][$i] . '">' . html($nav['name'][$i]) . '</a></li>' . "\n";
		if ($i < $max_index)			$header['navigator'] .= "\t" . '<li class="' . $nav['class'][$i] . '_' . $nav['class'][$i + 1] . '">&nbsp;</li>' . "\n";		
	}
	if ($nav['class'][$max_index] == 'suggest')	$header['navigator'] .= "\t" . '<li class="suggest_close">&nbsp;</li>' . "\n";
	else										$header['navigator'] .= "\t" . '<li class="nav_close">&nbsp;</li>' . "\n";	
	$header['navigator'] .= '</ul>' . "\n";
}
unset($nav);




//  +------------------------------------------------------------------------+
//  | Head                                                                   |
//  +------------------------------------------------------------------------+
$header['title'] = 'netjukebox &bull; ';
if (NJB_SCRIPT == 'message.php')					$header['title'] .= 'Message';
elseif ($cfg['username'] == '')						$header['title'] .= 'Live @ ' . html($_SERVER['HTTP_HOST']);
elseif (@$_GET['authenticate'] == 'logout')			$header['title'] .= 'Logout';
elseif (@$_REQUEST['sign'])							$header['title'] .= 'Signed (' . html(strtolower($breadcrumbs)) . ')';
elseif (empty($breadcrumbs))						$header['title'] .= 'Undefined';
else												$header['title'] .=  html($breadcrumbs);

$header['head']  = '<head>' . "\n";
$header['head']  .= "\t" . '<meta http-equiv="Content-Type" content="text/html; charset=' . html(NJB_DEFAULT_CHARSET) .'">' . "\n";
$header['head'] .= "\t" . '<meta name="application-name" content="netjukebox ' . html(NJB_VERSION) . ', Copyright ' . html_entity_decode('&copy;', null, NJB_DEFAULT_CHARSET) . ' 2001-2015 Willem Bartels">' . "\n";
$header['head'] .= "\t" . '<title>' . $header['title'] . '</title>' . "\n";
if (isset($cfg['access_media']) && $cfg['access_media']) {
	$header['head'] .= "\t" . '<link rel="search" type="application/opensearchdescription+xml" title="netjukebox - Album Artist" href="' . NJB_HOME_URL . 'opensearch.php?action=installAlbumArtist">' . "\n";
	$header['head'] .= "\t" . '<link rel="search" type="application/opensearchdescription+xml" title="netjukebox - Track Artist" href="' . NJB_HOME_URL . 'opensearch.php?action=installTrackArtist">' . "\n";
	$header['head'] .= "\t" . '<link rel="search" type="application/opensearchdescription+xml" title="netjukebox - Title" href="' . NJB_HOME_URL . 'opensearch.php?action=installTrackTitle">' . "\n";
}
$header['head'] .= "\t" . '<link rel="shortcut icon" type="image/png" href="image/favicon.png">' . "\n";
$header['head'] .= "\t" . '<link rel="apple-touch-icon" href="image/apple_touch_icon.png">' . "\n";
$header['head'] .= "\t" . '<link rel="stylesheet" type="text/css" href="cache.php?action=css&amp;skin=' . rawurlencode($cfg['skin']) . '&amp;hash=' . css_hash() . '">' . "\n";
$header['head'] .= "\t" . '<script type="text/javascript" src="cache.php?action=javascript&amp;hash=' . javascript_hash() . '"></script>' . "\n";
$header['head'] .= '</head>' . "\n";




//  +------------------------------------------------------------------------+
//  | Menu                                                                   |
//  +------------------------------------------------------------------------+
$header['menu'] = '<ul id="menu">' . "\n";
$header['menu'] .= "\t" . '<li class="' . ($cfg['menu'] == 'media' ? 'on' : 'off') . '"><a href="index.php">media</a></li>' . "\n";
$header['menu'] .= "\t" . '<li class="' . ($cfg['menu'] == 'favorite' ? 'on' : 'off') . '"><a href="favorite.php">favorites</a></li>' . "\n";
$header['menu'] .= "\t" . '<li class="' . ($cfg['menu'] == 'playlist' ? 'on' : 'off') . '"><a href="playlist.php">playlist</a></li>' . "\n";
$header['menu'] .= "\t" . '<li class="' . ($cfg['menu'] == 'config' ? 'on' : 'off') . '"><a href="config.php">config</a></li>' . "\n";
$header['menu'] .= '</ul>' . "\n";




//  +------------------------------------------------------------------------+
//  | Submenu                                                                |
//  +------------------------------------------------------------------------+
$header['submenu'] = '<ul id="submenu">' . "\n";
if ($cfg['menu'] == 'media') {
	$header['submenu'] .= "\t" . '<li><a class="character" href="index.php?action=view1&amp;filter=start&amp;artist=%23">#</a></li><!--' . "\n";
	for ($i = 'a'; $i != 'aa'; $i++)
		$header['submenu'] .= "\t" . '--><li class="character"><a href="index.php?action=view1&amp;filter=start&amp;artist=' . $i . '">' . $i . '</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="index.php?action=view2&amp;artist=Various&amp;filter=exact">various</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="index.php?action=viewYear">year</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="index.php?action=view2&amp;filter=all&amp;order=added&amp;sort=desc&amp;page=1">new</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="index.php?action=viewPopular&amp;period=overall">popular</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="index.php?action=viewRandomAlbum">random</a></li>' . "\n";
}
elseif ($cfg['menu'] == 'favorite')	{
	$header['submenu'] .= "\t" . '<li><a href="favorite.php">favorites</a></li>' . "\n";
}
elseif ($cfg['menu'] == 'playlist')	{
	$header['submenu'] .= "\t";
	if ($cfg['access_stream'] && $cfg['access_playlist'])
		$header['submenu'] .= '<li><a href="stream.php?action=m3uPlaylist&amp;stream_id=' . $cfg['stream_id'] . '&amp;short_sid=' . substr($cfg['sid'], 0, 8) . '&amp;hash=' . hmacsha1(@$cfg['server_seed'] . $cfg['sid'], 'm3uPlaylist' . $cfg['stream_id']) . '&amp;menu=playlist">stream playlist</a></li><!--' . "\n\t" . '-->';
	$header['submenu'] .= '<li><a href="javascript:ajaxRequest(\'play.php?action=deletePlaylist&amp;menu=playlist\');">delete playlist</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="javascript:ajaxRequest(\'play.php?action=deletePlayed&amp;menu=playlist\');">delete played</a></li>' . "\n";
}
elseif ($cfg['menu'] == 'config') {
	$header['submenu'] .= "\t" . '<li><a href="config.php?action=playerProfile">player profile</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="config.php?action=streamProfile">stream profile</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="config.php?action=downloadProfile">download profile</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="config.php?action=skinProfile">skin profile</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="users.php">users</a></li><!--' . "\n";
	$header['submenu'] .= "\t" . '--><li><a href="users.php?action=online">online</a></li><!--' . "\n";	
	$header['submenu'] .= "\t" . '--><li><a href="update.php?action=update">update</a></li>' . "\n";
}
$header['submenu'] .= '</ul>' . "\n";




//  +------------------------------------------------------------------------+
//  | No script                                                              |
//  +------------------------------------------------------------------------+
$header['no_javascript'] = '';
if (NJB_SCRIPT != 'about.php') {
	$header['no_javascript'] .= '<noscript>' . "\n";
	$header['no_javascript'] .= '<table class="error">' . "\n";
	$header['no_javascript'] .= '<tr>' . "\n";
	$header['no_javascript'] .= "\t" . '<td><img src="' . $cfg['img'] . 'medium_message_error.png" alt=""></td>' . "\n";
	$header['no_javascript'] .= "\t" . '<td><strong>JavaScript is required</strong><br>Enable JavaScript in the web browser.</td>' . "\n";
	$header['no_javascript'] .= '</tr>' . "\n";
	$header['no_javascript'] .= '</table>' . "\n";
	$header['no_javascript'] .= '</noscript>' . "\n";
}




//  +------------------------------------------------------------------------+
//  | Header template                                                        |
//  +------------------------------------------------------------------------+
require_once(NJB_HOME_DIR . 'skin/' . $cfg['skin'] . '/template.header.php');
unset($header);

