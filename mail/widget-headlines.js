/*!
 * Expresso Lite
 * Widget to render the email headlines. jQuery plugin.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $, ThreadMail, DateFormat ) {
$.fn.headlines = function(options) {
	var userOpts = $.extend({
		folderCache: []
	}, options);

	var exp = { };

	var $targetDiv    = this;
	var curFolder     = null; // folder object currently loaded
	var menu          = null; // context menu object
	var onClickCB     = null; // user callbacks
	var onCheckCB     = null;
	var onMarkReadCB  = null;
	var onMoveCB      = null;
	var $prevClick    = null; // used in checkbox click event, modified by buildContextMenu()
	var lastCheckWith = null; // 'leftClick' || 'rightClick', used in buildContextMenu()

	function _CreateDivLoading(msg) {
		return msg === null ?
			$('<div class="loadingMessageIcon">'+$('#icons .throbber').serialize()+'</div>') :
			$('<div class="loadingMessage">'+msg+' '+$('#icons .throbber').serialize()+'</div>');
	}

	function _FindHeadlineDiv(headline) {
		var $retDiv = null; // given a headline object, find the DIV to which it belongs
		$targetDiv.children('div').each(function(idx, div) {
			var $div = $(div);
			var thread = $div.data('thread'); // each thread is an array of headlines
			for(var i = 0; i < thread.length; ++i) {
				if(thread[i].id === headline.id) {
					$retDiv = $div;
					return false; // break
				}
			}
		});
		return $retDiv;
	}

	function _RemoveDuplicates(arr) {
		var ret = [];
		for(var a = 0; a < arr.length; ++a) { // faster than simply using indexOf
			var exists = false;
			for(var r = 0; r < ret.length; ++r) {
				if(arr[a] === ret[r]) {
					exists = true;
					break;
				}
			}
			if(!exists) ret.push(arr[a]);
		}
		return ret;
	}

	function _BuildSendersText(thread, isSentFolder) {
		var names = { read:[], unread:[] };
		for(var i = 0; i < thread.length; ++i) { // a thread is an array of headlines
			var msg = thread[i];
			var who = msg.unread ? names.unread : names.read;
			if(!isSentFolder) {
				who.push( msg.from.name.indexOf('@') !== -1 ? // an actual email address
					Contacts.HumanizeLogin(msg.from.name, false) : msg.from.name );
			} else { // on Sent folder, show "to" field instead of "from"
				for(var t = 0; t < msg.to.length; ++t)
					who.push(Contacts.HumanizeLogin(msg.to[t], msg.to.length > 1));
			}
		}
		names.read.reverse(); // senders in chronological order, originally newest first
		names.unread.reverse();
		names.read = _RemoveDuplicates(names.read);
		names.unread = _RemoveDuplicates(names.unread);

		if(names.read.length + names.unread.length > 1) {
			for(var i = 0; i < names.read.length; ++i)
				names.read[i] = names.read[i].split(' ')[0]; // many people, remove surnames
			for(var i = 0; i < names.unread.length; ++i)
				names.unread[i] = names.unread[i].split(' ')[0]; // unread also in bold
		}
		for(var i = 0; i < names.unread.length; ++i)
			names.unread[i] = '<b>'+names.unread[i]+'</b>'; // unread in bold

		if(!names.read.length && !names.unread.length) {
			return '(ninguém)';
		} else {
			return (names.read.length ? names.read.join(', ') : '') +
				(names.read.length && names.unread.length ? ', ' : '') +
				(names.unread.length ? names.unread.join(', ') : '') +
				(thread.length > 1 ? ' ('+thread.length+')' : '');
		}
	}

	function _BuildDiv(thread, isSentFolder) {
		var hasHighlight  = false;
		var hasReplied    = false;
		var hasForwarded  = false;
		var hasAttachment = false;
		var hasImportant  = false;
		var hasUnread     = false;
		var hasSigned     = false;
		var wantConfirm   = false;
		for(var i = 0; i < thread.length; ++i) { // a thread is an array of headlines
			var msg = thread[i];
			if(msg.flagged)       hasHighlight = true; // at least 1 email in the thread has highlight status
			if(msg.hasAttachment) hasAttachment = true;
			if(msg.important)     hasImportant = true;
			if(msg.unread)        hasUnread = true;
			if(msg.signed)        hasSigned = true;
			if(msg.replied)       hasReplied = true;
			if(msg.forwarded)     hasForwarded = true
			if(msg.unread && msg.wantConfirm) wantConfirm = true; // confirmation only if unread
		}

		var unreadClass = hasUnread ? 'headlines_entryUnread' : 'headlines_entryRead';
		var $elemHl = hasHighlight ? $('#icons .icoHigh1') : $('#icons .icoHigh0');

		var $div = $('#templates .headlines_entry').clone();
		$div.addClass(unreadClass);
		$div.find('.headlines_sender').html(_BuildSendersText(thread, isSentFolder));
		$div.find('.headlines_highlight').html($elemHl.serialize());
		$div.find('.headlines_subject').text(thread[thread.length-1].subject != '' ?
			thread[thread.length-1].subject : '(sem assunto)');
		$div.find('.headlines_icons').html(
			(hasReplied    ? $('#icons .icoReplied').serialize()   : '')+' ' +
			(wantConfirm   ? $('#icons .icoConfirm').serialize()   : '')+' ' +
			(hasImportant  ? $('#icons .icoImportant').serialize() : '')+' ' +
			(hasSigned     ? $('#icons .icoSigned').serialize()    : '')+' ' +
			(hasAttachment ? $('#icons .icoAttach').serialize()    : '')+' ' +
			(hasForwarded  ? $('#icons .icoForwarded').serialize() : '')+' '
		);
		$div.find('.headlines_when').text(DateFormat.Humanize(thread[0].received));

		$div.data('thread', thread); // keep thread object within DIV
		return $div;
	}

	function _BuildAllThreadsDivs() {
		var divs = [];
		for(var i = 0; i < curFolder.threads.length; ++i)
			divs.push(_BuildDiv(curFolder.threads[i], curFolder.globalName === 'INBOX/Sent'));
		return divs;
	}

	function _RedrawDiv($div) {
		var $newDiv = _BuildDiv($div.data('thread'), curFolder.globalName === 'INBOX/Sent');
		if(!$div.children('.headlines_check').is(':visible'))
			$newDiv.find('.headlines_check').hide();
		if($div.hasClass('headlines_entryCurrent'))
			$newDiv.addClass('headlines_entryCurrent');
		var isChecked = $div.hasClass('headlines_entryChecked');
		$div.replaceWith($newDiv);
		if(isChecked)
			$newDiv.find('.headlines_check > div').trigger('click');
	}

	function _FetchDraftMessage($div, headline, onDone) {
		if(headline.body === null) { // message content not fetched yet
			var htmlCheck = $div.find('.headlines_check > [class^=icoCheck]').serialize(); // keep
			$div.find('.headlines_check > [class^=icoCheck]').replaceWith(
				$('#icons .throbber').clone().css('padding', '8px') ); // replace checkbox with throbber

			$.post('../', { r:'getMessage', id:headline.id })
			.always(function() {
				$div.find('.throbber').replaceWith(htmlCheck); // restore checkbox
			}).fail(function(resp) {
				window.alert('Erro ao carregar email.\n' +
					'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
			}).done(function(msg) {
				headline.attachments = msg.attachments; // cache
				headline.body = msg.body;
				if(onDone !== undefined) onDone();
			});
		} else {
			if(onDone !== undefined) onDone();
		}
	}

	exp.markRead = function(asRead) {
		var relevantHeadlines = []; // headlines to have their flag actually changed
		var $checkedDivs = $targetDiv.find('.headlines_entryChecked');

		$checkedDivs.each(function(idx, elem) {
			var $div = $(elem);
			var thread = $div.data('thread'); // a thread is an array of messages
			for(var i = 0; i < thread.length; ++i) {
				if((asRead && thread[i].unread) || (!asRead && !thread[i].unread)) {
					thread[i].unread = !thread[i].unread; // update cache
					asRead ? --curFolder.unreadMails : ++curFolder.unreadMails;
					relevantHeadlines.push(thread[i]);
				}
			}
		});

		if(!relevantHeadlines.length) {
			window.alert('Nenhuma mensagem a ser marcada como '+(asRead?'':'não')+' lida.');
		} else {
			var htmlCheck = $checkedDivs.find('.icoCheck1:first').serialize(); // keep
			$checkedDivs.find('.icoCheck1').replaceWith(
				$('#icons .throbber').clone().css('padding', '8px') ); // replace checkbox with throbber
			var relevantIds = $.map(relevantHeadlines, function(elem) { return elem.id; });

			$.post('../', { r:'markAsRead', asRead:(asRead?1:0), ids:relevantIds.join(',') })
			.always(function() {
				$checkedDivs.find('.throbber').replaceWith(htmlCheck); // restore checkbox
				if(onMarkReadCB !== null)
					onMarkReadCB(curFolder);
			}).fail(function(resp) {
				window.alert('Erro ao alterar o flag de leitura das mensagens.\n' +
					'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
			}).done(function() {
				$checkedDivs.each(function(idx, elem) {
					_RedrawDiv($(elem));
				});
			});
		}
		return exp;
	};

	exp.moveMessages = function(destFolder) {
		var $checkedDivs = $targetDiv.find('.headlines_entryChecked');
		if($checkedDivs.find('.throbber').length) // already working?
			return;
		var headlines = []; // will hold all individual headlines
		$checkedDivs.each(function(idx, elem) { // each selected row
			var thread = $(elem).data('thread'); // thread to be moved, a thread is an array of headlines
			headlines.push.apply(headlines, thread);
			$(elem).children('.headlines_sender').html($('#icons .throbber').serialize() +
				'&nbsp; <i>Movendo para '+destFolder.localName+'...</i>');

			curFolder.totalMails -= thread.length; // update cache
			destFolder.totalMails += thread.length;
			for(var i = 0; i < thread.length; ++i) {
				if(thread[i].unread) {
					--curFolder.unreadMails;
					++destFolder.unreadMails;
				}
			}
		});
		var msgIds = $.map(headlines, function(elem) { return elem.id; });
		ThreadMail.RemoveHeadlinesFromFolder(msgIds, curFolder);
		destFolder.messages.length = 0; // force cache rebuild
		destFolder.threads.length = 0;

		$.post('../', { r:'moveMessages', messages:msgIds.join(','), folder:destFolder.id })
		.fail(function(resp) {
			window.alert('Erro ao mover email.\n' +
				'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
		}).done(function() {
			$checkedDivs.slideUp(200).promise('fx').done(function() {
				$checkedDivs.remove();
				if(onMoveCB !== null)
					onMoveCB(destFolder);
			});
		});
		return exp;
	};

	exp.toggleStarred = function() {
		var $checkedDivs = $targetDiv.find('.headlines_entryChecked');
		var headlines = [];
		var willStar = false;

		$checkedDivs.each(function(idx, elem) {
			var $div = $(elem);
			var thread = $div.data('thread');
			for(var i = 0; i < thread.length; ++i) {
				headlines.push(thread[i]);
				if(!thread[i].flagged)
					willStar = true; // at least 1 unstarred? star all
			}
		});
		var msgIds = $.map(headlines, function(elem) { return elem.id; });
		$checkedDivs.find('div[class^=icoHigh]').replaceWith(_CreateDivLoading(null));
		for(var i = 0; i < headlines.length; ++i)
			headlines[i].flagged = willStar; // update cache

		$.post('../', { r:'markAsHighlighted', ids:msgIds.join(','), asHighlighted:(willStar?'1':'0') })
		.always(function() {
			$checkedDivs.find('.loadingMessageIcon').replaceWith(
				(willStar ? $('#icons .icoHigh1') : $('#icons .icoHigh0')).serialize() );
		}).fail(function(resp) {
			window.alert('Erro ao alterar o flag de destaque das mensagens.\n' +
				'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
		});
		return exp;
	};

	exp.deleteMessages = function() {
		if(curFolder.globalName !== 'INBOX/Trash') { // just move to trash folder
			exp.moveMessages(ThreadMail.FindFolderByGlobalName('INBOX/Trash', userOpts.folderCache));
		} else if(window.confirm('Deseja apagar as mensagens selecionadas?')) { // we're in trash folder, add deleted flag
			var $checkedDivs = $targetDiv.find('.headlines_entryChecked');
			if($checkedDivs.find('.throbber').length) // already working?
				return;
			var headlines = [];
			$checkedDivs.each(function(idx, elem) {
				var thread = $(elem).data('thread');
				headlines.push.apply(headlines, thread);
				$(elem).children('.headlines_sender').html($('#icons .throbber').serialize() +
					'&nbsp; <i>Excluindo...</i>');

				curFolder.totalMails -= thread.length; // update cache
				for(var i = 0; i < thread.length; ++i)
					if(thread[i].unread)
						--curFolder.unreadMails;
			});
			var msgIds = $.map(headlines, function(elem) { return elem.id; });
			ThreadMail.RemoveHeadlinesFromFolder(msgIds, curFolder);

			$.post('../', { r:'deleteMessages', messages:msgIds.join(','), forever:1 })
			.fail(function(resp) {
				window.alert('Erro ao apagar email.\n' +
					'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
			}).done(function(status) {
				$checkedDivs.slideUp(200).promise('fx').done(function() {
					$checkedDivs.remove();
					if(onMoveCB !== null)
						onMoveCB(null);
				});
			});
		}
		return exp;
	};

	exp.loadFolder = function(folder, howMany, onDone) {
		curFolder = folder; // cache
		$targetDiv.empty();
		if(!curFolder.messages.length) { // not cached yet
			_CreateDivLoading('Carregando mensagens...').appendTo($targetDiv);
			$.post('../', { r:'getFolderHeadlines', folderId:curFolder.id, start:0, limit:howMany })
			.always(function() { $targetDiv.children('.loadingMessage').remove(); })
			.fail(function(resp) {
				window.alert('Erro na consulta dos emails de "'+curFolder.localName+'"\n'+resp.responseText);
			}).done(function(headlines) {
				curFolder.messages.length = 0;
				curFolder.messages.push.apply(curFolder.messages, ThreadMail.ParseTimestamps(headlines));
				curFolder.threads.length = 0;
				curFolder.threads.push.apply(curFolder.threads,
					ThreadMail.MakeThreads(headlines, curFolder.globalName === 'INBOX/Drafts')); // in thread: oldest first
				$targetDiv.append(_BuildAllThreadsDivs());
				if(onDone !== undefined && onDone !== null)
					onDone();
			});
		} else {
			$targetDiv.append(_BuildAllThreadsDivs());
			if(onDone !== undefined && onDone !== null)
				onDone();
		}
		return exp;
	};

	exp.loadMore = function(howMany, onDone) {
		var $divLoading = _CreateDivLoading('Carregando mensagens...')
		$divLoading.appendTo($targetDiv);

		$.post('../', { r:'getFolderHeadlines', folderId:curFolder.id, start:curFolder.messages.length, limit:howMany })
		.always(function() { $divLoading.remove(); })
		.fail(function(resp) {
			window.alert('Erro ao trazer mais emails de "'+curFolder.localName+'"\n'+resp.responseText);
		}).done(function(mails2) {
			ThreadMail.Merge(curFolder.messages, ThreadMail.ParseTimestamps(mails2)); // cache
			curFolder.threads.length = 0;
			curFolder.threads.push.apply(curFolder.threads,
				ThreadMail.MakeThreads(curFolder.messages, curFolder.globalName === 'INBOX/Drafts')); // rebuild
			$targetDiv.empty().append(_BuildAllThreadsDivs());
			if(onDone !== undefined && onDone !== null)
				onDone();
		});

		return exp;
	};

	exp.loadNew = function(howMany, onDone) {
		var $divLoading = _CreateDivLoading('Carregando mensagens...');
		$targetDiv.prepend($divLoading);

		var headl0 = null;
		var $current = $targetDiv.find('.headlines_entryCurrent');
		if($current.length)
			headl0 = $current.data('thread')[0]; // 1st headline of thread being read

		$.post('../', { r:'getFolderHeadlines', folderId:curFolder.id, start:0, limit:howMany })
		.always(function() { $divLoading.remove(); })
		.fail(function(resp) {
			window.alert('Erro na consulta dos emails de "'+curFolder.localName+'".\n' +
				'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
		}).done(function(headlines) {
			ThreadMail.Merge(curFolder.messages, ThreadMail.ParseTimestamps(headlines)); // insert into cache
			curFolder.threads = ThreadMail.MakeThreads(curFolder.messages, curFolder.globalName === 'INBOX/Drafts'); // in thread: oldest first
			$targetDiv.empty().append(_BuildAllThreadsDivs());
			if(headl0 !== null)
				_FindHeadlineDiv(headl0).addClass('headlines_entryCurrent');

			if(onDone !== undefined && onDone !== null)
				onDone(); // invoke user callback
		});

		return exp;
	};

	exp.getCurrent = function() {
		var curThread = null;
		var $cur = $targetDiv.find('.headlines_entryCurrent');
		if($cur.length)
			curThread = $cur.data('thread');
		return curThread;
	};

	exp.clearCurrent = function() {
		$targetDiv.find('.headlines_entryCurrent').removeClass('headlines_entryCurrent');
		return exp;
	};

	exp.getChecked = function() {
		var $checkeds = $targetDiv.find('.headlines_entryChecked');
		var chThreads = [];
		$checkeds.each(function(idx, chDiv) {
			chThreads.push($(chDiv).data('thread')); // each thread is an array of headlines
		});
		return chThreads;
	};

	exp.clearChecked = function() {
		var $checkeds = $targetDiv.find('.headlines_entryChecked');
		$checkeds.removeClass('headlines_entryChecked');
		$checkeds.find('.icoCheck1').removeClass('icoCheck1').addClass('icoCheck0');
		if(onCheckCB !== null)
			onCheckCB(); // invoke user callback
		return exp;
	};

	exp.redraw = function(headline) {
		var $div = _FindHeadlineDiv(headline); // the DIV which contains the headline
		if($div !== null)
			_RedrawDiv($div);
		return exp;
	};

	exp.redrawByThread = function(thread, onDone) {
		$targetDiv.children('div').each(function(idx, div) {
			var $div = $(div);
			if($div.data('thread') === thread) { // compare references
				if(!thread.length) { // headline entry will be deleted
					$div.slideUp(200, function() {
						$div.remove();
						if(onDone !== undefined && onDone !== null)
							onDone();
					});
				} else { // headline entry will be just updated
					var $newDiv = _BuildDiv($div.data('thread'), curFolder.globalName === 'INBOX/Sent');
					if(!$div.children('.headlines_check').is(':visible'))
						$newDiv.find('.headlines_check').hide();
					if($div.hasClass('headlines_entryCurrent'))
						$newDiv.addClass('headlines_entryCurrent');
					$div.replaceWith($newDiv);
					if(onDone !== undefined && onDone !== null)
						onDone();
				}
				return false; // break
			}
		});
		return exp;
	};

	exp.calcScrollTopOf = function(thread) {
		var cy = 0;
		$targetDiv.children('div').each(function(idx, div) {
			var $div = $(div);
			var cyDiv = $div.outerHeight();
			if($div.data('thread')[0].id === thread[0].id) {
				cy += cyDiv / 2;
				return false; // break
			}
			cy += cyDiv;
		});
		return cy;
	};

	exp.onClick = function(callback) {
		onClickCB = callback; // onClick(thread)
		return exp;
	};

	exp.onCheck = function(callback) {
		onCheckCB = callback; // onCheck()
		return exp;
	};

	exp.onMarkRead = function(callback) {
		onMarkReadCB = callback; // onMarkRead(folder)
		return exp;
	};

	exp.onMove = function(callback) {
		onMoveCB = callback; // onMove(destFolder)
		return exp;
	};

	$targetDiv.on('click', '.headlines_entry', function(ev) { // click on headline
		if(!ev.shiftKey) {
			var $div = $(this);
			var thread = $div.data('thread');
			if(curFolder.globalName === 'INBOX/Drafts') {
				var headline = thread[0]; // drafts are not supposed to be threaded, so message is always 1st
				_FetchDraftMessage($div, headline, function() { // get content and remove div from list
					if(onClickCB !== null)
						onClickCB([ headline ]); // create a new thread, because the original is destroyed
				});
			} else {
				$targetDiv.find('.headlines_entryCurrent').removeClass('headlines_entryCurrent');
				$div.addClass('headlines_entryCurrent');
				if(onClickCB !== null)
					onClickCB(thread);
			}
		}
		return false;
	});

	$targetDiv.on('click', '.headlines_check > div', function(ev) { // click on checkbox
		ev.stopImmediatePropagation();
		var $chk = $(this);
		var $divClicked = $chk.closest('div.headlines_entry');
		var willCheck = $chk.hasClass('icoCheck0');
		$chk.toggleClass('icoCheck0', !willCheck).toggleClass('icoCheck1', willCheck);
		lastCheckWith = 'leftClick';

		if(ev.shiftKey && $prevClick !== null) { // will check a range
			var isIn = false;
			$targetDiv.find('div.headlines_entry').each(function(idx, div) {
				var $div = $(div);
				if(isIn) { // we're in the middle of the selection range
					$div.addClass('headlines_entryChecked').find('.icoCheck0')
						.removeClass('icoCheck0').addClass('icoCheck1');
				}
				if($div.is($divClicked) || $div.is($prevClick)) { // boundary DIV?
					if(!isIn) { // we've just entered the selection range
						isIn = true;
						$div.addClass('headlines_entryChecked').find('.icoCheck0')
							.removeClass('icoCheck0').addClass('icoCheck1');
					} else { // last one of the selection range
						return false; // break
					}
				}
			});
		} else { // won't check a range
			willCheck ?
				$chk.closest('.headlines_entry').addClass('headlines_entryChecked') :
				$chk.closest('.headlines_entry').removeClass('headlines_entryChecked');
		}

		$prevClick = $divClicked; // keep
		if(onCheckCB !== null)
			onCheckCB(); // invoke user callback
	});

	return exp;
};
})( jQuery, ThreadMail, DateFormat );