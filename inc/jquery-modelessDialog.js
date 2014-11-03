/*!
 * Expresso Lite
 * A resizable and draggable modeless popup DIV, for desktop and phones. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, UrlStack ) {
$(document).ready(function() {
    $(document.head).append('<style>' +
    '#modelessDialog_box { position:fixed; background:white; box-shadow:1px 1px 12px #666; } ' +
    '#modelessDialog_bar { overflow:hidden; padding:6px; border-bottom:1px solid #DDD; cursor:move; ' +
        '-moz-user-select:none; -webkit-user-select:none; user-select:none; background:#005483; } ' +
    '#modelessDialog_title { font-weight:bold; color:#F0F0F0; display:inline-block; margin-top:3px; } ' +
    '#modelessDialog_minCage,#modelessDialog_closeCage { padding:0 1px; display:inline-block; float:right; } ' +
    '#modelessDialog_resz { position:absolute; right:0; bottom:0; background:#F8F8F8; ' +
        'cursor:se-resize; width:30px; height:30px; }' +
    '.modelessDialog_unselectable { -moz-user-select:none; -webkit-user-select:none; user-select:none; } ' +
    '</style>');
});

$.fn.modelessDialog = function(options) {
    var userOpts = $.extend({
        caption: 'Modeless popup',
        width: 400,
        height: 400,
        minWidth: 100,
        minHeight: 100,
        resizable: true,
        marginMaximized: 8,
        marginLeftMaximized: 360,
        cxPhone: 767 // max smartphone width, in pixels
    }, options);

    var exp = { };

    var onUserCloseCB = null; // user callbacks
    var onCloseCB     = null;
    var onResizeCB    = null;
    var $targetDiv    = this;
    var $popupDiv     = null;
    var cyTitle       = 0; // height of titlebar; never changes
    var prevMinPos    = { x:0, y:0, cx:0, cy:0 }; // position/size before minimize
    var prevMaxPos    = { x:0, y:0, cx:0, cy:0 }; // before maximize

    (function _Ctor() {
        $popupDiv = $('<div id="modelessDialog_box">' +
            '<div id="modelessDialog_bar">' +
                '<div id="modelessDialog_title">'+userOpts.caption+'</div>' +
                '<div id="modelessDialog_closeCage"><input type="button" value="X" title="Fechar esta janela"/></div>' +
                '<div id="modelessDialog_minCage"><input type="button" value="_" title="Minimizar esta janela"/></div>' +
            '</div>' +
            '<div id="modelessDialog_content"></div>' +
            '<div id="modelessDialog_resz"></div>' +
            '</div>');
        var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
        var szCss = (wnd.cx <= userOpts.cxPhone) ? // smartphone screen
            { width:wnd.cx+'px', height:wnd.cy+'px' } :
            { width:userOpts.width+'px', height:userOpts.height+'px' };
        $popupDiv.css(szCss).appendTo(document.body);
        $targetDiv.detach().appendTo('#modelessDialog_content').show();

        if(wnd.cx <= userOpts.cxPhone) { // smartphone screen
            $('#modelessDialog_minCage,#modelessDialog_resz').hide();
            UrlStack.push('#modelessDialog', function() {
                $('#modelessDialog_closeCage input:first').trigger('click');
            });
        } else {
            $popupDiv.css({ 'min-width': userOpts.minWidth+'px',
                'min-height': userOpts.minHeight+'px',
                left:($(window).outerWidth() / 2 - $popupDiv.outerWidth() / 2)+'px', // center screen
                top:Math.max(0, $(window).outerHeight() / 2 - $popupDiv.outerHeight() / 2)+'px' });
            if(!userOpts.resizable)
                $('#modelessDialog_resz').hide();
        }
        cyTitle = $('#modelessDialog_bar').outerHeight();
    })();

    exp.getContentArea = function() {
        return { cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() - cyTitle };
    };

    exp.isOpen = function() {
        return $popupDiv !== null;
    };

    exp.close = function() {
        $targetDiv.detach().appendTo(document.body).hide();
        $popupDiv.remove();
        $popupDiv = null;
        UrlStack.pop('#modelessDialog');
        if(onCloseCB !== null)
            onCloseCB(); // invoke user callback
        return exp;
    };

    exp.isMinimized = function() {
        return !$('#modelessDialog_content').is(':visible');
    };

    exp.toggleMinimize = function() {
        //~ var willMin = $('#modelessDialog_content').is(':visible');
        var willMin = !exp.isMinimized();
        $('#modelessDialog_content,#modelessDialog_resz').toggle();
        $('#modelessDialog_closeCage').toggle(!willMin);
        $('#modelessDialog_minCage input').val(willMin ? '¯' : '_');
        if(willMin) {
            prevMinPos = { x:$popupDiv.offset().left, y:$popupDiv.offset().top,
                cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() }; // keep current pos
            $popupDiv.css({ width:userOpts.minWidth+'px',
                left:($(window).outerWidth() - userOpts.minWidth - 18)+'px',
                top:($(window).outerHeight() - cyTitle)+'px' });
        } else {
            $popupDiv.css({ left:prevMinPos.x+'px', top:prevMinPos.y+'px',
                width:prevMinPos.cx+'px', height:prevMinPos.cy+'px' }); // restore previous pos
        }
    };

    exp.setCaption = function(htmlText) {
        $('#modelessDialog_title').html(htmlText);
        return exp;
    };

    exp.removeCloseButton = function() {
        $('#modelessDialog_closeCage').remove();
        return exp;
    };

    exp.onUserClose = function(callback) {
        onUserCloseCB = callback; // triggered only when the user clicks the close button
        return exp;
    };

    exp.onClose = function(callback) {
        onCloseCB = callback; // onClose()
        return exp;
    };

    exp.onResize = function(callback) {
        onResizeCB = callback; // onResize()
        return exp;
    };

    $('#modelessDialog_bar').on('mousedown', function(ev) {
        ev.stopImmediatePropagation();

        if($('#modelessDialog_content').is(':visible')) {
            var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
            var pop = { x:$popupDiv.offset().left, y:$popupDiv.offset().top,
                cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() };
            var offset = { x:ev.clientX - pop.x, y:ev.clientY - pop.y };
            $('div').addClass('modelessDialog_unselectable');

            $(document).on('mousemove.modelessDialog', function(ev) {
                ev.stopImmediatePropagation();
                var newPos = { x:ev.clientX - offset.x, y:ev.clientY - offset.y };
                var destCss = { };
                if(newPos.x >= 0 && newPos.x + pop.cx <= wnd.cx) destCss.left = newPos.x+'px';
                if(newPos.y >= 0 && newPos.y + pop.cy <= wnd.cy) destCss.top = newPos.y+'px';
                $popupDiv.css(destCss);
            });

            $(document).on('mouseup.modelessDialog', function(ev) {
                $('div').removeClass('modelessDialog_unselectable');
                $(document).off('.modelessDialog');
            });
        } else {
            exp.toggleMinimize();
        }
    });

    $('#modelessDialog_bar').on('dblclick', function(ev) {
        ev.stopImmediatePropagation();
        var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
        if(wnd.cx > userOpts.cxPhone) { // on phones doubleclick does nothing
            if($('#modelessDialog_content').is(':visible')) { // not minimized
                var isMaximized = ($popupDiv.offset().left === userOpts.marginLeftMaximized) &&
                    ($popupDiv.offset().top === userOpts.marginMaximized) &&
                    ($popupDiv.outerWidth() === wnd.cx - userOpts.marginLeftMaximized - userOpts.marginMaximized) &&
                    ($popupDiv.outerHeight() === wnd.cy - userOpts.marginMaximized * 2);
                if(!isMaximized) {
                    prevMaxPos = { x:$popupDiv.offset().left, y:$popupDiv.offset().top,
                        cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() }; // keep current pos
                    $popupDiv.css({
                        left:   userOpts.marginLeftMaximized+'px',
                        top:    userOpts.marginMaximized+'px',
                        width:  (wnd.cx - userOpts.marginLeftMaximized - userOpts.marginMaximized)+'px',
                        height: (wnd.cy - userOpts.marginMaximized * 2)+'px'
                    });
                } else {
                    $popupDiv.css({ left:prevMaxPos.x+'px', top:prevMaxPos.y+'px',
                        width:prevMaxPos.cx+'px', height:prevMaxPos.cy+'px' }); // restore previous pos
                }
                if(onResizeCB !== null)
                    onResizeCB(); // invoke user callback
            } else { // if minimized, simply restore; never happens because mousedown comes first
                exp.toggleMinimize();
            }
        }
    });

    $('#modelessDialog_resz').on('mousedown', function(ev) {
        ev.stopImmediatePropagation();
        var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
        var pop = { x:$popupDiv.offset().left, y:$popupDiv.offset().top,
            cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() };
        var orig = { x:ev.clientX, y:ev.clientY };
        $('div').addClass('modelessDialog_unselectable');

        $(document).on('mousemove.modelessDialog', function(ev) {
            ev.stopImmediatePropagation();
            var newSz = { cx:pop.cx + ev.clientX - orig.x, cy:pop.cy + ev.clientY - orig.y };
            var destCss = { };
            if(pop.x + newSz.cx < wnd.cx) destCss.width = newSz.cx+'px';
            if(pop.y + newSz.cy < wnd.cy) destCss.height = newSz.cy+'px';
            $popupDiv.css(destCss);
            if(onResizeCB !== null)
                onResizeCB();
        });

        $(document).on('mouseup.modelessDialog', function(ev) {
            $('div').removeClass('modelessDialog_unselectable');
            $(document).off('.modelessDialog');
        });
    });

    $('#modelessDialog_minCage input').on('click', function(ev) {
        ev.stopImmediatePropagation();
        exp.toggleMinimize();
    });

    $('#modelessDialog_closeCage input').on('click', function(ev) {
        ev.stopImmediatePropagation();
        if(onUserCloseCB !== null)
            onUserCloseCB(); // invoke user callback, user must call close() himself
    });

    return exp;
};
})( jQuery, UrlStack );