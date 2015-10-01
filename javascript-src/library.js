//  +------------------------------------------------------------------------+
//  | netjukebox, Copyright (c) 2001-2015 Willem Bartels                     |
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
//  | Initialize                                                             |
//  +------------------------------------------------------------------------+
function init() {
	if (window.initialize) initialize();
	tooltip.init();
}




//  +------------------------------------------------------------------------+
//  | Session cookie                                                         |
//  +------------------------------------------------------------------------+
function sessionCookie() {
	var width = window.innerWidth || document.documentElement.clientWidth || 1024;
	document.cookie = 'netjukebox_width=' + width;
}




//  +------------------------------------------------------------------------+
//  | SHA1 (sha1.js)                                                         |
//  +------------------------------------------------------------------------+
function sha1(data) {
	return rstr2hex(rstr_sha1(str2rstr_utf8(data)));
}




//  +------------------------------------------------------------------------+
//  | HMAC-SHA1 (sha1.js)                                                    |
//  +------------------------------------------------------------------------+
function hmacsha1(key, data) {
	return rstr2hex(rstr_hmac_sha1(str2rstr_utf8(key), str2rstr_utf8(data)));
}




//  +------------------------------------------------------------------------+
//  | Formatted time                                                         |
//  +------------------------------------------------------------------------+
function formattedTime(miliseconds) {
	var seconds 	= Math.round(miliseconds / 1000);
	var hour 		= Math.floor(seconds / 3600);
	var minutes 	= Math.floor(seconds / 60) % 60;
	seconds 		= seconds % 60;
		
	if (hour > 0)	return hour + ':' + zeroPad(minutes, 2) + ':' + zeroPad(seconds, 2);
	else			return minutes + ':' + zeroPad(seconds, 2);
}




//  +------------------------------------------------------------------------+
//  | Zero pad                                                               |
//  +------------------------------------------------------------------------+
function zeroPad(number, n) { 
	var zeroPad = '' + number;
	
	while(zeroPad.length < n)
		zeroPad = '0' + zeroPad; 
	
	return zeroPad;
}




//  +------------------------------------------------------------------------+
//  | Show hide                                                              |
//  +------------------------------------------------------------------------+
function showHide(a, b) {
	document.getElementById(a).style.display = (document.getElementById(a).style.display == 'none') ? 'block' : 'none';
	document.getElementById(b).style.display = (document.getElementById(b).style.display == 'none') ? 'block' : 'none';
}




//  +------------------------------------------------------------------------+
//  | Inverse checkbox                                                       |
//  +------------------------------------------------------------------------+
function inverseCheckbox(frm) {
	for (var i = 0; i < frm.elements.length; i++) {
		if (frm.elements[i].type == 'checkbox') 
			frm.elements[i].checked = !frm.elements[i].checked;
	}
}




//  +------------------------------------------------------------------------+
//  | Get relative X                                                         |
//  +------------------------------------------------------------------------+
function getRelativeX(e, o) {
	if (e.pageX) {	
		var x = e.pageX;
		while (o.offsetParent) { 
			x -= o.offsetLeft;
			o = o.offsetParent; 
		} 
		return x;
	}
	else if (e.offsetX)
		return e.offsetX;
	else
		return 0;
}




//  +---------------------------------------------------------------------------+
//  | AJAX request                                                              |
//  +---------------------------------------------------------------------------+
function ajaxRequest(url, callback, postData) {
	if (typeof timer == 'function')
		timer();
	if (typeof XMLHttpRequest != 'undefined' && typeof(JSON) == 'object' && typeof(JSON.parse) == 'function') {
		url += (url.indexOf('?') == -1) ? '?ajax=1' : '&ajax=1';
		var method = (postData) ? 'post' : 'get';
		var http = new XMLHttpRequest();
		http.open(method, url, true);
		if (postData)
		    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		http.onreadystatechange = function() {
			if (http.readyState == 4 && http.status == 200 && typeof callback != 'undefined') {
				var data = JSON.parse(http.responseText);
				callback(data);
			}
			else if (http.readyState == 4 && http.status == 500) {
				var url = JSON.parse(http.responseText);
				if (url.substr(0,7) == 'http://' || url.substr(0,8) == 'https://')
					window.location.href=url;
			}
		}
		http.send(postData);
	}
	else
		alert('Your browser is not supported anymore.' + "\n" + 'Please update to a more recent one.');
}