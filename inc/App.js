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

$.ajaxSetup({
    type: 'POST',
    headers: { 'cache-control':'no-cache' } // iOS devices may cache POST requests
});

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
        return $.post(require.toUrl('.'), // follows require.config() baseUrl
            $.extend({ r:requestName }, params));
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
    }
};
});
