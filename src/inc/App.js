/*!
 * Expresso Lite
 * Provides infrastructure services to all modules.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery'], function($) {
    function _DisableRefreshOnPullDown() {
        var isFirefoxAndroid =
            navigator.userAgent.indexOf('Mozilla') !== -1 &&
            navigator.userAgent.indexOf('Android') !== -1 &&
            navigator.userAgent.indexOf('Firefox') !== -1;

        if (!isFirefoxAndroid) {
            var lastTouchY = 0;
            var preventPullToRefresh = false;

            $('body').on('touchstart', function(e) {
                if (e.originalEvent.touches.length != 1) return;
                lastTouchY = e.originalEvent.touches[0].clientY;
                preventPullToRefresh = window.pageYOffset == 0;
            });

            $('body').on('touchmove', function(e) {
                var touchY = e.originalEvent.touches[0].clientY;
                var touchYDelta = touchY - lastTouchY;
                lastTouchY = touchY;
                if (preventPullToRefresh) {
                    preventPullToRefresh = false;
                    if (touchYDelta > 0) {
                        e.preventDefault();
                        return;
                    }
                }
            });
        }
    }

    (function _Constructor() {
        $.ajaxSetup({ // iOS devices may cache POST requests, so make no-cache explicit
            type: 'POST',
            headers: { 'cache-control':'no-cache' }
        });

        _DisableRefreshOnPullDown();
    })();

return {
    LoadCss: function(cssFiles) { // pass any number of files as arguments
        var head = document.getElementsByTagName('head')[0];
        for (var i = 0; i < arguments.length; ++i) {
            var link = document.createElement('link');
            link.type = 'text/css';
            link.rel = 'stylesheet';
            link.href = require.toUrl(arguments[i]); // follows require.config() baseUrl
            document.getElementsByTagName('head')[0].appendChild(link);
        }
    },

    Post: function(requestName, params) {
        // Usage: App.Post('searchFolders', { parentFolder:'1234' });
        // Returns a promise object.
        var backendUrl = require.toUrl('.'); // follows require.config() baseUrl

        var defer = $.Deferred();

        $.post(
            backendUrl + '/api/ajax.php',
            $.extend({r:requestName}, params)
        ).done(function (data) {
            defer.resolve(data);
        }).fail(function (data) {
            if (data.status === 401) { //session timeout
                document.location.href='../';
                // as this will leave the current screen, we
                // won't neither resolve or reject
            } else {
                defer.reject(data);
            }
        });

        return defer.promise();
     },

    LoadTemplate: function(htmlFileName) {
        // HTML file can be a relative path.
        // Pure HTML files are cached by the browser.
        var defer = $.Deferred();
        $.get(htmlFileName).done(function(elems) {
            $(document.body).append(elems);
            defer.resolve();
        });
        return defer.promise();
    },

    IsPhone: function() {
        return $(window).width() <= 1024; // should be consistent with all CSS media queries
    },

    SetUserInfo: function(entryIndex, entryValue) {
        localStorage.setItem('user_info_'+entryIndex, entryValue);
    },

    GetUserInfo: function(entryIndex) {
        return localStorage.getItem('user_info_'+entryIndex);
    },

    SetCookie: function (cookieName, cookieValue, expireDays) {
        var d = new Date();
        d.setTime(d.getTime() + (expireDays * 24 * 60 * 60 * 1000));
        var expires = 'expires='+d.toUTCString();
        document.cookie = cookieName+'='+cookieValue+'; '+expires;
    },

    GetCookie: function (cookieName) {
        var name = cookieName+'=';
        var allCookies = document.cookie.split(';');
        for(var i=0; i < allCookies.length; i++) {
            var cookie = allCookies[i].replace(/^\s+/,''); //this trims spaces in the left of the string;
            if (cookie.indexOf(name) === 0) {
                return cookie.substring(name.length,cookie.length);
            }
        }
        return null;
    }
};
});
