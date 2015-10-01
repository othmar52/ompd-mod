//  +------------------------------------------------------------------------+
//  | Coded by Travis Beckham                                                |
//  | http://www.squidfingers.com                                            |
//  | http://www.podlob.com                                                  |
//  | If want to use this code, feel free to do so,                          |
//  | but please leave this message intact.                                  |
//  |                                                                        |
//  | Modified by Willem Bartels for the netjukebox project                  |
//  | http://www.netjukebox.nl                                               |
//  | http://forum.netjukebox.nl                                             |
//  +------------------------------------------------------------------------+
var tooltip = {
	id			: 'tooltip',
	tags		: ['a', 'tr', 'td', 'div', 'input'],
	disable		: /android|blackberry|ipad|ipod|mobi|palmos|phone|symbian|tablet|touchpad|webos/i,
	offsetX		: 15,
	offsetY		: 15,
	tip			: null
}




tooltip.init = function () {
	if (navigator.userAgent.match(this.disable)) return;
	this.tip = document.getElementById(this.id);
	if (this.tip) document.onmousemove = function(e) {tooltip.move(e)}
	var elements, element, title;
	for (var i = 0; i < this.tags.length; i++) {
		elements = document.getElementsByTagName(this.tags[i]);
		for (var j = 0; j < elements.length; j++) {
			element = elements[j];
			title = element.getAttribute('title');
			if (title) {
				element.setAttribute('tooltip', title);
				element.removeAttribute('title');
				element.onmouseover = function() {tooltip.show(this.getAttribute('tooltip'))}
				element.onmouseout = function() {tooltip.hide()}
			}
		}
	}
}




tooltip.move = function(e) {
	var x = this.offsetX;
	var y = this.offsetY;
	if (document.all) {
		x += (document.documentElement && document.documentElement.scrollLeft) ? document.documentElement.scrollLeft : document.body.scrollLeft;
		y += (document.documentElement && document.documentElement.scrollTop) ? document.documentElement.scrollTop : document.body.scrollTop;
		x += window.event.clientX;
		y += window.event.clientY;		
	}
	else {
		x += e.pageX;
		y += e.pageY;
	}
	this.tip.style.left = x + 'px';
	this.tip.style.top = y + 'px';
}




tooltip.show = function(text) {
	if (!this.tip) return;
	this.tip.innerHTML = text;
	this.tip.style.display = 'block';
}




tooltip.hide = function() {
	if (!this.tip) return;
	this.tip.style.display = 'none';
	this.tip.innerHTML = '';
}