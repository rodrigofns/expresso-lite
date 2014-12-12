/*!
 * Expresso Lite
 * A resizable and draggable modeless popup DIV, for desktop and phones. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

LoadCss('../inc/ModelessDialog.css');

(function( $, UrlStack ) {
window.ModelessDialog = function(options) {
    var userOpts = $.extend({
        elem: null, // jQuery object for the target DIV
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

    var obj           = this;
    var onUserCloseCB = null; // user callbacks
    var onCloseCB     = null;
    var onResizeCB    = null;
    var $targetDiv    = userOpts.elem;
    var $popupDiv     = null;
    var cyTitle       = 0; // height of titlebar; never changes
    var prevMinPos    = { x:0, y:0, cx:0, cy:0 }; // position/size before minimize
    var prevMaxPos    = { x:0, y:0, cx:0, cy:0 }; // before maximize

    (function _Ctor() {
        $popupDiv = $('<div id="ModelessDialog_box">' +
            '<div id="ModelessDialog_bar">' +
                '<div id="ModelessDialog_title">'+userOpts.caption+'</div>' +
                '<div id="ModelessDialog_closeCage"><input type="button" value="X" title="Fechar esta janela"/></div>' +
                '<div id="ModelessDialog_minCage"><input type="button" value="_" title="Minimizar esta janela"/></div>' +
            '</div>' +
            '<div id="ModelessDialog_content"></div>' +
            '<div id="ModelessDialog_resz"></div>' +
            '</div>');
        var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
        var szCss = (wnd.cx <= userOpts.cxPhone) ? // smartphone screen
            { width:wnd.cx+'px', height:wnd.cy+'px' } :
            { width:userOpts.width+'px', height:userOpts.height+'px' };
        $popupDiv.css(szCss).appendTo(document.body);
        $targetDiv.detach().appendTo('#ModelessDialog_content').show();

        if (wnd.cx <= userOpts.cxPhone) { // smartphone screen
            $('#ModelessDialog_minCage,#ModelessDialog_resz').hide();
            UrlStack.push('#ModelessDialog', function() {
                $('#ModelessDialog_closeCage input:first').trigger('click');
            });
        } else {
            $popupDiv.css({ 'min-width': userOpts.minWidth+'px',
                'min-height': userOpts.minHeight+'px',
                left:($(window).outerWidth() / 2 - $popupDiv.outerWidth() / 2)+'px', // center screen
                top:Math.max(0, $(window).outerHeight() / 2 - $popupDiv.outerHeight() / 2)+'px' });
            if (!userOpts.resizable)
                $('#ModelessDialog_resz').hide();
        }
        cyTitle = $('#ModelessDialog_bar').outerHeight();
    })();

    obj.getContentArea = function() {
        return { cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() - cyTitle };
    };

    obj.isOpen = function() {
        return $popupDiv !== null;
    };

    obj.close = function() {
        $targetDiv.detach().appendTo(document.body).hide();
        $popupDiv.remove();
        $popupDiv = null;
        UrlStack.pop('#ModelessDialog');
        if (onCloseCB !== null)
            onCloseCB(); // invoke user callback
        return obj;
    };

    obj.isMinimized = function() {
        return !$('#ModelessDialog_content').is(':visible');
    };

    obj.toggleMinimize = function() {
        //~ var willMin = $('#ModelessDialog_content').is(':visible');
        var willMin = !obj.isMinimized();
        $('#ModelessDialog_content,#ModelessDialog_resz').toggle();
        $('#ModelessDialog_closeCage').toggle(!willMin);
        $('#ModelessDialog_minCage input').val(willMin ? '¯' : '_');
        if (willMin) {
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

    obj.setCaption = function(htmlText) {
        $('#ModelessDialog_title').html(htmlText);
        return obj;
    };

    obj.removeCloseButton = function() {
        $('#ModelessDialog_closeCage').remove();
        return obj;
    };

    obj.onUserClose = function(callback) {
        onUserCloseCB = callback; // triggered only when the user clicks the close button
        return obj;
    };

    obj.onClose = function(callback) {
        onCloseCB = callback; // onClose()
        return obj;
    };

    obj.onResize = function(callback) {
        onResizeCB = callback; // onResize()
        return obj;
    };

    $('#ModelessDialog_bar').on('mousedown', function(ev) {
        ev.stopImmediatePropagation();

        if ($('#ModelessDialog_content').is(':visible')) {
            var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
            var pop = { x:$popupDiv.offset().left, y:$popupDiv.offset().top,
                cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() };
            var offset = { x:ev.clientX - pop.x, y:ev.clientY - pop.y };
            $('div').addClass('ModelessDialog_unselectable');

            $(document).on('mousemove.ModelessDialog', function(ev) {
                ev.stopImmediatePropagation();
                var newPos = { x:ev.clientX - offset.x, y:ev.clientY - offset.y };
                var destCss = { };
                if (newPos.x >= 0 && newPos.x + pop.cx <= wnd.cx) destCss.left = newPos.x+'px';
                if (newPos.y >= 0 && newPos.y + pop.cy <= wnd.cy) destCss.top = newPos.y+'px';
                $popupDiv.css(destCss);
            });

            $(document).on('mouseup.ModelessDialog', function(ev) {
                $('div').removeClass('ModelessDialog_unselectable');
                $(document).off('.ModelessDialog');
            });
        } else {
            obj.toggleMinimize();
        }
    });

    $('#ModelessDialog_bar').on('dblclick', function(ev) {
        ev.stopImmediatePropagation();
        var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
        if (wnd.cx > userOpts.cxPhone) { // on phones doubleclick does nothing
            if ($('#ModelessDialog_content').is(':visible')) { // not minimized
                var isMaximized = ($popupDiv.offset().left === userOpts.marginLeftMaximized) &&
                    ($popupDiv.offset().top === userOpts.marginMaximized) &&
                    ($popupDiv.outerWidth() === wnd.cx - userOpts.marginLeftMaximized - userOpts.marginMaximized) &&
                    ($popupDiv.outerHeight() === wnd.cy - userOpts.marginMaximized * 2);
                if (!isMaximized) {
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
                if (onResizeCB !== null)
                    onResizeCB(); // invoke user callback
            } else { // if minimized, simply restore; never happens because mousedown comes first
                obj.toggleMinimize();
            }
        }
    });

    $('#ModelessDialog_resz').on('mousedown', function(ev) {
        ev.stopImmediatePropagation();
        var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
        var pop = { x:$popupDiv.offset().left, y:$popupDiv.offset().top,
            cx:$popupDiv.outerWidth(), cy:$popupDiv.outerHeight() };
        var orig = { x:ev.clientX, y:ev.clientY };
        $('div').addClass('ModelessDialog_unselectable');

        $(document).on('mousemove.ModelessDialog', function(ev) {
            ev.stopImmediatePropagation();
            var newSz = { cx:pop.cx + ev.clientX - orig.x, cy:pop.cy + ev.clientY - orig.y };
            var destCss = { };
            if (pop.x + newSz.cx < wnd.cx) destCss.width = newSz.cx+'px';
            if (pop.y + newSz.cy < wnd.cy) destCss.height = newSz.cy+'px';
            $popupDiv.css(destCss);
            if (onResizeCB !== null)
                onResizeCB();
        });

        $(document).on('mouseup.ModelessDialog', function(ev) {
            $('div').removeClass('ModelessDialog_unselectable');
            $(document).off('.ModelessDialog');
        });
    });

    $('#ModelessDialog_minCage input').on('click', function(ev) {
        ev.stopImmediatePropagation();
        obj.toggleMinimize();
    });

    $('#ModelessDialog_closeCage input').on('click', function(ev) {
        ev.stopImmediatePropagation();
        if (onUserCloseCB !== null)
            onUserCloseCB(); // invoke user callback, user must call close() himself
    });
};
})( jQuery, UrlStack );