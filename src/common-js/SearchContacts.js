/*!
 * Expresso Lite
 * Search contacts popup widget.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/Contacts',
    'common-js/Dialog'
],
function($, App, Contacts, Dialog) {
App.LoadCss('common-js/SearchContacts.css');
var SearchContacts = function(options) {
    var userOpts = $.extend({
        caption: 'Pesquisar contato',
        text: ''
    }, options);

    var THIS          = this;
    var $tpl          = null; // jQuery object for HTML template
    var popup         = null; // Dialog object
    var $tplResult    = null; // template for each displayed contact
    var isCacheSearch = true; // current search is within cached contacts?
    var onCloseCB     = null;

    function _GetLastToken($textArea) {
        var token = $textArea.val();
        var lastComma = token.lastIndexOf(',');
        if (lastComma > 0) {
            token = $.trim(token.substr(lastComma + 1));
        }
        return token; // text to be searched
    }

    function _FormatResults(contacts) {
        var elems = [];
        for (var i = 0; i < contacts.length; ++i) {
            var $elem = $tplResult.clone();
            $elem.find('.SearchContacts_name').text(contacts[i].name);
            if (contacts[i].org !== undefined) {
                $elem.find('.SearchContacts_orgUnit').text(contacts[i].org+', ');
            }
            $elem.find('.SearchContacts_mail').text(contacts[i].emails.join(', '));
            $elem.data('contact', contacts[i]);
            elems.push($elem);
        }
        return elems;
    }

    function _FormatMoreResults(newContacts) {
        if (!newContacts.length) return;
        var people = [];
        var duplicates = 0;
        $tpl.find('.SearchContacts_oneResult').each(function(idx, opt) {
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
            if (alreadyExists) {
                ++duplicates;
            } else {
                people.push({ // append new one
                    name: newContacts[c].name,
                    emails: [newContacts[c].email],
                    org: newContacts[c].org
                });
            }
        }
        people.sort(function(a, b) { return a.name.localeCompare(b.name); });
        return {
            entries: _FormatResults(people),
            duplicates: duplicates
        };
    }

    function _FocusTextField($field) {
        var text = $field.val();
        $field.focus();
        $field[0].setSelectionRange(text.length, text.length);
        return $field;
    }

    function _SetEvents() {
        popup.onUserClose(function() { // user clicked X
            popup.close();
        });

        popup.onClose(function() { // after popup is closed
            var emails = $tpl.find('.SearchContacts_field').val().split(/[ ,]+/);
            if (emails[emails.length-1] === '') {
                emails.pop();
            }
            if (onCloseCB !== null) {
                onCloseCB(emails);
            }
            $tpl = null;
        });

        $tpl.find('.SearchContacts_field').on('keypress', function(ev) {
            if (ev.keyCode === 13) { // enter; new line disabled
                ev.stopImmediatePropagation();
                return false;
            }
        });

        $tpl.find('.SearchContacts_field').on('keydown', function(ev) {
            if ([0, 27, 13, 38, 40].indexOf(ev.which) !== -1) { // esc, enter, up, dn
                ev.stopImmediatePropagation();
                var $resArea = $tpl.find('.SearchContacts_results');
                if (ev.which === 27) { // esc
                    retStatus = 'cancel';
                    popup.close();
                } else if (ev.which === 13) { // enter
                    var $curSel = $resArea.find('.SearchContacts_oneResultSel');
                    if ($curSel.length) {
                        $curSel.trigger('click');
                    } else {
                        popup.close();
                    }
                } else if (ev.which === 40 || ev.which === 38 && $tpl.find('.SearchContacts_oneResult').length) {
                    var $curSel = $resArea.find('.SearchContacts_oneResultSel');
                    $curSel.removeClass('SearchContacts_oneResultSel'); // if any
                    if (ev.which === 40) { // down arrow
                        $curSel = ($curSel.length && !$curSel.is(':last-child')) ?
                            $curSel.next() :
                            $resArea.find('.SearchContacts_oneResult:first');
                        var yBottom = $curSel.offset().top + $curSel.outerHeight() - $resArea.offset().top;
                        if (yBottom > $resArea.outerHeight() || $curSel.is(':first-child')) {
                            $resArea.scrollTop($curSel.offset().top
                                - $resArea.offset().top
                                + $resArea.scrollTop()
                                - $resArea.outerHeight() * .3);
                        }
                    } else if (ev.which === 38) { // up arrow
                        $curSel = ($curSel.length && !$curSel.is(':first-child')) ?
                            $curSel.prev() :
                            $resArea.find('.SearchContacts_oneResult:last');
                        var selTop = $curSel.offset().top - $resArea.offset().top;
                        if (selTop < 0 || $curSel.is(':last-child')) {
                            $resArea.scrollTop(selTop
                                + $resArea.scrollTop()
                                - $resArea.outerHeight() * .4);
                        }
                    }
                    $curSel.addClass('SearchContacts_oneResultSel');
                }
                return false;
            }
        });

        $tpl.find('.SearchContacts_field').on('keyup', function(ev) {
            if ([0, 27, 13, 38, 40].indexOf(ev.which) !== -1) { // esc, enter, up, dn
                ev.stopImmediatePropagation();
            } else {
                var token = _GetLastToken($(this));
                var $outp = $tpl.find('.SearchContacts_results');
                var $btn = $tpl.find('.SearchContacts_searchBeyond');
                $outp.empty();
                if (token.length >= 2) {
                    var contacts = Contacts.searchByToken(token);
                    $outp.append(_FormatResults(contacts)).scrollTop(0);
                    $btn.show();
                } else {
                    $btn.hide();
                }
                isCacheSearch = true;
                $btn.val('Mais resultados...');
            }
        });

        $tpl.find('.SearchContacts_results').on('click', '.SearchContacts_oneResult', function() {
            var $txtArea = $tpl.find('.SearchContacts_field');
            var curTxt = $txtArea.val();
            var lastComma = curTxt.lastIndexOf(',');
            curTxt = (lastComma > 0) ? $.trim(curTxt.substr(0, lastComma))+', ' : '';
            curTxt += $(this).data('contact').emails.join(', ')+', ';
            $tpl.find('.SearchContacts_results').empty();
            $txtArea.val(curTxt);
            _FocusTextField($txtArea);
            $tpl.find('.SearchContacts_searchBeyond').val('Mais resultados...').hide();
        });

        $tpl.find('.SearchContacts_searchBeyond').on('click', function() {
            var $btn = $(this);
            var $field = $tpl.find('.SearchContacts_field');
            var token = _GetLastToken($field);
            if (token.length >= 2) {
                var $outp = $tpl.find('.SearchContacts_results');
                var start = 0;
                $field.prop('disabled', true);
                $btn.hide();
                $tpl.find('.SearchContacts_throbber').show();

                if (!isCacheSearch) { // subsequent "load more" contacts, requesting more pages
                    start = $outp.find('.SearchContacts_oneResult').length;
                } else { // first "load more" request
                    $outp.empty();
                }

                App.Post('searchContactsByToken', { token:token, start:start })
                .always(function() {
                    $field.prop('disabled', false);
                    $btn.show();
                    $tpl.find('.SearchContacts_throbber').hide();
                    _FocusTextField($field);
                }).fail(function(resp) {
                    window.alert('Erro na pesquisa de contatos no catálogo do Expresso.\n'+resp.responseText);
                }).done(function(resp) {
                    var entries = _FormatMoreResults(resp.contacts);
                    $outp.empty().append(entries.entries);
                    var numLoaded = $outp.find('.SearchContacts_oneResult').length + entries.duplicates;
                    $btn.val('Carregar +'+Math.min(50, resp.totalCount - numLoaded));
                    if (numLoaded >= resp.totalCount) {
                        $btn.hide(); // no more pages left
                    }
                });
            } else {
                window.alert('Não há caracteres suficientes para efetuar a busca.');
                _FocusTextField($field);
            }
            isCacheSearch = false;
        });
    }

    (function _Ctor() {
        $tpl = $('#SearchContacts_template .SearchContacts_box').clone();
        popup = new Dialog({ // create new modal dialog popup
            $elem: $tpl,
            width: 420,
            height: $(window).outerHeight() - 300,
            minHeight: 400,
            caption: userOpts.caption,
            minimizable: false,
            modal: true
        });
        _SetEvents();
        var $field = $tpl.find('.SearchContacts_field');
        if (userOpts.text.length) {
            $field.text(userOpts.text+', ');
        }
        popup.show().done(function() {
            _FocusTextField($field);
        });
        $tplResult = $('#SearchContacts_template .SearchContacts_oneResult').clone(); // cache
    })();

    THIS.onClose = function(callback) {
        onCloseCB = callback; // onClose(emails)
        return THIS;
    };
};

SearchContacts.Load = function() {
    // Static method, since this class can be instantied ad-hoc.
    return $('#SearchContacts_template').length ?
        $.Deferred().resolve().promise() :
        App.LoadTemplate('../common-js/SearchContacts.html');
};

return SearchContacts;
});
