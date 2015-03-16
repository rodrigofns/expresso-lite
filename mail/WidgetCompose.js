/*!
 * Expresso Lite
 * Widget to render the compose email fields.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery', 'inc/App', 'inc/DateFormat', 'inc/ModelessDialog',
    'mail/ThreadMail', 'mail/WidgetAttacher', 'mail/WidgetSearchAddr'],
function($, App, DateFormat, ModelessDialog, ThreadMail, WidgetAttacher, WidgetSearchAddr) {
App.LoadCss('mail/WidgetCompose.css');
return function(options) {
    var userOpts = $.extend({
        address: 'user@domain', // user email address
        signature: '', // user footer email signature
        folderCache: [] // array with all email folders
    }, options);

    var THIS      = this;
    var onCloseCB = null; // user callbacks
    var onSendCB  = null;
    var onDraftCB = null;
    var $tpl      = null; // jQuery object with our HTML template
    var popup     = null; // ModelessDialog object, created on show()
    var attacher  = null; // WidgetAttacher object, created on show()
    var autocomp  = null; // WidgetSearchAddr object, created on keydown
    var msg       = { fwd:null, re:null, draft:null }; // we have a forwarded/replied/draft message
    var isSending = false; // a "send" async request is running

    function _DeleteOldDraftIfAny(draftMsgObj, onDone) {
        if (draftMsgObj !== null) { // are we editing an old draft?
            popup.setCaption( $(document.createElement('span'))
                .append('Atualizando rascunho... ')
                .append($('#Compose_template .Compose_throbber').clone()) );

            App.Post('deleteMessages', { messages:draftMsgObj.id, forever:1 })
            .fail(function(resp) {
                window.alert('Erro ao apagar o rascunho antigo.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function(status) {
                var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', userOpts.folderCache);
                --draftFolder.totalMails;
                if (onDone !== undefined) onDone();
            });
        } else {
            if (onDone !== undefined) onDone(); // do nothing and just invoke callback
        }
    }

    function _ResizeWriteField() {
        var cy = popup.getContentArea().cy;
        var cyUsed = 0;
        $tpl.children(':visible:not(.Compose_body)').each(function(idx, elem) {
            cyUsed += $(elem).outerHeight(true);
        });
        $tpl.find('.Compose_body').css('height', (cy - cyUsed - 30)+'px');
    }

    function _PrepareBodyToQuote(action, headline) {
        var out = '';
        if (action === 'draft') {
            return headline.body.message;
        } else if (action === 'reply') { // prepare mail content to be replied
            out = '<br/>Em '+DateFormat.Medium(headline.received)+', ' +
                headline.from.name+' escreveu:' +
                '<blockquote>'+headline.body.message+'<br/>' +
                (headline.body.quoted !== null ? headline.body.quoted : '') +
                '</blockquote>';
        } else if (action === 'forward') { // prepare mail content to be forwarded
            out = '<br/>-----Mensagem original-----<br/>' +
                '<b>Assunto:</b> '+headline.subject+'<br/>' +
                '<b>Remetente:</b> "'+headline.from.name+'" &lt;'+headline.from.email+'&gt;<br/>' +
                '<b>Para:</b> '+headline.to.join(', ')+'<br/>' +
                (headline.cc.length ? '<b>Cc:</b> '+headline.cc.join(', ')+'<br/>' : '') +
                '<b>Data:</b> '+DateFormat.Medium(headline.received)+'<br/><br/>' +
                headline.body.message+'<br/>' +
                (headline.body.quoted !== null ? headline.body.quoted : '');
        }
        return '<br/><br/>'+userOpts.signature+'<br/>'+out; // append user signature
    }

    function _UserWroteSomethingNew() {
        function removeSpacesAndTrimCommas(txt) {
            return txt.replace(/\s/g, '')
                .replace(/^[,\s]+|[,\s]+$/g, '');
        }
        var curAddr = {
            to: removeSpacesAndTrimCommas($tpl.find('.Compose_to').val()),
            cc: removeSpacesAndTrimCommas($tpl.find('.Compose_cc').val()),
            bcc: removeSpacesAndTrimCommas($tpl.find('.Compose_bcc').val())
        };
        var changedDraftAddr = (msg.draft !== null && (
            curAddr.to !== removeSpacesAndTrimCommas(msg.draft.to.join(',')) ||
            curAddr.cc !== removeSpacesAndTrimCommas(msg.draft.cc.join(',')) ||
            curAddr.bcc !== removeSpacesAndTrimCommas(msg.draft.bcc.join(',')) ));
        if (changedDraftAddr) {
            return true;
        }

        var origAction = 'new';
        var origMsg = null;

        if (msg.fwd !== null) {
            origAction = 'forward';
            origMsg = msg.fwd;
        } else if (msg.re !== null) {
            origAction = 'reply';
            origMsg = msg.re;
        } else if (msg.draft !== null) {
            origAction = 'draft';
            origMsg = msg.draft;
        } else { // new message being written from scratch
            var subj = $.trim($tpl.find('.Compose_subject').val());
            if (subj !== '' || curAddr.to !== '' || curAddr.cc !== '' || curAddr.bcc !== '') {
                return true;
            }
        }

        // Compare current message body with the one the user had at the
        // moment he opened the popup, to see if he changed the body.
        var origHtmlBody = _PrepareBodyToQuote(origAction, origMsg);
        return $tpl.find('.Compose_body').text() !== $(origHtmlBody).text();
    }

    function _ValidateAddresses(strAddrs) {
        var mails = strAddrs.split(/[\s,;]+/); // single string with all addresses into array
        for (var i = 0; i < mails.length; ++i) {
            if (!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(mails[i])) {
                return { status:false, address:mails[i] }; // invalid address is returned
            }
        }
        return { status:true };
    }

    function _JoinReplyAddresses(headline) {
        var ourMail = userOpts.address;
        var clonedTo = $.grep(headline.to, function(elem, i) {
                return elem !== ourMail &&
                    elem !== headline.from.email &&
                    _ValidateAddresses(elem).status;
            }),
            clonedCc = $.grep(headline.cc, function(elem, i) {
                return elem !== ourMail &&
                    elem !== headline.from.email &&
                    _ValidateAddresses(elem).status;
            });
        if (clonedTo.length && clonedCc.length) {
            return clonedTo.join(', ')+', '+clonedCc.join(', ')+', ';
        } else if (clonedTo.length) {
            return clonedTo.join(', ')+', ';
        } else if (clonedCc.length) {
            return clonedCc.join(', ')+', ';
        } else {
            return '';
        }
    }

    function _BuildMessageObject() {
        var message = {
            subject: $.trim($tpl.find('.Compose_subject').val()),
            body: $tpl.find('.Compose_body').html(),
            to: $tpl.find('.Compose_to').val().replace(/^[,\s]+|[,\s]+$/g, ''), // trim spaces and commas
            cc: $tpl.find('.Compose_cc').val().replace(/^[,\s]+|[,\s]+$/g, ''),
            bcc: $tpl.find('.Compose_bcc').val().replace(/^[,\s]+|[,\s]+$/g, ''),
            isImportant: '0', // 0|1 means false|true
            replyToId: null,
            forwardFromId: null,
            origDraftId: null,
            attachs: ''
        };

        if ($tpl.find('.Compose_important').is(':checked')) {
            message.isImportant = '1';
        }

        if (msg.re !== null) { // is this message a reply to other one?
            message.replyToId = msg.re.id;
        } else if (msg.fwd !== null) { // is this message a forwarding of other one?
            message.forwardFromId = msg.fwd.id;
        }

        if (msg.draft !== null) { // are we editing an existing draft?
            message.origDraftId = msg.draft.id;
        }

        var attachments = attacher.getAll();
        if (attachments.length) {
            message.attachs = JSON.stringify(attachments); // attachments already uploaded to temp area
        }

        return message;
    }

    function _ValidateSend(message, allowBlankDest) {
        if (message.subject == '') {
            window.alert('O email está sem assunto.');
            $tpl.find('.Compose_subject').focus();
            return false;
        } else if (allowBlankDest === undefined && message.to == '' && message.cc == '' && message.bcc == '') {
            window.alert('Não há destinatários para o email.');
            //~ $tpl.find('.Compose_to').focus();
            return false;
        }

        if (message.to != '') {
            var valid = _ValidateAddresses(message.to);
            if (!valid.status) {
                window.alert('O campo "para" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }
        if (message.cc != '') {
            var valid = _ValidateAddresses(message.cc);
            if (!valid.status) {
                window.alert('O campo "Cc" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }
        if (message.bcc != '') {
            var valid = _ValidateAddresses(message.bcc);
            if (!valid.status) {
                window.alert('O campo "Bcc" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }

        return true;
    }

    function _PopupClosed() {
        msg.fwd = null; // cleanup
        msg.re = null;
        msg.draft = null;
        popup = null;
        attacher.removeAll();
        attacher = null;
        $tpl = null; // discard the cloned HTML template
        if (onCloseCB !== null) {
            onCloseCB(); // invoke user callback
        }
    }

    function _FillNewFields(showOpts) {
        if (showOpts.forward === null && showOpts.reply === null && showOpts.draft === null) {
            $tpl.find('.Compose_body').html(_PrepareBodyToQuote('new', null));
            //$tpl.find('.Compose_subject').focus();
        } else if (showOpts.forward !== null) {
            msg.fwd = showOpts.forward; // keep forwarded headline
            $tpl.find('.Compose_subject').val('Fwd: '+msg.fwd.subject);
            //$tpl.find('.Compose_body').html(_PrepareBodyToQuote('forward', msg.fwd)).focus();
            attacher.rebuildFromMsg(msg.fwd); // when forwarding, keep attachments
        } else if (showOpts.reply !== null) {
            msg.re = showOpts.reply; // keep replied headline
            $tpl.find('.Compose_subject').val('Re: '+msg.re.subject);
            $tpl.find('.Compose_to').val(msg.re.from.email + ', ');
            $tpl.find('.Compose_cc').val(_JoinReplyAddresses(msg.re).toLowerCase());
            $tpl.find('.Compose_toggs a').trigger('click'); // empty ones will be hidden soon, ahead
            //$tpl.find('.Compose_body').html(_PrepareBodyToQuote('reply', msg.re)).focus();
        } else if (showOpts.draft !== null) {
            msg.draft = showOpts.draft; // keep draft headline
            $tpl.find('.Compose_subject').val(msg.draft.subject);
            $tpl.find('.Compose_to').val(msg.draft.to.join(', ').toLowerCase().toLowerCase());
            $tpl.find('.Compose_cc').val(msg.draft.cc.join(', ').toLowerCase());
            $tpl.find('.Compose_bcc').val(msg.draft.bcc.join(', ').toLowerCase());
            $tpl.find('.Compose_toggs a').trigger('click'); // empty ones will be hidden soon, ahead
            //$tpl.find('.Compose_body').html(_PrepareBodyToQuote('draft', msg.draft)).focus();
            attacher.rebuildFromMsg(msg.draft); // keep attachments
        }
        $tpl.find('.Compose_to,.Compose_cc,.Compose_bcc').trigger('blur');
        _ResizeWriteField();
    }

    function _SetEvents() {
        $tpl.find('.Compose_toggs a').on('click', function(ev) { // click To, Cc or Bcc link
            var $lnk = $(this);
            switch ($lnk.text()) {
                case 'Para...': $tpl.find('.Compose_to').show().focus(); break;
                case 'Cc...'  : $tpl.find('.Compose_cc').show().focus(); break;
                case 'Bcc...' : $tpl.find('.Compose_bcc').show().focus();
            }
            $lnk.hide();
            if (!$tpl.find('.Compose_toggs a:visible').length) {
                $tpl.find('.Compose_toggs').hide();
            }
            _ResizeWriteField();
            return false;
        });

        $tpl.find('.Compose_to,.Compose_cc,.Compose_bcc').on('blur', function(ev) { // field loses focus
            var $txt = $(this); // textarea
            if ($.trim($txt.val()) === '') { // when the field is empty and loses focus, hide it
                $tpl.find('.Compose_toggs').show(); // "show" links container
                $tpl.find('.'+$txt.attr('class')+'Btn').show();
                $txt.hide();
                _ResizeWriteField();
            }

            window.setTimeout(function() { // allow click event to be triggered
                if (autocomp !== null) {
                    autocomp.close();
                    autocomp = null;
                }
            }, 50);
        });

        $tpl.find('.Compose_send').on('click', function() { // send email
            $(this).blur();
            var message = _BuildMessageObject();
            if (_ValidateSend(message)) {
                isSending = true;
                popup.removeCloseButton();
                popup.setCaption( $(document.createElement('span'))
                    .append('Enviando email... ')
                    .append($('#Compose_template .Compose_throbber').clone()) );
                popup.toggleMinimize();

                var reMsg = msg.re, // cache to send to callback, since they'll soon be nulled by a close()
                    fwdMsg = msg.fwd,
                    draftMsg = msg.draft;

                App.Post('saveMessage', message)
                .fail(function(resp) {
                    window.alert('Erro ao enviar email.\n' +
                        'Sua interface está inconsistente, pressione F5.\n'+resp.responseText);
                    isSending = false;
                    popup.close();
                }).done(function(status) {
                    _DeleteOldDraftIfAny(draftMsg, function() {
                        if (reMsg !== null) reMsg.replied = true; // update cache
                        if (fwdMsg !== null) fwdMsg.forwarded = true;
                        isSending = false;
                        popup.close();
                        if (onSendCB !== null) {
                            onSendCB(reMsg, fwdMsg, draftMsg); // invoke user callback
                        }
                    });
                });
            }
        });

        $tpl.find('.Compose_draft').on('click', function() { // save as draft
            $(this).blur();
            var message = _BuildMessageObject();
            if (_ValidateSend(message, 'allowBlankDest')) {
                isSending = true;
                popup.removeCloseButton();
                popup.setCaption( $(document.createElement('span'))
                    .append('Salvando rascunho... ')
                    .append($('#Compose_template .Compose_throbber').clone()) );
                popup.toggleMinimize();
                var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', userOpts.folderCache);

                App.Post('saveMessageDraft', $.extend({ draftFolderId:draftFolder.id }, message))
                .fail(function(resp) {
                    window.alert('Erro ao salvar rascunho.\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                    isSending = false;
                    popup.close();
                }).done(function(status) {
                    _DeleteOldDraftIfAny(msg.draft, function() {
                        isSending = false;
                        popup.close();
                        if (onDraftCB !== null) {
                            onDraftCB(); // invoke user callback
                        }
                    });
                });
            }
        });

        $tpl.find('.Compose_attachNew').on('click', function() {
            $(this).blur();
            attacher.newAttachment();
        });

        $tpl.find('.Compose_to,.Compose_cc,.Compose_bcc').on('keydown', function KeyHandle(ev) {
            if ([0, 27, 13, 38, 40].indexOf(ev.which) !== -1) { // esc, enter, up, dn
                ev.stopImmediatePropagation();
                autocomp.processKey(ev.which);
            } else {
                if (KeyHandle.timer !== undefined && KeyHandle.timer !== null) {
                    window.clearTimeout(KeyHandle.timer);
                    KeyHandle.timer = null;
                }
                var $textarea = $(this);
                KeyHandle.timer = window.setTimeout(function() { // so that keydown process completes
                    autocomp = new WidgetSearchAddr({ $elem:$textarea });
                    autocomp.onClick(function(token, ct) {
                        var newtxt = $textarea.val();
                        newtxt = newtxt.substr(0, newtxt.length - token.length) + ct.emails.join(', ');
                        $textarea.val(newtxt+', ');
                        $textarea[0].setSelectionRange(newtxt.length + 2, newtxt.length + 2);
                        window.setTimeout(function() { $textarea.focus(); }, 10);
                    });
                }, 150);
            }
        });

        $tpl.find('.Compose_to,.Compose_cc,.Compose_bcc').on('keypress', function(ev) {
            if (ev.keyCode === 13) { // enter; new line disabled
                ev.stopImmediatePropagation();
                return false;
            }
        });
    }

    function _CreateNewDialog(showOpts) {
        var defer = $.Deferred();
        $tpl = $('#Compose_template .Compose_panel').clone(); // create new HTML template object
        _SetEvents();

        popup = new ModelessDialog({ // create new modeless dialog object
            $elem: $tpl,
            caption: 'Escrever email',
            width: 550,
            height: $(window).outerHeight() - 120,
            minWidth: 300,
            minHeight: 450
        });
        popup.onUserClose(function() { // when user clicked X button
            if (_UserWroteSomethingNew()) {
                var question = (msg.draft === null) ?
                    'Deseja descartar este email?' :
                    'Deseja descartar as modificações?';
                if (window.confirm(question)) {
                    popup.close();
                }
            } else {
                popup.close();
            }
        });
        popup.onClose(_PopupClosed); // when dialog is being dismissed
        popup.onResize(_ResizeWriteField);
        popup.show().done(function() {
            if (showOpts.forward === null && showOpts.reply === null && showOpts.draft === null) {
                $tpl.find('.Compose_subject').focus();
            } else if (showOpts.forward !== null) {
                $tpl.find('.Compose_body').html(_PrepareBodyToQuote('forward', msg.fwd)).focus();
            } else if (showOpts.reply !== null) {
                $tpl.find('.Compose_body').html(_PrepareBodyToQuote('reply', msg.re)).focus();
            } else if (showOpts.draft !== null) {
                $tpl.find('.Compose_body').html(_PrepareBodyToQuote('draft', msg.draft)).focus();
            }
            defer.resolve();
        });

        attacher = new WidgetAttacher({ $elem:$tpl.find('.Compose_attacher') });
        attacher.onContentChange(function() {
            $tpl.find('.Compose_attacher').toggle(attacher.getAll().length > 0);
            _ResizeWriteField();
        });

        _FillNewFields(showOpts);
        return defer.promise();
    }

    THIS.load = function() {
        var defer = $.Deferred();
        ( $('#Compose_template').length ? // load once
            $.Deferred().done() :
            App.LoadTemplate('WidgetCompose.html')
        ).done(function() {
            $.when(
                ModelessDialog.Load(),
                WidgetSearchAddr.Load(),
                WidgetAttacher.Load()
            ).done(function() {
                defer.resolve();
            });
        });
        return defer.promise();
    };

    THIS.show = function(showOptions) {
        var showOpts = $.extend({
            forward: null, // headline object; this email is a forwarding
            reply: null, // this email is a replying
            draft: null // this email is a draft editing
        }, showOptions);

        if (isSending) { // "send" asynchronous request is running right now
            window.alert('Um email está sendo enviado, aguarde.');
        } else if (popup !== null && popup.isOpen()) { // popup is already open with another message
            if (popup.isMinimized()) {
                popup.toggleMinimize(); // if there's a popup active, just restore it
            }
            if (_UserWroteSomethingNew()) {
                if (window.confirm('Há um email sendo escrito que ainda não foi enviado.\n' +
                    'Deseja descartá-lo?')) {
                    popup.close().done(function() {
                        _CreateNewDialog(showOpts);
                    });
                }
            } else { // close current message, since user wrote nothing new
                popup.close().done(function() {
                    _CreateNewDialog(showOpts);
                });
            }
        } else { // a fresh, new window
            _CreateNewDialog(showOpts);
        }

        return THIS;
    };

    THIS.onClose = function(callback) {
        onCloseCB = callback; // onClose()
        return THIS;
    };

    THIS.onSend = function(callback) {
        onSendCB = callback;
        return THIS;
    };

    THIS.onDraft = function(callback) {
        onDraftCB = callback;
        return THIS;
    };
};
});
