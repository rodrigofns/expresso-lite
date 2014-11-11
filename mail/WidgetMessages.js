/*!
 * Expresso Lite
 * Widget to render the message bodies.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, Contacts, DateFormat, ThreadMail, DropdownMenu ) {
window.WidgetMessages = function(options) {
    var userOpts = $.extend({
        elem: '', // jQuery selector for the target DIV
        folderCache: [],
        wndCompose: null, // WidgetCompose object
        genericMugshot: '../img/person-generic.gif'
    }, options);

    var obj          = this;
    var $targetDiv   = $(userOpts.elem);
    var curFolder    = null; // folder object currently loaded
    var menu         = null; // context menu object
    var onViewCB     = null; // user callbacks
    var onMarkReadCB = null;
    var onMoveCB     = null;

    function _FormatAttachments(attachs) {
        var ret = '';
        if(attachs !== undefined && attachs.length) {
            ret = '<b>'+(attachs.length === 1 ? 'Anexo' : attachs.length+' anexos')+'</b>: ';
            for(var i = 0; i < attachs.length; ++i) {
                ret += '<span style="white-space:nowrap;"><a href="#">'+attachs[i].filename+'</a> ' +
                    '('+ThreadMail.FormatBytes(attachs[i].size)+')</span>, ';
            }
            ret = ret.substr(0, ret.length - 2); // remove last comma
        }
        return ret;
    }

    function _FormatManyAddresses(addrs) {
        if(!addrs.length) return '<i>(ninguém)</i>';
        var ret = '';
        for(var i = 0; i < addrs.length; ++i) {
            var $span = $('#templates .messages_addrPerson').clone();
            var ad = addrs[i].toLowerCase();
            ret += $span.find('.messages_addrName').text( ad.substr(0, ad.indexOf('@')) ).serialize() +
                $span.find('.messages_addrDomain').text( ad.substr(ad.indexOf('@')) ).serialize() + ', ';
        }
        return ret.substr(0, ret.length - 2);
    }

    function _BuildDropdownMenu($divUnit) {
        var menu = new DropdownMenu({ btn:$divUnit.find('.messages_dropdown') });
        menu.addOption('Marcar como não lida', function() { _MarkRead($divUnit, false); })
            .addOption('Responder', function() { _NewMail($divUnit, 're'); })
            .addOption('Encaminhar', function() { _NewMail($divUnit, 'fwd'); })
            .addOption('Apagar', function() { _DeleteMessage($divUnit); })
            .addHeader('Mover para...');

        var MenuRenderFolderLevel = function(folders, level) {
            $.each(folders, function(idx, folder) {
                if(folder.globalName !== curFolder.globalName) { // avoid move to current folder
                    menu.addOption(folder.localName, function() {
                        _MoveMessage($divUnit, folder); // pass folder object
                    }, level); // indentation
                }
                MenuRenderFolderLevel(folder.subfolders, level + 1);
            });
        };
        MenuRenderFolderLevel(userOpts.folderCache, 0);
    }

    function _BuildIcons(headline) {
        return (headline.replied       ? $('#icons .icoReplied').serialize()   : '')+' ' +
            (headline.wantConfirm   ? $('#icons .icoConfirm').serialize()   : '')+' ' +
            (headline.important     ? $('#icons .icoImportant').serialize() : '')+' ' +
            (headline.signed        ? $('#icons .icoSigned').serialize()    : '')+' ' +
            (headline.hasAttachment ? $('#icons .icoAttach').serialize()    : '')+' ' +
            (headline.forwarded     ? $('#icons .icoForwarded').serialize() : '')+' ';
    }

    function _BuildDivMail(headline) {
        var mugshot = Contacts.getMugshotSrc(headline.from.email);
        if(mugshot === '')
            mugshot = userOpts.genericMugshot;

        var unreadClass = headline.unread ? 'messages_unread' : 'messages_read';

        var $div = $('#templates .messages_unit').clone();
        $div.find('.messages_top1').addClass(unreadClass);
        $div.find('.messages_mugshot > img').attr('src', mugshot);
        $div.find('.messages_fromName').text(headline.from.name);
        $div.find('.messages_fromMail').text('('+headline.from.email+')');
        $div.find('.messages_icons').html(_BuildIcons(headline));
        $div.find('.messages_when').text(DateFormat.Long(headline.received));
        $div.find('.messages_top2').addClass(unreadClass);
        $div.find('.messages_addrTo').html(_FormatManyAddresses(headline.to));
        headline.cc.length ?
            $div.find('.messages_addrCc').html(_FormatManyAddresses(headline.cc)) :
            $div.find('.messages_addrCc').parent().remove();
        headline.bcc.length ?
            $div.find('.messages_addrBcc').html(_FormatManyAddresses(headline.bcc)) :
            $div.find('.messages_addrBcc').parent().remove();

        _BuildDropdownMenu($div);

        $div.data('headline', headline); // keep object
        return $div;
    }

    function _NewMail($div, action) {
        if(!$div.hasClass('messages_unit'))
            $div = $div.closest('.messages_unit');
        if(!$div.children('.messages_content').is(':visible')) {
            window.alert('Abra a mensagem antes de '+(action==='fwd'?'encaminhá':'respondê')+'-la.');
        } else {
            var opts = { curFolder:curFolder };

            if(action === 'fwd') opts.forward = $div.data('headline');
            else if(action === 're') opts.reply = $div.data('headline');

            userOpts.wndCompose.show(opts);
        }
    }

    function _MarkRead($elem, asRead) {
        if(!$elem.hasClass('messages_unit'))
            $elem = $elem.closest('.messages_unit');
        var headline = $elem.data('headline');
        if( (asRead && !headline.unread) || (!asRead && headline.unread) ) {
            window.alert('Mensagem já marcada como '+(asRead?'':'não')+' lida.');
        } else {
            $elem.find('.messages_from:first').append(
                '<span class="messages_throbber">&nbsp; '+$('#icons .throbber').serialize()+'</span>' );
            if(!asRead && $elem.find('.messages_content').is(':visible'))
                    $elem.children('.messages_top1').trigger('click'); // collapse if expanded

            $.post('../', { r:'markAsRead', asRead:(asRead?1:0), ids:headline.id })
            .always(function() { $elem.find('.messages_throbber').remove(); })
            .fail(function(resp) {
                window.alert('Erro ao alterar o flag de leitura das mensagens.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function() {
                headline.unread = !headline.unread; // update cache
                asRead ? --curFolder.unreadMails : ++curFolder.unreadMails;
                $elem.children('.messages_top1,.messages_top2')
                    .toggleClass('messages_read', asRead).toggleClass('.messages_unread', !asRead);
                if(onMarkReadCB !== null)
                    onMarkReadCB(curFolder, headline); // invoke user callback
            });
        }
    }

    function _MoveMessage($elem, destFolder) {
        if(!$elem.hasClass('messages_unit'))
            $elem = $elem.closest('.messages_unit');
        if($elem.find('.throbber').length) // already working?
            return;
        var headline = $elem.data('headline');

        function ProceedMoving() {
            $elem.find('.messages_fromName').hide();
            $elem.find('.messages_fromMail').html(' &nbsp; <i>Movendo para '+destFolder.localName+'...</i>');
            $elem.find('.messages_from').append($('#icons .throbber').serialize());
            $elem.children('.messages_top2,.messages_attachs,.messages_content').remove(); // won't expand anymore

            $.post('../', { r:'moveMessages', messages:headline.id, folder:destFolder.id })
            .always(function() { $elem.find('.throbber').remove(); })
            .fail(function(resp) {
                window.alert('Erro ao mover mensagem.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function() {
                $elem.slideUp(200, function() {
                    $elem.remove();
                    var origThread = ThreadMail.FindThread(curFolder.threads, headline);
                    --curFolder.totalMails; // update cache
                    ++destFolder.totalMails;
                    if(headline.unread) {
                        --curFolder.unreadMails;
                        ++destFolder.unreadMails;
                    }
                    ThreadMail.RemoveHeadlinesFromFolder([ headline.id ], curFolder);
                    destFolder.messages.length = 0; // force cache rebuild
                    destFolder.threads.length = 0;
                    if(onMoveCB !== null)
                        onMoveCB(destFolder, origThread);
                });
            });
        }

        $elem.find('.messages_top2').is(':visible') ?
            $elem.find('.messages_top1').trigger('click', ProceedMoving) : // if expanded, collapse
            ProceedMoving();
    }

    function _DeleteMessage($elem) {
        if(!$elem.hasClass('messages_unit'))
            $elem = $elem.closest('.messages_unit');
        if($elem.find('.throbber').length) // already working?
            return;
        var headline = $elem.data('headline');

        if(curFolder.globalName !== 'INBOX/Trash') { // just move to trash folder
            _MoveMessage($elem, ThreadMail.FindFolderByGlobalName('INBOX/Trash', userOpts.folderCache));
        } else if(window.confirm('Deseja apagar esta mensagem?')) { // we're in trash folder, add deleted flag
            function ProceedDeleting() {
                $elem.find('.messages_fromName').hide();
                $elem.find('.messages_fromMail').html(' &nbsp; <i>Excluindo...</i>');
                $elem.find('.messages_from').append($('#icons .throbber').serialize());
                $elem.children('.messages_top2,.messages_attachs,.messages_content').remove(); // won't expand anymore

                $.post('../', { r:'deleteMessages', messages:headline.id, forever:1 })
                .always(function() { $elem.find('.throbber').remove(); })
                .fail(function(resp) {
                    window.alert('Erro ao apagar email.\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                }).done(function(status) {
                    $elem.slideUp(200, function() {
                        $elem.remove();
                        var origThread = ThreadMail.FindThread(curFolder.threads, headline);
                        --curFolder.totalMails; // update cache
                        if(headline.unread)
                            --curFolder.unreadMails;
                        ThreadMail.RemoveHeadlinesFromFolder([ headline.id ], curFolder);
                        if(onMoveCB !== null)
                            onMoveCB(null, origThread);
                    });
                });
            }

            $elem.find('.messages_top2').is(':visible') ?
                $elem.find('.messages_top1').trigger('click', ProceedDeleting) : // if expanded, collapse
                ProceedDeleting();
        }
    }

    function _LoadMugshots(thread, unitDivs, onDone) {
        var mugshotAddrs = []; // email addresses to have mugshot fetched
        for(var i = 0; i < thread.length; ++i) { // each thread is an array of headlines
            var fromAddr = thread[i].from.email.toLowerCase();
            if(fromAddr.indexOf('@serpro.gov.br') !== -1 && mugshotAddrs.indexOf(fromAddr) === -1) // only @serpro addresses
                mugshotAddrs.push(fromAddr);
        }

        Contacts.loadMugshots(mugshotAddrs, function() {
            for(var i = 0; i < unitDivs.length; ++i) {
                if(!unitDivs[i].closest('body').length) // not in DOM anymore
                    continue;
                if(unitDivs[i].find('.messages_mugshot > img').attr('src') === userOpts.genericMugshot) {
                    var imgsrc = Contacts.getMugshotSrc(unitDivs[i].data('headline').from.email);
                    if(imgsrc !== '') {
                        unitDivs[i].find('.messages_mugshot > img')
                            .attr('src', imgsrc)
                            .hide()
                            .fadeIn(500);
                    }
                }
            }
            if(onDone !== undefined)
                onDone();
        });
    }

    obj.empty = function() {
        $targetDiv.children('.messages_unit').remove();
        return obj;
    };

    obj.render = function(thread, currentFolder) {
        curFolder = currentFolder; // keep
        $targetDiv.children('.messages_unit').remove(); // clear, if any
        var divs = [];
        var firstUnread = -1;
        thread.reverse(); // headlines now sorted oldest first
        for(var i = 0; i < thread.length; ++i) { // each thread is an array of headlines
            divs.push(_BuildDivMail(thread[i]));
            if(firstUnread === -1 && thread[i].unread)
                firstUnread = i;
        }
        thread.reverse(); // headlines now sorted newest first again
        $targetDiv.append(divs);
        firstUnread = (firstUnread !== -1) ? firstUnread : thread.length - 1; // open 1st unread, or last
        $targetDiv.find('.messages_top1:eq('+firstUnread+')').trigger('click', function() {
            _LoadMugshots(thread, divs);
        });
        return obj;
    };

    obj.redrawIcons = function(headline) {
        $targetDiv.children('.messages_unit').each(function(idx, elem) {
            var $div = $(elem);
            if($div.data('headline').id === headline.id)
                $div.find('.messages_icons').html(_BuildIcons(headline));
        });
        return obj;
    };

    obj.count = function() {
        return $targetDiv.find('div.messages_unit').length;
    };

    obj.countOpen = function() {
        return $targetDiv.find('div.messages_body:visible').length;
    };

    obj.closeAll = function() {
        $targetDiv.find('div.messages_content:visible')
            .prevAll('div.messages_top1').trigger('click');
        return obj;
    };

    obj.onView = function(callback) {
        onViewCB = callback;
        return obj;
    };

    obj.onMarkRead = function(callback) {
        onMarkReadCB = callback;
        return obj;
    };

    obj.onMove = function(callback) {
        onMoveCB = callback;
        return obj;
    };

    $targetDiv.on('click', '.messages_top1,.messages_top2', function(ev, onDone) { // open message
        var $divUnit = $(this).closest('.messages_unit');
        var headline = $divUnit.data('headline');
        if(!$divUnit.find('.messages_top2').is(':visible')) { // will expand

            function PutContentsAndSlideDown() {
                $divUnit.find('.messages_attachs').html(_FormatAttachments(headline.attachments));
                $divUnit.find('.messages_body').html(headline.body.message);
                if(headline.body.quoted !== null) {
                    $divUnit.find('.messages_showQuote').show();
                    $divUnit.find('.messages_quote').html(headline.body.quoted);
                }
                var toSlide = headline.attachments.length ?
                    '.messages_top2,.messages_attachs,.messages_content' :
                    '.messages_top2,.messages_content';
                $divUnit.find(toSlide).slideDown(200).promise('fx').done(function() {
                    $divUnit.children('.messages_top1,.messages_top2')
                        .removeClass('messages_unread').addClass('messages_read');

                    if(headline.unread) _MarkRead($divUnit, true);
                    if(onViewCB !== null)
                        onViewCB(curFolder, headline); // invoke user callback
                    if(onDone !== undefined)
                        onDone();
                });
            }

            if(headline.body === null) { // not cached yet
                $divUnit.append('<div class="loadingMessage">' +
                    'Carregando mensagem... '+$('#icons .throbber').serialize()+'</div>');
                $.post('../', { r:'getMessage', id:headline.id })
                .always(function() { $divUnit.find('.loadingMessage').remove(); })
                .fail(function(resp) {
                    window.alert('Erro ao carregar email.\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                }).done(function(msg) {
                    headline.attachments = msg.attachments; // cache
                    headline.body = msg.body;
                    PutContentsAndSlideDown();
                });
            } else { // already cached
                PutContentsAndSlideDown();
            }
        } else { // will collapse
            $divUnit.find('.messages_quote').hide();
            var toGo = headline.attachments.length ?
                '.messages_top2,.messages_attachs,.messages_content' :
                '.messages_top2,.messages_content';
            $divUnit.find(toGo).slideUp(200).promise('fx').done(function() {
                if(onDone !== undefined)
                    onDone();
            });
        }
        return false;
    });

    $targetDiv.on('click', '.messages_attachs a', function() { // click attachment
        var $lnk = $(this);
        $lnk.blur();
        var idx = $lnk.parent('span').index(); // child index; 0 is "<b>Anexo</b>", others are the link spans
        var headline = $lnk.closest('.messages_unit').data('headline');
        var attach = headline.attachments[idx - 1];
        window.open('../?' +
            'r=downloadAttachment&' +
            'fileName='+encodeURIComponent(attach.filename)+'&' +
            'messageId='+headline.id+'&' +
            'partId='+attach.partId,
            '_blank'); // usually will open another tab on the browser
        return false;
    });

    $targetDiv.on('click', '.messages_showQuote', function() {
        $(this).next('.messages_quote').slideToggle(200);
    });

    $targetDiv.on('mouseenter', '.messages_mugshot > img', function(ev) {
        var $img = $(this);
        var src = $img.attr('src');
        if(src.substr(0, 5) === 'data:') { // apply effect only to real pictures
            $img.css('box-shadow', '3px 3px 3px #888')
                .animate({ width:'90px' }, { duration:70, queue:false });
        }
    }).on('mouseleave', '.messages_mugshot > img', function(ev) {
        var $img = $(this);
        var src = $img.attr('src');
        if(src.substr(0, 5) === 'data:') { // apply effect only to real pictures
            $img.animate({ width:'20px' }, { duration:70, queue:false, complete:function() {
                $img.css('box-shadow', '');
            } });
        }
    });
};
})( jQuery, Contacts, DateFormat, ThreadMail, DropdownMenu );
