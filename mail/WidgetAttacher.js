/*!
 * Expresso Lite
 * Handles uploads for email attachments.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, UploadFile ) {
window.WidgetAttacher = function(options) {
    var userOpts = $.extend({
        elem: '' // jQuery selector for the target DIV
    }, options);

    var THIS = this;
    var $targetDiv = $(userOpts.elem);
    var onContentChangeCB = null; // user callback

    function _BuildDivSlot() {
        return $('<div class="attacher_unit" style="border:1px solid #BBB; margin-bottom:2px; background:rgba(192,192,192,.15);">' +
            '<table width="100%"><tr>' +
            '<td class="attacher_text">aguardando arquivo...</td>' +
            '<td style="text-align:right;"><progress class="attacher_pro"></progress></td>' +
            '<td style="text-align:right;">' +
                '<input type="button" class="attacher_remove" title="Remover este arquivo anexo" value="remover"/>' +
            '</td>' +
            '</tr></table></div>');
    }

    THIS.getAll = function() {
        var ret = [];
        $targetDiv.children('.attacher_unit').each(function(idx, div) {
            var attachmentObj = $(div).data('file'); // previously kept into DIV
            ret.push(attachmentObj);
        });
        return ret;
    };

    THIS.rebuildFromMsg = function(headline) {
        for (var i = 0; i < headline.attachments.length; ++i) {
            var file = headline.attachments[i];
            var $divSlot = _BuildDivSlot();
            $divSlot.find('.attacher_pro').remove();
            $divSlot.find('.attacher_text').html(file.filename+' ' +
                '<span style="font-size:90%%; color:#777;">' +
                '('+ThreadMail.FormatBytes(file.size)+')</span>');
            $divSlot.appendTo($targetDiv);
            $divSlot.data('file', { // keep attachment object into DIV
                name: file.filename,
                size: parseInt(file.size),
                type: file['content-type'],
                partId: file.partId
            });
        }
        if (headline.attachments.length && onContentChangeCB !== null)
            onContentChangeCB(); // invoke user callback
        return THIS;
    };

    THIS.newAttachment = function() {
        var $divSlot = null;
        var tempFiles = [];

        var up = new UploadFile({
            url: '../?r=uploadTempFile',
            chunkSize: 1024 * 200 // file sliced into 200 KB chunks
        });
        up.onProgress(function(pct, xhr) {
            if ($divSlot === null) {
                ($divSlot = _BuildDivSlot()).appendTo($targetDiv);
                if (onContentChangeCB !== null)
                    onContentChangeCB(); // invoke user callback
            }
            tempFiles.push(xhr.responseJSON.tempFile); // object returned by Tinebase.uploadTempFile
            $divSlot.find('.attacher_text')
                .text('Carregando... '+(pct * 100).toFixed(0)+'%');
            $divSlot.find('.attacher_pro')[0].value = pct;
        }).onDone(function(file, xhr) {
            if ($divSlot === null) {
                ($divSlot = _BuildDivSlot()).appendTo($targetDiv);
                if (onContentChangeCB !== null)
                    onContentChangeCB(); // invoke user callback
            }
            if (xhr.responseJSON.status === 'success') {
                $.post('../', { r:'joinTempFiles', tempFiles:JSON.stringify(tempFiles) }).done(function(tmpf) {
                    $divSlot.data('file', { // keep attachment object into DIV
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        tempFile: tmpf
                    });
                    $divSlot.find('.attacher_text').html(file.name+' ' +
                        '<span style="font-size:90%%; color:#777;">' +
                        '('+ThreadMail.FormatBytes(file.size)+')</span>');
                    $divSlot.find('.attacher_pro').remove();
                });
            } else {
                $divSlot.find('.attacher_text').text('Erro no carregamento do anexo.');
            }
        }).onFail(function(xhr, str) {
            console.log(xhr);
            alert(str);
        });

        return THIS;
    };

    THIS.removeAll = function() {
        $targetDiv.children('.attacher_unit').remove();
        return THIS;
    };

    THIS.onContentChange = function(callback) {
        onContentChangeCB = callback;
        return THIS;
    };

    $targetDiv.on('click', '.attacher_remove', function() {
        var $slot = $(this).closest('.attacher_unit');
        $slot.remove();
        if (onContentChangeCB !== null)
            onContentChangeCB(); // invoke user callback
    });
};
})( jQuery, UploadFile );
