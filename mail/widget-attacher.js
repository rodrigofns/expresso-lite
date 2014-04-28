/*!
 * Expresso Lite
 * Handles uploads for email attachments. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $ ) {
$.fn.attacher = function(options) {
	var userOpts = $.extend({
	}, options);

	var exp = { };

	var $targetDiv = this;
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

	exp.getAll = function() {
		var ret = [];
		$targetDiv.children('.attacher_unit').each(function(idx, div) {
			var attachmentObj = $(div).data('file'); // previously kept into DIV
			ret.push(attachmentObj);
		});
		return ret;
	};

	exp.rebuildFromMsg = function(headline) {
		for(var i = 0; i < headline.attachments.length; ++i) {
			var file = headline.attachments[i];
			var $divSlot = _BuildDivSlot();
			$divSlot.find('.attacher_pro').remove();
			$divSlot.find('.attacher_text').html(
				sprintf('%s <span style="font-size:90%%; color:#777;">(%s)</span>',
					file.filename, ThreadMail.FormatBytes(file.size)) );
			$divSlot.appendTo($targetDiv);
			$divSlot.data('file', { // keep attachment object into DIV
				name: file.filename,
				size: parseInt(file.size),
				type: file['content-type'],
				partId: file.partId
			});
		}
		if(headline.attachments.length && onContentChangeCB !== null)
			onContentChangeCB(); // invoke user callback
		return exp;
	};

	exp.newAttachment = function() {
		var $divSlot = null;
		var tempFiles = [];

		var up = $.uploadFile({
			url: '../?r=uploadTempFile',
			chunkSize: 1024 * 200 // file sliced into 200 KB chunks
		});
		up.onProgress(function(pct, xhr) {
			if($divSlot === null) {
				($divSlot = _BuildDivSlot()).appendTo($targetDiv);
				if(onContentChangeCB !== null)
					onContentChangeCB(); // invoke user callback
			}
			tempFiles.push(xhr.responseJSON.tempFile); // object returned by Tinebase.uploadTempFile
			$divSlot.find('.attacher_text')
				.text(sprintf('Carregando... %.0f%%', pct * 100));
			$divSlot.find('.attacher_pro')[0].value = pct;
		}).onDone(function(file, xhr) {
			if($divSlot === null) {
				($divSlot = _BuildDivSlot()).appendTo($targetDiv);
				if(onContentChangeCB !== null)
					onContentChangeCB(); // invoke user callback
			}
			if(xhr.responseJSON.status === 'success') {
				$.post('../', { r:'joinTempFiles', tempFiles:JSON.stringify(tempFiles) }).done(function(tmpf) {
					$divSlot.data('file', { // keep attachment object into DIV
						name: file.name,
						size: file.size,
						type: file.type,
						tempFile: tmpf
					});
					$divSlot.find('.attacher_text').html(
						sprintf('%s <span style="font-size:90%%; color:#777;">(%s)</span>',
							file.name, ThreadMail.FormatBytes(file.size)) );
					$divSlot.find('.attacher_pro').remove();
				});
			} else {
				$divSlot.find('.attacher_text').text('Erro no carregamento do anexo.');
			}
		}).onFail(function(xhr, str) {
			console.log(xhr);
			alert(str);
		});

		return exp;
	};

	exp.removeAll = function() {
		$targetDiv.children('.attacher_unit').remove();
		return exp;
	};

	exp.onContentChange = function(callback) {
		onContentChangeCB = callback;
		return exp;
	};

	$targetDiv.on('click', '.attacher_remove', function() {
		var $slot = $(this).closest('.attacher_unit');
		$slot.remove();
		if(onContentChangeCB !== null)
			onContentChangeCB(); // invoke user callback
	});

	return exp;
};
})( jQuery );