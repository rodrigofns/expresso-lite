/*!
 * Expresso Lite
 * Dynamic dropdown widget, which is shown on hovering. jQuery plugin; uses UrlStack.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, UrlStack ) {
$(document).ready(function() {
	$(document.head).append('<style>' +
		'div.dropdownMenu_surrounding { display:inline; padding:0; margin:0; text-align:left; }' +
		'div.dropdownMenu_surrounding * { -moz-user-select:none; -webkit-user-select:none; }' +
		'ul.dropdownMenu_box { margin:0; overflow-y:auto; background:white; box-shadow:3px 3px 18px #666; padding:3px; } ' +
		'@media (max-width:767px) { ul.dropdownMenu_box { box-shadow:3px 3px 180px #666; } } ' +
		'li.dropdownMenu_option { list-style:none; margin:0; padding:4px 8px; cursor:pointer; } ' +
		'li.dropdownMenu_option:hover { background:#E8F1FD; } ' +
		'li.dropdownMenu_header { border-top:1px solid #E8E8E8; padding-top:8px; color:#8A8A8A; font-style:italic; cursor:default; } ' +
		'li.dropdownMenu_header:hover { background:white; } ' +
		'a.dropdownMenu_link { color:#222; text-decoration:none; } ' +
		'</style>');
});

$.fn.dropdownMenu = function(options) {
	var userOpts = $.extend({
		cxPhone: 767 // max smartphone width, in pixels
	}, options);

	var exp = { };

	var $userBtn = this; // user button; when mouse goes over it, dropdown is shown
	var $surroDiv = null; // this DIV will take place of user button; user button will be placed inside of it
	var $list = null; // the dropdown UL
	var entries = []; // populated by addOption() and addHeader()

	function _RenderLastEntry() {
		var eLast = entries[entries.length-1]; // append the last element as an LI into the container UL
		var $liEntry = $(document.createElement('li')).addClass('dropdownMenu_option');
		if(eLast.onClick === null)
			$liEntry.addClass('dropdownMenu_header'); // non-clickable

		if(eLast.onClick === null) {
			$liEntry.append( $(document.createElement('span')).text(eLast.text) );
		} else {
			$liEntry.append( $(document.createElement('a'))
				.attr('href', '#')
				.addClass('dropdownMenu_link')
				.html(eLast.text) );
		}

		$liEntry.data('entry', eLast); // keep entry within LI
		$list.append($liEntry);
	}

	function _ApplyIndentation(text, nIndent) {
		var prefix = '';
		if(nIndent !== undefined && nIndent !== null) // text indentation
			for(var i = 0; i < nIndent; ++i)
				prefix += '&nbsp; ';
		return prefix+text;
	}

	exp.purge = function() {
		entries.length = 0;
		$list.empty();
		exp.hidePopup();
		return exp;
	};

	exp.addOption = function(text, callback, indent) {
		entries.push({ text:_ApplyIndentation(text, indent), onClick:callback });
		_RenderLastEntry();
		return exp;
	};

	exp.addHeader = function(text, indent) {
		entries.push({ text:_ApplyIndentation(text, indent), onClick:null });
		_RenderLastEntry();
		return exp;
	};

	exp.hidePopup = function() {
		if($list.is(':visible')) {
			$list.hide();
			$surroDiv.prepend($userBtn).css({
				position: 'static',
				width: $userBtn.outerWidth()+'px',
				'z-index': 'auto'
			});
			$userBtn.css('position', 'static');
			UrlStack.pop('#dropdownMenu');
		}
	};

	(function _Ctor() {
		$surroDiv = $(document.createElement('div')) // create surrounding DIV
			.addClass('dropdownMenu_surrounding')
			.css({
				width: $userBtn.outerWidth()+'px', // same size of user button
				height: $userBtn.outerHeight()+'px'
			});
		$userBtn.replaceWith($surroDiv); // takes place of user button
		$surroDiv.append($userBtn); // and user button goes inside the new DIV

		$list = $(document.createElement('ul')) // create dropdown UL
			.addClass('dropdownMenu_box')
			.css({
				//~ 'max-height': '500px', // too many items, scrollbar kicks in
				//~ 'overflow-y': 'auto',
				//~ 'overflow-x': 'hidden',
			}).hide().appendTo($surroDiv);
	})();

	$userBtn.on('click.dropdownMenu', function(ev) {
		ev.stopImmediatePropagation();
		exp.hidePopup();
		var page = { cx:$(window).width(), cy:$(window).height() };
		if(page.cx <= userOpts.cxPhone) { // click works only on smartphones
			$list.css('height', ''); // revert to natural height, if changed
			if(entries.length && page.cx <= userOpts.cxPhone) { // click works only on smartphones
				var list = { cx:$list.outerWidth(), cy:$list.outerHeight() };
				list.cx += 16; // even more room on smartphones

				if(list.cy > page.cy - 16) { // dropdown goes beyond page height; shrink, scrollbar will appear
					$list.height(page.cy - 16); // 14px gap for prettiness
					list.cx += 16; // scrollbar room
				}

				$surroDiv.css({
					position: 'fixed',
					width: list.cx+'px',
					height: list.cy+'px',
					left: (page.cx / 2 - list.cx / 2)+'px',
					top: '5px',
					'z-index': 1
				});
				$userBtn.detach().insertBefore($surroDiv);
				$list.show();
				UrlStack.push('#dropdownMenu', exp.hidePopup);
			}
		}
	});

	$surroDiv.on('mouseenter.dropdownMenu', function() { // mouse over user button
		var page = { cx:$(window).width(), cy:$(window).height() };
		if(page.cx > userOpts.cxPhone) { // mouse over does nothing on smartphones, since there's no mouse
			$list.css('height', ''); // revert to natural height, if changed
			if(entries.length) {
				var userBtn = $userBtn.offset(); // left, top
				var list = { cx:$list.outerWidth(), cy:$list.outerHeight() };
				var page = { cx:$(window).width(), cy:$(window).height() };
				var surrou = { };
				userBtn.cy = $userBtn.outerHeight();

				if(userBtn.top  + userBtn.cy + list.cy > page.cy) { // dropdown goes beyond page height; shrink, scrollbar will appear
					$list.height(page.cy - userBtn.top - userBtn.cy - 14); // 14px gap for prettiness
					list.cx += 14; // scrollbar room
				}

				surrou.cx = (userBtn.left + list.cx + 6 > page.cx) ?
					(page.cx - list.cx - 6) : // dropdown goes beyond page width, pull it back
					userBtn.left;

				$surroDiv.css({
					position: 'fixed',
					width: list.cx+'px',
					height: list.cy+'px',
					left: surrou.cx+'px',
					top: userBtn.top+'px',
					'padding-top': userBtn.cy+'px',
					'z-index': 1
				});
				$userBtn.detach().insertBefore($surroDiv);
				$list.show();
			}
		}
	});

	$surroDiv.on('mouseleave.dropdownMenu', function() {
		if($(window).width() > userOpts.cxPhone) // mouse over does nothing on smartphones, since there's no mouse
			exp.hidePopup();
	});

	$surroDiv.on('click.dropdownMenu', '.dropdownMenu_option:not(.dropdownMenu_header)', function(ev) {
		$(this).blur();
		exp.hidePopup();
		$(this).closest('li').data('entry').onClick(); // invoke user callback
		return false;
	});

	return exp;
};
})( jQuery, UrlStack );