/*!
 * Expresso Lite
 * Widget to render the compose email fields. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, ThreadMail, DateFormat ) {
$.compose = function(options) {
    var userOpts = $.extend({
        folderCache: []
    }, options);

    var exp = { };

    var onCloseCB = null; // user callbacks
    var onSendCB  = null;
    var onDraftCB = null;
    var popup     = null; // $().modelessDialog() object, created on show()
    var attacher  = null; // $().attacher() widget, created on show()
    var autocomp  = null; // $().searchAddr() widget, created on keydown
    var msg       = { fwd:null, re:null, draft:null }; // we have a forwarded/replied/draft message
    var isSending = false; // a "send" async request is running

    function _DeleteOldDraftIfAny(draftMsgObj, onDone) {
        if(draftMsgObj !== null) { // are we editing an old draft?
            popup.setCaption('Atualizando rascunho... '+$('#icons .throbber').serialize());
            $.post('../', { r:'deleteMessages', messages:draftMsgObj.id, forever:1 })
            .fail(function(resp) {
                window.alert('Erro ao apagar o rascunho antigo.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function(status) {
                var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', userOpts.folderCache);
                --draftFolder.totalMails;
                if(onDone !== undefined) onDone();
            });
        } else {
            if(onDone !== undefined) onDone(); // do nothing and just invoke callback
        }
    }

    function _ResizeWriteField() {
        var cy = popup.getContentArea().cy;
        var cyUsed = 0;
        $('#composePanel').children(':visible:not(#composePanel_body)').each(function(idx, elem) {
            cyUsed += $(elem).outerHeight(true);
        });
        $('#composePanel_body').css('height', (cy - cyUsed - 30)+'px');
    }

    function _PrepareBodyToQuote(action, headline) {
        var out = '';
        if(action === 'draft') {
            return headline.body.message;
        } else if(action === 'reply') { // prepare mail content to be replied
            out = '<br/>Em '+DateFormat.Medium(headline.received)+', ' +
                headline.from.name+' escreveu:' +
                '<blockquote>'+headline.body.message+'<br/>' +
                (headline.body.quoted !== null ? headline.body.quoted : '') +
                '</blockquote>';
        } else if(action === 'forward') { // prepare mail content to be forwarded
            out = '<br/>-----Mensagem original-----<br/>' +
                '<b>Assunto:</b> '+headline.subject+'<br/>' +
                '<b>Remetente:</b> "'+headline.from.name+'" &lt;'+headline.from.email+'&gt;<br/>' +
                '<b>Para:</b> '+headline.to.join(', ')+'<br/>' +
                (headline.cc.length ? '<b>Cc:</b> '+headline.cc.join(', ')+'<br/>' : '') +
                '<b>Data:</b> '+DateFormat.Medium(headline.received)+'<br/><br/>' +
                headline.body.message+'<br/>' +
                (headline.body.quoted !== null ? headline.body.quoted : '');
        }
        return '<br/><br/>'+$('#mailSignature').val()+'<br/>'+out; // append user signature
    }

    function _UserWroteSomethingNew() {
        var origAction = 'new';
        var origMsg = null;

        if(msg.fwd !== null) {
            origAction = 'forward';
            origMsg = msg.fwd;
        } else if(msg.re !== null) {
            origAction = 'reply';
            origMsg = msg.re;
        } else if(msg.draft !== null) {
            origAction = 'draft';
            origMsg = msg.draft;
        }

        // Compare current message body with the one the user had at the
        // moment he opened the popup, to see if he changed the body.
        var origHtmlBody = _PrepareBodyToQuote(origAction, origMsg);
        return $('#composePanel_body').text() !== $(origHtmlBody).text();
    }

    function _ValidateAddresses(strAddrs) {
        var mails = strAddrs.split(/[\s,;]+/); // single string with all addresses into array
        for(var i = 0; i < mails.length; ++i)
            if(!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(mails[i]))
                return { status:false, address:mails[i] }; // invalid address is returned
        return { status:true };
    }

    function _JoinReplyAddresses(headline) {
        var ourMail = $('#mailAddress').val(); // our email address is stored into input/hidden at page load
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
        if(clonedTo.length && clonedCc.length) return clonedTo.join(', ') + ', ' + clonedCc.join(', ') + ', ';
        else if(clonedTo.length) return clonedTo.join(', ') + ', ';
        else if(clonedCc.length) return clonedCc.join(', ') + ', ';
        else return '';
    }

    function _BuildMessageObject() {
        var message = {
            subject: $.trim($('#composePanel_subject').val()),
            body: $('#composePanel_body').html(),
            to: $('#composePanel_to').val().replace(/^[,\s]+|[,\s]+$/g, ''), // trim spaces and commas
            cc: $('#composePanel_cc').val().replace(/^[,\s]+|[,\s]+$/g, ''),
            bcc: $('#composePanel_bcc').val().replace(/^[,\s]+|[,\s]+$/g, ''),
            isImportant: '0', // 0|1 means false|true
            replyToId: null,
            forwardFromId: null,
            origDraftId: null,
            attachs: ''
        };

        if($('#composePanel_important').is(':checked'))
            message.isImportant = '1';

        if(msg.re !== null) // is this message a reply to other one?
            message.replyToId = msg.re.id;
        else if(msg.fwd !== null) // is this message a forwarding of other one?
            message.forwardFromId = msg.fwd.id;

        if(msg.draft !== null) // are we editing an existing draft?
            message.origDraftId = msg.draft.id;

        var attachments = attacher.getAll();
        if(attachments.length)
            message.attachs = JSON.stringify(attachments); // attachments already uploaded to temp area

        return message;
    }

    function _ValidateSend(message, allowBlankDest) {
        if(message.subject == '') {
            window.alert('O email está sem assunto.');
            $('#composePanel_subject').focus();
            return false;
        } else if(allowBlankDest === undefined && message.to == '' && message.cc == '' && message.bcc == '') {
            window.alert('Não há destinatários para o email.');
            //~ $('#composePanel_to').focus();
            return false;
        }

        if(message.to != '') {
            var valid = _ValidateAddresses(message.to);
            if(!valid.status) {
                window.alert('O campo "para" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }
        if(message.cc != '') {
            var valid = _ValidateAddresses(message.cc);
            if(!valid.status) {
                window.alert('O campo "Cc" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }
        if(message.bcc != '') {
            var valid = _ValidateAddresses(message.bcc);
            if(!valid.status) {
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
        $('#composePanel_attacher').hide();
        if(onCloseCB !== null)
            onCloseCB(); // invoke user callback
    }

    function _SetupNewModelessDialog() {
        popup = $('#composePanel').modelessDialog({
            caption: 'Escrever email',
            width: 550,
            height: $(window).outerHeight() - 120,
            minWidth: 300,
            minHeight: 450
        });
        popup.onUserClose(function() { // when user clicked X button
            if(_UserWroteSomethingNew()) {
                if(window.confirm('Deseja descartar este email?'))
                    popup.close();
            } else {
                popup.close();
            }
        });
        popup.onClose(_PopupClosed); // when dialog is being dismissed
        popup.onResize(_ResizeWriteField);

        attacher = $('#composePanel_attacher').attacher();
        attacher.onContentChange(function() {
            $('#composePanel_attacher').toggle(attacher.getAll().length > 0);
            _ResizeWriteField();
        });
    }

    exp.show = function(showOptions) {
        var showOpts = $.extend({
            forward: null, // headline object; this email is a forwarding
            reply: null, // this email is a replying
            draft: null // this email is a draft editing
        }, showOptions);

        if(isSending) { // "send" asynchronous request is running right now
            window.alert('Um email está sendo enviado, aguarde.');
        } else if(popup !== null && popup.isOpen()) { // popup is already open with another message
            if(popup.isMinimized())
                popup.toggleMinimize();
            if(_UserWroteSomethingNew()) {
                if(window.confirm('Há um email sendo escrito que ainda não foi enviado.\n' +
                    'Deseja descartá-lo?')) {
                    popup.close();
                    CreateNewDialog();
                }
            } else { // close current message, since user wrote nothing new
                popup.close();
                CreateNewDialog();
            }
        } else { // a fresh, new window
            CreateNewDialog();
        }

        function CreateNewDialog() {
            _SetupNewModelessDialog();

            if(showOpts.forward === null && showOpts.reply === null && showOpts.draft === null) {
                $('#composePanel_to,#composePanel_cc,#composePanel_bcc').val('');
                $('#composePanel_body').html(_PrepareBodyToQuote('new', null));
                $('#composePanel_subject').val('').focus();
            } else if(showOpts.forward !== null) {
                msg.fwd = showOpts.forward; // keep forwarded headline
                $('#composePanel_subject').val('Fwd: '+msg.fwd.subject);
                $('#composePanel_to,#composePanel_cc,#composePanel_bcc').val('');
                $('#composePanel_body').html(_PrepareBodyToQuote('forward', msg.fwd)).focus();
                attacher.rebuildFromMsg(msg.fwd); // when forwarding, keep attachments
            } else if(showOpts.reply !== null) {
                msg.re = showOpts.reply; // keep replied headline
                $('#composePanel_subject').val('Re: '+msg.re.subject);
                $('#composePanel_to').val(msg.re.from.email + ', ');
                $('#composePanel_cc').val(_JoinReplyAddresses(msg.re).toLowerCase());
                $('#composePanel_toggs a').trigger('click'); // empty ones will be hidden soon, ahead
                $('#composePanel_body').html(_PrepareBodyToQuote('reply', msg.re)).focus();
            } else if(showOpts.draft !== null) {
                msg.draft = showOpts.draft; // keep draft headline
                $('#composePanel_subject').val(msg.draft.subject);
                $('#composePanel_to').val(msg.draft.to.join(', ').toLowerCase().toLowerCase());
                $('#composePanel_cc').val(msg.draft.cc.join(', ').toLowerCase());
                $('#composePanel_bcc').val(msg.draft.bcc.join(', ').toLowerCase());
                $('#composePanel_toggs a').trigger('click'); // empty ones will be hidden soon, ahead
                $('#composePanel_body').html(_PrepareBodyToQuote('draft', msg.draft)).focus();
                attacher.rebuildFromMsg(msg.draft); // keep attachments
            }

            $('#composePanel_to,#composePanel_cc,#composePanel_bcc').trigger('blur');
            _ResizeWriteField();
        }

        return exp;
    };

    exp.onClose = function(callback) {
        onCloseCB = callback; // onClose()
        return exp;
    };

    exp.onSend = function(callback) {
        onSendCB = callback;
        return exp;
    };

    exp.onDraft = function(callback) {
        onDraftCB = callback;
        return exp;
    };

    $('#composePanel_toggs a').on('click', function(ev) { // click To, Cc or Bcc link
        var $lnk = $(this);
        switch($lnk.text()) {
            case 'Para...': $('#composePanel_to').show().focus(); break;
            case 'Cc...'  : $('#composePanel_cc').show().focus(); break;
            case 'Bcc...' : $('#composePanel_bcc').show().focus();
        }
        $lnk.hide();
        if(!$('#composePanel_toggs a:visible').length)
            $('#composePanel_toggs').hide();
        _ResizeWriteField();
        return false;
    });

    $('#composePanel_to,#composePanel_cc,#composePanel_bcc').on('blur', function(ev) { // field loses focus
        var $txt = $(this); // textarea
        if($.trim($txt.val()) === '') { // when the field is empty and loses focus, hide it
            $('#composePanel_toggs').show(); // "show" links container
            $('#'+$txt.attr('id')+'Btn').show();
            $txt.hide();
            _ResizeWriteField();
        }

        window.setTimeout(function() { // allow click event to be triggered
            if(autocomp !== null) {
                autocomp.close();
                autocomp = null;
            }
        }, 50);
    });

    $('#composePanel_send').on('click', function() { // send email
        var message = _BuildMessageObject();
        if(_ValidateSend(message)) {
            isSending = true;
            popup.removeCloseButton()
                .setCaption('Enviando email... '+$('#icons .throbber').serialize())
                .toggleMinimize();
            $('#composePanel').hide()
                .after('<div class="loadingMessage"><br/><br/><br/>Enviando email...</div>');

            var reMsg = msg.re, // cache to send to callback, since they'll soon be nulled by a close()
                fwdMsg = msg.fwd,
                draftMsg = msg.draft;

            $.post('../', $.extend({ r:'saveMessage' }, message))
            .fail(function(resp) {
                window.alert('Erro ao enviar email.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                isSending = false;
                popup.close();
            }).done(function(status) {
                _DeleteOldDraftIfAny(draftMsg, function() {
                    if(reMsg !== null) reMsg.replied = true; // update cache
                    if(fwdMsg !== null) fwdMsg.forwarded = true;
                    isSending = false;
                    popup.close();
                    if(onSendCB !== null)
                        onSendCB(reMsg, fwdMsg, draftMsg); // invoke user callback
                });
            });
        }
    });

    $('#composePanel_draft').on('click', function() { // save as draft
        var message = _BuildMessageObject();
        if(_ValidateSend(message, 'allowBlankDest')) {
            isSending = true;
            popup.removeCloseButton()
                .setCaption('Salvando rascunho... '+$('#icons .throbber').serialize())
                .toggleMinimize();
            $('#composePanel').hide()
                .after('<div class="loadingMessage"><br/><br/><br/>Salvando rascunho...</div>');
            var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', userOpts.folderCache);

            $.post('../', $.extend({ r:'saveMessageDraft', draftFolderId:draftFolder.id }, message))
            .fail(function(resp) {
                window.alert('Erro ao salvar rascunho.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                isSending = false;
                popup.close();
            }).done(function(status) {
                _DeleteOldDraftIfAny(msg.draft, function() {
                    isSending = false;
                    popup.close();
                    if(onDraftCB !== null)
                        onDraftCB(); // invoke user callback
                });
            });
        }
    });

    $('#composePanel_attachNew').on('click', function() {
        attacher.newAttachment();
    });

    $('#composePanel_to,#composePanel_cc,#composePanel_bcc').on('keydown', function KeyHandle(ev) {
        if([0, 27, 13, 38, 40].indexOf(ev.which) !== -1) { // esc, enter, up, dn
            ev.stopImmediatePropagation();
            autocomp.processKey(ev.which);
        } else {
            if(KeyHandle.timer !== undefined && KeyHandle.timer !== null) {
                window.clearTimeout(KeyHandle.timer);
                KeyHandle.timer = null;
            }
            var $textarea = $(this);
            KeyHandle.timer = window.setTimeout(function() { // so that keydown process completes
                autocomp = $textarea.searchAddr();
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

    $('#composePanel_to,#composePanel_cc,#composePanel_bcc').on('keypress', function(ev) {
        if(ev.keyCode === 13) { // enter; new line disabled
            ev.stopImmediatePropagation();
            return false;
        }
    });

    return exp;
};
})( jQuery, ThreadMail, DateFormat );
