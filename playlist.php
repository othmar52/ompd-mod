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
//  | playlist.php                                                           |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
$cfg['menu'] = 'playlist';

authenticate('access_playlist');
require_once('include/play.inc.php');

// Navigator
$nav			= array();
$nav['name'][]	= 'Playlist';
$nav['url'][]	= '';
$nav['class'][]	= 'nav';

navPlayerProfile();

require_once('include/header.inc.php');


if ($cfg['player_type'] == NJB_HTTPQ) {
	$hash			= httpq('gethash');
	$listpos		= httpq('getlistpos');
	$file			= httpq('getplaylistfilelist', 'delim=*');
	$file			= str_replace('\\', '/', $file);
	$file			= explode('*', $file);
	$listlength		= (empty($file[0])) ? 0 : count($file);
	$volume			= true;
	$max_volume		= 255;
	$default_image	= $cfg['img'] . 'large_httpq.png';
			
	// Get relative directory based on $cfg['media_share']
	foreach ($file as $i => $value)	{
		if (strtolower(substr($file[$i], 0, strlen($cfg['media_share']))) == strtolower($cfg['media_share']))
			$file[$i] = substr($file[$i], strlen($cfg['media_share']));
	}
}
elseif ($cfg['player_type'] == NJB_MPD)	{
	$status 		= mpd('status');
	$listpos		= isset($status['song']) ? $status['song'] : 0;
	$file			= mpd('playlist');
	$hash			= md5(implode('<seperation>', $file));
	$listlength		= $status['playlistlength'];
	$volume			= (isset($status['volume']) == false || $status['volume'] == -1) ? false : true;
	$max_volume		= 100;
	$default_image	= $cfg['img'] . 'large_mpd.png';
}
elseif ($cfg['player_type'] == NJB_VLC)
	message(__FILE__, __LINE__, 'warning', '[b]videoLAN playlist not supported yet[/b]');
else
	message(__FILE__, __LINE__, 'error', '[b]Player not supported[/b]');


$featuring = false;
for ($i = 0; $i < $listlength && !$featuring; $i++) {
	$query = mysqli_query($db, 'SELECT featuring FROM track WHERE featuring != "" AND relative_file = "' . mysqli_real_escape_string($db, $file[$i]) . '"');
	if (mysqli_fetch_row($query)) $featuring = true;
}

 
$control = '<ul id="playlist_control">' . "\n";
if ($cfg['access_play']) {
	// Shuffle & repeat
	$control .= "\t" . '<li id="shuffle" class="shuffle off onclick" onclick="ajaxRequest(\'play.php?action=toggleShuffle&amp;menu=playlist\',evaluateShuffle);">shuffle</li>' . "\n";
	$control .= "\t" . '<li id="repeat" class="repeat off onclick" onclick="ajaxRequest(\'play.php?action=toggleRepeat&amp;menu=playlist\',evaluateRepeat);">repeat</li>' . "\n";
}
if ($cfg['access_play'] && $cfg['player_type'] == NJB_MPD && version_compare($cfg['mpd_version'], '0.16.0', '>=')) {
	// Replay gain
	$control .= "\t" . '<li id="gain" class="gain off onclick" onclick="ajaxRequest(\'play.php?action=loopGain&amp;menu=playlist\',evaluateGain);">gain</li>' . "\n";
}
if ($cfg['access_play']) {
	// Play
	$control .= "\t" . '<li class="previous onclick" onclick="ajaxRequest(\'play.php?action=prev&amp;menu=playlist\',evaluateListpos);">previous</li>' . "\n";
	$control .= "\t" . '<li id="play" class="play off onclick" onclick="ajaxRequest(\'play.php?action=play&amp;menu=playlist\',evaluateIsplaying);">play</li>' . "\n";
	$control .= "\t" . '<li id="pause" class="pause off onclick" onclick="ajaxRequest(\'play.php?action=pause&amp;menu=playlist\',evaluateIsplaying);">pause</li>' . "\n";
	$control .= "\t" . '<li class="next onclick" onclick="ajaxRequest(\'play.php?action=next&amp;menu=playlist\',evaluateListpos);">next</li>' . "\n";
	$control .= "\t" . '<li class="stop onclick" onclick="ajaxRequest(\'play.php?action=stop&amp;menu=playlist\',evaluateIsplaying);">stop</li>' . "\n";
	$control .= "\t" . '<li class="progress onclick"><div id="time_input" class="input" onclick="ajaxRequest(\'play.php?action=seekImageMap&amp;dx=\' + this.clientWidth + \'&amp;x=\' + getRelativeX(event, this) + \'&amp;menu=playlist\',evaluatePlaytime);"><div id="time_bar" class="on"></div></div></li>' . "\n";
	$control .= "\t" . '<li id="time" class="display"></li>' . "\n";
}
if ($cfg['access_play'] && $volume) {
	// Volume
	$control .= "\t" . '<li class="progress onclick"><div id="volume_input" class="input" onclick="ajaxRequest(\'play.php?action=volumeImageMap&amp;dx=\' + this.clientWidth + \'&amp;x=\' + getRelativeX(event, this) + \'&amp;menu=playlist\',evaluateVolume);"><div id="volume_bar" class="on"></div></div></li>' . "\n";
	$control .= "\t" . '<li id="volume" class="display onclick" onclick="ajaxRequest(\'play.php?action=toggleMute&amp;menu=playlist\',evaluateVolume);"></li>' . "\n";
}
if ($cfg['access_play'] == false) {
	// Readonly shuffle & repeat
	$control .= "\t" . '<li id="shuffle" class="shuffle off">shuffle</li>' . "\n";
	$control .= "\t" . '<li id="repeat" class="repeat off">repeat</li>' . "\n";
}
if ($cfg['access_play'] == false && $cfg['player_type'] == NJB_MPD && version_compare($cfg['mpd_version'], '0.16.0', '>=')) {
	// Readonly replay gain
	$control .= "\t" . '<li id="gain" class="gain off">gain</li>' . "\n";
}
if ($cfg['access_play']  == false) {
	// Readonly play
	$control .= "\t" . '<li class="previous">previous</li>' . "\n";
	$control .= "\t" . '<li id="play" class="play off">play</li>' . "\n";
	$control .= "\t" . '<li id="pause" class="pause off">pause</li>' . "\n";
	$control .= "\t" . '<li class="next">next</li>' . "\n";
	$control .= "\t" . '<li class="stop">stop</li>' . "\n";
	$control .= "\t" . '<li class="progress"><div id="time_input" class="input"><div id="time_bar" class="on"></div></div></li>' . "\n";
	$control .= "\t" . '<li id="time" class="display"></li>' . "\n";
}
if ($cfg['access_play'] == false && $volume) {
	// Readonly volume
	$control .= "\t" . '<li class="progress"><div id="volume_input" class="input"><div id="volume_bar" class="on"></div></div></li>' . "\n";
	$control .= "\t" . '<li id="volume" class="display"></li>' . "\n";
}
$control .='</ul>' . "\n";
?>
<div id="playlist_container">
	<div id="image"></div>
	<ul id="playlist_text">
		<li class="odd"><span class="description">Artist:</span><span id="artist"></span></li>
		<li class="even"><span class="description">Title:</span><span id="title"></span></li>
		<li class="odd"><span class="description">Album:</span><span id="album"></span></li>
		<li class="even"><span class="description">By:</span><span id="by"></span></li>
	</ul>
</div>
<?php echo $control; ?>

<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Artist</td>
	<td class="textspace"></td>
	<td>Title</td>
	<td class="textspace"></td>
	<td><?php if ($featuring) echo'Featuring'; ?></td><!-- optional featuring -->
	<td<?php if ($featuring) echo' class="textspace"'; ?>></td>
	<td></td><!-- optional delete -->
	<td class="space"></td>
	<td class="text-align-right">Time</td>
	<td class="space"></td>
</tr>
<?php
$playtime = array();
$track_id = array();
for ($i=0; $i < $listlength; $i++) {
	$query = mysqli_query($db, 'SELECT title, artist, featuring, miliseconds, track_id FROM track WHERE relative_file = "' . mysqli_real_escape_string($db, $file[$i]) . '"');
	$table_track = mysqli_fetch_assoc($query);
	$playtime[] = (int) $table_track['miliseconds'];
	$track_id[] = (string) $table_track['track_id'];
	if (!isset($table_track['artist'])) {
		$table_track['artist']	= $file[$i];
		$table_track['title']	= 'Unknown';
	}
?>
<tr class="<?php if ($i == $listpos) echo 'select'; else echo ($i & 1) ? 'even mouseover' : 'odd mouseover'; ?>" id="track<?php echo $i; ?>">
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playIndex&amp;index=' . $i . '&amp;menu=playlist\',evaluateListpos);">' . html($table_track['artist']) . '</a>'; else echo html($table_track['artist']);?></td>
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=playIndex&amp;index=' . $i . '&amp;menu=playlist\',evaluateListpos);">' . html($table_track['title']) . '</a>'; else echo html($table_track['title']);?></td>
	<td></td>
	<td><?php if (isset($table_track['featuring'])) echo html($table_track['featuring']); ?></td>
	<td></td>
	<td><?php if ($cfg['access_play']) echo '<a href="javascript:ajaxRequest(\'play.php?action=deleteIndex&amp;index=' . $i . '&amp;menu=playlist\');"><img src="' . $cfg['img'] . 'small_delete.png" alt="" class="small"></a>'; ?></td>
	<td></td>
	<td class="text-align-right"><?php if (isset($table_track['miliseconds'])) echo formattedTime($table_track['miliseconds']); ?></td>
	<td></td>
</tr>
<?php
}
?>
</table>

<script type="text/javascript">
var previous_hash			= '<?php echo $hash; ?>';
var previous_listpos		= <?php echo $listpos; ?>;
var previous_isplaying		= -1; // force update
var previous_repeat			= -1;
var previous_shuffle		= -1;
var previous_gain			= -1;
var previous_miliseconds	= -1;
var previous_volume			= -1;
var playtime				= <?php echo safe_json_encode($playtime); ?>;
var track_id				= <?php echo safe_json_encode($track_id); ?>;
var timer_id				= 0;


function initialize() {
	ajaxRequest('play.php?action=playlistTrack&track_id=' + track_id[<?php echo $listpos; ?>] + '&menu=playlist',evaluateTrack);
	ajaxRequest('play.php?action=playlistStatus&menu=playlist',evaluateStatus);
}


function timer() {
	clearTimeout(timer_id);
	timer_id = setTimeout('ajaxRequest("play.php?action=playlistStatus&menu=playlist",evaluateStatus)', 1000);
}


function evaluateStatus(data) {
	// data.hash, data.miliseconds, data.listpos, data.volume
	// data.isplaying, data.repeat, data.shuffle, data.gain
	if (previous_hash != data.hash) {
		window.location.href="<?php echo NJB_HOME_URL ?>playlist.php";
	}
	data.max = playtime[data.listpos];
	evaluateListpos(data.listpos);
	evaluatePlaytime(data);
	evaluateRepeat(data.repeat);
	evaluateShuffle(data.shuffle);
	evaluateIsplaying(data.isplaying);
	evaluateGain(data.gain);
<?php echo ($volume) ? "\t" . 'evaluateVolume(data.volume);' . "\n" : ''; ?>
}


function evaluateListpos(listpos) {
	if (previous_listpos != listpos) {
		document.getElementById('track' + previous_listpos).className = (previous_listpos & 1) ? 'even mouseover' : 'odd mouseover';
		document.getElementById('track' + listpos).className = 'select';
		document.getElementById('time').innerHTML = formattedTime(0);
		document.getElementById('time_bar').style.width = 0;
		ajaxRequest('play.php?action=playlistTrack&track_id=' + track_id[listpos] + '&menu=playlist',evaluateTrack);
		previous_miliseconds = 0;
		previous_listpos = listpos;
	}
}


function evaluatePlaytime(data) {
	// data.miliseconds, data.max
	var dx = document.getElementById('time_input').clientWidth;
	if (previous_miliseconds != data.miliseconds) {
		document.getElementById('time').innerHTML = formattedTime(data.miliseconds);
		var width = 0;
		if (data.max > 0)
			width = Math.round(data.miliseconds / data.max * dx);
		if (width > dx)
			width = dx;
		document.getElementById('time_bar').style.width = width + 'px';
		previous_miliseconds = data.miliseconds;
	}
}


function evaluateVolume(volume) {
	var dx = document.getElementById('volume_input').clientWidth;
	if (previous_volume != volume && volume >= 0) {
		// Volume
		var volume_percentage	= Math.round(100 * volume / <?php echo $max_volume; ?>);
		var width				= Math.round(dx * volume / <?php echo $max_volume; ?>);
		document.getElementById('volume').innerHTML = volume_percentage + '%';
		document.getElementById('volume_bar').className = '<?php echo $cfg['access_play'] ? 'on onclick' : 'on'; ?>';
		document.getElementById('volume_bar').style.width = width + 'px';
		previous_volume = volume;
	}
	if (previous_volume != volume && volume < 0) {
		// Mute volume
		var mute_volume = -1 * volume;
		var volume_percentage	= Math.round(100 * mute_volume / <?php echo $max_volume; ?>);
		var width				= Math.round(dx * mute_volume / <?php echo $max_volume; ?>);
		document.getElementById('volume').innerHTML = 'mute';
		document.getElementById('volume_bar').className = '<?php echo $cfg['access_play'] ? 'off onclick' : 'off'; ?>';
		document.getElementById('volume_bar').style.width = width + 'px';
		previous_volume = volume;
	}
}


function evaluateIsplaying(isplaying) {
	if (previous_isplaying != isplaying) {
		if (isplaying == 0) {
			// stop
			document.getElementById('play').className = '<?php echo $cfg['access_play'] ? 'play off onclick' : 'play off'; ?>';
			document.getElementById('pause').className = '<?php echo $cfg['access_play'] ? 'pause off onclick' : 'pause off'; ?>';
			document.getElementById('time').innerHTML = formattedTime(0);
			document.getElementById('time_bar').style.width = 0;
			previous_miliseconds = 0;
		}
		else if (isplaying == 1) {
			// play
			document.getElementById('play').className = '<?php echo $cfg['access_play'] ? 'play on onclick' : 'play on'; ?>';
			document.getElementById('pause').className = '<?php echo $cfg['access_play'] ? 'pause off onclick' : 'pause off'; ?>';
		}
		else if (isplaying == 3) {
			// pause
			document.getElementById('play').className = '<?php echo $cfg['access_play'] ? 'play off onclick' : 'play off'; ?>';
			document.getElementById('pause').className = '<?php echo $cfg['access_play'] ? 'pause on onclick' : 'pause on'; ?>';
		}
		previous_isplaying = isplaying
	}
}


function evaluateRepeat(repeat) {
	if (previous_repeat != repeat) {
		if (repeat == 0) document.getElementById('repeat').className = '<?php echo $cfg['access_play'] ? 'repeat off onclick' : 'repeat off'; ?>';
		if (repeat == 1) document.getElementById('repeat').className = '<?php echo $cfg['access_play'] ? 'repeat on onclick' : 'repeat on'; ?>';
		previous_repeat = repeat;
	}
}


function evaluateShuffle(shuffle) {
	if (previous_shuffle != shuffle) {
		if (shuffle == 0) document.getElementById('shuffle').className = '<?php echo $cfg['access_play'] ? 'shuffle off onclick' : 'shuffle off'; ?>';
		if (shuffle == 1) document.getElementById('shuffle').className = '<?php echo $cfg['access_play'] ? 'shuffle on onclick' : 'shuffle on'; ?>';
		previous_shuffle = shuffle;
	}
}


function evaluateGain(gain) {
	if (previous_gain != gain) {
		if (gain == 'off')		{ document.getElementById('gain').className = '<?php echo $cfg['access_play'] ? 'gain off onclick' : 'gain off'; ?>'; document.getElementById('gain').innerHTML = 'gain'; }
		if (gain == 'album')	{ document.getElementById('gain').className = '<?php echo $cfg['access_play'] ? 'gain album onclick' : 'gain album'; ?>'; document.getElementById('gain').innerHTML = 'album'; }
		if (gain == 'track')	{ document.getElementById('gain').className = '<?php echo $cfg['access_play'] ? 'gain track onclick' : 'gain track'; ?>'; document.getElementById('gain').innerHTML = 'track'; }
		if (gain == 'fade')		{ document.getElementById('gain').className = '<?php echo $cfg['access_play'] ? 'gain fade onclick' : 'gain fade'; ?>';	document.getElementById('gain').innerHTML = 'fade'; }
		previous_gain = gain;
	}
}


function evaluateTrack(data) {
	// data.artist, data.title, data.album, data.by, data.album_id, data.image_id
	document.getElementById('artist').innerHTML = data.artist;
	document.getElementById('title').innerHTML = data.title;
	document.getElementById('album').innerHTML = data.album;
	document.getElementById('by').innerHTML = data.by;
	
	if (data.album_id) {
		document.getElementById('image').innerHTML = '<a href="index.php?action=view3&album_id=' + data.album_id + '" title="Jump to album"><img src="image.php?image_id=' + data.image_id + '" alt="" width="100" height="100"><\/a>';
		tooltip.init();
	}
	else
		document.getElementById('image').innerHTML = '<img src="<?php echo $default_image; ?>" alt="" width="100" height="100">';
}
</script>
<?php
require_once('include/footer.inc.php');
