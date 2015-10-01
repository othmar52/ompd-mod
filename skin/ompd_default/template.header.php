<?php
//  +------------------------------------------------------------------------+
//  | template.header.php                                                    |
//  +------------------------------------------------------------------------+
if (isset($header) == false)
	exit();
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale: 1, minimal-ui">
<meta name="mobile-web-app-capable" content="yes">
<link rel="icon" type="image/png" sizes="196x196" href="image/icon.png">


<script src="jquery/jquery-1.11.3.min.js"></script>
<script src="javascript-src/spin.min.js"></script>
<script src="javascript-src/arts.functions.js"></script>
<script src="javascript-src/jquery.touchSwipe.min.js"></script>

<script type="text/javascript">

//global variables
var previous_volume;
var $tileCount=3;
var $tileSize = 0;
var $tileSizeArr = calcTileSize();
$tileSize = $tileSizeArr[0];
$containerWidth = $tileSizeArr[1];

var opts = {
  lines: 13, // The number of lines to draw
  length: 21, // The length of each line
  width: 10, // The line thickness
  radius: 28, // The radius of the inner circle
  corners: 0.6, // Corner roundness (0..1)
  rotate: 43, // The rotation offset
  direction: 1, // 1: clockwise, -1: counterclockwise
  color: '#fff', // #rgb or #rrggbb
  speed: 1, // Rounds per second
  trail: 36, // Afterglow percentage
  shadow: false, // Whether to render a shadow
  hwaccel: true, // Whether to use hardware acceleration
  className: 'spinner', // The CSS class to assign to the spinner
  zIndex: 2e9, // The z-index (defaults to 2000000000)
  top: 'auto', // Top position relative to parent in px
  left: 'auto' // Left position relative to parent in px
};



//adding swipe support
$.fn.swipe.defaults.excludedElements = "input, textarea, .noSwipe";
$.fn.swipe.defaults.threshold = $(window).width() * 0.2;
$(function() {    
		
	<?php
	switch (strtolower($cfg['menu'])) {
		case "library":
			echo 'nextP = "playlist.php";
			prevP = "config.php";';
			break;
		case "playlist":
			echo 'nextP = "favorite.php";
			prevP = "index.php";';
			break;
		case "favorite":
			echo 'nextP = "config.php";
			prevP = "playlist.php";';
			break;
		case "about":
		case "config": 
			echo 'nextP = "index.php";
			prevP = "favorite.php";';
			break;
	}
	?>
		
	if (nextP && prevP) { 
      $(document).swipe( {
        swipeLeft:function(event, direction, distance, duration, fingerCount, fingerData) {
			if (duration < 1000) window.location.href = nextP; 
        },
		swipeRight:function(event, direction, distance, duration, fingerCount, fingerData) {
			if (duration < 1000) window.location.href = prevP; 
        }
      });
    };
});
	

function changePlayer(player_id) {
	$.ajax({
			type: "GET",
			url: "ajax-change-player.php",
			data: { 'player_id': player_id, 'sid': '<?php echo cookie('netjukebox_sid'); ?>'},
			dataType : 'json',
			success : function(json) {
				$("#activePlayer").html(json['player']);
				var winLoc = location.href;
				var n = winLoc.indexOf("playlist.php");
				var m = winLoc.indexOf("message.php");
				if (n>0 || m>0) {
					window.location = "playlist.php";
				};
				toggleChangePlayer();
			},
			error : function() {
			}	
		});
};

$(document).ready(function () {
	ajaxRequest('ajax-evaluate-status.php', evaluateVolume);
	$tileSizeArr = calcTileSize();
	<?php
	global $tileSizePHP;
	if (!$tileSizePHP) echo 'resizeTile($tileSizeArr[0],$tileSizeArr[1]);';
	?>

	resizeSuggested($tileSizeArr[0],$tileSizeArr[1]);
	resizeUsersTab($tileSizeArr[0],$tileSizeArr[1]);
	changeTileSizeInfo();
	resizeImgContainer();
	//hideAddressBar();
	
	
	$(window).resize(function() {
        $tileSizeArr = calcTileSize();
		resizeTile($tileSizeArr[0],$tileSizeArr[1]);
		changeTileSizeInfo();
		//hideAddressBar();
		resizeImgContainer();
		//resizeSuggested($tileSize,$containerWidth);
    });
	
	var offset = 0.6 * $(window).height();
    var duration = 500;
    $(window).scroll(function() {
        if ($(this).scrollTop() > offset) {
            $('.back-to-top').fadeIn(duration);
        } else {
            $('.back-to-top').fadeOut(duration);
        }
    });
    
    $('.back-to-top').click(function(event) {
        event.preventDefault();
        $('html, body').animate({scrollTop: 0}, duration);
        return false;
    })
	
});


function evaluateVolume(data) {
	if (typeof data.player != 'undefined') {
		player = data.player;
		$('#activePlayer').html(player);
	}
	if (typeof data.volume != 'undefined') {
		volume = data.volume;
	}
	else {
		volume = data;
	}
	<?php $max_volume = 100; ?>
	if (volume < 0) {
		//volume setting not available
		$('#currentVolume').html('---');
		$('#volumeBar').width(0);
		$('#setVolume').attr('onclick','').unbind('click');
	}
	else {
		var volume_percentage = Math.round(100 * volume / <?php echo $max_volume; ?>);
		var maxWidth = $('#volumeValue').width();
		var width = Math.round(maxWidth * volume / <?php echo $max_volume; ?>);
		if (volume_percentage == 0) {
			$('#currentVolume').html('mute');
		}
		else {
			$('#currentVolume').html(volume_percentage);
		}
		$('#volumeBar').width(width);
		$('#setVolume').attr('onclick','toggleVolume();');
	}
}
</script>



<?php echo $header['head']; ?>

<body <?php echo $header['body']; ?>>

<!--
<div id="back-ground-wrapper">
<img id="back-ground-img" src="skin/<?php echo $cfg['skin'] ?>/img/bg4.jpg">
<div id="back-ground-"></div>
</div>
-->

<div id="waitIndicator"></div>

<?php 
$pos = strpos($_SERVER['PHP_SELF'], 'playlist.php');
$action 		= get('action');
?>
<script type="text/javascript">
	var target = document.getElementById('waitIndicator');
	var spinner = new Spinner(opts);
	<?php if ($action != 'view3' && $pos === false) echo ('showSpinner();'); ?>
</script>

<div class="wrapper">


<div class="overlib" id="overDiv"></div>
<table cellspacing="0" cellpadding="0" class="fullscreen" id="main_table">
<tr valign="top">
	<td>

<div id="menu" class="menu">



<?php 
//require_once('include/play.inc.php');
//$status = mpdSilent('status');

$query1=mysql_query('SELECT player.player_name as pl FROM player, session WHERE (sid = BINARY "' . cookie('netjukebox_sid') . '") and player.player_id=session.player_id');
$session1 = mysql_fetch_assoc($query1);
$player1=$session1['pl'];

$query2 = mysql_query('SELECT player_name, player_type, player_id FROM player ORDER BY player_name');

?>
<div id="fixedMenu">
<div>
<table cellspacing="0" cellpadding="0" class="menu_top">
<tr>
	<td class="menu_top_left"><div class="mpd_title pointer" onclick="javascript: window.location.href='http://ompd.pl'"><span id="logo1">O!</span><span id="logo2">MPD</span></div></td>
	<td class="menu_top<?php echo ($cfg['menu'] == 'Library') ? ' menu_top_selected' : ''; ?>" onclick="javascript: window.location.href='index.php';"><p>&nbsp;library</p></td>
	<td class="menu_top<?php echo ($cfg['menu'] == 'playlist') ? ' menu_top_selected' : ''; ?>" onclick="javascript: window.location.href='playlist.php';">&nbsp;now playing</td>
	<td class="menu_top<?php echo ($cfg['menu'] == 'favorite') ? ' menu_top_selected' : ''; ?>" onclick="javascript: window.location.href='favorite.php';">&nbsp;favorites</td>
	<td>&nbsp;</td>
	<td class="menu_top menu_top_config" id="setVolume" onclick='javascript: toggleVolume();'>
		<div><i id="iconVolumeToggler" class="fa fa-volume-up fa-lg"></i></div>
		<div id="currentVolume"><?php //echo $status['volume']; ?></div>
	</td>
	<?php if (mysql_num_rows($query2)>1) { ?>
	<td class="menu_top menu_top_config" id="playerProfile" onclick='javascript: toggleChangePlayer();'>
		<div><i id="iconPlayerToggler" class="fa fa-music fa-lg"></i></div>
		<div id="activePlayer"><?php echo $player1; ?></div>
	</td>
	<?php }; ?>
	<td class="menu_top menu_top_config<?php echo ($cfg['menu'] == 'config') ? ' menu_top_selected' : ''; ?>" onclick="javascript: window.location.href='config.php';">
		<i class="fa fa-cog fa-lg"></i>
	</td>
	<td class="menu_top menu_top_config" id="searchToggler" onclick='javascript: toggleSearch();'>
		<i id="iconSearchToggler" class="fa fa-search fa-lg"></i>
	</td>
</tr>
</table>
</div>

<div id="playerList" class="buttons">
<?php
	while ($player = mysql_fetch_assoc($query2)) {
?>
	<span onclick="javascript: changePlayer(<?php echo $player['player_id']; ?>);"><?php echo html($player['player_name']); ?></span>
<?php
}
?>
</div>
<div>
<form action="search.php" id="searchFormAll">
	<div>
	<input type="text" id="search_string" name="search_string" autocomplete="off" value=""><div onclick="goSearch();" class="icon-selected">GO</div>
	<input type="hidden" name="filter" value="base">
	<input type="hidden" name="action" value="search_all">
	</div>
	</form>
</div>
<div id="volumeArea">

<div class="playlist_indicator">
	<div class="buttons">
		<span class="pointer" onClick="ajaxRequest('play.php?action=volumeImageMap&amp;dx=' + $('#volumeValue').width() + '&amp;x=' + ($('#volumeBar').width() -($('#volumeValue').width() * 0.05)) + '&amp;menu=playlist', evaluateVolume);"><i class="fa fa-volume-down fa-lg fa-fw"></i></span>
		<div id="volumeValue" class="out pointer" onClick="ajaxRequest('play.php?action=volumeImageMap&amp;dx=' + this.clientWidth + '&amp;x=' + getRelativeX(event, this) + '&amp;menu=playlist', evaluateVolume);">
			<div id="volumeBar" style="width: 0px; overflow: hidden;" class="in"></div>
		</div>
		<span class="playlist_status_off" id="volume" style="text-align: left; padding-left: 1px; display: none; align: middle;"></span>
		<span class="pointer" onClick="ajaxRequest('play.php?action=volumeImageMap&amp;dx=' + $('#volumeValue').width() + '&amp;x=' + ($('#volumeBar').width() +($('#volumeValue').width() * 0.05)) + '&amp;menu=playlist', evaluateVolume);"><i class="fa fa-volume-up fa-lg fa-fw"></i></span>
	</div>
</div>
	
<?php
	 /* End volume */ ?>
</div>
</div>
<div id="fixedMenuFill"></div>
<script type="text/javascript">
//$('#search_string').focus(function() {$(this).val('');});
</script>
<table cellspacing="0" cellpadding="0" class="menu_middle">
<tr>
	<td class="menu_middle_left"></td>
	<td class="menu_middle">
<?php 
	if ($cfg['menu'] == 'Library') {
?>



<span id="menuMiddleMedia">
<span id="list" onclick='toggleSubMiddle("Alpha");'>artist <i id="iconmenuSubMiddleMediaAlpha" class="fa fa-chevron-circle-down"></i></span>

<?php echo $header['seperation']; ?>
<span id="genre" onclick='toggleSubMiddle("Genre");'>genre <i id="iconmenuSubMiddleMediaGenre" class="fa fa-chevron-circle-down"></i></span>

<?php echo $header['seperation']; ?>
<span id="quickSearch" onclick='toggleSubMiddle("QuickSearch");'>quick search <i id="iconmenuSubMiddleMediaQuickSearch" class="fa fa-chevron-circle-down"></i></span>

<?php echo $header['seperation']; ?>

<?php
	
	$header['menu'] = "\t" . '<a href="index.php?action=viewYear">year</a>' . $header['seperation'];
	$header['menu'] .= "\t" . '<a href="index.php?action=viewNew&page=1">new</a>'. $header['seperation'];
	$header['menu'] .= "\t" . '<a href="index.php?action=viewPopular&amp;period=overall">popular</a>' . $header['seperation'];
	//$header['menu'] .= "\t" . $header['seperation'];
	$header['menu'] .= '<a href="index.php?action=viewRandomAlbum&amp;order=artist">random</a>';
	echo $header['menu'];
?>

</span>



<div id="menuSubMiddleMediaAlpha">
	<?php
	$header['menu'] = '<a href="index.php?action=view2&amp;filter=all&amp;order=artist"><span>all</span></a>';
	//ArtS
	$header['menu'] .= "\t" . '<a href="index.php?action=view2&amp;filter=symbol&amp;artist=%23&amp;order=artist"><span>#</span></a>';
	for ($i = 'a'; $i != 'aa'; $i++)
		  $header['menu'] .= "\t" . '<a href="index.php?action=view2&amp;filter=start&amp;artist='. $i .'&amp;order=artist"><span>' . $i . '</span></a>';
	$header['menu'] .= "\t"  . '<a href="index.php?action=view2&amp;artist=Various%20Artists&amp;filter=exact&amp;order=artist"><span>VA</span></a>';
	echo $header['menu'];
	?>
</div>

<div id="menuSubMiddleMediaGenre">
	<?php
	$query = mysql_query('SELECT genre, genre_id
		FROM genre 
		WHERE genre !=""
		ORDER BY genre');
	if (mysql_num_rows($query) > 0) {
		//echo '<div class="genre">';
		//$genre1 = ($genre['genre'] == '' ? $genre['genre'] : 'Unknown genre');
		while ($genre = mysql_fetch_assoc($query))
			echo  '<p><span><a href="index.php?action=view2&amp;order=artist&amp;sort=asc&amp;genre_id=' . $genre['genre_id'] . '">' . str_replace(" ", "&nbsp;", html($genre['genre'])) . '</a></span></p>';
			
	//echo '&nbsp;' . $genre_seperation .'&nbsp;' . "\n";
	
	}
	?>
</div>

<div id="menuSubMiddleMediaQuickSearch">
	<?php 
	//print_r($cfg['quick_search']);
	$quick_search = $cfg['quick_search'];
	$count = count($quick_search);
	for ($i=1; $i<$count+1; $i++) {
	?>
	<p><span>
	<a href="index.php?action=view2&amp;filter=exact&amp;order=artist&amp;qsType=<?php echo $i; ?>"><?php echo str_replace(" ", "&nbsp;", html($quick_search[$i][0])); ?></a>
	</p></span>
	<?php
		//if ($i<$count) echo '&nbsp;|&nbsp;';
	}
	?>
</div>

<!-- search form here -->

<?php		 
	}
	else {
		echo $header['menu'];
	}?>
	</td>
	<td class="menu_middle_right"></td>
</tr>

</table>
<table cellspacing="0" cellpadding="0" class="menu_bottom">
<tr>
	<td class="menu_bottom_left"></td>
	<td></td>
	<td class="menu_bottom_right"></td>
</tr>
</table>
</div><!-- end menu -->

	</td>
</tr>
<tr>
	<td height="100%">

<div id="content" class="content">
<table cellspacing="0" cellpadding="0" class="fullscreen">
<!-- <tr>
	<td colspan="3" height="3px"></td>
</tr>
-->
<tr <?php echo $cfg['align'] ? 'align="center" valign="middle"' : 'valign="top"'; ?>>
	<td class="side-margin"></td>
	<td>

	

	
<!-- end header -->
