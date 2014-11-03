/*!
 * Expresso Lite
 * Widget to render the folder list. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $ ) {
$.fn.folders = function(options) {
    var userOpts = $.extend({
        folderCache: []
    }, options);

    var exp = { };

    var $targetDiv   = this;
    var menu         = null; // context menu object
    var isFirstClick = true; // flag to avoid immediate refresh (unnecessary) on 1st click
    var curFolder    = null; // cache currently selected folder

    var onClickCB         = null; // user callbacks
    var onTreeChangedCB   = null;
    var onFolderUpdatedCB = null;

    function _FindFolderLi(folder) {
        var $retLi = null; // given a folder object, find the LI to which it belongs
        $targetDiv.find('li').each(function(idx, li) {
            var $li = $(li);
            if($li.data('folder').globalName === folder.globalName) {
                $retLi = $li;
                return false; // break
            }
        });
        return $retLi;
    }

    function _BuildDiv(folder, isExpanded) {
        var lnkToggle = '';
        if(folder.hasSubfolders) {
            lnkToggle = isExpanded ?
            '<a href="#" class="folders_toggle" title="Recolher pasta"><div class="arrowDown"></div></a>' :
                '<a href="#" class="folders_toggle" title="Expandir pasta"><div class="arrowRite"></div></a>';
        }
        var lnkText = '<div class="folders_folderName">'+folder.localName+'</div> ' +
            '<span class="folders_counter">('+folder.unreadMails+'/'+folder.totalMails+')</span>';
        var text = folder.unreadMails ? '<b>'+lnkText+'</b>' : lnkText;
        var $div = $('<div class="folders_text" role="button">'+lnkToggle+' '+text+'</div>');
        return $div;
    }

    function _BuildUl(folders, isRootLevel) {
        var $ul = $('<ul class="folders_ul" style="padding-left:'+(isRootLevel?'0':'11')+'px;"></ul>');
        for(var i = 0; i < folders.length; ++i) {
            var $li = $('<li class="folders_li"></li>');
            $li.data('folder', folders[i]); // keep folder object within LI
            $li.append(_BuildDiv(folders[i], false));
            if(folders[i].subfolders.length)
                _BuildUl(folders[i].subfolders, false).appendTo($li);
            $li.appendTo($ul);
        }
        return $ul;
    }

    function _UpdateOneFolder($li, onDone) {
        if(!$li.hasClass('folders_li'))
            $li = $li.closest('.folders_li');
        var folder = $li.data('folder');
        var $counter = $li.find('.folders_counter:first').replaceWith($('#icons .throbber').serialize());

        $.post('../', { r:'updateMessageCache', folderId:folder.id })
        .always(function() { $li.find('.throbber:first').replaceWith($counter); })
        .fail(function(resp) {
            window.alert('Erro na consulta dos emails de "'+folder.localName+'".\n' +
                'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
        }).done(function(stats) {
            var hasChanged = (folder.totalMails !== stats.totalMails) ||
                (folder.unreadMails !== stats.unreadMails);
            if(hasChanged) { // folder status changed
                folder.totalMails = stats.totalMails;
                folder.unreadMails = stats.unreadMails;

                if(folder.id === curFolder.id) { // current folder
                    exp.redraw(folder);
                    if(onFolderUpdatedCB !== null) onFolderUpdatedCB(folder);
                } else { // not current folder
                    folder.messages.length = 0; // force cache rebuild
                    folder.threads.length = 0;
                    exp.redraw(folder);
                }
            }
            if(onDone !== undefined) onDone();
        });
    }

    function _UpdateSubfolders($li, onDone) {
        if(!$li.hasClass('folders_li'))
            $li = $li.closest('.folders_li');

        _UpdateOneFolder($li, function() {
            var all = $li.find('.folders_li:visible').toArray(); // all updateable
            (function GoNext() {
                if(all.length) _UpdateOneFolder($(all.shift()), GoNext);
                else if(onDone !== undefined && onDone !== null) onDone();
            })();
        });
    }

    function _LoadSubfolders(parentFolder, onDone) {
        var $divLoading = $('<div class="loadingMessage">' +
            'Carregando pastas... '+$('#icons .throbber').serialize()+'</div>');
        if(parentFolder === null) { // root folder
            $divLoading.appendTo($targetDiv);

            $.post('../', { r:'searchFolders' })
            .always(function() { $divLoading.remove(); })
            .fail(function(resp) {
                window.alert('Erro na primeira consulta das pastas.\n' +
                    'Atualize a página para tentar novamente.\n' + resp.responseText);
            }).done(function(folders) {
                userOpts.folderCache.length = 0;
                userOpts.folderCache.push.apply(userOpts.folderCache, folders); // cache
                _BuildUl(folders, true).appendTo($targetDiv);
                if(onDone !== undefined && onDone !== null) onDone();
            });
        } else { // non-root folder
            var $li = _FindFolderLi(parentFolder);
            $divLoading.appendTo($li);

            $.post('../', { r:'searchFolders', parentFolder:parentFolder.globalName })
            .always(function() { $divLoading.remove(); })
            .fail(function(resp) {
                window.alert('Erro na consulta das subpastas de '+parentFolder.localName+'\n' +
                    resp.responseText);
            }).done(function(subfolders) {
                parentFolder.subfolders.length = 0;
                parentFolder.subfolders.push.apply(parentFolder.subfolders, subfolders); // cache
                _BuildUl(subfolders, false).appendTo($li);
                if(onDone !== undefined && onDone !== null) onDone();
                if(onTreeChangedCB !== null) onTreeChangedCB();
            });
        }
    }

    exp.loadRoot = function(onDone) {
        _LoadSubfolders(null, onDone);
        return exp;
    };

    exp.setCurrent = function(folder) {
        _FindFolderLi(folder).children('.folders_text').trigger('click');
        return exp;
    };

    exp.getCurrent = function() {
        return curFolder;
    };

    exp.redraw = function(folder) {
        var $li = _FindFolderLi(folder);
        var $div = $li.children('div:first');
        var $childUl = $div.next('ul');
        var isExpanded = $childUl.length && $childUl.is(':visible');
        var $newDiv = _BuildDiv(folder, isExpanded);
        if($div.hasClass('folders_current'))
            $newDiv.addClass('folders_current');
        $div.replaceWith($newDiv);
        return exp;
    };

    exp.expand = function(folder) {
        _FindFolderLi(folder).find('.folders_text > .folders_toggle:first').trigger('click');
        return exp;
    };

    exp.updateAll = function(onDone) {
        _UpdateSubfolders($targetDiv.find('.folders_li:first'), onDone);
        return exp;
    };

    exp.onClick = function(callback) {
        onClickCB = callback; // onClick(folder)
        return exp;
    };

    exp.onTreeChanged = function(callback) {
        onTreeChangedCB = callback; // onTreeChanged()
        return exp;
    };

    exp.onFolderUpdated = function(callback) {
        onFolderUpdatedCB = callback; // onFolderUpdated(folder)
        return exp;
    };

    $targetDiv.on('click', 'a.folders_toggle', function() {
        $(this).blur();
        var $li = $(this).closest('li');
        var folder = $li.data('folder');

        if(folder.hasSubfolders && !folder.subfolders.length) { // subfolders not cached yet
            _LoadSubfolders(folder);
            $(this).find('div').removeClass('arrowRite').addClass('arrowDown');
        } else {
            var $childUl = $li.children('ul:first');
            $childUl.toggle();
            $childUl.is(':visible') ?
                $(this).find('div').removeClass('arrowRite').addClass('arrowDown') :
                $(this).find('div').removeClass('arrowDown').addClass('arrowRite');
        }
        return false;
    });

    $targetDiv.on('click', 'div.folders_text', function() {
        var $li = $(this).closest('li');
        curFolder = $li.data('folder'); // cache
        $targetDiv.find('.folders_current').removeClass('folders_current');
        $(this).addClass('folders_current');

        if(!curFolder.messages.length) { // if messages not cached yet
            if(isFirstClick) {
                isFirstClick = false;
                if(onClickCB !== null)
                    onClickCB(curFolder); // invoke user callback
            } else {
                var $counter = $li.find('.folders_counter:first')
                    .replaceWith( $('#icons .throbber').serialize() );

                $.post('../', { r:'updateMessageCache', folderId:curFolder.id })
                .always(function() { $li.find('.throbber:first').replaceWith($counter); })
                .fail(function(resp) {
                    window.alert('Erro ao atualizar a pasta "'+curFolder.localName+'".\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                }).done(function(stats) {
                    var hasChanged = (curFolder.totalMails !== stats.totalMails) ||
                        (curFolder.unreadMails !== stats.unreadMails);
                    if(hasChanged) {
                        curFolder.totalMails = stats.totalMails;
                        curFolder.unreadMails = stats.unreadMails;
                        curFolder.messages.length = 0; // clear cache, will force reload
                        curFolder.threads.length = 0;
                        exp.redraw(curFolder);
                    }
                    if(onClickCB !== null)
                        onClickCB(curFolder); // invoke user callback
                });
            }
        } else { // messages already cached, won't look for more right now
            if(onClickCB !== null)
                onClickCB(curFolder); // invoke user callback
        }
        return false;
    });

    return exp;
};
})( jQuery );
