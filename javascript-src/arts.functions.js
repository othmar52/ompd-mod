//  +------------------------------------------------------------------------+
//  | O!MPD, Copyright © 2015 Artur Sierzant		                         |
//  | http://www.ompd.pl           		                                     |
//  |                                                                        |
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


function changeTileSizeInfo() {
	$("a[href]")
	.each(function() {
	if (this.href.indexOf('tileSizePHP')<0) {
		if (this.href.indexOf('index.php?')<0) {
			this.href = this.href.replace('index.php','index.php?tileSizePHP=' + $tileSizeArr[0]);
			}
		else {
			this.href = this.href.replace('index.php?','index.php?tileSizePHP=' + $tileSizeArr[0] + '&');
		}
	}
	else {
		pos1 = this.href.indexOf('PHP=');
		ts = this.href.substr(pos1 + 4, 3);
		this.href = this.href.replace('?tileSizePHP=' + ts,'?tileSizePHP=' + $tileSizeArr[0]);
	}
	});
	
	$("[onclick]")
	.each(function() {
		v1 = $(this).attr('onclick');
		if (v1.indexOf('tileSizePHP')<0) {
			if (v1.indexOf('index.php?')<0) {
			v2 = v1.replace('index.php','index.php?tileSizePHP=' + $tileSizeArr[0]);
			}
			else {
			v2 = v1.replace('index.php?','index.php?tileSizePHP=' + $tileSizeArr[0] + '&');
			}
			$(this).attr('onclick', v2);	
		}
		else {
			pos1 = v1.indexOf('PHP=');
			ts = v1.substr(pos1 + 4, 3);
			v2 = v1.replace('tileSizePHP=' + ts,'tileSizePHP=' + $tileSizeArr[0]);
			$(this).attr('onclick', v2);	
		}
	});
}

function calcTileSize() {
    var $containerWidth = $(window).width();
	if ($containerWidth > 1280) $containerWidth = 1280;
	
    if ($containerWidth <= 639) {
		$tileCount=3;
		$tileSize = Math.floor(($containerWidth/$tileCount) - 2);
    	$('.tile_info').css('font-size', function() { return Math.floor($tileSize/12) + 'px'; });
	}
	
    if ($containerWidth > 639 && $containerWidth <= 1024) {
		$tileCount=5;
		$tileSize = Math.floor(($containerWidth/$tileCount) - 2);
        $('.tile_info').css('font-size', function() { return Math.floor($tileSize/12) + 'px'; });
	}
	
	if ($containerWidth > 1024 && $containerWidth <=1280) {
		$tileCount=7;
		$tileSize = Math.floor(($containerWidth/$tileCount) - 2);
    	$('.tile_info').css('font-size', function() { return Math.floor($tileSize/13) + 'px'; });
    	//$('.tile_info').css('font-size', function() { return '0.8em'; });
	}
	//console.log ($containerWidth);
	return [$tileSize,$containerWidth];
}

function updateAddPlay(data) {

	$('#played').html(data.played);
	$('#last_played').html(data.last_played + '<span id="playedCal" class=" icon-anchor" onclick="togglePlayedHistory();">&nbsp;&nbsp;<i class="fa fa-calendar fa-lg"></i></span>');
	$('#popularity').html(data.popularity);
	var bar_pop = $('#bar-popularity-out').width() * data.popularity/100;
	//console.log ('bar_pop:' + bar_pop);
	$('#bar_popularity').css('width', bar_pop);
};



function resizeTile($tileSize,$containerWidth) {
	$('.tile').css('width', function() { return $tileSize; });
	$('.tile').css('height', function() { return $tileSize; });
	resizeSuggested($tileSize,$containerWidth);
	resizeUsersTab($tileSize,$containerWidth);
	
}

function resizeSuggested($tileSize,$containerWidth) {
	$('.full').css('height', function() { return ($tileSize + 4); });
	$('.full').css('width', function() { 
	return ($tileCount * $tileSize + (($tileCount - 1) * 1) + 'px'); 
	});
}

function resizeUsersTab($tileSize,$containerWidth) {
	$('#usersTab').css('width', function() { 
	return ($tileCount * $tileSize + (($tileCount - 1) * 2) + 'px'); 
	});
}

function resizeImgContainer() {
	
	if ($("#searchFormAll").css('display')=='none') {
	//window.scrollTo(0, 0);
	var winH = $(window).height();
	var winW = $(window).width();
	var bodyMaxWidth = $('body').css('max-width');
	var maxH;
	var minW;
	var imageH;
		
	if (winW > parseInt(bodyMaxWidth)) winW = bodyMaxWidth;
	
	$('#image_container').css('width', '');
	$('#image_container').css('height', '');
	$('#image_container').css('max-height', '');
	$('#image').css('max-width', '');
	$('#image').css('height', '');
	$('#image').css('max-height', '');
	$('.pl-track-info-right').css('width', '');
	$('.album-info-area-right').css('width', '');
	
	$('#image_in').css("top", "0");
	
	if (winW < 530) {
		$('#image').css('max-width', (winW));
		maxH = $(window).height() - $('.pl-track-info-right').height() - $('#menu').height() - 3;
		//maxH = $(window).height() - $('#menu').height() - 3;
		//console.log ('maxH=' + maxH);
		if ((winW - maxH) > 20) $('#image').css('width', maxH);
		else $('#image').css('width', '');
		$('#image').css('height', maxH);
		$('#image').css('max-height', maxH);
		$('#image_container').css('max-height', function(){return (winW) * 1.1;});
		
		/* imageH = $('#image').height();
		if (imageH > maxH * 1.1) {
			var imgTop = (imageH - maxH)/2;
			//console.log ("maxH=" + maxH + " imageH=" + imageH);
			$('#image_in').css("top", function() { return ("-" + (imageH - maxH)/2) + "px"});
		} */
		
	} 
	else {
		$('#image').css('width', '');
		minW = parseInt($('#image_container').css('min-width'));
		maxHpx = (winH - $('.menu_top').height() - $('.menu_middle').height() - 5);
		maxH = maxHpx/winW * 100;
		maxH = Math.floor(maxH);
		if (maxHpx<minW){
			// $('#image_container').css('width', '');
			// $('.pl-track-info-right').css('width', '');
			// $('.album-info-area-right').css('width', '');
		}
		else if (maxH<50 || winW == bodyMaxWidth) {
			$('#image_container').css('width', maxH  + "%");
			$('.pl-track-info-right').css('width', (100 - maxH - 2) + "%");
			$('.album-info-area-right').css('width', (100 - maxH - 3) + "%");
		} 
		else {
			$('#image_container').css('width', '50%');
			$('.pl-track-info-right').css('width', '48%');
			$('.album-info-area-right').css('width', '47%');
		}
		$('#image').css('max-height', maxHpx);
		$('#image').css('min-height', '220px');
		
		imageH = $('#image').height();
		if (imageH > maxHpx * 1.1) {
			var imgTop = (imageH - maxH)/2;
			$('#image_in').css("top", function() { return ("-" + (imageH - maxHpx)/2) + "px"});
		}
	} 
}
} 


function toggleMenuSub($id) {
	$('[id^=menu-sub-track]').slideUp("slow", function() {    
	});
	$('[id^=menu-icon]').removeClass("icon-small-selected");
	//console.log($( '#' + $subId ).css("display"));
	if ($( '#menu-sub-track' + $id ).css("display")=="none"){
		$( '#menu-sub-track' + $id ).slideDown( "slow", function() {});
		$('#menu-icon' + $id).addClass("icon-small-selected");
		//console.log('#menu-icon' + $id);
	}
	else {
		$( '#menu-sub-track' + $id ).slideUp( "slow", function() { });
		$('#menu-icon' + $id).removeClass("icon-small-selected");
	}
};

function offMenuSub($id) {
	$( '#menu-sub-track' + $id ).slideUp( "slow", function() {    
	});
	$('#menu-icon' + $id).removeClass("icon-small-selected");
};


function toggleSubMiddle($id) {
	$('[id^=menuSubMiddleMedia]').slideUp("slow", function() {    
	});
	$('[id^=iconmenu]').removeClass("icon-selected");
	//console.log(( $id ));
	if ($( '#menuSubMiddleMedia' + $id ).css("display")=="none"){
		$( '#menuSubMiddleMedia' + $id ).slideDown( "slow", function() {});
		$( '#iconmenuSubMiddleMedia' + $id ).addClass("icon-selected");
		//console.log('#menu-icon' + $id);
	}
	else {
		$( '#menuSubMiddleMedia' + $id ).slideUp( "slow", function() { });
		$('#iconmenuSubMiddleMedia' + $id).removeClass("icon-selected");
	}
};

function toggleSearchResults($id) {
	if ($( '#searchResults' + $id ).css("display")=="none"){
		$( '#searchResults' + $id ).slideDown( "slow", function() {});
		$( '#iconSearchResults' + $id ).addClass("icon-selected");
		$('#iconSearchResults' + $id).removeClass("icon-anchor");
		//console.log('#menu-icon' + $id);
	}
	else {
		$( '#searchResults' + $id ).slideUp( "slow", function() { });
		$('#iconSearchResults' + $id).removeClass("icon-selected");
		$('#iconSearchResults' + $id).addClass("icon-anchor");
	}
};

function toggleSearch() {
	$('#iconPlayerToggler').removeClass("icon-selected");
	$('#iconVolumeToggler').removeClass("icon-selected");
	$('#playerList').slideUp( "slow", function() {});
	$('#volumeArea').slideUp( "slow", function() { });
	if ($('#searchFormAll').css("display")=="none"){
		$('#searchFormAll').slideDown( "slow", function() {});
		$('#iconSearchToggler').addClass("icon-selected");
		$('#search_string').focus();
	}
	else {
		$('#searchFormAll').slideUp( "slow", function() { });
		$('#iconSearchToggler').removeClass("icon-selected");
		$('#iconSearchToggler').focus();
		}
};

function goSearch () {
	$('#searchFormAll').submit();
};

function toggleChangePlayer() {
	ajaxRequest('ajax-evaluate-status.php', evaluateVolume);
	$('#iconSearchToggler').removeClass("icon-selected");
	$('#iconVolumeToggler').removeClass("icon-selected");
	$('#searchFormAll').slideUp( "slow", function() {});
	$('#volumeArea').slideUp( "slow", function() { });
	if ($('#playerList').css("display")=="none"){
		$('#playerList').slideDown( "slow", function() {});
		$('#iconPlayerToggler').addClass("icon-selected");
	}
	else {
		$('#playerList').slideUp( "slow", function() { });
		$('#iconPlayerToggler').removeClass("icon-selected");
		}
};

function toggleVolume() {
	ajaxRequest('ajax-evaluate-status.php', evaluateVolume);
	$('#iconSearchToggler').removeClass("icon-selected");
	$('#iconPlayerToggler').removeClass("icon-selected");
	$('#searchFormAll').slideUp( "slow", function() {});
	$('#playerList').slideUp( "slow", function() {});
	if ($('#volumeArea').css("display")=="none"){
		$('#volumeArea').slideDown( "slow", function() {});
		$('#iconVolumeToggler').addClass("icon-selected");
	}
	else {
		$('#volumeArea').slideUp( "slow", function() { });
		$('#iconVolumeToggler').removeClass("icon-selected");
		}
};

function togglePlayedHistory() {
	$('#playedHistory').slideUp("slow", function() {    
	});
	if ($('#playedHistory').css("display")=="none"){
		$('#playedHistory').slideDown( "slow", function() {});
		$('#playedCal i').addClass("icon-selected");
		//console.log('#menu-icon' + $id);
	}
	else {
		$('#playedHistory').slideUp( "slow", function() { });
		$('#playedCal i').removeClass("icon-selected");
	}
};


function showSpinner() {
	target.style.width = $( window ).width();
	target.style.height = $( window ).height();
	target.style.marginTop = "-" + ($( window ).height())/2 + "px";
	target.style.marginLeft = "-" + ($( window ).width())/2 + "px";
	target.style.display = "block";
	spinner.spin(target);
};

function hideSpinner() {
	spinner.stop();
	target.style.display = "none";
	
};