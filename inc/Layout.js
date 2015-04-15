/*!
 * Expresso Lite
 * Main layout object code.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery', 'inc/App', 'inc/UrlStack', 'inc/ContextMenu'],
function($, App, UrlStack, ContextMenu) {
App.LoadCss('inc/Layout.css');
return function(options) {
    var userOpts = $.extend({
        userMail: '',   // string with user email, for displaying purposes
        $menu: null,    // jQuery object with the DIV for the left menu
        $content: null, // jQuery object with the DIV for all the content
        showMenuTime: 250,
        hideMenuTime: 200,
        keepAliveTime: 10 * 60 * 1000 // 10 minutes, Tine default
    }, options);

    var THIS               = this;
    var contextMenu        = null; // ContextMenu object
    var isContentFullWidth = false;
    var onKeepAliveCB      = null;
    var onSearchCB         = null;

    THIS.load = function() {
        var defer = $.Deferred();
        App.LoadTemplate('../inc/Layout.html').done(function() {
            // Detach and attach the user DIVs on the layout structure.
            $('#Layout_menuDarkCover,#Layout_arrowLeft').css('display', 'none');
            $('#Layout_menu').append(userOpts.$menu); // user elements are detached and attached
            $('#Layout_content').append(userOpts.$content);
            $('#Layout_userMail').text(userOpts.userMail);

            // Layout internal events.
            _SetEvents();

            // Init some stuff.
            contextMenu = new ContextMenu({ $btn:$('#Layout_context') });
            THIS.setContextMenuVisible(false); // initially hidden
            _SetCurrentModuleAsBold();
            defer.resolve();
        });
        return defer.promise();
    };

    THIS.setLeftMenuVisibleOnPhone = function(isVisible) {
        // Intended to be used when the page is loading, so any loading occurring
        // on the left menu is shown, after that user calls method(false) to hide it.
        var defer = $.Deferred();
        if (!App.IsPhone()) {
            window.setTimeout(function() { defer.resolve(); }, 10);
        } else if (isVisible) { // show left menu on phones, does nothing on desktops
            $('#Layout_menuDarkCover').css('display', ''); // reverting from "none"
            var $leftSec = $('#Layout_leftSection');
            var cx = $leftSec.outerWidth();
            $leftSec.css({
                left: '-'+cx+'px',
                display: 'block'
            });
            $leftSec.scrollTop(0);
            $leftSec.animate({ left:'0' }, userOpts.showMenuTime, function() {
                UrlStack.push('#leftMenu', function() { THIS.setLeftMenuVisibleOnPhone(false); });
                defer.resolve();
            });
        } else { // hides left menu on phones, does nothing on desktops
            $('#Layout_menuDarkCover').css('display', 'none');
            UrlStack.pop('#leftMenu');
            var $leftSec = $('#Layout_leftSection');
            var cx = $leftSec.outerWidth();
            $leftSec.css('left', '0');
            $leftSec.animate({ left:'-'+cx+'px' }, userOpts.hideMenuTime, function() {
                $leftSec.css({
                    left: '',
                    display: '' // reverting from "block"
                });
                defer.resolve();
            });
        }
        return defer.promise();
    };

    THIS.setContentFullWidth = function(isFullWidth) {
        // Hides the left menu and sets the content to fill page width.
        // Does nothing on phone, since left menu is hidden by default.

        var retCallback = { };

        if (isFullWidth) {
            if (!isContentFullWidth) {
                isContentFullWidth = true; // set flag
                if (!App.IsPhone()) {
                    var $layoutTop = $('#Layout_top');
                    $('#Layout_content').css({ left:0, width:'100%' });
                    $('#Layout_leftSection').scrollTop(0).css({
                        height: $layoutTop.outerHeight()+'px',
                        overflow: 'hidden',
                        'border-bottom-width': $layoutTop.css('border-bottom-width'), // copy border-bottom
                        'border-bottom-style': $layoutTop.css('border-bottom-style'),
                        'border-bottom-color': $layoutTop.css('border-bottom-color')
                    });
                }
                $('#Layout_logo3Lines').css('display', 'none');
                $('#Layout_arrowLeft').css('display', '');
                UrlStack.push('#fullContent', function() { THIS.setContentFullWidth(false); });
            }
            retCallback = { // return value will allow onUnset() chained call
                onUnset: function(callback) { THIS.setContentFullWidthCB = callback; }
            };
        } else if (!isFullWidth) {
            if (isContentFullWidth) {
                isContentFullWidth = false; // clear flag
                if (!App.IsPhone()) {
                    $('#Layout_content').css({ left:'', width:'' });
                    $('#Layout_leftSection').css({
                        height: '',
                        overflow: '',
                        'border-bottom': ''
                    });
                }
                $('#Layout_logo3Lines').css('display', '');
                $('#Layout_arrowLeft').css('display', 'none');
            }
            UrlStack.pop('#fullContent');
            if (THIS.setContentFullWidthCB !== undefined) {
                var userCb = THIS.setContentFullWidthCB;
                delete THIS.setContentFullWidthCB;
                userCb();
            }
        }
        return retCallback;
    };

    THIS.isContentFullWidth = function() {
        return isContentFullWidth;
    };

    THIS.getContextMenu = function() {
        return contextMenu; // simply return the object itself, for whatever purpose
    };

    THIS.setContextMenuVisible = function(isVisible) {
        $('#Layout_context').css('display', isVisible ? '' : 'none');
        return THIS;
    };

    THIS.setTitle = function(title) {
        $('#Layout_title').html(title);
        return THIS;
    };

    THIS.onKeepAlive = function(callback) {
        onKeepAliveCB = callback; // onKeepAlive()
        return THIS;
    };

    THIS.onSearch = function(callback) {
        onSearchCB = callback; // onSearch(text)
        return THIS;
    };

    function _SetCurrentModuleAsBold() {
        var curModule = location.href;
        curModule = curModule.split('/');
        curModule = curModule[curModule.length - 1] !== '' ?
            curModule[curModule.length - 1] : curModule[curModule.length - 2];
        $('#Layout_modules li').each(function(i, li) {
            var module = $(li).find('a:first').attr('href');
            if (module !== undefined) {
                module = module.substr(module.indexOf('/') + 1);
                if (module === curModule) {
                    $(li).find('span').css('font-weight', 'bold');
                    return false;
                }
            }
        });
    }

    function _SetEvents() {
        $('#Layout_logo3Lines').on('click.Layout', function() {
            THIS.setLeftMenuVisibleOnPhone(true);
        });

        $('#Layout_arrowLeft').on('click.Layout', function() {
            THIS.setContentFullWidth(false);
        });

        $('#Layout_txtSearch').on('keypress.Layout', function(ev) {
            if (ev.which === 13) {
                $('#Layout_btnSearch').trigger('click'); // submit search on Enter
            }
        });

        $('#Layout_btnSearch').on('click.Layout', function() {
            if (onSearchCB !== null) {
                var searchTerm = App.IsPhone() ?
                    window.prompt('Busca') : $('#Layout_txtSearch').val();
                if (searchTerm === null) searchTerm = '';
                onSearchCB(searchTerm);
            }
        });

        $('#Layout_logo,#Layout_menuArrowLeft').on('click.Layout', function() {
            THIS.setLeftMenuVisibleOnPhone(false);
        });

        $('#Layout_menuDarkCover').on('click.Layout', function() {
            THIS.setLeftMenuVisibleOnPhone(false);
        });

        $('#Layout_logoff').on('click.Layout', function(ev) { // logoff the whole application
            ev.stopImmediatePropagation();
            $('#Layout_logoff').css('display', 'none');
            $('#Layout_loggingOff').css('display', 'inline-block');
            App.Post('logoff')
            .done(function(data) {
                location.href = '.';
            }).fail(function(error) {
                console.error('Logout error: ' + error.responseText);
                location.href = '.';
                // server side processing will usually invalidate the current
                // session even if it throws an error. So, it's reasonably
                // safe to fail silently and go back to the login screen
            });
        });

        $('#Layout_modules li,#Layout_modules a').on('click.Layout', function(ev) { // click on a module
            ev.preventDefault();
            ev.stopImmediatePropagation();
            var href = $(this).attr('href') !== undefined ?
                $(this).attr('href') : $(this).find('a').attr('href');
            location.href = href;
        });

        $(document).ajaxComplete(function AjaxComplete() {
            if (onKeepAliveCB !== null) {
                if (AjaxComplete.timer !== undefined && AjaxComplete.timer !== null) {
                    window.clearTimeout(AjaxComplete.timer);
                }
                AjaxComplete.timer = window.setTimeout(function() {
                    AjaxComplete.timer = null;
                    onKeepAliveCB(); // invoke user callback
                }, userOpts.keepAliveTime); // X minutes after the last request, an update should be performed (keep-alive)
            }
        });
    }
};
});
