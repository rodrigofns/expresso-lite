/*!
 * Expresso Lite
 * Push/pop URL states using HTML5 API.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

window.UrlStack = (function() {
    var exp = { };

    var states = [];

    exp.keepClean = function() {
        if(location.href.indexOf('#') !== -1)
            history.replaceState('', '', location.pathname);
        return exp; // to be called once in document.ready
    };

    exp.push = function(label, onPop) {
        var newState = { label:label, callback:onPop };
        states.push(newState);
        window.history.pushState(null, '', label);
        return exp;
    };

    exp.pop = function(label) {
        if(location.href.match(new RegExp(label+'$'))) {
            var lastState = states.pop();
            window.history.replaceState(null, '',
                states.length ? states[states.length-1].label : location.pathname); // force back key
        }
        return exp; // do not invoke user callback
    };

    window.onpopstate = function(ev) {
        if(states.length) {
            var lastState = states.pop();
            if(lastState.callback !== null)
                lastState.callback(); // invoke user callback
        }
    };

    return exp;
})();