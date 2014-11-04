/*!
 * Expresso Lite
 * Main script of email module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, Contacts, UrlStack ) {
    var Cache = { // global page cache with all objects
        MAILBATCH: 50, // overwritten with value from conf in document.ready
        folders: [], // all folder objects
        lstFolder: null, // folder rendering widget
        lstHeadline: null, // headline rendering widget
        lstMessage: null, // message bodies rendering widget
        menuHeadlines: null, // dropdown menu for headlines actions
        wndCompose: null // compose modeless popup
    };

    $(document).ready(function() {
        Cache.MAILBATCH = $('#mailBatch').val();

        // Init widget objects.
        Cache.wndCompose = new WidgetCompose({ folderCache:Cache.folders });
        Cache.lstFolder = new WidgetFolders({ elem:'#foldersArea', folderCache:Cache.folders });
        Cache.lstHeadline = new WidgetHeadlines({ elem:'#headlinesArea', folderCache:Cache.folders });
        Cache.lstMessage = new WidgetMessages({ elem:'#messagesArea', folderCache:Cache.folders,
            contactsCache:Cache.contacts, wndCompose:Cache.wndCompose });
        Cache.menuHeadlines = $('#headerHeadlinesDropdown').dropdownMenu({ });

        // Setup all page events.
        SetupEvents();

        // Do some init stuff.
        UrlStack.keepClean();
        $(window).trigger('resize');
        $('#leftColumn').addClass('foldersShown'); // show folders popup in phones
        $('#headerCloseMessage').addClass('headerElemHidden'); // initally hidden; phones
        $('#headerHeadlinesMenu,#updateFolders,#headlinesFooter').hide();

        Cache.lstFolder.loadRoot(function() {
            CloseFoldersMenu();
            $('#updateFolders').show();
            Cache.lstFolder.expand(Cache.folders[0]); // expand 1st folder (probably inbox)
            Cache.lstFolder.setCurrent(Cache.folders[0], 'noClickEvent'); // ...and select it
            Contacts.loadPersonal();
        });
    });

    $(document).ajaxComplete(function AjaxComplete() {
        if(AjaxComplete.timer !== undefined && AjaxComplete.timer !== null)
            window.clearTimeout(AjaxComplete.timer);
        AjaxComplete.timer = window.setTimeout(function() {
            AjaxComplete.timer = null;
            $('#updateFolders').trigger('click');
        }, 10 * 60 * 1000); // 10 minutes after the last request, an update will be performed (keep-alive)
    });

    function SetupEvents() {
        $(window).resize(function() {
            CloseFoldersMenu();
            $('#bigheader,#bigbody').hide();
            $('#bigbody').css({ height:($(window).height() - $('#bigheader').outerHeight())+'px',
                top:$('#bigheader').outerHeight()+'px' });
            $('#bigheader,#bigbody').show();
        });

        $('.logoff').on('click', DoLogoff);
        $('#updateFolders').on('click', UpdateFolders);
        $('.compose').on('click', function() { Cache.wndCompose.show({ curFolder:Cache.lstFolder.getCurrent() }); });
        $('#headerMenu').on('click', FoldersMenuClick);
        $('#loadMore').on('click', LoadMoreHeadlines);
        $('#closeMessages,#headerCloseMessage').on('click', CloseMailView);

        Cache.lstFolder.onClick(LoadFirstHeadlines);
        //~ Cache.lstFolder.onTreeChanged(function() { Cache.lstHeadline.buildContextMenu(); });
        Cache.lstFolder.onFolderUpdated(LoadNewHeadlines);
        Cache.lstHeadline.onClick(HeadlineClicked);
        Cache.lstHeadline.onCheck(HeadlineChecked);
        Cache.lstHeadline.onMarkRead(function(folder) { CloseMailView(); Cache.lstFolder.redraw(folder); UpdatePageTitle(); });
        Cache.lstHeadline.onMove(ThreadMoved);
        Cache.lstMessage.onView(function(folder, headline) { Cache.lstHeadline.redraw(headline); Cache.lstFolder.redraw(folder); });
        Cache.lstMessage.onMarkRead(function(folder, headline) { Cache.lstHeadline.redraw(headline); Cache.lstFolder.redraw(folder); UpdatePageTitle(); });
        Cache.lstMessage.onMove(MailMoved);
        Cache.wndCompose.onClose(function() { $('#composeFoldersSlot').show(); });
        Cache.wndCompose.onSend(MailSent);
        Cache.wndCompose.onDraft(DraftSaved);

        $(document).on('keydown', function(ev) { if(ev.keyCode === 27) EscKey(); });
    }

    function DoLogoff(ev) {
        $(ev.target).replaceWith( $('#icons .throbber').serialize() );

        $.post('../', { r:'logoff' }).done(function(data) {
            location.href = '.';
        });
    }

    function EscKey() {
        if($('#centerColumn').hasClass('headlinesForReading')) { // email being read
            Cache.lstMessage.count() > 1 && Cache.lstMessage.countOpen() > 0 ?
                Cache.lstMessage.closeAll() :
                $('#closeMessages').trigger('click'); // close reading panel
        } else {
            Cache.lstHeadline.clearChecked();
        }
    }

    function UpdatePageTitle() {
        var folder = Cache.lstFolder.getCurrent();
        var counter = (folder.unreadMails > 0) ? '('+folder.unreadMails+') ' : '';
        document.title = folder.localName+' '+counter+'- '+$('.userAddr:first').text()+' - Expresso Lite';
    }

    function UpdateFolders() {
        $('#updateFolders').hide();
        $('#updateFoldersWait').show();
        Cache.lstFolder.updateAll(function() {
            $('#updateFoldersWait').hide();
            $('#updateFolders').show();
        });
    }

    function FoldersMenuClick() {
        var $divMenu = $('#leftColumn');
        if(!$divMenu.hasClass('foldersShown')) { // will show
            $divMenu.addClass('foldersShown'); // has effect only on smartphones
            UrlStack.push('#foldersMenu', CloseFoldersMenu);
        } else { // will hide
            CloseFoldersMenu();
        }
    }

    function CloseFoldersMenu() {
        var $divMenu = $('#leftColumn');
        $divMenu.removeClass('foldersShown');
        UrlStack.pop('#foldersMenu');
    }

    function UpdateHeadlineFooter() {
        var folder = Cache.lstFolder.getCurrent();
        var s = folder.messages.length > 1 ? 's' : '';
        $('#loadedCount').text(folder.messages.length+' email'+s+' carregado'+s);
        (folder.messages.length >= folder.totalMails) ? // no more emails to be fetched from server
            $('#loadMore').hide() :
            $('#loadMore').val('carregar mais ' +
                Math.min(folder.totalMails - folder.messages.length, Cache.MAILBATCH)).show();
    }

    function LoadFirstHeadlines(folder) {
        CloseFoldersMenu();
        $('#headerMenuFolderName').text(folder.localName);
        $('#headerFolderCounter').text(' ('+folder.unreadMails+'/'+folder.totalMails+')');
        $('#headerHeadlinesMenu,#headlinesFooter').hide();
        UpdatePageTitle();
        Cache.lstHeadline.loadFolder(folder, Cache.MAILBATCH, function() {
            $('#centerColumn').scrollTop(0);
            $('#headlinesFooter').show();
            UpdateHeadlineFooter();
        });
    }

    function LoadMoreHeadlines() {
        $('#headerHeadlinesMenu,#headlinesFooter').hide();
        CloseMailView();
        Cache.lstHeadline.loadMore(Cache.MAILBATCH, function() {
            $('#headlinesFooter').show();
            UpdateHeadlineFooter();
            $('#centerColumn').scrollTop( $('#centerColumn')[0].scrollHeight ); // scroll to bottom
        });
    }

    function LoadNewHeadlines(folder) {
        Cache.lstHeadline.loadNew(Cache.MAILBATCH, function() {
            UpdateHeadlineFooter();
            $('#centerColumn').scrollTop(0); // scroll to top
        });
    }

    function OpenMailView() {
        var isMessagesOpened = $('#rightColumn').hasClass('messagesShown');
        if(!isMessagesOpened) {
            CloseFoldersMenu();
            $('#leftColumn').addClass('foldersForReading'); // folder tree is hidden both in desktop and phone
            $('#headerMenu').addClass('headerElemHidden'); // in phones, hide folders menu
            $('#headerCloseMessage').removeClass('headerElemHidden'); // ...and show specific back button
            $('#headerHeadlinesMenu').hide();

            $('#centerColumn').addClass('headlinesForReading'); // desktop, goes left and small; phone, hides
            $('#rightColumn').addClass('messagesShown'); // desktop, gets 1/3 of screen; phone, whole screen
            $('.headlines_check').hide();

            UrlStack.push('#mailView', CloseMailView);
        }
    }

    function HeadlineClicked(thread) {
        var curFolder = Cache.lstFolder.getCurrent();
        if(curFolder.globalName === 'INBOX/Drafts') {
            Cache.wndCompose.show({ draft:thread[0] }); // drafts are not supposed to be threaded, so message is always 1st
        } else {
            OpenMailView();
            $('#subjectText').text(thread[thread.length-1].subject);
            Cache.lstMessage.render(thread, curFolder); // headlines sorted newest first
        }

        var cyPos = Cache.lstHeadline.calcScrollTopOf(thread), // scroll headlines to have selected at center
            cyHalf = $('#centerColumn').outerHeight() / 2;
        $('#centerColumn').scrollTop(cyPos > cyHalf ? cyPos - cyHalf : 0);
    }

    function HeadlineChecked() {
        var isMessagesOpened = $('#rightColumn').hasClass('messagesShown');
        $('#headerHeadlinesMenu').toggle(!isMessagesOpened && Cache.lstHeadline.getChecked().length > 0); // show dropdown button if any checked
        Cache.menuHeadlines.purge(); // rebuild context dropdown menu

        Cache.menuHeadlines
            .addOption('Desselecionar todas', Cache.lstHeadline.clearChecked)
            .addOption('Marcar como lida', function() { Cache.lstHeadline.markRead(true); })
            .addOption('Marcar como não lida', function() { Cache.lstHeadline.markRead(false); })
            .addOption('Alterar destaque', Cache.lstHeadline.toggleStarred)
            .addOption('Apagar', Cache.lstHeadline.deleteMessages)
            .addHeader('Mover para...');

        var curFolder = Cache.lstFolder.getCurrent();
        var MenuRenderFolderLevel = function(folders, level) {
            $.each(folders, function(idx, folder) {
                if(folder.globalName !== curFolder.globalName) { // avoid move to current folder
                    Cache.menuHeadlines.addOption(folder.localName, function() {
                        Cache.lstHeadline.moveMessages(folder); // pass folder object
                    }, level); // indentation
                }
                MenuRenderFolderLevel(folder.subfolders, level + 1);
            });
        };
        MenuRenderFolderLevel(Cache.folders, 0);
    }

    function ThreadMoved(destFolder) {
        CloseMailView();
        if(destFolder !== null)
            Cache.lstFolder.redraw(destFolder);
        Cache.lstFolder.redraw(Cache.lstFolder.getCurrent());
        UpdateHeadlineFooter();
        UpdatePageTitle();
    }

    function MailMoved(destFolder, origThread) {
        Cache.lstHeadline.redrawByThread(origThread, function() {
            if(!Cache.lstMessage.count())
                CloseMailView();
            if(destFolder !== null)
                Cache.lstFolder.redraw(destFolder);
            Cache.lstFolder.redraw(Cache.lstFolder.getCurrent());
            UpdateHeadlineFooter();
        });
    }

    function CloseMailView() {
        var openedThread = Cache.lstHeadline.getCurrent();

        $('#headerCloseMessage').addClass('headerElemHidden'); // in phones, hide back button
        $('#headerMenu').removeClass('headerElemHidden'); // ...and show folders menu
        $('#headerHeadlinesMenu').toggle(Cache.lstHeadline.getChecked().length > 0);

        $('#leftColumn').removeClass('foldersForReading');
        $('#centerColumn').removeClass('headlinesForReading');
        $('#rightColumn').removeClass('messagesShown');
        $('.headlines_check').show();
        window.setTimeout(function() { Cache.lstHeadline.clearCurrent(); }, 400); // flash hint before go
        $('#subjectText').text('');
        Cache.lstMessage.empty();

        if(openedThread !== null) {
            var cyPos = Cache.lstHeadline.calcScrollTopOf(openedThread), // scroll headlines to have selected at center
                cyHalf = $('#centerColumn').outerHeight() / 2;
            $('#centerColumn').scrollTop(cyPos > cyHalf ? cyPos - cyHalf : 0);
        }

        UrlStack.pop('#mailView');
    }

    function MailSent(reMsg, fwdMsg, draftMsg) {
        var sentFolder = ThreadMail.FindFolderByGlobalName('INBOX/Sent', Cache.folders);
        ++sentFolder.totalMails;
        Cache.lstFolder.redraw(sentFolder);

        if(reMsg !== null) {
            Cache.lstHeadline.redraw(reMsg); // if currently shown, will redraw
            Cache.lstMessage.redrawIcons(reMsg);
        } else if(fwdMsg !== null) {
            Cache.lstHeadline.redraw(fwdMsg);
            Cache.lstMessage.redrawIcons(fwdMsg);
        }

        if(Cache.lstFolder.getCurrent().globalName === 'INBOX/Sent') {
            Cache.lstHeadline.loadNew(1);
        } else {
            sentFolder.messages.length = 0; // force cache rebuild
            sentFolder.threads.length = 0;
        }

        if(draftMsg !== null) { // a draft was sent
            var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', Cache.folders);
            Cache.lstFolder.redraw(draftFolder);
            draftFolder.messages.length = 0; // force cache rebuild
            draftFolder.threads.length = 0;
            if(Cache.lstFolder.getCurrent().globalName === 'INBOX/Drafts')
                LoadFirstHeadlines(draftFolder);
        }

        UpdateHeadlineFooter();

        //~ Compose.SaveNewPersonalContacts(message);
    }

    function DraftSaved() {
        var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', Cache.folders);
        ++draftFolder.totalMails;
        Cache.lstFolder.redraw(draftFolder);
        draftFolder.messages.length = 0; // force cache rebuild
        draftFolder.threads.length = 0;
        if(Cache.lstFolder.getCurrent().globalName === 'INBOX/Drafts')
            LoadFirstHeadlines(draftFolder);
    }
})( jQuery, Contacts, UrlStack );
