/*!
 * Expresso Lite
 * Uploads a file by slicing its contents using HTML5 API.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $ ) {
$.extend({
	uploadFile: function(options) {
		var userOpts = $.extend({
			url: '',
			fileName: 'file',
			chunkSize: 102400 // file sliced into 100 KB chunks
		}, options);

		var exp = { };

		var onProgressCB = null; // (pct, xhr)
		var onDoneCB = null; // (file, xhr)
		var onFailCB = null; // (xhr, str, err)

		function _ToBlob(dataChunk) {
			// http://stackoverflow.com/questions/7211902/corruption-with-filereader-into-formdata
			var ui8a = new Uint8Array(dataChunk.length);
			for(var i = 0; i < dataChunk.length; ++i)
				ui8a[i] = dataChunk.charCodeAt(i);
			return new Blob([ ui8a.buffer ]);
		}

		(function _Ctor() {
			$('<form enctype="multipart/form-data" style="display:none;">' +
				'<input type="file" name="'+userOpts.fileName+'"/>' +
			'</form>')
			.appendTo('body').children(':file').change(function() {
				// http://stackoverflow.com/questions/166221/how-can-i-upload-files-asynchronously-with-jquery
				var file = this.files[0];
				var binaryReader = new FileReader();
				var curSlice = 0;

				var ReadNextChunk = function() {
					// http://www.html5rocks.com/en/tutorials/file/dndfiles/
					var chunk = file.slice(curSlice,
						Math.min(curSlice + userOpts.chunkSize, file.size));
					binaryReader.readAsBinaryString(chunk);
				};

				binaryReader.onload = function(loadedFile) {
					$.ajax({ // http://api.jquery.com/jQuery.ajax/
						url: userOpts.url,
						type: 'POST',
						cache: false,
						data: _ToBlob(loadedFile.target.result),
						contentType: false,
						processData: false,
						headers: { 'X-File-Name':file.name, 'X-File-Type':file.type },
						complete: function(jqxhr, msg) {
							curSlice += userOpts.chunkSize;
							if(curSlice > file.size) curSlice = file.size;
							if(onProgressCB !== null) onProgressCB(curSlice / file.size, jqxhr);

							if(curSlice < file.size) { // still more chunks to upload
								ReadNextChunk();
							} else { // finished last chunk
								if(onDoneCB !== null)
									onDoneCB(file, jqxhr);
							}
						},
						error: function(jqxhr, txtStatus, errThrown) {
							if(onFailCB !== null)
								onFailCB(jqxhr, txtStatus, errThrown);
						}
					});
				};

				ReadNextChunk(); // 1st call
			}).click();
		})();

		exp.onProgress = function(callback) { onProgressCB = callback; return exp; };
		exp.onDone =     function(callback) { onDoneCB = callback; return exp; };
		exp.onFail =     function(callback) { onFailCB = callback; return exp; };

		return exp;
	}
});
})( jQuery );