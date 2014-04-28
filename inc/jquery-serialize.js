/*!
 * Expresso Lite
 * Serialization of HTML element. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $ ) {
$.fn.serialize = function() {
	// http://stackoverflow.com/questions/1700870/how-do-i-do-outerhtml-in-firefox
	return this[0].outerHTML || (new XMLSerializer().serializeToString(this[0]));
};
})( jQuery );