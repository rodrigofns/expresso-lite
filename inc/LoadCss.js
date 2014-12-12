/*!
 * Expresso Lite
 * Loads a CSS file programmatically.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */

(function() {
window.LoadCss = function(cssFiles) { // pass any number of files as arguments
    var head = document.getElementsByTagName('head')[0];
    for (var i = 0; i < arguments.length; ++i) {
        var link = document.createElement('link');
        link.setAttribute('type', 'text/css');
        link.setAttribute('rel', 'stylesheet');
        link.setAttribute('href', arguments[i]);
        head.appendChild(link);
    }
};
})();
