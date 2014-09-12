<?php
/*!
 * Expresso Lite
 * Entry index page for mail module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

@session_start();
require_once(dirname(__FILE__).'/../conf.php');
require_once(dirname(__FILE__).'/../inc/Ajax.class.php');
if(!Tine::isLogged()) header('location: ../');
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8"/>
	<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1"/>
	<link rel="stylesheet" href="../inc/general.css"/>
	<link rel="stylesheet" href="mail.css"/>
	<title>Expresso Lite</title>
	<script src="../inc/UrlStack.js"></script>
	<script src="../inc/DateFormat.js"></script>
	<script src="../inc/sprintf.min.js"></script>
	<script src="../inc/jquery-2.1.0.min.js"></script>
	<script src="../inc/toe.min.js"></script>
	<script src="../inc/jquery-serialize.js"></script>
	<script src="../inc/jquery-contextMenu.js"></script>
	<script src="../inc/jquery-modelessDialog.js"></script>
	<script src="../inc/jquery-uploadFile.js"></script>
	<script src="Contacts.js"></script>
	<script src="ThreadMail.js"></script>
	<script src="widget-folders.js"></script>
	<script src="widget-headlines.js"></script>
	<script src="widget-messages.js"></script>
	<script src="widget-attacher.js"></script>
	<script src="widget-compose.js"></script>
	<script src="widget-searchAddr.js"></script>
	<script src="mail.js"></script>
</head>
<body>
	<header id="bigheader">
		<div id="headerStatic"><img style="width:120px;" src="../img/logo-lite-165.png"/></div>
		<div id="headerMenu">&laquo; <span id="headerMenuFolderName"></span>
			<span id="headerFolderCounter" class="folders_counter"></span></div>
		<div id="headerCloseMessage">&laquo; <span>voltar &nbsp;</span></div>
		<div id="headerCompose"><input type="button" class="compose" value="escrever" title="Escrever um novo email"/></div>
		<div id="headerLogoff"><div class="userAddr"><?=$_SESSION['user_email']?></div> &nbsp;
			<input type="button" class="logoff" value="logoff"/></div>
	</header>
	<section id="bigbody">
		<aside id="leftColumn">
			<div id="composeFoldersSlot"><input type="button" class="compose" value="escrever" title="Escrever um novo email"/></div>
			<div id="foldersArea"></div>
			<div id="logoffFoldersSlot"><span class="userAddr"><?=$_SESSION['user_email']?></span><br/><br/>
				<input type="button" class="logoff" value="logoff" title="Sair do Expresso Lite"/></div>
		</aside>
		<section id="centerColumn">
			<div id="headlinesArea"></div>
			<footer id="headlinesFooter">
				<span id="loadedCount"></span> &nbsp;
				<input type="button" id="loadMore" value="carregar mais"/>
			</footer>
		</section>
		<section id="rightColumn">
			<div id="subjectTop">
				<div id="subjectText"></div>
				<div id="closeButton"><input type="button" id="closeMessages" value="fechar"/></div>
			</div>
			<div id="messagesArea"></div>
		</section>
	</section>
	<section id="composePanel">
		<div><input type="text" id="composePanel_subject" placeholder="Assunto..."/></div>
		<div id="composePanel_toggs">
			<a id="composePanel_toBtn" href="#" title="Adicionar destinatários do email">Para...</a> &nbsp;
			<a id="composePanel_ccBtn" href="#" title="Adicionar destinatários a receber cópia do email">Cc...</a> &nbsp;
			<a id="composePanel_bccBtn" href="#" title="Adicionar destinatários a receber cópia oculta do email">Bcc...</a>
		</div>
		<textarea id="composePanel_to" placeholder="Para..."></textarea>
		<textarea id="composePanel_cc" placeholder="Cc..."></textarea>
		<textarea id="composePanel_bcc" placeholder="Bcc..."></textarea>
		<div contentEditable="true" id="composePanel_body"></div>
		<div id="composePanel_attacher"></div>
		<div>
			<input type="button" id="composePanel_send" value="&nbsp; enviar &nbsp;" title="Enviar este email"/> &nbsp;
			<input type="button" id="composePanel_draft" value="salvar rascunho" title="Salvar na pasta Rascunhos"/> &nbsp;
			<input type="button" id="composePanel_attachNew" title="Anexar arquivo" value="anexar..."/> &nbsp; &nbsp;
			<label title="Marcar esta mensagem como importante"><input type="checkbox" id="composePanel_important"/><div
				class="icoImportant" title="Marcar esta mensagem como importante"></div></label>
		</div>
	</section>
	<div id="icons">
		<img class="throbber" src="../img/chromiumthrobber.svg"/>
		<div class="icoReplied" title="Você respondeu este email"></div>
		<div class="icoConfirm" title="O remetente deste email receberá uma confirmação de leitura"></div>
		<div class="icoImportant" title="O remetente deste email o marcou como importante"></div>
		<div class="icoAttach" title="Este email contém anexo(s)"></div>
		<div class="icoForwarded" title="Você já encaminhou este email"></div>
		<div class="icoHigh0" title="Mensagem não destacada"></div>
		<div class="icoHigh1" title="Mensagem destacada"></div>
		<div class="icoCheck0" title="Selecionar mensagem"></div>
		<div class="icoCheck1" title="Desselecionar mensagem"></div>
	</div>
	<div id="info">
		<input type="hidden" id="mailBatch" value="<?=MAIL_BATCH?>"/>
		<input type="hidden" id="mailAddress" value="<?=$_SESSION['user_email']?>"/>
		<input type="hidden" id="mailSignature" value="<?=$_SESSION['user_signature']?>"/>
	</div>
</body>
</html>