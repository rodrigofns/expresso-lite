/*!
 * Expresso Lite
 * A resizable and draggable modeless popup DIV, for desktop and phones. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery', 'inc/App', 'inc/UrlStack'], function($, App, UrlStack) {
App.LoadCss('inc/ModelessDialog.css');
var ModelessDialog = function(options) {
    var userOpts = $.extend({
        $elem: null, // jQuery object for the target DIV
        caption: 'Modeless popup',
        width: 400,
        height: 400,
        minWidth: 100,
        minHeight: 100,
        resizable: true,
        marginMaximized: 8,
        marginLeftMaximized: 360
    }, options);

    var THIS          = this;
    var onUserCloseCB = null; // user callbacks
    var onCloseCB     = null;
    var onResizeCB    = null;
    var $targetDiv    = userOpts.$elem;
    var $tpl          = null;
    var cyTitle       = 0; // height of titlebar; never changes
    var prevMinPos    = { x:0, y:0, cx:0, cy:0 }; // position/size before minimize
    var prevMaxPos    = { x:0, y:0, cx:0, cy:0 }; // before maximize

    function _SetEvents() {
        $tpl.find('.ModelessDialog_bar').on('mousedown', function(ev) {
            ev.stopImmediatePropagation();

            if ($tpl.find('.ModelessDialog_content').is(':visible')) {
                var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
                var pop = { x:$tpl.offset().left, y:$tpl.offset().top,
                    cx:$tpl.outerWidth(), cy:$tpl.outerHeight() };
                var offset = { x:ev.clientX - pop.x, y:ev.clientY - pop.y };
                $('div').addClass('ModelessDialog_unselectable');

                $(document).on('mousemove.ModelessDialog', function(ev) {
                    ev.stopImmediatePropagation();
                    var newPos = { x:ev.clientX - offset.x, y:ev.clientY - offset.y };
                    var destCss = { };
                    if (newPos.x >= 0 && newPos.x + pop.cx <= wnd.cx) destCss.left = newPos.x+'px';
                    if (newPos.y >= 0 && newPos.y + pop.cy <= wnd.cy) destCss.top = newPos.y+'px';
                    $tpl.css(destCss);
                });

                $(document).on('mouseup.ModelessDialog', function(ev) {
                    $('div').removeClass('ModelessDialog_unselectable');
                    $(document).off('.ModelessDialog');
                });
            } else {
                THIS.toggleMinimize();
            }
        });

        $tpl.find('.ModelessDialog_bar').on('dblclick', function(ev) {
            ev.stopImmediatePropagation();
            var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
            if (!App.IsPhone()) { // on phones doubleclick does nothing
                if ($tpl.find('.ModelessDialog_content').is(':visible')) { // not minimized
                    var isMaximized = ($tpl.offset().left === userOpts.marginLeftMaximized) &&
                        ($tpl.offset().top === userOpts.marginMaximized) &&
                        ($tpl.outerWidth() === wnd.cx - userOpts.marginLeftMaximized - userOpts.marginMaximized) &&
                        ($tpl.outerHeight() === wnd.cy - userOpts.marginMaximized * 2);

                    if (!isMaximized) {
                        prevMaxPos = { // keep current pos
                            x: $tpl.offset().left,
                            y: $tpl.offset().top,
                            cx: $tpl.outerWidth(),
                            cy: $tpl.outerHeight()
                        };
                        $tpl.css({
                            left: userOpts.marginLeftMaximized+'px',
                            top: userOpts.marginMaximized+'px',
                            width: (wnd.cx - userOpts.marginLeftMaximized - userOpts.marginMaximized)+'px',
                            height: (wnd.cy - userOpts.marginMaximized * 2)+'px'
                        });
                    } else {
                        $tpl.css({ // restore previous pos
                            left: prevMaxPos.x+'px',
                            top: prevMaxPos.y+'px',
                            width: prevMaxPos.cx+'px',
                            height: prevMaxPos.cy+'px'
                        });
                    }

                    if (onResizeCB !== null) {
                        onResizeCB(); // invoke user callback
                    }
                } else { // if minimized, simply restore; never happens because mousedown comes first
                    THIS.toggleMinimize();
                }
            }
        });

        $tpl.find('.ModelessDialog_resz').on('mousedown', function(ev) {
            ev.stopImmediatePropagation();
            var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
            var pop = { x:$tpl.offset().left, y:$tpl.offset().top,
                cx:$tpl.outerWidth(), cy:$tpl.outerHeight() };
            var orig = { x:ev.clientX, y:ev.clientY };
            $('div').addClass('ModelessDialog_unselectable');

            $(document).on('mousemove.ModelessDialog', function(ev) {
                ev.stopImmediatePropagation();
                var newSz = { cx:pop.cx + ev.clientX - orig.x, cy:pop.cy + ev.clientY - orig.y };
                var destCss = { };
                if (pop.x + newSz.cx < wnd.cx) {
                    destCss.width = newSz.cx+'px';
                }
                if (pop.y + newSz.cy < wnd.cy) {
                    destCss.height = newSz.cy+'px';
                }
                $tpl.css(destCss);
                if (onResizeCB !== null) {
                    onResizeCB();
                }
            });

            $(document).on('mouseup.ModelessDialog', function(ev) {
                $('div').removeClass('ModelessDialog_unselectable');
                $(document).off('.ModelessDialog');
            });
        });

        $tpl.find('.ModelessDialog_minCage input').on('click', function(ev) {
            ev.stopImmediatePropagation();
            THIS.toggleMinimize();
        });

        $tpl.find('.ModelessDialog_closeCage input').on('click', function(ev) {
            ev.stopImmediatePropagation();
            if (onUserCloseCB !== null) {
                onUserCloseCB(); // invoke user callback, user must call close() himself
            }
        });
    }

    THIS.show = function() {
        var defer = $.Deferred();
        $tpl = $('#ModelessDialog_template .ModelessDialog_box').clone();
        $tpl.find('.ModelessDialog_title').html(userOpts.caption);

        var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
        var szCss = App.IsPhone() ?
            { width:wnd.cx+'px', height:wnd.cy+'px' } :
            { width:userOpts.width+'px', height:userOpts.height+'px' };
        $tpl.css(szCss).appendTo(document.body);
        $tpl.find('.ModelessDialog_content').append($targetDiv);

        if (App.IsPhone()) {
            $tpl.find('.ModelessDialog_minCage,.ModelessDialog_resz').hide();
            UrlStack.push('#ModelessDialog', function() {
                $tpl.find('.ModelessDialog_closeCage input:first').trigger('click');
            });

            $tpl.offset({ left:$(window).outerWidth() }) // slide from right
                .animate({ left:'0px' }, 300, function() { defer.resolve(); });
        } else {
            $tpl.css({
                'min-width': userOpts.minWidth+'px',
                'min-height': userOpts.minHeight+'px',
                left: ($(window).outerWidth() / 2 - $tpl.outerWidth() / 2)+'px', // center screen
                top: Math.max(0, $(window).outerHeight() / 2 - $tpl.outerHeight() / 2)+'px'
            });
            if (!userOpts.resizable) {
                $tpl.find('.ModelessDialog_resz').hide();
            }

            var yOff = $tpl.offset().top;
            $tpl.offset({ top:-$tpl.outerHeight() }) // slide from above
                .animate({ top:yOff+'px' }, 200, function() { defer.resolve(); });
        }

        cyTitle = $tpl.find('.ModelessDialog_bar').outerHeight();
        _SetEvents();
        return defer.promise();
    };

    THIS.getContentArea = function() {
        return { cx:$tpl.outerWidth(), cy:$tpl.outerHeight() - cyTitle };
    };

    THIS.isOpen = function() {
        return $tpl !== null;
    };

    THIS.close = function() {
        var defer = $.Deferred();
        var animMove = App.IsPhone() || THIS.isMinimized() ?
            { left: $(window).outerWidth()+'px' } :
            { top: $(window).outerHeight()+'px' };
        $tpl.animate(animMove, 200, function() {
            $targetDiv.detach(); // element is up to user
            $tpl.remove();
            $tpl = null;
            UrlStack.pop('#ModelessDialog');
            if (onCloseCB !== null) {
                onCloseCB(); // invoke user callback
            }
            defer.resolve();
        });
        return defer.promise();
    };

    THIS.isMinimized = function() {
        return !$tpl.find('.ModelessDialog_content').is(':visible');
    };

    THIS.toggleMinimize = function() {
        var willMin = !THIS.isMinimized();
        $tpl.find('.ModelessDialog_content,.ModelessDialog_resz').toggle();
        $tpl.find('.ModelessDialog_closeCage').toggle(!willMin);
        $tpl.find('.ModelessDialog_minCage input').val(willMin ? '¯' : '_');
        if (willMin) {
            prevMinPos = { // keep current pos
                x: $tpl.offset().left,
                y: $tpl.offset().top,
                cx: $tpl.outerWidth(),
                cy: $tpl.outerHeight()
            };
            $tpl.css({
                width: userOpts.minWidth+'px',
                left: ($(window).outerWidth() - userOpts.minWidth - 18)+'px',
                top: ($(window).outerHeight() - cyTitle)+'px'
            });
        } else {
            $tpl.css({ // restore previous pos
                left: prevMinPos.x+'px',
                top: prevMinPos.y+'px',
                width: prevMinPos.cx+'px',
                height: prevMinPos.cy+'px'
            });
        }
    };

    THIS.setCaption = function(text) {
        $tpl.find('.ModelessDialog_title').empty().append(text);
        return THIS;
    };

    THIS.removeCloseButton = function() {
        $tpl.find('.ModelessDialog_closeCage').remove();
        return THIS;
    };

    THIS.onUserClose = function(callback) {
        onUserCloseCB = callback; // triggered only when the user clicks the close button
        return THIS;
    };

    THIS.onClose = function(callback) {
        onCloseCB = callback; // onClose()
        return THIS;
    };

    THIS.onResize = function(callback) {
        onResizeCB = callback; // onResize()
        return THIS;
    };
};

ModelessDialog.Load = function() {
    // Static method, since this class can be instantied ad-hoc.
    return $('#ModelessDialog_template').length ?
        $.Deferred().done() :
        App.LoadTemplate('../inc/ModelessDialog.html');
};

return ModelessDialog;
});
