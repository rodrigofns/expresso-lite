/*!
 * Expresso Lite
 * Right-click context menu, long click at phones. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, UrlStack ) {
(function() {
	$(document).ready(function() {
		$(document.head).append('<style>' +
		'.contextMenu_box { overflow-y:auto; background:#FDFDFD; padding:4px; border:1px solid #DDD; box-shadow:1px 3px 6px #909090; }' +
		'.contextMenu_box > ul { margin:0; padding:0; } ' +
		'.contextMenu_box * { -moz-user-select:none; -webkit-user-select:none; } ' +
		'.contextMenu_entry { list-style:none; margin:1px; padding:2px 12px 2px 6px; cursor:pointer; } ' +
		'.contextMenu_entry:hover { background:#E8F1FD; } ' +
		'.contextMenu_label { list-style:none; margin:1px; padding:2px 4px; font-style:italic; color:#777; } ' +
		'.contextMenu_sep { list-style:none; } ' +
		'.contextMenu_sep > hr { margin:0 2px; padding:0; border:none; height:1px; background:#DDD; }' +
		'</style>');
	});

	$(document).on('keydown.contextMenu', function(ev) {
		if(ev.keyCode === 27) { // ESC key
			if($('.contextMenu_box').length) {
				ev.stopImmediatePropagation();
				_Kill();
			}
		}
	});

	$(window).on('resize.contextMenu', function() { _Kill(); });
	$(window).on('blur.contextMenu', function() { _Kill(); });
})();

function _Kill() {
	if($('.contextMenu_box').length) {
		$('.contextMenu_box li').detach(); // keep in memory to be reused
		$('.contextMenu_box').remove();
		$('body *').off('click.contextMenu');
		UrlStack.pop('#contextMenu');
	}
}

$.fn.contextMenu = function(options) {
	var userOpts = $.extend({
		selector: null, // descendants filter, like jQuery.on()
		cxPhone: 767 // max smartphone width, in pixels
	}, options);

	var exp = { };

	var $target = this;
	var lis = [];
	var $clickedElem = null;
	var onShowCB = null; // when menu is just displayed

	function _SetPlacement(ev, $div) {
		var wnd = { cx:$(window).width(), cy:$(window).height() };
		if(wnd.cx <= userOpts.cxPhone) { // smartphones
			$div.css({
				position: 'fixed',
				top: '5%',
				left: (wnd.cx/2 - 130)+'px',
				width: '260px',
				height: '90%'
			});
		} else { // other devices
			var cyList = $div.outerHeight();
			var pos = { x: (ev.clientX > wnd.cx / 2) ? ev.pageX - $div.outerWidth() : ev.pageX,
				y: (ev.clientY > wnd.cy / 2) ? ev.pageY - cyList : ev.pageY };
			if(pos.y < 0) pos.y = 0;
			$div.css({ left:pos.x+'px', top:pos.y+'px' });
			if(pos.y + cyList > wnd.cy)
				$div.css('height', (wnd.cy - pos.y)+'px');
		}
	}

	function _BuildDiv() {
		var $ul = $('<ul></ul>');
		$ul.append(lis); // LIs are built and cached on Add() calls
		var $div = $('<div class="contextMenu_box" style="' +
			'position:absolute;' + // at 0,0
			'display:block;' +
			'z-index:999;' +
			'"></div>');
		$div.append($ul);
		return $div;
	}

	function _Clicked() {
		_Kill();
		var $li = $(this);
		var cb = $li.data('onClick');
		if(cb !== undefined && cb !== null) cb($clickedElem); // invoke user callback
		$clickedElem = null; // life cycle ends here
	}

	exp.add = function(text, onClick) {
		var $li = $('<li class="contextMenu_entry">'+text+'</li>');
		$li.data('onClick', onClick); // onClick($elem)
		$li.on('click.contextMenu', _Clicked);
		lis.push($li); // object is appended to page only when menu is displayed
		return exp;
	};

	exp.addSeparator = function() {
		lis.push($('<li class="contextMenu_sep"><hr/></li>'));
		return exp;
	};

	exp.addLabel = function(text) {
		lis.push($('<li class="contextMenu_label">'+text+'</li>'));
		return exp;
	};

	exp.purge = function() {
		lis.length = 0;
		return exp;
	};

	exp.onShow = function(callback) {
		onShowCB = callback; // onShow($elem)
		return exp;
	};

	$target.on('mousedown.contextMenu', userOpts.selector, function(ev) {
		_Kill();
		if(ev.which === 3) { // right-click only
			ev.preventDefault();
			ev.stopImmediatePropagation();
			var $divMenu = _BuildDiv();
			$divMenu.on('contextmenu.contextMenu', function() { return false; });
			$divMenu.appendTo(document.body);
			_SetPlacement(ev, $divMenu);
			$('body *').one('click.contextMenu', function(ev) { _Kill(); });
			$clickedElem = $(ev.target); // keep
			if(onShowCB !== null) onShowCB($clickedElem); // invoke user callback
		}
	});

	$target.on('taphold.contextMenu', userOpts.selector, function(ev) {
		var $menu = _BuildDiv();
		$menu.appendTo(document.body);
		_SetPlacement(ev, $menu);
		$('body *').one('click.contextMenu', function(ev) { _Kill(); });
		$clickedElem = $(ev.target); // keep
		if(onShowCB !== null) onShowCB($clickedElem); // invoke user callback

		UrlStack.push('#contextMenu', _Kill);
	});

	$target.on('contextmenu.contextMenu', userOpts.selector, function() {
		return false;
	});

	return exp;
};
})( jQuery, UrlStack );