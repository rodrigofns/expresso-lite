/*!
 * Expresso Lite
 * Popup for search and autocomplete email addresses.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, Contacts ) {
window.WidgetSearchAddr = function(options) {
    var userOpts = $.extend({
        $elem: '', // jQuery object for the target DIV
        maxVisibleContacts: 10
    }, options);

    var obj       = this;
    var $txtbox   = userOpts.$elem; // a textarea element
    var token     = '';
    var onClickCB = null; // user callback

    function _InsertNamesIntoList(contacts) {
        var $list = $('#searchAddr_list');
        for (var c = 0; c < contacts.length; ++c) {
            var addr = contacts[c].emails.length > 1 ?
                (contacts[c].emails.length)+' emails' : contacts[c].emails[0];
            var $opt = $('<option>'+contacts[c].name+' ('+addr+')'+'</option>');
            $opt.data('contact', contacts[c]) // keep contact object into entry
                .appendTo($list);
        }
    }

    function _InsertMoreNamesIntoList(newContacts) {
        if (!newContacts.length) return;
        var $list = $('#searchAddr_list');
        var people = [];
        $list.children('option').each(function(idx, opt) {
            people.push($(opt).data('contact')); // array with currently displayed contacts
        });
        for (var c = 0; c < newContacts.length; ++c) {
            var alreadyExists = false;
            for (var p = 0; p < people.length; ++p) {
                if (people[p].emails.length !== 1) continue; // don't count groups
                if (people[p].emails[0] === newContacts[c].email) {
                    alreadyExists = true;
                    break;
                }
            }
            if (!alreadyExists)
                people.push({ name:newContacts[c].name, emails:[newContacts[c].email] }); // append new one
        }
        people.sort(function(a, b) { return a.name.localeCompare(b.name); });
        $list.empty();
        _InsertNamesIntoList(people);
        if (people.length > 2)
            $list.attr('size', Math.min(people.length, userOpts.maxVisibleContacts));
    }

    function _BuildPopup(numContacts) {
        var size = (numContacts <= 2 ? 2 :
            Math.min(numContacts, userOpts.maxVisibleContacts));
        var $popup = $('<div id="searchAddr_popup">' +
            '<select id="searchAddr_list" size="'+size+'"></select>' +
            '<div id="searchAddr_more">' +
                '<a href="#" title="Buscar também no catálogo geral do Expresso">mais resultados...</a>' +
            '</div>' +
            '</div>');
        $popup.css({
            left: ($txtbox.offset().left + 3)+'px',
            top: ($txtbox.offset().top + $txtbox.outerHeight() - 2)+'px',
            width: $txtbox.width()+'px'
        });
        $popup.appendTo(document.body);
        return $popup;
    }

    obj.close = function() {
        $('#searchAddr_popup').remove(); // if any
        return obj;
    };

    obj.processKey = function(key) {
        if (key === 0) {
            // dummy
        } else if (key === 27) { // ESC
            obj.close();
        } else if (key === 13) { // enter
            var opt = $('#searchAddr_list > option:selected');
            if (opt.length) {
                opt.trigger('mousedown');
                obj.close();
            }
        } else if (key === 38) { // up arrow
            var $listbox = $('#searchAddr_list');
            var $selopt = $listbox.children('option:selected');
            !$selopt.length ?
                $listbox.children('option:last').prop('selected', true) :
                $selopt.prev().prop('selected', true);
        } else if (key === 40) { // down arrow
            var $listbox = $('#searchAddr_list');
            var $selopt = $listbox.children('option:selected');
            !$selopt.length ?
                $listbox.children('option:first').prop('selected', true) :
                $selopt.next().prop('selected', true);
        }
    };

    obj.onClick = function(callback) {
        onClickCB = callback;
        return obj;
    };

    (function _Ctor() {
        obj.close(); // there can be only one
        token = $txtbox.val(); // string to be searched among contacts
        var lastComma = token.lastIndexOf(',');
        if (lastComma > 0) token = $.trim(token.substr(lastComma + 1));
        if (token.length >= 2) {
            var contacts = Contacts.searchByToken(token);
            _BuildPopup(contacts.length);
            _InsertNamesIntoList(contacts);
        }
    })();

    $('#searchAddr_list').on('mousedown', 'option', function(ev) {
        if (onClickCB !== null)
            onClickCB(token, $(this).data('contact')); // invoke user callback
    });

    $('#searchAddr_more > a').on('mousedown', function(ev) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
        $('#searchAddr_more > a').hide();
        $('#searchAddr_more').append($('#icons .throbber').serialize());

        $.post('../', { r:'searchContactsByToken', token:token })
        .always(function() { $('#searchAddr_more').hide(); })
        .fail(function(resp) {
            window.alert('Erro na pesquisa de contatos no catálogo do Expresso.\n' + resp.responseText);
        }).done(function(contacts) {
            if (contacts.length > 45) {
                _InsertMoreNamesIntoList([]);
                window.alert('Muitos contatos com "'+token+'" foram encontrados.\nUse um termo mais específico.');
            } else {
                _InsertMoreNamesIntoList(contacts);
            }
        });
    });
};
})( jQuery, Contacts );
